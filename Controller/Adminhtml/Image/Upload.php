<?php
/**
 * Copyright (c) 2021. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Image;

use Hryvinskyi\BannerSliderApi\Api\UploadImageInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class Upload extends Action implements HttpPostActionInterface
{
    /**
     * Upload constructor.
     *
     * @param Context $context
     * @param UploadImageInterface $uploadImage
     */
    public function __construct(
        Context $context,
        private readonly UploadImageInterface $uploadImage
    ) {
        parent::__construct($context);
    }

    /**
     * Check admin permissions for this controller
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    #[\Override]
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageCloud_BannerSlider::image_save');
    }

    /**
     * Upload file controller action
     * @return ResultInterface
     */
    #[\Override]
    public function execute(): ResultInterface
    {
        $imageId = $this->_request->getParam('param_name', 'image');

        try {
            $result = $this->uploadImage->execute($imageId);
        } catch (LocalizedException $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
