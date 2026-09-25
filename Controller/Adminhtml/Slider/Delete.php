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
use Magento\Framework\Exception\LocalizedException;

/**
 * Deletes one slider with its banners and breakpoints, then returns to the slider grid.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider_delete';

    /**
     * @param Context $context
     * @param SliderRepositoryInterface $sliderRepository
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Delete the slider named by `slider_id`
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $sliderId = $this->idReader->read($this->getRequest(), 'slider_id');
        if ($sliderId === null) {
            $this->messageManager->addErrorMessage(__('Choose a slider to delete.'));

            return $redirect;
        }

        try {
            $this->sliderRepository->deleteById($sliderId);
            $this->messageManager->addSuccessMessage(__('The slider has been deleted.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('Something went wrong while deleting the slider.')
            );
        }

        return $redirect;
    }
}
