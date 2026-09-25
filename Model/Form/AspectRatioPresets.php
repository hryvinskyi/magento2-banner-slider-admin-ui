<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Model\Form;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * The video aspect ratios the banner form offers as presets (`W:H` values), plus the "custom" choice.
 *
 * The presets come from the option source wired in `etc/adminhtml/di.xml`, so the select and the form mapping share
 * one list.
 */
class AspectRatioPresets
{
    /**
     * Select value that switches to the free-text custom ratio field
     */
    public const CUSTOM_CHOICE = 'custom';

    /**
     * @param OptionSourceInterface $presetOptions Options valued `W:H`
     */
    public function __construct(
        private readonly OptionSourceInterface $presetOptions
    ) {
    }

    /**
     * The preset options, value and label, in display order
     *
     * @return list<array{value: string, label: string}>
     */
    public function getOptions(): array
    {
        $options = [];
        foreach ($this->presetOptions->toOptionArray() as $option) {
            if (!is_array($option) || !isset($option['value']) || !is_string($option['value'])) {
                continue;
            }
            $label = $option['label'] ?? $option['value'];
            $options[] = [
                'value' => $option['value'],
                'label' => is_string($label) || $label instanceof \Stringable ? (string)$label : $option['value'],
            ];
        }

        return $options;
    }

    /**
     * Whether a `W:H` value is one of the presets
     *
     * @param string $ratio
     * @return bool
     */
    public function isPreset(string $ratio): bool
    {
        foreach ($this->getOptions() as $option) {
            if ($option['value'] === $ratio) {
                return true;
            }
        }

        return false;
    }
}
