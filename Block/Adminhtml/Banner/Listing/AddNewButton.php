<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Listing;

use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\GenericButton;

/**
 * "Add New Banner" on the banner grid, for admins allowed to save banners; from a slider's banner grid the new
 * banner starts in that slider.
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
        if (!$this->isAllowed('Hryvinskyi_BannerSlider::banner_save')) {
            return [];
        }
        $sliderId = $this->getRequestedId('slider_id');

        return [
            'label' => __('Add New Banner'),
            'class' => 'primary',
            'url' => $this->getUrl('*/*/new', $sliderId === null ? [] : ['slider_id' => $sliderId]),
            'sort_order' => 10,
        ];
    }
}
