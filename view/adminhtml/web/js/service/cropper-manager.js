/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'cropperjs',
    'cropperConfig'
], function (Cropper, config) {
    'use strict';

    /**
     * Factory function to create cropper manager instance
     *
     * @param {Object} callbacks - {onCrop, onCropEnd, onReady}
     * @returns {Object}
     */
    return function (callbacks) {
        var cropper = null;

        return {
            /**
             * Initialize cropper on image element
             *
             * @param {HTMLElement} imageElement
             * @param {Object} breakpoint - {target_width, target_height}
             * @param {Object|null} savedCropData - Previous crop coordinates
             */
            init: function (imageElement, breakpoint, savedCropData) {
                if (cropper) {
                    this.destroy();
                }

                // Constrain image dimensions before Cropper.js reads them
                imageElement.style.maxWidth = config.CROPPER_MAX_WIDTH + 'px';
                imageElement.style.maxHeight = config.CROPPER_MAX_HEIGHT + 'px';
                imageElement.style.width = 'auto';
                imageElement.style.height = 'auto';

                var aspectRatio = (breakpoint.target_height && breakpoint.target_height > 0)
                    ? breakpoint.target_width / breakpoint.target_height
                    : NaN;

                var hasSavedCrop = savedCropData && savedCropData.crop_width > 0;

                // Copy the values before cropper modifies them
                var restoreData = hasSavedCrop ? {
                    x: savedCropData.crop_x,
                    y: savedCropData.crop_y,
                    width: savedCropData.crop_width,
                    height: savedCropData.crop_height
                } : null;

                cropper = new Cropper(imageElement, {
                    aspectRatio: aspectRatio,
                    viewMode: 1,
                    autoCrop: !hasSavedCrop,
                    autoCropArea: 1,
                    responsive: true,
                    guides: true,
                    center: true,
                    highlight: true,
                    background: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    zoomable: false,
                    zoomOnTouch: false,
                    zoomOnWheel: false,
                    ready: function () {
                        if (restoreData) {
                            cropper.crop();
                            cropper.setData(restoreData);
                        }

                        if (callbacks && typeof callbacks.onReady === 'function') {
                            callbacks.onReady();
                        }
                    },
                    crop: function (event) {
                        if (callbacks && typeof callbacks.onCrop === 'function') {
                            callbacks.onCrop(event.detail);
                        }
                    },
                    cropend: function () {
                        if (callbacks && typeof callbacks.onCropEnd === 'function') {
                            callbacks.onCropEnd();
                        }
                    }
                });
            },

            /**
             * Destroy cropper instance
             */
            destroy: function () {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            },

            /**
             * Check if cropper is initialized
             *
             * @returns {boolean}
             */
            isInitialized: function () {
                return cropper !== null;
            },

            /**
             * Get cropped canvas
             *
             * @param {number} width
             * @param {number} height
             * @returns {HTMLCanvasElement|null}
             */
            getCroppedCanvas: function (width, height) {
                if (!cropper) {
                    return null;
                }

                return cropper.getCroppedCanvas({
                    width: width,
                    height: height,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });
            },

            /**
             * Get current crop data
             *
             * @returns {Object}
             */
            getData: function () {
                if (!cropper) {
                    return {};
                }

                return cropper.getData();
            },

            /**
             * Set crop data
             *
             * @param {Object} data
             */
            setData: function (data) {
                if (cropper && data) {
                    cropper.setData(data);
                }
            },

            /**
             * Get the underlying Cropper instance
             *
             * @returns {Cropper|null}
             */
            getInstance: function () {
                return cropper;
            }
        };
    };
});
