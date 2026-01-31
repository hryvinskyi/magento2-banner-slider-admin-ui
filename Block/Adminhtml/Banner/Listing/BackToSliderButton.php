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
 * Back to Slider button - only visible when slider_id parameter exists
 */
class BackToSliderButton implements ButtonProviderInterface
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
        $sliderId = $this->context->getRequest()->getParam('slider_id');

        if (!$sliderId) {
            return [];
        }

        return [
            'label' => __('Back to Slider'),
            'class' => 'back',
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl((int)$sliderId)),
            'sort_order' => 5,
        ];
    }

    /**
     * Get URL for back to slider button
     *
     * @param int $sliderId
     * @return string
     */
    private function getBackUrl(int $sliderId): string
    {
        return $this->context->getUrlBuilder()->getUrl(
            'banner_slider/slider/edit',
            ['slider_id' => $sliderId]
        );
    }
}
