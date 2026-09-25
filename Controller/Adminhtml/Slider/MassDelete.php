<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider;

use Hryvinskyi\BannerSliderAdminUi\Model\Request\EntityIdReader;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Deletes the sliders selected in the grid, then returns to it.
 *
 * The selection is resolved to ids in one query on the grid collection (injected in `etc/adminhtml/di.xml`), and
 * each slider is deleted through the repository, so its banners, breakpoints, crops and files go with it.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param AbstractDb $collection The slider grid collection, one instance per request
     * @param SliderRepositoryInterface $sliderRepository
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly AbstractDb $collection,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Delete the selected sliders
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');

        try {
            $deleted = 0;
            foreach ($this->filter->getCollection($this->collection)->getAllIds() as $rawId) {
                $sliderId = $this->idReader->parse($rawId);
                if ($sliderId !== null && $this->delete($sliderId)) {
                    $deleted++;
                }
            }
            if ($deleted > 0) {
                $this->messageManager->addSuccessMessage(__('A total of %1 slider(s) have been deleted.', $deleted));
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Something went wrong while deleting the sliders.')
            );
        }

        return $redirect;
    }

    /**
     * Delete one slider; a slider already gone counts as not deleted, a failure is reported and skipped
     *
     * @param int $sliderId
     * @return bool Whether the slider was deleted
     */
    private function delete(int $sliderId): bool
    {
        try {
            $this->sliderRepository->deleteById($sliderId);
        } catch (NoSuchEntityException) {
            return false;
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage(
                __('Slider %1 could not be deleted: %2', $sliderId, $exception->getMessage())
            );

            return false;
        }

        return true;
    }
}
