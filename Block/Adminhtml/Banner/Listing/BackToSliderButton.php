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
 * "Back to Slider" on a slider's banner grid, for admins allowed to see sliders.
 */
class BackToSliderButton extends GenericButton
{
    /**
     * Button data; none when the button does not apply
     *
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        $sliderId = $this->getRequestedId('slider_id');
        if ($sliderId === null || !$this->isAllowed('Hryvinskyi_BannerSlider::slider')) {
            return [];
        }

        return $this->linkButton(
            __('Back to Slider'),
            $this->getUrl('banner_slider/slider/edit', ['slider_id' => $sliderId]),
            'back',
            5
        );
    }
}
