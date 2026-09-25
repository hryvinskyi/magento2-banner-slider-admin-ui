<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner;

use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Deletes one banner, then returns to the banner grid.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_delete';

    /**
     * @param Context $context
     * @param BannerRepositoryInterface $bannerRepository
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly BannerRepositoryInterface $bannerRepository,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Delete the banner named by `banner_id`
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $bannerId = $this->idReader->read($this->getRequest(), 'banner_id');
        if ($bannerId === null) {
            $this->messageManager->addErrorMessage(__('Choose a banner to delete.'));

            return $redirect;
        }

        try {
            $this->bannerRepository->deleteById($bannerId);
            $this->messageManager->addSuccessMessage(__('The banner has been deleted.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Something went wrong while deleting the banner.')
            );
        }

        return $redirect;
    }
}
