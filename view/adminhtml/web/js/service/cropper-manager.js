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
        var lastCropData = null;

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
                    viewMode: 0,
                    autoCrop: true,
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
                            // Get current crop data (from autoCropArea)
                            var currentData = cropper.getData();

                            // Check if saved size differs from current
                            var sizeDiffers = Math.abs(currentData.width - restoreData.width) > 1 ||
                                              Math.abs(currentData.height - restoreData.height) > 1;

                            if (sizeDiffers) {
                                // Size is different, set full data
                                cropper.setData(restoreData);
                            } else {
                                // Size is same (100% crop), only set position
                                cropper.setData({
                                    x: restoreData.x,
                                    y: restoreData.y
                                });
                            }
                        }

                        if (callbacks && typeof callbacks.onReady === 'function') {
                            callbacks.onReady();
                        }
                    },
                    crop: function (event) {
                        // Track crop data during drag
                        lastCropData = event.detail;

                        if (callbacks && typeof callbacks.onCrop === 'function') {
                            callbacks.onCrop(event.detail);
                        }
                    },
                    cropend: function () {
                        if (callbacks && typeof callbacks.onCropEnd === 'function' && lastCropData) {
                            // Clamp crop data to image bounds
                            var imageData = cropper.getImageData();
                            var clampedData = {
                                x: Math.max(0, Math.min(lastCropData.x, imageData.naturalWidth - lastCropData.width)),
                                y: Math.max(0, Math.min(lastCropData.y, imageData.naturalHeight - lastCropData.height)),
                                width: Math.min(lastCropData.width, imageData.naturalWidth),
                                height: Math.min(lastCropData.height, imageData.naturalHeight)
                            };

                            // Update cropper to show clamped position
                            cropper.setData(clampedData);

                            callbacks.onCropEnd(clampedData);
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
