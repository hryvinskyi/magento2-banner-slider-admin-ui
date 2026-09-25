/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Which image formats the browser can produce, and in which format a crop's fallback image is made.
 *
 * - The registry maps a format code to an encoder `{available(): Promise<Boolean>, encode(canvas, quality):
 *   Promise<Blob>}`. A code without an encoder resolves to an explicit "unsupported" encoder: it is never available
 *   and its `encode()` rejects with an error whose `formatCode` names the format, so an unknown format fails loudly
 *   instead of producing nothing.
 * - The fallback (original-format) image of a crop is JPEG when the source image is JPEG and PNG for every other
 *   source (PNG, GIF, WebP, AVIF, unknown), so transparency survives. The server applies the same rule and ignores a
 *   browser image in any other format.
 * - A requested extra format equal to the fallback format is not encoded a second time.
 */
define([], function () {
    'use strict';

    var PHOTO_FORMAT = 'jpeg',
        FALLBACK_FORMAT = 'png',
        MIME_FORMATS = {
            'image/jpeg': 'jpeg',
            'image/pjpeg': 'jpeg',
            'image/png': 'png',
            'image/gif': 'gif',
            'image/webp': 'webp',
            'image/avif': 'avif'
        },
        EXTENSION_FORMATS = {
            jpg: 'jpeg',
            jpeg: 'jpeg',
            jpe: 'jpeg',
            png: 'png',
            gif: 'gif',
            webp: 'webp',
            avif: 'avif'
        };

    /**
     * Whether an object has its own property of the given name
     *
     * @param {Object} object
     * @param {String} key
     * @returns {Boolean}
     */
    function owns(object, key) {
        return Object.prototype.hasOwnProperty.call(object, key);
    }

    /**
     * The format code of a source image, from its MIME type or else its file extension; null when unknown
     *
     * @param {String|null} mimeType
     * @param {String|null} path
     * @returns {String|null}
     */
    function sourceFormat(mimeType, path) {
        var mime = String(mimeType || '').toLowerCase().split(';')[0].trim(),
            name = String(path || '').split(/[?#]/)[0],
            dot = name.lastIndexOf('.'),
            extension = dot === -1 ? '' : name.slice(dot + 1).toLowerCase();

        if (owns(MIME_FORMATS, mime)) {
            return MIME_FORMATS[mime];
        }

        return owns(EXTENSION_FORMATS, extension) ? EXTENSION_FORMATS[extension] : null;
    }

    /**
     * The format of a crop's fallback image for a source of the given format
     *
     * @param {String|null} sourceFormatCode
     * @returns {String}
     */
    function originalFormatFor(sourceFormatCode) {
        return sourceFormatCode === PHOTO_FORMAT ? PHOTO_FORMAT : FALLBACK_FORMAT;
    }

    /**
     * The extra formats to encode: the requested ones without the fallback format and without repeats
     *
     * @param {Array<{format: String, quality: Number}>} requests
     * @param {String} originalCode
     * @returns {Array<{format: String, quality: Number}>}
     */
    function variantsToEncode(requests, originalCode) {
        var seen = {};

        return requests.filter(function (request) {
            if (request.format === originalCode || owns(seen, request.format)) {
                return false;
            }
            seen[request.format] = true;

            return true;
        });
    }

    /**
     * The encoder of a format no encoder is registered for
     *
     * @param {String} code
     * @returns {{supported: Boolean, available: Function, encode: Function}}
     */
    function unsupported(code) {
        return {
            supported: false,

            /**
             * Never available
             *
             * @returns {Promise<Boolean>}
             */
            available: function () {
                return Promise.resolve(false);
            },

            /**
             * Always fails, naming the format
             *
             * @returns {Promise}
             */
            encode: function () {
                var error = new Error('No browser encoder is registered for the image format "' + code + '".');

                error.name = 'UnsupportedFormatError';
                error.formatCode = code;

                return Promise.reject(error);
            }
        };
    }

    /**
     * A registry of encoders by format code
     *
     * @param {Object<String, {available: Function, encode: Function}>} encoders
     * @returns {{has: Function, get: Function, isAvailable: Function, availability: Function}}
     * @throws {TypeError} When an encoder lacks `available()` or `encode()`
     */
    function createRegistry(encoders) {
        var byCode = {};

        Object.keys(encoders || {}).forEach(function (code) {
            var encoder = encoders[code];

            if (!encoder || typeof encoder.available !== 'function' || typeof encoder.encode !== 'function') {
                throw new TypeError('The encoder of the image format "' + code + '" needs available() and encode().');
            }
            byCode[code] = encoder;
        });

        /**
         * The encoder of a format; the "unsupported" encoder when none is registered
         *
         * @param {String} code
         * @returns {{available: Function, encode: Function}}
         */
        function get(code) {
            return owns(byCode, code) ? byCode[code] : unsupported(code);
        }

        /**
         * Whether this browser can encode a format; a failing check counts as "no"
         *
         * @param {String} code
         * @returns {Promise<Boolean>}
         */
        function isAvailable(code) {
            return Promise.resolve()
                .then(function () {
                    return get(code).available();
                })
                .then(Boolean, function () {
                    return false;
                });
        }

        return {
            /**
             * Whether an encoder is registered for a format
             *
             * @param {String} code
             * @returns {Boolean}
             */
            has: function (code) {
                return owns(byCode, code);
            },

            get: get,

            isAvailable: isAvailable,

            /**
             * Which of the given formats this browser can encode
             *
             * @param {Array<String>} codes
             * @returns {Promise<Object<String, Boolean>>}
             */
            availability: function (codes) {
                return Promise.all(codes.map(isAvailable)).then(function (answers) {
                    var map = {};

                    codes.forEach(function (code, index) {
                        map[code] = answers[index];
                    });

                    return map;
                });
            }
        };
    }

    return {
        sourceFormat: sourceFormat,
        originalFormatFor: originalFormatFor,
        variantsToEncode: variantsToEncode,
        createRegistry: createRegistry
    };
});
