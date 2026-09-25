<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Ui\Component\Form;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Store view options of the slider form: "All Store Views" (store `0`) first, then the website / store / store view
 * tree of the wrapped source (`etc/adminhtml/di.xml`), which has no all-stores option of its own.
 *
 * Most sliders are shown on all store views, so without the option the admin could neither see nor keep that
 * scope.
 */
class StoreViewOptions implements OptionSourceInterface
{
    public const ALL_STORE_VIEWS = '0';

    /**
     * @var array<mixed>|null
     */
    private ?array $options = null;

    /**
     * @param OptionSourceInterface $storeOptions
     */
    public function __construct(
        private readonly OptionSourceInterface $storeOptions
    ) {
    }

    /**
     * "All Store Views" followed by the store view tree
     *
     * @return array<mixed>
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = [
                ['label' => __('All Store Views'), 'value' => self::ALL_STORE_VIEWS],
                ...array_values($this->storeOptions->toOptionArray()),
            ];
        }

        return $this->options;
    }
}
