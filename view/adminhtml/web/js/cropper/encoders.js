/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The image encoders this browser offers, as a format-support registry, and the base64 form the post needs.
 *
 * - `jpeg` and `png` come from the canvas itself (`toBlob`); JPEG takes the quality when one is given.
 * - `webp` and `avif` come from the bundled WebAssembly codecs in `js/lib/jsquash/`, loaded on first use as ES
 *   modules from next to this file. They are available wherever WebAssembly is; a codec that fails to load makes
 *   that encoding fail, which the caller reports.
 * Any other format code gets the registry's "unsupported" encoder.
 */
define([
    'module',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/format-support'
], function (module, formatSupport) {
    'use strict';

    var codecBaseUrl = String(module.uri || '').replace(/cropper\/encoders(\.min)?\.js.*$/, 'lib/jsquash/');

    /**
     * An encoder that uses the canvas's own `toBlob()`
     *
     * @param {String} mimeType
     * @param {Boolean} takesQuality
     * @returns {{available: Function, encode: Function}}
     */
    function canvasEncoder(mimeType, takesQuality) {
        return {
            /**
             * @returns {Promise<Boolean>}
             */
            available: function () {
                return Promise.resolve(
                    typeof HTMLCanvasElement !== 'undefined' && typeof HTMLCanvasElement.prototype.toBlob === 'function'
                );
            },

            /**
             * @param {HTMLCanvasElement} canvas
             * @param {Number|null} quality 1..100, or null for the browser default
             * @returns {Promise<Blob>}
             */
            encode: function (canvas, quality) {
                return new Promise(function (resolve, reject) {
                    canvas.toBlob(function (blob) {
                        if (blob && blob.type === mimeType) {
                            resolve(blob);

                            return;
                        }
                        reject(new Error('The browser could not encode the image as ' + mimeType + '.'));
                    }, mimeType, takesQuality && quality ? quality / 100 : undefined);
                });
            }
        };
    }

    /**
     * An encoder backed by a bundled WebAssembly codec
     *
     * @param {String} modulePath Codec entry module, relative to the codec folder
     * @param {String} mimeType
     * @returns {{available: Function, encode: Function}}
     */
    function wasmEncoder(modulePath, mimeType) {
        var loading = null;

        /**
         * The codec module, loaded once; a failed load is retried on the next call
         *
         * @returns {Promise<Object>}
         */
        function codec() {
            if (loading === null) {
                loading = import(codecBaseUrl + modulePath).catch(function (error) {
                    loading = null;
                    throw error;
                });
            }

            return loading;
        }

        return {
            /**
             * @returns {Promise<Boolean>}
             */
            available: function () {
                return Promise.resolve(
                    typeof WebAssembly === 'object' && typeof WebAssembly.instantiate === 'function'
                );
            },

            /**
             * @param {HTMLCanvasElement} canvas
             * @param {Number} quality 1..100
             * @returns {Promise<Blob>}
             */
            encode: function (canvas, quality) {
                return codec().then(function (loaded) {
                    var pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);

                    return loaded.default(pixels, {quality: quality});
                }).then(function (buffer) {
                    return new Blob([buffer], {type: mimeType});
                });
            }
        };
    }

    return {
        registry: formatSupport.createRegistry({
            jpeg: canvasEncoder('image/jpeg', true),
            png: canvasEncoder('image/png', false),
            webp: wasmEncoder('webp/encode.js', 'image/webp'),
            avif: wasmEncoder('avif/encode.js', 'image/avif')
        }),

        /**
         * The plain base64 (no `data:` prefix) of a blob
         *
         * @param {Blob} blob
         * @returns {Promise<String>}
         */
        toBase64: function (blob) {
            return new Promise(function (resolve, reject) {
                var reader = new FileReader();

                reader.onload = function () {
                    var result = String(reader.result),
                        comma = result.indexOf(',');

                    resolve(comma === -1 ? '' : result.slice(comma + 1));
                };
                reader.onerror = function () {
                    reject(reader.error || new Error('The encoded image could not be read.'));
                };
                reader.readAsDataURL(blob);
            });
        }
    };
});
