/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Registers the `validate-hbs-*` rules the banner and slider forms use on the admin form validator.
 *
 * An empty field passes every rule (`required-entry` decides whether it may be empty); anything else is trimmed and
 * checked by the matching value rule.
 */
define([
    'mage/translate',
    'Hryvinskyi_BannerSliderAdminUi/js/validation/value-rules'
], function ($t, valueRules) {
    'use strict';

    /**
     * A validator handler that lets empty values pass and checks the trimmed text of the rest
     *
     * @param {function(String): Boolean} predicate
     * @returns {function(*): Boolean}
     */
    function optional(predicate) {
        return function (value) {
            var text = value === undefined || value === null ? '' : String(value).trim();

            return text === '' || predicate(text);
        };
    }

    return function (validator) {
        validator.addRule(
            'validate-hbs-link-url',
            optional(valueRules.isLinkUrl),
            $t('Enter an http, https, mailto or tel URL, or a relative URL such as /sale.html.')
        );
        validator.addRule(
            'validate-hbs-identifier',
            optional(valueRules.isIdentifier),
            $t('Use up to 50 lowercase letters, digits, "_" or "-", starting with a letter or digit.')
        );
        validator.addRule(
            'validate-hbs-aspect-ratio',
            optional(valueRules.isAspectRatio),
            $t('Enter the aspect ratio as width:height in whole numbers from 1 to 100, for example 3:2.')
        );
        validator.addRule(
            'validate-hbs-location',
            optional(valueRules.isLocation),
            $t('Use only letters, digits, "_" or "-" (up to 255 characters).')
        );
        validator.addRule(
            'validate-hbs-css-length',
            optional(valueRules.isCssLength),
            $t('Enter a CSS length such as 16px, 1.5rem, 1em or 2%.')
        );

        return validator;
    };
});
