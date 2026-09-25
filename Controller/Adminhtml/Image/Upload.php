<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Image;

use Hryvinskyi\BannerSliderAdminUi\Model\Upload\MediaUploadHandler;
use Hryvinskyi\BannerSliderApi\Api\Media\ImageUploadInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Stores the banner image picked in the form's image uploader and answers the uploader JSON.
 *
 * The file field is named by the uploader's `param_name` (`image` by default).
 */
class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';
    private const DEFAULT_FIELD = 'image';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param MediaUploadHandler $uploadHandler
     * @param ImageUploadInterface $imageUpload
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly MediaUploadHandler $uploadHandler,
        private readonly ImageUploadInterface $imageUpload
    ) {
        parent::__construct($context);
    }

    /**
     * Store the uploaded image
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $field = $this->getRequest()->getParam('param_name');

        return $this->jsonFactory->create()->setData($this->uploadHandler->handle(
            $this->getRequest(),
            is_string($field) && $field !== '' ? $field : self::DEFAULT_FIELD,
            $this->imageUpload->upload(...)
        ));
    }
}
