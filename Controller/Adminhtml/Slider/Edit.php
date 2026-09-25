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
use Magento\Backend\Model\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * The slider form page, for a new slider or a stored one; a slider that no longer exists sends the admin back to
 * the grid with a message.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SliderRepositoryInterface $sliderRepository
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Show the slider form
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $sliderId = $this->idReader->read($this->getRequest(), 'slider_id');
        $title = __('New Slider');

        if ($sliderId !== null) {
            try {
                $title = __('Edit Slider: %1', $this->sliderRepository->getById($sliderId)->getName());
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $page = $this->resultPageFactory->create();
        $page->addDefaultHandle();
        $page->setActiveMenu('Hryvinskyi_BannerSlider::slider');
        $page->getConfig()->getTitle()->prepend($title->render());

        return $page;
    }
}
