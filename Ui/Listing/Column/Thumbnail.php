<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Listing\Column;

use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Thumbnail column for banner listing
 */
class Thumbnail extends Column
{
    private const ALT_FIELD = 'title';
    private const PLACEHOLDER_IMAGE = 'Hryvinskyi_BannerSliderAdminUi::images/placeholder/image.svg';
    private const PLACEHOLDER_VIDEO = 'Hryvinskyi_BannerSliderAdminUi::images/placeholder/video.svg';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param AssetRepository $assetRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $urlBuilder,
        private readonly AssetRepository $assetRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        foreach ($dataSource['data']['items'] as &$item) {
            $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
            $alt = $item[$altField] ?? '';
            $editLink = $this->urlBuilder->getUrl(
                'banner_slider/banner/edit',
                ['banner_id' => $item['banner_id']]
            );

            $imageUrl = $this->resolveImageUrl($item, $fieldName, $mediaUrl);

            $item[$fieldName . '_src'] = $imageUrl;
            $item[$fieldName . '_alt'] = $alt;
            $item[$fieldName . '_link'] = $editLink;
            $item[$fieldName . '_orig_src'] = $imageUrl;
        }

        return $dataSource;
    }

    /**
     * Resolves the image URL based on banner type and image availability.
     *
     * @param array<string, mixed> $item
     * @param string $fieldName
     * @param string $mediaUrl
     * @return string
     */
    private function resolveImageUrl(array $item, string $fieldName, string $mediaUrl): string
    {
        $bannerType = (int)($item['type'] ?? BannerInterface::TYPE_IMAGE);

        if ($bannerType === BannerInterface::TYPE_VIDEO) {
            return $this->assetRepository->getUrl(self::PLACEHOLDER_VIDEO);
        }

        if (empty($item[$fieldName])) {
            return $this->assetRepository->getUrl(self::PLACEHOLDER_IMAGE);
        }

        return $mediaUrl . $item[$fieldName];
    }
}
