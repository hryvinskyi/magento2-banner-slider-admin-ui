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
 * "Save" on the banner form, for admins allowed to save banners.
 *
 * It asks the crop editor component to prepare the crop images first; the component then submits the form. The
 * button id must not be `save`: the form component binds its own save to an element with that id, so one click would
 * submit the form twice.
 */
class SaveButton extends GenericButton
{
    public const ID = 'save_banner';
    public const CROP_EDITOR = 'hryvinskyi_banner_slider_banner_form.hryvinskyi_banner_slider_banner_form'
        . '.image_settings.responsive_cropper_container';

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

        return [
            'id' => self::ID,
            'label' => __('Save'),
            'class' => 'save primary',
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [['targetName' => self::CROP_EDITOR, 'actionName' => 'submitForm']],
                    ],
                ],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
