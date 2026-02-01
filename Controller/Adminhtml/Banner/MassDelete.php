<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Banner;

use Hryvinskyi\BannerSlider\Model\ResourceModel\Banner\CollectionFactory;
use Hryvinskyi\BannerSliderApi\Api\BannerRepositoryInterface;
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Mass delete banners controller
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param BannerRepositoryInterface $bannerRepository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly BannerRepositoryInterface $bannerRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Execute mass delete action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deletedCount = 0;

            /** @var BannerInterface $banner */
            foreach ($collection->getItems() as $banner) {
                try {
                    $this->bannerRepository->deleteById((int)$banner->getBannerId());
                    $deletedCount++;
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(
                        __('Could not delete banner ID %1: %2', $banner->getBannerId(), $e->getMessage())
                    );
                }
            }

            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 banner(s) have been deleted.', $deletedCount)
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting banners.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
