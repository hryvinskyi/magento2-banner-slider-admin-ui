/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The value rules the banner and slider forms check in the browser, written to match what the server accepts.
 *
 * Every predicate takes a trimmed, non-empty string; whether a field may be empty is the form's decision.
 * - Link URL: an `http:`, `https:`, `mailto:` or `tel:` URL, or a relative one (no scheme); no control characters.
 * - Breakpoint identifier: `^[a-z0-9][a-z0-9_-]{0,49}$`.
 * - Aspect ratio: `W:H` in whole numbers from 1 to 100, spaces around the terms allowed.
 * - Slider location code: letters, digits, `_` and `-`, up to 255.
 * - CSS length: a non-negative number with `px`, `rem`, `em` or `%`.
 */
define([], function () {
    'use strict';

    var LINK_SCHEMES = ['http', 'https', 'mailto', 'tel'],
        SCHEME = /^([A-Za-z][A-Za-z0-9+.-]*):/,
        CONTROL_CHARACTERS = /[\u0000-\u001F\u007F]/,
        IDENTIFIER = /^[a-z0-9][a-z0-9_-]{0,49}$/,
        ASPECT_RATIO = /^\s*(\d{1,9})\s*:\s*(\d{1,9})\s*$/,
        ASPECT_TERM_MAX = 100,
        LOCATION = /^[A-Za-z0-9_-]{1,255}$/,
        CSS_LENGTH = /^\d+(\.\d+)?(px|rem|em|%)$/;

    /**
     * Whether a link URL may be saved
     *
     * @param {String} value
     * @returns {Boolean}
     */
    function isLinkUrl(value) {
        var scheme = SCHEME.exec(value);

        return !CONTROL_CHARACTERS.test(value)
            && (scheme === null || LINK_SCHEMES.indexOf(scheme[1].toLowerCase()) !== -1);
    }

    /**
     * The terms of a `W:H` aspect ratio, or null when the text is not one or a term is outside 1..100
     *
     * @param {String} value
     * @returns {{width: Number, height: Number}|null}
     */
    function parseAspectRatio(value) {
        var match = ASPECT_RATIO.exec(String(value)),
            width = match ? Number(match[1]) : 0,
            height = match ? Number(match[2]) : 0;

        return width >= 1 && width <= ASPECT_TERM_MAX && height >= 1 && height <= ASPECT_TERM_MAX
            ? {width: width, height: height}
            : null;
    }

    return {
        isLinkUrl: isLinkUrl,

        /**
         * @param {String} value
         * @returns {Boolean}
         */
        isIdentifier: function (value) {
            return IDENTIFIER.test(value);
        },

        parseAspectRatio: parseAspectRatio,

        /**
         * @param {String} value
         * @returns {Boolean}
         */
        isAspectRatio: function (value) {
            return parseAspectRatio(value) !== null;
        },

        /**
         * @param {String} value
         * @returns {Boolean}
         */
        isLocation: function (value) {
            return LOCATION.test(value);
        },

        /**
         * @param {String} value
         * @returns {Boolean}
         */
        isCssLength: function (value) {
            return CSS_LENGTH.test(value);
        }
    };
});
