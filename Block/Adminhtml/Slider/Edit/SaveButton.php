<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Slider\Edit;

use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\GenericButton;

/**
 * "Save" on the slider form, for admins allowed to save sliders.
 */
class SaveButton extends GenericButton
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
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
