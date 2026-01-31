<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Video;

use Hryvinskyi\BannerSliderApi\Api\Video\UploadInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Video upload controller
 */
class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param UploadInterface $videoUpload
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly UploadInterface $videoUpload
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
        $fileId = $this->_request->getParam('param_name', 'video_path');

        try {
            $result = $this->videoUpload->uploadToTmp($fileId);
            return $resultJson->setData($result);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode(),
            ]);
        }
    }
}
