<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner\Modifier;

use Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Breakpoint\ImageUpload;
use Hryvinskyi\BannerSliderAdminUi\Model\Presenter\BreakpointPresenter;
use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Config\ImageConfigInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ImageFormatRegistryInterface;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\ResponsiveCropRepositoryInterface;
use Magento\Framework\Convert\DataSize;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Size;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Everything the crop editor of the banner form starts from.
 *
 * - Data (`responsive_cropper.breakpoints`): one entry per enabled breakpoint of the record's slider, widest first,
 *   with the banner's stored crop for it (area, source image, variants, generated file URLs), or empty crop values.
 *   `responsive_cropper.slider_id` names that slider (null without one), so the editor can tell whether the form's
 *   slider field still matches the breakpoints it was given.
 * - Config of the `image_settings.responsive_cropper_container` component: the endpoint URLs, the variant formats and
 *   which of them the server can encode, the default formats and qualities, the image upload limit and the request
 *   size limit (`post_max_size`) the editor must keep its post under.
 */
class ResponsiveCropperModifier implements ModifierInterface
{
    public const DATA_KEY = 'responsive_cropper';
    private const COMPONENT_PATH = ['image_settings', 'children', 'responsive_cropper_container'];

    /**
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param ResponsiveCropRepositoryInterface $cropRepository
     * @param BreakpointPresenter $breakpointPresenter
     * @param MediaUrlResolverInterface $mediaUrlResolver
     * @param ImageFormatRegistryInterface $formatRegistry
     * @param ImageConfigInterface $imageConfig
     * @param UrlInterface $urlBuilder
     * @param Size $fileSize
     * @param DataSize $dataSize
     * @param EntityIdReader $idReader
     */
    public function __construct(
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        private readonly ResponsiveCropRepositoryInterface $cropRepository,
        private readonly BreakpointPresenter $breakpointPresenter,
        private readonly MediaUrlResolverInterface $mediaUrlResolver,
        private readonly ImageFormatRegistryInterface $formatRegistry,
        private readonly ImageConfigInterface $imageConfig,
        private readonly UrlInterface $urlBuilder,
        private readonly Size $fileSize,
        private readonly DataSize $dataSize,
        private readonly EntityIdReader $idReader
    ) {
    }

    /**
     * Add the crop editor state to every banner record
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public function modifyData(array $data): array
    {
        foreach ($data as $key => $record) {
            if (!is_array($record)) {
                continue;
            }
            $sliderId = $this->idReader->parse($record[BannerInterface::SLIDER_ID] ?? null);
            $bannerId = $this->idReader->parse($record[BannerInterface::BANNER_ID] ?? null);
            $record[self::DATA_KEY] = [
                'slider_id' => $sliderId,
                'breakpoints' => $sliderId === null ? [] : $this->breakpoints($sliderId, $bannerId),
            ];
            $data[$key] = $record;
        }

        return $data;
    }

    /**
     * Add the crop editor configuration to the component meta
     *
     * @param array<mixed> $meta
     * @return array<mixed>
     */
    public function modifyMeta(array $meta): array
    {
        [$fieldset, $children, $component] = self::COMPONENT_PATH;

        return array_replace_recursive($meta, [
            $fieldset => [$children => [$component => ['arguments' => ['data' => ['config' => $this->config()]]]]],
        ]);
    }

    /**
     * The crop editor configuration
     *
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $formats = [];
        $defaultQuality = [];
        foreach ($this->formatRegistry->getVariantFormats() as $format) {
            $code = $format->getCode();
            $formats[] = [
                'code' => $code,
                'mime' => $format->getMimeType(),
                'label' => strtoupper($code),
                'encodable' => $this->formatRegistry->isEncodable($format),
            ];
            try {
                $defaultQuality[$code] = $this->imageConfig->getDefaultQuality($code);
            } catch (\InvalidArgumentException) {
                continue;
            }
        }

        return [
            'breakpointsUrl' => $this->urlBuilder->getUrl('banner_slider/responsivecrop/breakpoints'),
            'imageUploadUrl' => $this->urlBuilder->getUrl('banner_slider/breakpoint/imageupload'),
            'imageUploadField' => ImageUpload::FIELD,
            'formats' => $formats,
            'defaultFormats' => $this->imageConfig->getDefaultVariantFormats(),
            'defaultQuality' => $defaultQuality,
            'maxUploadBytes' => $this->imageConfig->getMaxUploadBytes(),
            'maxPostBytes' => (int)$this->dataSize->convertSizeToBytes($this->fileSize->getPostMaxSize()),
        ];
    }

    /**
     * The crop editor entries of a slider's enabled breakpoints, with the banner's crops
     *
     * @param int $sliderId
     * @param int|null $bannerId Null for a new banner
     * @return list<array<string, mixed>>
     */
    private function breakpoints(int $sliderId, ?int $bannerId): array
    {
        $crops = [];
        foreach ($bannerId === null ? [] : $this->cropRepository->getByBannerId($bannerId) as $crop) {
            $crops[(int)$crop->getBreakpointId()] = $crop;
        }

        $entries = [];
        foreach ($this->breakpointRepository->getBySliderId($sliderId, true) as $breakpoint) {
            $entries[] = $this->entry($breakpoint, $crops[(int)$breakpoint->getBreakpointId()] ?? null);
        }

        return $entries;
    }

    /**
     * One breakpoint with its crop state; `enabled` is whether the crop is shown (true while there is no crop)
     *
     * @param BreakpointInterface $breakpoint
     * @param ResponsiveCropInterface|null $crop
     * @return array<string, mixed>
     */
    private function entry(BreakpointInterface $breakpoint, ?ResponsiveCropInterface $crop): array
    {
        $rect = $crop?->getCropRect();
        $sourceImage = $crop?->getSourceImage();
        $variants = [];
        foreach ($crop?->getVariants() ?? [] as $variant) {
            $variants[] = [
                'format' => $variant->getFormat(),
                'quality' => $variant->getQuality(),
                'url' => $this->url($variant->getPath()),
            ];
        }

        return array_replace($this->breakpointPresenter->present($breakpoint), [
            'crop' => $rect === null ? null : [
                'x' => $rect->getX(),
                'y' => $rect->getY(),
                'width' => $rect->getWidth(),
                'height' => $rect->getHeight(),
            ],
            'source_image' => $sourceImage,
            'source_url' => $this->url($sourceImage),
            BreakpointPresenter::ENABLED => $crop === null || $crop->isEnabled(),
            'variants' => $variants,
            'cropped_url' => $this->url($crop?->getCroppedImage()),
        ]);
    }

    /**
     * Public URL of a stored path; null when there is no path or it cannot be resolved
     *
     * @param string|null $path
     * @return string|null
     */
    private function url(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        try {
            return $this->mediaUrlResolver->getUrl($path);
        } catch (\InvalidArgumentException | LocalizedException) {
            return null;
        }
    }
}
