<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Component\Form;

use Hryvinskyi\BannerSliderAdminUi\Model\Form\AspectRatioPresets;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Video aspect ratio select of the banner form: the presets, then "Custom", which reveals the free-text ratio field.
 */
class VideoAspectRatioOptions implements OptionSourceInterface
{
    /**
     * @param AspectRatioPresets $presets
     */
    public function __construct(
        private readonly AspectRatioPresets $presets
    ) {
    }

    /**
     * The preset ratios and the custom choice
     *
     * @return list<array{value: string, label: \Magento\Framework\Phrase|string}>
     */
    public function toOptionArray(): array
    {
        return [
            ...$this->presets->getOptions(),
            ['value' => AspectRatioPresets::CUSTOM_CHOICE, 'label' => __('Custom')],
        ];
    }
}
