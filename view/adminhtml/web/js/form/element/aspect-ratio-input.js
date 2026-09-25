/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The custom video aspect ratio field: shown, and required, only while the aspect ratio select is on its custom
 * choice (`imports.aspectRatioChoice` and `customChoice` come from the form). The value itself is checked by the
 * `validate-hbs-aspect-ratio` rule, the same rule the server applies.
 */
define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'ui/form/element/input',
            customChoice: 'custom',
            aspectRatioChoice: '',
            listens: {
                aspectRatioChoice: 'onChoiceChange'
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.onChoiceChange(this.aspectRatioChoice);

            return this;
        },

        /**
         * Show and require the field for the custom choice only
         *
         * @param {String} choice
         * @returns {void}
         */
        onChoiceChange: function (choice) {
            var custom = choice === this.customChoice;

            this.visible(custom);
            this.validation['required-entry'] = custom;
            this.required(custom);
            if (!custom) {
                this.error('');
            }
        }
    });
});
