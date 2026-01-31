<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop;

use Hryvinskyi\BannerSliderApi\Api\BreakpointRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Image\ResponsiveImageGeneratorInterface;
use Hryvinskyi\BannerSliderApi\Api\ResponsiveCropRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Generate responsive images controller
 */
class Generate extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ResponsiveCropRepositoryInterface $responsiveCropRepository
     * @param BreakpointRepositoryInterface $breakpointRepository
     * @param ResponsiveImageGeneratorInterface $imageGenerator
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ResponsiveCropRepositoryInterface $responsiveCropRepository,
        private readonly BreakpointRepositoryInterface $breakpointRepository,
        private readonly ResponsiveImageGeneratorInterface $imageGenerator,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $cropId = (int)$this->getRequest()->getParam('crop_id');
            $bannerId = (int)$this->getRequest()->getParam('banner_id');

            if ($cropId) {
                $crop = $this->responsiveCropRepository->getById($cropId);
                $breakpoint = $this->breakpointRepository->getById($crop->getBreakpointId());
                $generatedCrop = $this->imageGenerator->generate($crop, $breakpoint);
                $this->responsiveCropRepository->save($generatedCrop);

                return $resultJson->setData([
                    'success' => true,
                    'message' => __('Images generated successfully.'),
                    'images' => $this->getCropImageUrls($generatedCrop),
                ]);
            }

            if ($bannerId) {
                $generatedCrops = $this->imageGenerator->generateForBanner($bannerId);

                $results = [];
                foreach ($generatedCrops as $crop) {
                    $results[$crop->getBreakpointId()] = $this->getCropImageUrls($crop);
                }

                return $resultJson->setData([
                    'success' => true,
                    'message' => __('%1 breakpoint image(s) generated successfully.', count($generatedCrops)),
                    'crops' => $results,
                ]);
            }

            return $resultJson->setData([
                'success' => false,
                'message' => __('Crop ID or Banner ID is required.'),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get URLs for generated crop images
     *
     * @param \Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface $crop
     * @return array<string, string|null>
     */
    private function getCropImageUrls($crop): array
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return [
            'cropped' => $crop->getCroppedImage() ? $mediaUrl . $crop->getCroppedImage() : null,
            'webp' => $crop->getWebpImage() ? $mediaUrl . $crop->getWebpImage() : null,
            'avif' => $crop->getAvifImage() ? $mediaUrl . $crop->getAvifImage() : null,
        ];
    }
}
