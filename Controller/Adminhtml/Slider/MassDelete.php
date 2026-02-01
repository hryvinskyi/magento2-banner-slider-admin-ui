<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider;

use Hryvinskyi\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Hryvinskyi\BannerSliderApi\Api\Data\SliderInterface;
use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Mass delete sliders controller
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider_delete';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param SliderRepositoryInterface $sliderRepository
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderRepositoryInterface $sliderRepository
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

            /** @var SliderInterface $slider */
            foreach ($collection->getItems() as $slider) {
                try {
                    $this->sliderRepository->deleteById((int)$slider->getSliderId());
                    $deletedCount++;
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(
                        __('Could not delete slider ID %1: %2', $slider->getSliderId(), $e->getMessage())
                    );
                }
            }

            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 slider(s) have been deleted.', $deletedCount)
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting sliders.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
