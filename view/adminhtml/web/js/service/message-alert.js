/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Shows messages in the admin alert modal as plain text: every line is escaped, so text that came from the server
 * or from a file name can never be rendered as markup.
 */
define([
    'underscore',
    'Magento_Ui/js/modal/alert'
], function (_, alert) {
    'use strict';

    /**
     * Show an alert with one paragraph per line
     *
     * @param {String} title Already translated
     * @param {Array<String>} lines Plain text
     * @param {Function} [onClose] Called when the alert closes, however it is closed
     * @returns {void}
     */
    return function (title, lines, onClose) {
        alert({
            title: title,
            content: lines
                .map(function (line) {
                    return '<p>' + _.escape(String(line)) + '</p>';
                })
                .join(''),
            actions: {
                always: onClose || function () {}
            }
        });
    };
});
