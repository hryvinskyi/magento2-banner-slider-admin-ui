<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\ResponsiveCrop;

use Hryvinskyi\BannerSliderApi\Api\UploadImageInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Upload source image for responsive crop controller
 */
class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly UploadImageInterface $uploadImage
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
            $fieldName = $this->getRequest()->getParam('param_name', 'responsive_image');
            $result = $this->uploadImage->execute($fieldName);

            return $resultJson->setData($result);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode(),
            ]);
        }
    }
}
