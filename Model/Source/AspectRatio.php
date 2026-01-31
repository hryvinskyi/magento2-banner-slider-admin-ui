<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Video aspect ratio source model
 */
class AspectRatio implements OptionSourceInterface
{
    public const string CUSTOM = 'custom';

    /** @var array<int, string> */
    public const array PREDEFINED_RATIOS = ['16:9', '4:3', '21:9', '1:1', '9:16'];

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '16:9', 'label' => __('16:9 (Widescreen)')],
            ['value' => '4:3', 'label' => __('4:3 (Standard)')],
            ['value' => '21:9', 'label' => __('21:9 (Ultrawide)')],
            ['value' => '1:1', 'label' => __('1:1 (Square)')],
            ['value' => '9:16', 'label' => __('9:16 (Vertical)')],
            ['value' => self::CUSTOM, 'label' => __('Custom Aspect Ratio')],
        ];
    }
}
