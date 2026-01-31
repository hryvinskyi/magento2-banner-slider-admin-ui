/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'Magento_Ui/js/form/element/select',
    'ko'
], function (Select, ko) {
    'use strict';

    return Select.extend({
        defaults: {
            editSliderUrl: '',
            elementTmpl: 'Hryvinskyi_BannerSliderAdminUi/form/element/slider-select'
        },

        /**
         * Initialize component
         *
         * @returns {Object}
         */
        initialize: function () {
            this._super();

            this.editSliderHref = ko.pureComputed(function () {
                var sliderId = this.value();

                if (!sliderId || !this.editSliderUrl) {
                    return '#';
                }

                return this.editSliderUrl.replace('__slider_id__', sliderId);
            }, this);

            return this;
        },

        /**
         * Open edit slider page in new tab
         */
        editSlider: function () {
            var url = this.editSliderHref();

            console.log(this.value());
            if (url && url !== '#') {
                window.open(url, '_blank');
            }
        }
    });
});
