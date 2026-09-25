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
use Magento\Backend\Model\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * The banner form page, for a new banner or a stored one; a banner that no longer exists sends the admin back to
 * the grid with a message.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::banner';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param BannerRepositoryInterface $bannerRepository
     * @param EntityIdReader $idReader
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly BannerRepositoryInterface $bannerRepository,
        private readonly EntityIdReader $idReader
    ) {
        parent::__construct($context);
    }

    /**
     * Show the banner form
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $bannerId = $this->idReader->read($this->getRequest(), 'banner_id');
        $title = __('New Banner');

        if ($bannerId !== null) {
            try {
                $title = __('Edit Banner: %1', $this->bannerRepository->getById($bannerId)->getName());
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('Hryvinskyi_BannerSlider::banner');
        $page->getConfig()->getTitle()->prepend($title->render());

        return $page;
    }
}
