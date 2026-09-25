<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * The form for a new slider: the edit page without an id.
 */
class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_BannerSlider::slider_save';

    /**
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Forward to the edit action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
