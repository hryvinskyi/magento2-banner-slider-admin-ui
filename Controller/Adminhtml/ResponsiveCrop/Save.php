<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop;

use Hryvinskyi\BannerSlider\Model\ResponsiveCropFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface;
use Hryvinskyi\BannerSliderApi\Api\ResponsiveCropRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * AJAX save responsive crop controller
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ResponsiveCropRepositoryInterface $responsiveCropRepository
     * @param ResponsiveCropFactory $responsiveCropFactory
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ResponsiveCropRepositoryInterface $responsiveCropRepository,
        private readonly ResponsiveCropFactory $responsiveCropFactory
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
            $data = $this->getRequest()->getPostValue();

            if (!$data) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('No data received.'),
                ]);
            }

            $bannerId = isset($data['banner_id']) ? (int)$data['banner_id'] : null;
            $breakpointId = isset($data['breakpoint_id']) ? (int)$data['breakpoint_id'] : null;

            if (!$bannerId || !$breakpointId) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Banner ID and Breakpoint ID are required.'),
                ]);
            }

            $existingCrop = $this->responsiveCropRepository->getByBannerAndBreakpoint($bannerId, $breakpointId);

            if ($existingCrop !== null) {
                $crop = $existingCrop;
            } else {
                $crop = $this->responsiveCropFactory->create();
            }

            $this->populateCrop($crop, $data);
            $savedCrop = $this->responsiveCropRepository->save($crop);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Crop data saved successfully.'),
                'crop_id' => $savedCrop->getCropId(),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Populate crop with form data
     *
     * @param ResponsiveCropInterface $crop
     * @param array<string, mixed> $data
     * @return void
     */
    private function populateCrop(ResponsiveCropInterface $crop, array $data): void
    {
        $crop->setBannerId((int)($data['banner_id'] ?? 0));
        $crop->setBreakpointId((int)($data['breakpoint_id'] ?? 0));
        $crop->setSourceImage($data['source_image'] ?? null);
        $crop->setCropX((int)($data['crop_x'] ?? 0));
        $crop->setCropY((int)($data['crop_y'] ?? 0));
        $crop->setCropWidth((int)($data['crop_width'] ?? 0));
        $crop->setCropHeight((int)($data['crop_height'] ?? 0));
        $crop->setGenerateWebpEnabled((bool)($data['generate_webp'] ?? true));
        $crop->setGenerateAvifEnabled((bool)($data['generate_avif'] ?? false));
        $crop->setWebpQuality((int)($data['webp_quality'] ?? 85));
        $crop->setAvifQuality((int)($data['avif_quality'] ?? 80));
        $crop->setSortOrder((int)($data['sort_order'] ?? 0));
        $crop->setStatus((int)($data['status'] ?? 1));
    }
}
