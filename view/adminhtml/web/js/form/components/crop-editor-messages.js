/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The crop editor's messages to the admin, worded here and shown as plain text in the admin alert.
 *
 * - Crop notices (see crop-submission and crop-payload) become one line per notice, naming the breakpoint and format.
 * - A failed request or step is logged to the console once and explained in words by its kind (see http-client).
 */
define([
    'mage/translate',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-submission',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-payload',
    'Hryvinskyi_BannerSliderAdminUi/js/service/message-alert'
], function ($t, cropSubmission, payload, messageAlert) {
    'use strict';

    /**
     * Show lines in the editor's alert
     *
     * @param {Array<String>} lines Plain text
     * @param {Function} [onClose]
     * @returns {void}
     */
    function show(lines, onClose) {
        messageAlert($t('Responsive Images'), lines, onClose);
    }

    /**
     * The wording of every crop notice kind; `%1` is the breakpoint name, `%2` the format
     *
     * @returns {Object<String, String>}
     */
    function noticeTexts() {
        var texts = {};

        texts[cropSubmission.SOURCE_NOT_LOADED] =
            $t('%1: the source image could not be loaded here, so the server will generate the crop.');
        texts[cropSubmission.NOT_ENCODED] =
            $t('%1: the %2 image could not be encoded here, so the server will generate it.');
        texts[cropSubmission.NO_AREA] = $t('%1: choose a crop area before saving; this crop was not changed.');
        texts[payload.REASON_TOO_LARGE] =
            $t('%1: the %2 image is larger than the upload limit, so the server will generate it.');
        texts[payload.REASON_POST_LIMIT] =
            $t('%1: the %2 image would make the form too large to send, so the server will generate it.');

        return texts;
    }

    return {
        show: show,

        /**
         * Show crop notices, then continue; continue at once when there are none
         *
         * @param {Array<{kind: String, breakpointId: Number, format: (String|null)}>} notices
         * @param {function(Number): String} nameOf Breakpoint name by id
         * @param {Function} [next]
         * @returns {void}
         */
        notices: function (notices, nameOf, next) {
            var texts = noticeTexts(),
                lines = notices.map(function (notice) {
                    return texts[notice.kind]
                        .replace('%1', nameOf(notice.breakpointId))
                        .replace('%2', String(notice.format || '').toUpperCase());
                });

            if (lines.length) {
                show(lines, next);
            } else if (next) {
                next();
            }
        },

        /**
         * Report a failed request or step
         *
         * @param {String} message What failed, already translated
         * @param {Error} error
         * @returns {void}
         */
        failure: function (message, error) {
            var reasons = {
                network: $t('The server could not be reached. Check the connection and try again.'),
                http: $t('The server answered with an error (HTTP %1).').replace('%1', error && error.status),
                invalid: $t('The server answer could not be read. Reload the page and try again.'),
                expired: $t('Your admin session has expired. Reload the page and sign in again.')
            };

            console.error(message, error);
            show([message, reasons[error && error.kind] || (error && error.message) || '']);
        }
    };
});
