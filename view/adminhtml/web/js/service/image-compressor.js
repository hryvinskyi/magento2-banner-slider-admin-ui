/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 */

define([
    'jquery',
    'mage/translate',
    'module'
], function ($, $t, module) {
    'use strict';

    var codecsLoaded = false;
    var webpModule = null;
    var avifModule = null;
    var loadingPromise = null;

    /**
     * Get base URL for module static files
     *
     * @returns {String}
     */
    function getModuleBaseUrl() {
        var moduleUri = module.uri || '';
        // Remove 'service/image-compressor.js' from path to get base js folder
        return moduleUri.replace(/service\/image-compressor\.js.*$/, '');
    }

    /**
     * Browser-based image compression service using jSquash WebAssembly codecs
     * @see https://github.com/jamsinclair/jSquash
     */
    return {
        /**
         * Get WebP encoder URL (local)
         *
         * @returns {String}
         */
        getWebpUrl: function () {
            return getModuleBaseUrl() + 'lib/jsquash/webp/encode.js';
        },

        /**
         * Get AVIF encoder URL (local)
         *
         * @returns {String}
         */
        getAvifUrl: function () {
            return getModuleBaseUrl() + 'lib/jsquash/avif/encode.js';
        },

        /**
         * Check if WebAssembly is supported
         *
         * @returns {Boolean}
         */
        isWasmSupported: function () {
            return typeof WebAssembly === 'object' &&
                typeof WebAssembly.instantiate === 'function';
        },

        /**
         * Load jSquash codecs dynamically
         *
         * @param {Function} progressCallback
         * @returns {Promise<Object>}
         */
        loadCodecs: function (progressCallback) {
            var self = this;

            if (codecsLoaded && webpModule && avifModule) {
                return Promise.resolve({webp: webpModule, avif: avifModule});
            }

            if (loadingPromise) {
                return loadingPromise;
            }

            if (!this.isWasmSupported()) {
                return Promise.reject(new Error($t('WebAssembly is not supported in this browser.')));
            }

            if (typeof progressCallback === 'function') {
                progressCallback(0, $t('Loading compression codecs...'));
            }

            loadingPromise = import(self.getWebpUrl())
                .then(function (webp) {
                    webpModule = webp;

                    if (typeof progressCallback === 'function') {
                        progressCallback(50, $t('Loading AVIF encoder...'));
                    }

                    return import(self.getAvifUrl());
                })
                .then(function (avif) {
                    avifModule = avif;
                    codecsLoaded = true;

                    if (typeof progressCallback === 'function') {
                        progressCallback(100, $t('Codecs ready'));
                    }

                    return {webp: webpModule, avif: avifModule};
                })
                .catch(function (error) {
                    loadingPromise = null;
                    throw new Error($t('Failed to load compression codecs: ') + error.message);
                });

            return loadingPromise;
        },

        /**
         * Crop and resize canvas to target dimensions
         *
         * @param {HTMLCanvasElement} sourceCanvas
         * @param {Number} targetWidth
         * @param {Number} targetHeight
         * @returns {HTMLCanvasElement}
         */
        cropAndResize: function (sourceCanvas, targetWidth, targetHeight) {
            var outputCanvas = document.createElement('canvas');
            var ctx = outputCanvas.getContext('2d');

            outputCanvas.width = targetWidth;
            outputCanvas.height = targetHeight;

            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';

            ctx.drawImage(
                sourceCanvas,
                0, 0, sourceCanvas.width, sourceCanvas.height,
                0, 0, targetWidth, targetHeight
            );

            return outputCanvas;
        },

        /**
         * Get ImageData from canvas
         *
         * @param {HTMLCanvasElement} canvas
         * @returns {ImageData}
         */
        getImageData: function (canvas) {
            var ctx = canvas.getContext('2d');
            return ctx.getImageData(0, 0, canvas.width, canvas.height);
        },

        /**
         * Compress canvas to WebP format using jSquash
         *
         * @param {HTMLCanvasElement} canvas
         * @param {Number} quality (1-100)
         * @param {Function} progressCallback
         * @returns {Promise<Object>} {blob, size, width, height}
         */
        compressToWebP: function (canvas, quality, progressCallback) {
            var self = this;

            quality = Math.max(1, Math.min(100, quality || 85));

            return this.loadCodecs(progressCallback)
                .then(function (codecs) {
                    if (typeof progressCallback === 'function') {
                        progressCallback(50, $t('Encoding WebP...'));
                    }

                    var imageData = self.getImageData(canvas);

                    // jSquash WebP encode expects quality 0-100
                    // Default export is accessed via .default when using dynamic import()
                    return codecs.webp.default(imageData, {
                        quality: quality
                    });
                })
                .then(function (encodedData) {
                    var blob = new Blob([encodedData], {type: 'image/webp'});

                    if (typeof progressCallback === 'function') {
                        progressCallback(100, $t('WebP encoding complete'));
                    }

                    return {
                        blob: blob,
                        size: blob.size,
                        width: canvas.width,
                        height: canvas.height,
                        format: 'webp',
                        mimeType: 'image/webp'
                    };
                });
        },

        /**
         * Compress canvas to AVIF format using jSquash
         *
         * @param {HTMLCanvasElement} canvas
         * @param {Number} quality (1-100)
         * @param {Function} progressCallback
         * @returns {Promise<Object>} {blob, size, width, height}
         */
        compressToAvif: function (canvas, quality, progressCallback) {
            var self = this;

            quality = Math.max(1, Math.min(100, quality || 80));

            return this.loadCodecs(progressCallback)
                .then(function (codecs) {
                    if (typeof progressCallback === 'function') {
                        progressCallback(50, $t('Encoding AVIF...'));
                    }

                    var imageData = self.getImageData(canvas);

                    // Default export is accessed via .default when using dynamic import()
                    // jSquash AVIF encoder uses quality 0-100 (higher = better quality)
                    return codecs.avif.default(imageData, {
                        quality: quality
                    });
                })
                .then(function (encodedData) {
                    var blob = new Blob([encodedData], {type: 'image/avif'});

                    if (typeof progressCallback === 'function') {
                        progressCallback(100, $t('AVIF encoding complete'));
                    }

                    return {
                        blob: blob,
                        size: blob.size,
                        width: canvas.width,
                        height: canvas.height,
                        format: 'avif',
                        mimeType: 'image/avif'
                    };
                });
        },

        /**
         * Convert canvas to original format (PNG or JPEG)
         *
         * @param {HTMLCanvasElement} canvas
         * @param {String} originalMimeType
         * @param {Number} quality (for JPEG)
         * @returns {Promise<Object>} {blob, size, width, height}
         */
        compressToOriginal: function (canvas, originalMimeType, quality) {
            return new Promise(function (resolve, reject) {
                var mimeType = originalMimeType || 'image/jpeg';
                var jpegQuality = quality ? quality / 100 : 0.92;

                canvas.toBlob(function (blob) {
                    if (!blob) {
                        reject(new Error($t('Failed to create image blob')));
                        return;
                    }

                    resolve({
                        blob: blob,
                        size: blob.size,
                        width: canvas.width,
                        height: canvas.height,
                        format: mimeType === 'image/png' ? 'png' : 'jpeg',
                        mimeType: mimeType
                    });
                }, mimeType, mimeType === 'image/jpeg' ? jpegQuality : undefined);
            });
        },

        /**
         * Compress all formats (original, WebP, AVIF)
         *
         * @param {HTMLCanvasElement} canvas
         * @param {Object} options
         * @param {Function} progressCallback
         * @returns {Promise<Object>}
         */
        compressAllFormats: function (canvas, options, progressCallback) {
            var self = this;
            var results = {};
            var totalSteps = 3;
            var completedSteps = 0;

            options = options || {};
            var webpQuality = options.webpQuality || 85;
            var avifQuality = options.avifQuality || 80;
            var generateWebp = options.generateWebp !== false;
            var generateAvif = options.generateAvif === true;
            var originalMimeType = options.originalMimeType || 'image/jpeg';

            var updateProgress = function (stepProgress, message) {
                if (typeof progressCallback === 'function') {
                    var overallProgress = Math.round(
                        ((completedSteps * 100) + stepProgress) / totalSteps
                    );
                    progressCallback(overallProgress, message);
                }
            };

            updateProgress(0, $t('Processing original image...'));

            return this.compressToOriginal(canvas, originalMimeType)
                .then(function (originalResult) {
                    results.original = originalResult;
                    completedSteps++;

                    if (!generateWebp) {
                        results.webp = null;
                        completedSteps++;
                        return Promise.resolve(null);
                    }

                    updateProgress(0, $t('Compressing to WebP...'));

                    return self.compressToWebP(canvas, webpQuality, function (p, m) {
                        updateProgress(p, m);
                    });
                })
                .then(function (webpResult) {
                    if (webpResult) {
                        results.webp = webpResult;
                        completedSteps++;
                    }

                    if (!generateAvif) {
                        results.avif = null;
                        completedSteps++;
                        return Promise.resolve(null);
                    }

                    updateProgress(0, $t('Compressing to AVIF...'));

                    return self.compressToAvif(canvas, avifQuality, function (p, m) {
                        updateProgress(p, m);
                    });
                })
                .then(function (avifResult) {
                    if (avifResult) {
                        results.avif = avifResult;
                    }

                    updateProgress(100, $t('Compression complete'));

                    return results;
                });
        },

        /**
         * Create a preview URL from compressed result
         *
         * @param {Object} compressedResult
         * @returns {String} Object URL
         */
        createPreviewUrl: function (compressedResult) {
            if (!compressedResult || !compressedResult.blob) {
                return null;
            }

            return URL.createObjectURL(compressedResult.blob);
        },

        /**
         * Revoke preview URL to free memory
         *
         * @param {String} url
         */
        revokePreviewUrl: function (url) {
            if (url && url.startsWith('blob:')) {
                URL.revokeObjectURL(url);
            }
        },

        /**
         * Format file size in human-readable format
         *
         * @param {Number} bytes
         * @returns {String}
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
         * Calculate savings percentage
         *
         * @param {Number} originalSize
         * @param {Number} compressedSize
         * @returns {String}
         */
        calculateSavings: function (originalSize, compressedSize) {
            if (!originalSize || originalSize === 0 || !compressedSize) {
                return '0%';
            }

            var savings = ((originalSize - compressedSize) / originalSize) * 100;

            return savings.toFixed(1) + '%';
        }
    };
});
