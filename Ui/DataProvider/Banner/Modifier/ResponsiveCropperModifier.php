<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner\Modifier;

use Hryvinskyi\BannerSliderApi\Api\Image\FormatConverterInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Modifier for responsive cropper UI component configuration
 */
class ResponsiveCropperModifier implements ModifierInterface
{
    /**
     * @param UrlInterface $urlBuilder
     * @param FormatConverterInterface $formatConverter
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly FormatConverterInterface $formatConverter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta): array
    {
        $meta['image_settings']['children']['responsive_cropper_container']['arguments']['data']['config'] = array_merge(
            $meta['image_settings']['children']['responsive_cropper_container']['arguments']['data']['config'] ?? [],
            [
                'saveUrl' => $this->urlBuilder->getUrl('banner_slider/responsivecrop/save'),
                'generateUrl' => $this->urlBuilder->getUrl('banner_slider/responsivecrop/generate'),
                'uploadCompressedUrl' => $this->urlBuilder->getUrl('banner_slider/responsivecrop/uploadcompressed'),
                'uploadUrl' => $this->urlBuilder->getUrl('banner_slider/responsivecrop/upload'),
                'uploadBreakpointImageUrl' => $this->urlBuilder->getUrl('banner_slider/responsivecrop/uploadbreakpointimage'),
                'webpSupported' => $this->formatConverter->isWebPSupported(),
                'avifSupported' => $this->formatConverter->isAvifSupported(),
            ]
        );

        return $meta;
    }
}
