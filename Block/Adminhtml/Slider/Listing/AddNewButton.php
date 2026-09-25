<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Listing;

use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\GenericButton;

/**
 * "Add New Slider" on the slider grid, for admins allowed to save sliders.
 */
class AddNewButton extends GenericButton
{
    /**
     * Button data; none when the button does not apply
     *
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        if (!$this->isAllowed('Hryvinskyi_BannerSlider::slider_save')) {
            return [];
        }

        return [
            'label' => __('Add New Slider'),
            'class' => 'primary',
            'url' => $this->getUrl('*/*/new'),
            'sort_order' => 10,
        ];
    }
}
