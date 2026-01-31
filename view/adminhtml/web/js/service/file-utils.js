/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'mage/translate',
    'cropperConfig'
], function ($t, config) {
    'use strict';

    return {
        /**
         * Format bytes to human-readable string
         *
         * @param {number} bytes
         * @returns {string}
         */
        formatFileSize: function (bytes) {
            if (!bytes || bytes === 0) {
                return '0 B';
            }

            var units = ['B', 'KB', 'MB', 'GB'];
            var i = 0;

            while (bytes >= 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }

            return bytes.toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
        },

        /**
         * Calculate savings percentage between original and optimized size
         *
         * @param {number} originalSize
         * @param {number} optimizedSize
         * @returns {string}
         */
        calculateSavings: function (originalSize, optimizedSize) {
            if (!originalSize || originalSize === 0 || !optimizedSize) {
                return '0%';
            }

            var savings = ((originalSize - optimizedSize) / originalSize) * 100;

            if (savings >= 0) {
                return '-' + savings.toFixed(1) + '%';
            }

            return '+' + Math.abs(savings).toFixed(1) + '%';
        },

        /**
         * Fetch file size via HEAD request with blob fallback
         *
         * @param {string} url
         * @returns {Promise<number>}
         */
        fetchFileSize: function (url) {
            return new Promise(function (resolve) {
                if (!url) {
                    resolve(0);
                    return;
                }

                fetch(url, {method: 'HEAD', cache: 'no-store'})
                    .then(function (response) {
                        var size = parseInt(response.headers.get('Content-Length'), 10);

                        if (size && size > 0) {
                            return size;
                        }

                        // Fallback: fetch the actual blob to get size
                        return fetch(url, {cache: 'no-store'})
                            .then(function (res) {
                                return res.blob();
                            })
                            .then(function (blob) {
                                return blob.size;
                            });
                    })
                    .then(function (size) {
                        resolve(size || 0);
                    })
                    .catch(function () {
                        resolve(0);
                    });
            });
        },

        /**
         * Validate file type and size
         *
         * @param {File} file
         * @returns {{valid: boolean, error: string|null}}
         */
        validateImageFile: function (file) {
            if (!file) {
                return {valid: false, error: $t('No file provided.')};
            }

            if (config.ALLOWED_IMAGE_TYPES.indexOf(file.type) === -1) {
                return {
                    valid: false,
                    error: $t('Invalid file type. Allowed types: JPG, PNG, GIF, WebP')
                };
            }

            if (file.size > config.MAX_FILE_SIZE) {
                return {
                    valid: false,
                    error: $t('File is too large. Maximum size is 10MB.')
                };
            }

            return {valid: true, error: null};
        },

        /**
         * Add cache buster to URL
         *
         * @param {string} url
         * @returns {string}
         */
        addCacheBuster: function (url) {
            if (!url) {
                return url;
            }

            var separator = url.indexOf('?') === -1 ? '?' : '&';

            return url + separator + 'v=' + Date.now();
        }
    };
});
