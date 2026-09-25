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
 * "Delete" on the form of a stored slider, for admins allowed to delete sliders.
 */
class DeleteButton extends GenericButton
{
    /**
     * Button data; none when the button does not apply
     *
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        $sliderId = $this->getRequestedId('slider_id');
        if ($sliderId === null || !$this->isAllowed('Hryvinskyi_BannerSlider::slider_delete')) {
            return [];
        }

        return $this->deleteButton(
            $this->getUrl('*/*/delete', ['slider_id' => $sliderId]),
            __('Are you sure you want to delete this slider? Its banners are deleted with it.')
        );
    }
}
