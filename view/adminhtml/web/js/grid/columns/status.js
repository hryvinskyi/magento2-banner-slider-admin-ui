/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Status column of the slider and banner grids: the option label, marked green when enabled and red otherwise.
 * The grid may hold the value as a number, a numeric string or a boolean.
 */
define([
    'Magento_Ui/js/grid/columns/select'
], function (Select) {
    'use strict';

    return Select.extend({
        defaults: {
            bodyTmpl: 'Hryvinskyi_BannerSliderAdminUi/grid/cells/status'
        },

        /**
         * Severity class of a row's status
         *
         * @param {Object} row
         * @returns {String}
         */
        getStatusClass: function (row) {
            var value = row[this.index];

            return value === true || String(value) === '1' ? 'grid-severity-notice' : 'grid-severity-major';
        }
    });
});
