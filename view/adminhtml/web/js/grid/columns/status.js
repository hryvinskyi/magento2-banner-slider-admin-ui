define([
    'Magento_Ui/js/grid/columns/select'
], function (Select) {
    'use strict';

    return Select.extend({
        defaults: {
            bodyTmpl: 'Hryvinskyi_BannerSliderAdminUi/grid/cells/status'
        },

        /**
         * Get status class based on value
         *
         * @param {Object} row
         * @returns {String}
         */
        getStatusClass: function (row) {
            var value = parseInt(row[this.index], 10);

            return value === 1 ? 'grid-severity-notice' : 'grid-severity-major';
        }
    });
});
