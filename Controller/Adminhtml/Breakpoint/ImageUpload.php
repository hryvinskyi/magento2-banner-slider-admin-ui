<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Breakpoint;

use Hryvinskyi\BannerSliderAdminUi\Model\Upload\MediaUploadHandler;
use Hryvinskyi\BannerSliderApi\Api\Media\ImageUploadInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Stores an image the crop editor uses as the source of one breakpoint's crop and answers the uploader JSON.
 *
 * The file is posted in the `breakpoint_image` field and stored by the same image upload service as the banner
 * image. Nothing is linked to the banner here: the returned path is sent back with the banner form as that crop's
 * `source_image`, so an image uploaded and then abandoned is never attached to anything.
 */
class ImageUpload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_save';
    public const FIELD = 'breakpoint_image';

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
     * Store the uploaded breakpoint image
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        return $this->jsonFactory->create()->setData(
            $this->uploadHandler->handle($this->getRequest(), self::FIELD, $this->imageUpload->upload(...))
        );
    }
}
