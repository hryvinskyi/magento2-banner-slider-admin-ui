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
 * "Manage Banners" on the form of a stored slider, for admins allowed to see banners: opens the slider's banner
 * grid.
 */
class ManageBannersButton extends GenericButton
{
    /**
     * Button data; none when the button does not apply
     *
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        $sliderId = $this->getRequestedId('slider_id');
        if ($sliderId === null || !$this->isAllowed('Hryvinskyi_BannerSlider::banner')) {
            return [];
        }

        return $this->linkButton(
            __('Manage Banners'),
            $this->getUrl('banner_slider/banner/index', ['slider_id' => $sliderId]),
            'secondary',
            15
        );
    }
}
