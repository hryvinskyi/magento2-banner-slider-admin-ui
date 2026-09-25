/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * File sizes for display, and how much smaller an optimised file is than the original.
 */
define([], function () {
    'use strict';

    var UNITS = ['B', 'KB', 'MB', 'GB'],
        STEP = 1024;

    /**
     * A byte count as `512 B`, `1.5 KB`, `2.3 MB`; anything that is not a positive number is `0 B`
     *
     * @param {Number} bytes
     * @returns {String}
     */
    function format(bytes) {
        var value = Number(bytes),
            unit = 0;

        if (!isFinite(value) || value <= 0) {
            return '0 ' + UNITS[0];
        }
        while (value >= STEP && unit < UNITS.length - 1) {
            value /= STEP;
            unit++;
        }

        return (unit === 0 ? String(Math.round(value)) : value.toFixed(1)) + ' ' + UNITS[unit];
    }

    /**
     * Percentage saved by the optimised file, one decimal: positive when it is smaller than the original, negative
     * when it is larger, null when either size is unknown
     *
     * @param {Number} originalBytes
     * @param {Number} optimisedBytes
     * @returns {Number|null}
     */
    function savingsPercent(originalBytes, optimisedBytes) {
        if (!(originalBytes > 0) || !(optimisedBytes > 0)) {
            return null;
        }

        return Math.round((originalBytes - optimisedBytes) / originalBytes * 1000) / 10;
    }

    return {
        format: format,
        savingsPercent: savingsPercent
    };
});
