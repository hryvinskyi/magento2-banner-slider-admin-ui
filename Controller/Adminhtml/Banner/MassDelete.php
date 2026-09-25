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
use Hryvinskyi\BannerSliderApi\Api\Data\BannerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Deletes the banners selected in the grid, then returns to it.
 *
 * The selection is resolved to ids in one query on the grid collection (injected in `etc/adminhtml/di.xml`), and
 * each banner is deleted through the repository, so its crops and files go with it.
 *
 * The grid of one slider posts that slider as `slider_id`, and the grid of every banner posts it empty. The
 * collection is limited to the posted slider before the selection is applied, so a banner of another slider is never
 * deleted, not even with "select all". A request without the parameter, or with one that is not a slider id, comes
 * from a page that did not state its scope and deletes nothing.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param AbstractDb $collection The banner grid collection, one instance per request
     * @param BannerRepositoryInterface $bannerRepository
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly AbstractDb $collection,
        private readonly BannerRepositoryInterface $bannerRepository,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Delete the selected banners
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $scope = $this->getRequest()->getParam(BannerInterface::SLIDER_ID);
        $sliderId = $this->idReader->parse($scope);
        $redirect = $this->resultRedirectFactory->create()->setPath(
            '*/*/',
            $sliderId === null ? [] : [BannerInterface::SLIDER_ID => $sliderId]
        );
        if ($scope === null || ($scope !== '' && $sliderId === null)) {
            $this->messageManager->addErrorMessage(
                __('Nothing was deleted: reload the banner grid and select the banners again.')
            );

            return $redirect;
        }
        if ($sliderId !== null) {
            $this->collection->addFieldToFilter(BannerInterface::SLIDER_ID, ['eq' => $sliderId]);
        }

        try {
            $deleted = 0;
            foreach ($this->filter->getCollection($this->collection)->getAllIds() as $rawId) {
                $bannerId = $this->idReader->parse($rawId);
                if ($bannerId !== null && $this->delete($bannerId)) {
                    $deleted++;
                }
            }
            if ($deleted > 0) {
                $this->messageManager->addSuccessMessage(__('A total of %1 banner(s) have been deleted.', $deleted));
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Something went wrong while deleting the banners.')
            );
        }

        return $redirect;
    }

    /**
     * Delete one banner; a banner already gone counts as not deleted, a failure is reported and skipped
     *
     * @param int $bannerId
     * @return bool Whether the banner was deleted
     */
    private function delete(int $bannerId): bool
    {
        try {
            $this->bannerRepository->deleteById($bannerId);
        } catch (NoSuchEntityException) {
            return false;
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage(
                __('Banner %1 could not be deleted: %2', $bannerId, $exception->getMessage())
            );

            return false;
        }

        return true;
    }
}
