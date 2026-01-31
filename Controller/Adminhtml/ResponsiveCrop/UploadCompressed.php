<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop;

use Hryvinskyi\BannerSliderApi\Api\Image\UploadCompressedImagesInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controller for uploading pre-compressed images from browser
 */
class UploadCompressed extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param UploadCompressedImagesInterface $uploadCompressedImages
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly UploadCompressedImagesInterface $uploadCompressedImages,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * Execute upload action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $cropId = (int)$this->getRequest()->getParam('crop_id');

            if (!$cropId) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Crop ID is required.'),
                ]);
            }

            $files = $this->getRequest()->getFiles()->toArray();
            $params = [
                'crop_x' => $this->getRequest()->getParam('crop_x'),
                'crop_y' => $this->getRequest()->getParam('crop_y'),
                'crop_width' => $this->getRequest()->getParam('crop_width'),
                'crop_height' => $this->getRequest()->getParam('crop_height'),
                'webp_quality' => $this->getRequest()->getParam('webp_quality'),
                'avif_quality' => $this->getRequest()->getParam('avif_quality'),
            ];

            $params = array_filter($params, fn($value) => $value !== null && $value !== '');

            $crop = $this->uploadCompressedImages->upload($cropId, $files, $params);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Images uploaded successfully.'),
                'images' => $this->getCropImageUrls($crop),
                'sizes' => $this->getCropImageSizes($crop),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get URLs for uploaded crop images
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

    /**
     * Get file sizes for uploaded crop images
     *
     * @param \Hryvinskyi\BannerSliderApi\Api\Data\ResponsiveCropInterface $crop
     * @return array<string, int|null>
     */
    private function getCropImageSizes($crop): array
    {
        $mediaPath = $this->storeManager->getStore()->getBaseMediaDir();

        $getSize = function (?string $relativePath) use ($mediaPath): ?int {
            if ($relativePath === null) {
                return null;
            }

            $fullPath = $mediaPath . '/' . $relativePath;

            if (!file_exists($fullPath)) {
                return null;
            }

            $size = filesize($fullPath);

            return $size !== false ? $size : null;
        };

        return [
            'original' => $getSize($crop->getCroppedImage()),
            'webp' => $getSize($crop->getWebpImage()),
            'avif' => $getSize($crop->getAvifImage()),
        ];
    }
}
