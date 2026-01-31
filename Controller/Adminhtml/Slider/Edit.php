<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider;

use Hryvinskyi\BannerSliderApi\Api\SliderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Edit slider controller
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SliderRepositoryInterface $sliderRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly SliderRepositoryInterface $sliderRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Page
     */
    public function execute(): Page
    {
        $sliderId = (int)$this->getRequest()->getParam('slider_id');
        $title = __('New Slider');

        if ($sliderId) {
            try {
                $slider = $this->sliderRepository->getById($sliderId);
                $title = __('Edit Slider: %1', $slider->getName());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Hryvinskyi_BannerSlider::slider');
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
