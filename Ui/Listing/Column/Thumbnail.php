<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column;

use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Media\MediaUrlResolverInterface;
use Hryvinskyi\BannerSliderApi\Api\Value\BannerType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Banner grid thumbnail: the banner image, a video placeholder for video banners, or an image placeholder when there
 * is no image (or its path cannot be turned into a URL).
 */
class Thumbnail extends Column
{
    private const ALT_FIELD = 'title';
    private const PLACEHOLDER_IMAGE = 'Hryvinskyi_BannerSliderAdminUi::images/placeholder/image.svg';
    private const PLACEHOLDER_VIDEO = 'Hryvinskyi_BannerSliderAdminUi::images/placeholder/video.svg';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param MediaUrlResolverInterface $mediaUrlResolver
     * @param UrlInterface $urlBuilder
     * @param AssetRepository $assetRepository
     * @param array<mixed> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly MediaUrlResolverInterface $mediaUrlResolver,
        private readonly UrlInterface $urlBuilder,
        private readonly AssetRepository $assetRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Add the thumbnail source, alternative text and edit link to every row
     *
     * @param array<mixed> $dataSource
     * @return array<mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        $data = $dataSource['data'] ?? null;
        $items = is_array($data) ? $data['items'] ?? null : null;
        $fieldName = $this->getData('name');
        if (!is_array($data) || !is_array($items) || !is_string($fieldName)) {
            return $dataSource;
        }

        $altField = $this->getData('config/altField');
        $altField = is_string($altField) && $altField !== '' ? $altField : self::ALT_FIELD;
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $source = $this->source($item, $fieldName);
            $alt = $item[$altField] ?? '';
            $item[$fieldName . '_src'] = $source;
            $item[$fieldName . '_orig_src'] = $source;
            $item[$fieldName . '_alt'] = is_string($alt) ? $alt : '';
            $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                'banner_slider/banner/edit',
                ['banner_id' => $item[BannerInterface::BANNER_ID] ?? null]
            );
            $items[$index] = $item;
        }
        $data['items'] = $items;
        $dataSource['data'] = $data;

        return $dataSource;
    }

    /**
     * The thumbnail URL of one row
     *
     * @param array<mixed> $item
     * @param string $fieldName
     * @return string
     */
    private function source(array $item, string $fieldName): string
    {
        $type = $item[BannerInterface::TYPE] ?? null;
        if (is_numeric($type) && BannerType::tryFrom((int)$type) === BannerType::VIDEO) {
            return $this->assetRepository->getUrl(self::PLACEHOLDER_VIDEO);
        }

        $path = $item[$fieldName] ?? null;
        if (is_string($path) && $path !== '') {
            try {
                return $this->mediaUrlResolver->getUrl($path);
            } catch (\InvalidArgumentException | LocalizedException) {
                return $this->assetRepository->getUrl(self::PLACEHOLDER_IMAGE);
            }
        }

        return $this->assetRepository->getUrl(self::PLACEHOLDER_IMAGE);
    }
}
