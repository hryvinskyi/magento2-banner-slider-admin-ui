<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Listing;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Add New Banner button with slider_id preselection
 */
class AddNewButton implements ButtonProviderInterface
{
    /**
     * @param Context $context
     */
    public function __construct(
        private readonly Context $context
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        $params = [];
        $sliderId = $this->context->getRequest()->getParam('slider_id');

        if ($sliderId) {
            $params['slider_id'] = $sliderId;
        }

        return [
            'label' => __('Add New Banner'),
            'class' => 'primary',
            'url' => $this->context->getUrlBuilder()->getUrl('*/*/new', $params),
            'sort_order' => 10,
        ];
    }
}
