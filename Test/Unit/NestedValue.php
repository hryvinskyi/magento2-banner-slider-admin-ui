<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\BannerSliderAdminUi\Test\Unit;

/**
 * Reads a value deep inside nested arrays (form data, UI meta) in tests.
 */
trait NestedValue
{
    /**
     * The value at the key path, or null when any step is missing or not an array
     *
     * @param mixed $value
     * @param int|string ...$keys
     * @return mixed
     */
    private function nested(mixed $value, int|string ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
