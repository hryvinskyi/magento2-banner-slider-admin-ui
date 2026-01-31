<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Manage Banners button for banner form
 */
class ManageBannersButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Manage Banners'),
            'on_click' => sprintf("location.href = '%s';", $this->getManageBannersUrl()),
            'class' => 'secondary',
            'sort_order' => 15,
        ];
    }

    /**
     * Get URL for manage banners button
     *
     * @return string
     */
    private function getManageBannersUrl(): string
    {
        $params = [];
        $sliderId = $this->getSliderId();

        if ($sliderId) {
            $params['slider_id'] = $sliderId;
        }

        return $this->getUrl('banner_slider/banner/index', $params);
    }
}
