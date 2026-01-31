/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'mage/translate'
], function (Abstract, $t) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'ui/form/element/input'
        },

        /**
         * Validate aspect ratio format
         *
         * @param {String} value
         * @returns {Boolean}
         */
        isValidAspectRatio: function (value) {
            if (!value || typeof value !== 'string') {
                return true;
            }

            var parts = value.split(':');

            if (parts.length !== 2) {
                return false;
            }

            var width = parseFloat(parts[0]);
            var height = parseFloat(parts[1]);

            if (isNaN(width) || isNaN(height)) {
                return false;
            }

            return width > 0 && height > 0;
        },

        /**
         * @inheritdoc
         */
        validate: function () {
            var value = this.value(),
                result = this._super(),
                isValid = true,
                message = '';

            if (this.visible() && value) {
                isValid = this.isValidAspectRatio(value);

                if (!isValid) {
                    message = $t('Please enter a valid aspect ratio (e.g., 3:2, 16:10, 2.35:1). Both width and height must be positive numbers.');
                    this.error(message);
                    this.source.set('params.invalid', true);
                }
            }

            return {
                valid: result.valid && isValid,
                target: this
            };
        }
    });
});
