<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop;

use Hryvinskyi\MediaUploader\Api\FileUploaderInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Upload custom image for a specific breakpoint
 */
class UploadBreakpointImage extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param FileUploaderInterface $fileUploader
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly FileUploaderInterface $fileUploader,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $result = $this->fileUploader->saveFileToTmpDir('breakpoint_image');
            $fileName = $result['file'] ?? $result['name'] ?? '';

            $movedFileName = $this->fileUploader->moveFileFromTmp($fileName);

            $basePath = $this->fileUploader->getBasePath();
            $filePath = $this->fileUploader->getFilePath($basePath, $movedFileName);

            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

            return $resultJson->setData([
                'success' => true,
                'file' => $filePath,
                'url' => $mediaUrl . $filePath,
                'name' => $movedFileName,
                'type' => $result['type'] ?? null,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'errorcode' => $e->getCode(),
            ]);
        }
    }
}
