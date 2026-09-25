<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\Banner\Edit;

use Hryvinskyi\BannerSliderAdminUi\Block\Adminhtml\GenericButton;

/**
 * "Delete" on the form of a stored banner, for admins allowed to delete banners.
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
        $bannerId = $this->getRequestedId('banner_id');
        if ($bannerId === null || !$this->isAllowed('Hryvinskyi_BannerSlider::banner_delete')) {
            return [];
        }

        return $this->deleteButton(
            $this->getUrl('*/*/delete', ['banner_id' => $bannerId]),
            __('Are you sure you want to delete this banner?')
        );
    }
}
