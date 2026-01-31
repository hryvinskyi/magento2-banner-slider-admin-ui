<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\DataProvider\Banner;

use Hryvinskyi\BannerSlider\Model\ResourceModel\Banner\CollectionFactory;
use Hryvinskyi\BannerSliderAdminUi\Api\DataProvider\PrepareDataProcessorInterface;
use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BreakpointInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface;
use Hryvinskyi\BannerSliderApi\Api\ResponsiveCropRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

/**
 * Banner form data provider
 */
class FormDataProvider extends AbstractDataProvider
{
    private const BREAKPOINT_IMAGE_PATH_PREFIX = 'banner_slider/breakpoint/';

    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param ResponsiveCropRepositoryInterface $responsiveCropRepository
     * @param PrepareDataProcessorInterface $prepareDataProcessor
     * @param RequestInterface $request
     * @param PoolInterface|null $pool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly StoreManagerInterface $storeManager,
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        private readonly ResponsiveCropRepositoryInterface $responsiveCropRepository,
        private readonly PrepareDataProcessorInterface $prepareDataProcessor,
        private readonly RequestInterface $request,
        private readonly ?PoolInterface $pool = null,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $items = $this->collection->getItems();

        /** @var BannerInterface $banner */
        foreach ($items as $banner) {
            $bannerData = $banner->getData();
            $this->prepareDataProcessor->execute($bannerData);
            $bannerData = $this->prepareResponsiveImageData($banner, $bannerData);
            $this->loadedData[$banner->getBannerId()] = $bannerData;
        }

        $data = $this->dataPersistor->get('hryvinskyi_banner_slider_banner');
        if (!empty($data)) {
            $banner = $this->collection->getNewEmptyItem();
            $banner->setData($data);
            $bannerData = $banner->getData();
            $this->prepareDataProcessor->execute($bannerData);
            $this->loadedData[$banner->getBannerId()] = $bannerData;
            $this->dataPersistor->clear('hryvinskyi_banner_slider_banner');
        }

        // Set default slider_id for new banner from request parameter
        if (empty($this->loadedData)) {
            $sliderId = $this->request->getParam('slider_id');
            if ($sliderId) {
                $this->loadedData[''] = ['slider_id' => (int)$sliderId];
            }
        }

        // Apply modifiers if pool exists
        if ($this->pool !== null) {
            foreach ($this->pool->getModifiersInstances() as $modifier) {
                $this->loadedData = $modifier->modifyData($this->loadedData);
            }
        }

        return $this->loadedData;
    }

    /**
     * @inheritDoc
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();

        // Apply modifiers if pool exists
        if ($this->pool !== null) {
            foreach ($this->pool->getModifiersInstances() as $modifier) {
                $meta = $modifier->modifyMeta($meta);
            }
        }

        return $meta;
    }

    /**
     * Prepare responsive image data including breakpoints and crops
     *
     * @param BannerInterface $banner
     * @param array $bannerData
     * @return array
     */
    private function prepareResponsiveImageData(BannerInterface $banner, array $bannerData): array
    {
        $sliderId = $banner->getSliderId();
        $bannerId = $banner->getBannerId();

        if (!$sliderId) {
            $bannerData['responsive_cropper'] = [
                'breakpoints' => [],
                'crops' => [],
                'banner_id' => $bannerId,
                'slider_id' => null,
            ];
            return $bannerData;
        }

        // Load breakpoints for the slider
        $breakpoints = $this->breakpointRepository->getBySliderId((int)$sliderId);
        $breakpointsData = [];

        /** @var BreakpointInterface $breakpoint */
        foreach ($breakpoints as $breakpoint) {
            $breakpointsData[] = [
                'breakpoint_id' => $breakpoint->getBreakpointId(),
                'name' => $breakpoint->getName(),
                'identifier' => $breakpoint->getIdentifier(),
                'media_query' => $breakpoint->getMediaQuery(),
                'min_width' => $breakpoint->getMinWidth(),
                'target_width' => $breakpoint->getTargetWidth(),
                'target_height' => $breakpoint->getTargetHeight(),
                'sort_order' => $breakpoint->getSortOrder(),
            ];
        }

        // Load existing crops for this banner
        $crops = [];
        if ($bannerId) {
            $existingCrops = $this->responsiveCropRepository->getByBannerId((int)$bannerId);
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            /** @var ResponsiveCropInterface $crop */
            foreach ($existingCrops as $crop) {
                $sourceImage = $crop->getSourceImage();
                $isCustomImage = $this->isCustomBreakpointImage($sourceImage);

                $cropData = [
                    'crop_id' => $crop->getCropId(),
                    'breakpoint_id' => $crop->getBreakpointId(),
                    'source_image' => $sourceImage,
                    'crop_x' => $crop->getCropX(),
                    'crop_y' => $crop->getCropY(),
                    'crop_width' => $crop->getCropWidth(),
                    'crop_height' => $crop->getCropHeight(),
                    'generate_webp' => $crop->isGenerateWebpEnabled(),
                    'generate_avif' => $crop->isGenerateAvifEnabled(),
                    'webp_quality' => $crop->getWebpQuality(),
                    'avif_quality' => $crop->getAvifQuality(),
                    'custom_source_image' => $isCustomImage ? $sourceImage : null,
                    'custom_source_image_url' => $isCustomImage && $sourceImage ? $mediaUrl . $sourceImage : null,
                ];

                // Add URLs for existing images (paths already include the responsive directory prefix)
                if ($sourceImage) {
                    $cropData['source_image_url'] = $mediaUrl . $sourceImage;
                }
                if ($crop->getCroppedImage()) {
                    $cropData['cropped_image_url'] = $mediaUrl . $crop->getCroppedImage();
                }
                if ($crop->getWebpImage()) {
                    $cropData['webp_image_url'] = $mediaUrl . $crop->getWebpImage();
                }
                if ($crop->getAvifImage()) {
                    $cropData['avif_image_url'] = $mediaUrl . $crop->getAvifImage();
                }

                $crops[$crop->getBreakpointId()] = $cropData;
            }
        }

        $bannerData['responsive_cropper'] = [
            'breakpoints' => $breakpointsData,
            'crops' => $crops,
            'banner_id' => $bannerId,
            'slider_id' => $sliderId,
        ];

        return $bannerData;
    }

    /**
     * Check if source image is a custom breakpoint image (not the main desktop image)
     *
     * @param string|null $sourceImage
     * @return bool
     */
    private function isCustomBreakpointImage(?string $sourceImage): bool
    {
        if ($sourceImage === null || $sourceImage === '') {
            return false;
        }

        return str_starts_with($sourceImage, self::BREAKPOINT_IMAGE_PATH_PREFIX);
    }
}
