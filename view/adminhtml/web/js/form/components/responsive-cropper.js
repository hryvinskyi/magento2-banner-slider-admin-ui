/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'jquery',
    'uiComponent',
    'uiRegistry',
    'ko',
    'mage/translate',
    'imageCompressor',
    'cropperConfig',
    'cropAjaxService',
    'cropperManager',
    'fileUtils'
], function ($, Component, registry, ko, $t, imageCompressor, config, ajaxService, CropperManager, fileUtils) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_BannerSliderAdminUi/form/responsive-cropper',
            breakpoints: [],
            bannerId: null,
            sliderId: null,
            crops: {},
            activeBreakpoint: null,
            cropperManager: null,
            cropperInitialized: false,
            isLoading: false,
            isSaving: false,
            isUploading: false,
            saveUrl: '',
            generateUrl: '',
            uploadCompressedUrl: '',
            uploadBreakpointImageUrl: '',
            webpSupported: true,
            avifSupported: true,
            useBrowserCompression: true,
            saveTimeout: null,
            desktopImageField: 'hryvinskyi_banner_slider_banner_form.hryvinskyi_banner_slider_banner_form.image_settings.image',
            breakpointImages: {},
            imports: {
                sliderIdValue: '${ $.provider }:data.slider_id',
                bannerIdValue: '${ $.provider }:data.banner_id',
                responsiveCropperData: '${ $.provider }:data.responsive_cropper'
            },
            listens: {
                sliderIdValue: 'onSliderChange',
                responsiveCropperData: 'onResponsiveCropperDataChange'
            }
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.initCropperManager();
            this.subscribeToDesktopImage();
            this.checkBrowserCompressionSupport();
            return this;
        },

        /**
         * Initialize the cropper manager instance
         */
        initCropperManager: function () {
            var self = this;

            this.cropperManager = CropperManager({
                onReady: function () {
                    self.cropperInitialized(true);
                },
                onCrop: function (detail) {
                    var breakpoint = self.activeBreakpoint();
                    if (breakpoint) {
                        self.updateCropValues(breakpoint.breakpoint_id, detail);
                    }
                },
                onCropEnd: function () {
                    self.autoSave();
                }
            });
        },

        /**
         * Initialize observables
         */
        initObservable: function () {
            this._super();

            this.observe(['breakpoints', 'cropperInitialized', 'bannerId', 'sliderId']);

            this.activeBreakpoint = ko.observable(null);
            this.isLoading = ko.observable(false);
            this.isSaving = ko.observable(false);
            this.isUploading = ko.observable(false);
            this.crops = ko.observable({});
            this.desktopImageUrl = ko.observable(null);
            this.breakpointImages = ko.observable({});
            this.comparisonMode = ko.observable('webp');
            this.showComparison = ko.observable(false);
            this.compressionProgress = ko.observable(0);
            this.compressionMessage = ko.observable('');
            this.isCompressing = ko.observable(false);
            this.wasmNotSupported = ko.observable(false);
            this.previewUrls = ko.observable({});
            this.savedCropsState = {};
            this.autoSaveHint = $t('Saves crop position and quality settings only. Images are generated when you save the banner.');

            this.subscribeToComparisonToggle();

            return this;
        },

        // ==================== HELPER METHODS ====================

        /**
         * Fields that trigger image regeneration when changed
         */
        imageRelatedFields: [
            'crop_x', 'crop_y', 'crop_width', 'crop_height',
            'webp_quality', 'avif_quality',
            'generate_webp', 'generate_avif',
            'source_image', 'custom_source_image'
        ],

        /**
         * Store current crops state as saved state
         */
        storeSavedCropsState: function () {
            var crops = this.crops();
            this.savedCropsState = JSON.parse(JSON.stringify(crops));
        },

        /**
         * Get list of breakpoint IDs that have changed image settings
         *
         * @returns {Array}
         */
        getChangedBreakpointIds: function () {
            var self = this;
            var currentCrops = this.crops();
            var savedCrops = this.savedCropsState;
            var changedIds = [];

            Object.keys(currentCrops).forEach(function (breakpointId) {
                var current = currentCrops[breakpointId] || {};
                var saved = savedCrops[breakpointId] || {};
                var hasChanges = false;

                self.imageRelatedFields.forEach(function (field) {
                    if (hasChanges) {
                        return;
                    }

                    var currentValue = current[field];
                    var savedValue = saved[field];

                    // Normalize undefined/null to compare properly
                    if (currentValue === undefined || currentValue === null) {
                        currentValue = '';
                    }
                    if (savedValue === undefined || savedValue === null) {
                        savedValue = '';
                    }

                    if (String(currentValue) !== String(savedValue)) {
                        hasChanges = true;
                    }
                });

                if (hasChanges) {
                    changedIds.push(breakpointId);
                }
            });

            return changedIds;
        },

        /**
         * Check if WebP generation is enabled
         *
         * @param {Object} cropData
         * @returns {Boolean}
         */
        isWebpEnabled: function (cropData) {
            return cropData.generate_webp !== false
                && cropData.generate_webp !== 0
                && cropData.generate_webp !== '0';
        },

        /**
         * Check if AVIF generation is enabled
         *
         * @param {Object} cropData
         * @returns {Boolean}
         */
        isAvifEnabled: function (cropData) {
            return cropData.generate_avif === true
                || cropData.generate_avif === 1
                || cropData.generate_avif === '1';
        },

        /**
         * Update crop data with image response
         *
         * @param {Object} cropData
         * @param {Object} response
         * @param {Boolean} includeSizes
         * @returns {Object}
         */
        updateCropDataFromResponse: function (cropData, response, includeSizes) {
            var updated = Object.assign({}, cropData);

            updated.cropped_image_url = fileUtils.addCacheBuster(response.cropped);
            updated.webp_image_url = response.webp ? fileUtils.addCacheBuster(response.webp) : null;
            updated.avif_image_url = response.avif ? fileUtils.addCacheBuster(response.avif) : null;

            if (includeSizes && response.sizes) {
                updated.original_size = response.sizes.original;
                updated.webp_size = response.sizes.webp;
                updated.avif_size = response.sizes.avif;
            } else {
                updated.original_size = null;
                updated.webp_size = null;
                updated.avif_size = null;
            }

            return updated;
        },

        /**
         * Build crop save data object
         *
         * @param {Object} breakpoint
         * @param {Object} cropData
         * @param {String} sourceImage
         * @returns {Object}
         */
        buildCropSaveData: function (breakpoint, cropData, sourceImage) {
            return {
                banner_id: this.bannerId(),
                breakpoint_id: breakpoint.breakpoint_id,
                source_image: sourceImage,
                crop_x: cropData.crop_x || 0,
                crop_y: cropData.crop_y || 0,
                crop_width: cropData.crop_width || 0,
                crop_height: cropData.crop_height || 0,
                generate_webp: this.isWebpEnabled(cropData) ? 1 : 0,
                generate_avif: this.isAvifEnabled(cropData) ? 1 : 0,
                webp_quality: cropData.webp_quality || config.WEBP_QUALITY_DEFAULT,
                avif_quality: cropData.avif_quality || config.AVIF_QUALITY_DEFAULT,
                sort_order: breakpoint.sort_order || 0
            };
        },

        /**
         * Update crop data after save
         *
         * @param {Object} cropData
         * @param {Object} response
         * @param {String} sourceImage
         */
        updateCropDataAfterSave: function (cropData, response, sourceImage) {
            cropData.crop_id = response.crop_id;
            cropData.source_image = sourceImage;
            cropData.generate_webp = this.isWebpEnabled(cropData);
            cropData.generate_avif = this.isAvifEnabled(cropData);
        },

        /**
         * Generate on server as fallback
         *
         * @param {Object} cropData
         * @param {Number} breakpointId
         * @returns {Promise}
         */
        generateOnServerFallback: function (cropData, breakpointId) {
            var self = this;

            return ajaxService.generateImages(this.generateUrl, {crop_id: cropData.crop_id})
                .then(function (response) {
                    if (response.images) {
                        var updated = self.updateCropDataFromResponse(cropData, response.images, false);
                        self.setCropData(breakpointId, updated);
                    }
                })
                .catch(function () {
                    // Silent fail
                });
        },

        // ==================== FORM SAVE METHODS ====================

        /**
         * Save crop data and then execute callback
         * Only regenerates images if image-related settings changed
         *
         * @param {Function} saveCallback
         */
        saveCropDataWithCallback: function (saveCallback) {
            var self = this;

            if (!this.bannerId() || this.breakpoints().length === 0) {
                saveCallback();
                return;
            }

            var changedBreakpointIds = this.getChangedBreakpointIds();

            self.isLoading(true);

            this.saveAllBreakpoints()
                .then(function () {
                    if (changedBreakpointIds.length > 0) {
                        return self.generateBreakpointImagesByIds(changedBreakpointIds);
                    }

                    return Promise.resolve();
                })
                .then(function () {
                    self.storeSavedCropsState();
                    self.isLoading(false);
                    saveCallback();
                })
                .catch(function () {
                    self.isLoading(false);
                    saveCallback();
                });
        },

        /**
         * Save crop data and save form
         */
        generateAndSave: function () {
            this.saveCropDataWithCallback(this.saveForm.bind(this));
        },

        /**
         * Save crop data and save form with continue edit
         */
        generateAndSaveAndContinue: function () {
            this.saveCropDataWithCallback(this.saveFormAndContinue.bind(this));
        },

        /**
         * Save the form via UI component
         *
         * @param {Boolean} redirect
         */
        saveFormInternal: function (redirect) {
            var formComponentName = this.ns + '.' + this.ns;

            registry.async(formComponentName)(function (form) {
                if (form && typeof form.save === 'function') {
                    form.save(redirect);
                }
            });
        },

        saveForm: function () {
            this.saveFormInternal(true);
        },

        saveFormAndContinue: function () {
            this.saveFormInternal(false);
        },

        // ==================== CROP DATA METHODS ====================

        /**
         * Get crop data for breakpoint
         *
         * @param {Number|String} breakpointId
         * @returns {Object}
         */
        getCropData: function (breakpointId) {
            var crops = this.crops();
            var id = String(breakpointId);
            return crops[id] || crops[breakpointId] || {};
        },

        /**
         * Set crop data for breakpoint
         *
         * @param {Number|String} breakpointId
         * @param {Object} data
         * @param {Boolean} silent
         */
        setCropData: function (breakpointId, data, silent) {
            var crops = this.crops();
            crops[String(breakpointId)] = data;

            if (!silent) {
                this.crops(Object.assign({}, crops));
            }
        },

        /**
         * Save crop data for single breakpoint
         *
         * @param {Object} breakpoint
         * @param {Boolean} silent
         */
        saveCropData: function (breakpoint, silent) {
            var self = this;
            var cropData = this.getCropData(breakpoint.breakpoint_id);
            var sourceImage = this.getBreakpointSourceImageFile(breakpoint.breakpoint_id);

            if (!sourceImage) {
                if (!silent) {
                    ajaxService.showWarning($t('Please upload an image first.'));
                }
                return;
            }

            if (!this.bannerId()) {
                if (!silent) {
                    ajaxService.showWarning($t('Please save the banner first.'));
                }
                return;
            }

            var data = this.buildCropSaveData(breakpoint, cropData, sourceImage);

            self.isSaving(true);

            ajaxService.saveCropData(this.saveUrl, data)
                .then(function (response) {
                    self.isSaving(false);
                    self.updateCropDataAfterSave(cropData, response, sourceImage);
                    cropData.webp_quality = data.webp_quality;
                    cropData.avif_quality = data.avif_quality;
                    self.setCropData(breakpoint.breakpoint_id, cropData, true);
                })
                .catch(function (error) {
                    self.isSaving(false);
                    ajaxService.showError(error.message);
                });
        },

        /**
         * Save all breakpoints
         *
         * @return {Promise}
         */
        saveAllBreakpoints: function () {
            var self = this;
            var savePromises = [];

            this.breakpoints().forEach(function (breakpoint) {
                var cropData = self.getCropData(breakpoint.breakpoint_id);
                var sourceImage = self.getBreakpointSourceImageFile(breakpoint.breakpoint_id);

                if (!sourceImage && !cropData.crop_id) {
                    return;
                }

                if (!sourceImage && cropData.source_image) {
                    sourceImage = cropData.source_image;
                }

                if (!sourceImage) {
                    return;
                }

                var data = self.buildCropSaveData(breakpoint, cropData, sourceImage);

                var promise = ajaxService.saveCropData(self.saveUrl, data)
                    .then(function (response) {
                        self.updateCropDataAfterSave(cropData, response, sourceImage);
                        cropData.webp_quality = data.webp_quality;
                        cropData.avif_quality = data.avif_quality;
                        self.setCropData(breakpoint.breakpoint_id, cropData, true);
                    });

                savePromises.push(promise);
            });

            return Promise.all(savePromises);
        },

        /**
         * Auto-save crop data with debounce
         */
        autoSave: function () {
            var self = this;
            var breakpoint = this.activeBreakpoint();

            if (!breakpoint) {
                return;
            }

            if (this.saveTimeout) {
                clearTimeout(this.saveTimeout);
            }

            this.saveTimeout = setTimeout(function () {
                self.saveCropData(breakpoint, true);
            }, config.AUTO_SAVE_DELAY);
        },

        // ==================== IMAGE GENERATION METHODS ====================

        /**
         * Generate images for breakpoint
         *
         * @param {Object} breakpoint
         */
        generateImages: function (breakpoint) {
            var cropData = this.getCropData(breakpoint.breakpoint_id);

            if (!cropData || !cropData.crop_id) {
                ajaxService.showWarning($t('Please save the crop data first.'));
                return;
            }

            if (this.useBrowserCompression && this.cropperManager.isInitialized()) {
                this.generateImagesInBrowser(breakpoint);
            } else {
                this.generateImagesOnServer(breakpoint);
            }
        },

        /**
         * Generate preview images for comparison (browser-only, no server upload)
         *
         * @param {Object} breakpoint
         */
        generatePreviewImages: function (breakpoint) {
            var self = this;
            var cropData = this.getCropData(breakpoint.breakpoint_id);

            if (!this.cropperManager.isInitialized()) {
                ajaxService.showWarning($t('Cropper is not initialized.'));
                return;
            }

            self.isCompressing(true);
            self.compressionProgress(0);
            self.compressionMessage($t('Generating preview...'));

            var targetWidth = breakpoint.target_width;
            var cropWidth = cropData.crop_width || 0;
            var cropHeight = cropData.crop_height || 0;

            var targetHeight = (cropWidth > 0 && cropHeight > 0)
                ? Math.round(targetWidth * cropHeight / cropWidth)
                : breakpoint.target_height;

            var canvas = this.cropperManager.getCroppedCanvas(targetWidth, targetHeight);

            if (!canvas) {
                self.isCompressing(false);
                ajaxService.showError($t('Failed to get cropped canvas.'));
                return;
            }

            var options = {
                webpQuality: cropData.webp_quality || config.WEBP_QUALITY_DEFAULT,
                avifQuality: cropData.avif_quality || config.AVIF_QUALITY_DEFAULT,
                generateWebp: this.isWebpEnabled(cropData),
                generateAvif: this.isAvifEnabled(cropData),
                originalMimeType: 'image/jpeg'
            };

            imageCompressor.compressAllFormats(canvas, options, function (progress, message) {
                self.compressionProgress(progress);
                self.compressionMessage(message);
            })
                .then(function (results) {
                    self.isCompressing(false);
                    self.compressionProgress(100);

                    // Revoke old preview URLs
                    if (cropData.preview_cropped_url) {
                        URL.revokeObjectURL(cropData.preview_cropped_url);
                    }
                    if (cropData.preview_webp_url) {
                        URL.revokeObjectURL(cropData.preview_webp_url);
                    }
                    if (cropData.preview_avif_url) {
                        URL.revokeObjectURL(cropData.preview_avif_url);
                    }

                    // Create new preview blob URLs
                    var updated = Object.assign({}, cropData);

                    if (results.original && results.original.blob) {
                        updated.cropped_image_url = URL.createObjectURL(results.original.blob);
                        updated.original_size = results.original.size;
                        updated.preview_cropped_url = updated.cropped_image_url;
                    }

                    if (results.webp && results.webp.blob) {
                        updated.webp_image_url = URL.createObjectURL(results.webp.blob);
                        updated.webp_size = results.webp.size;
                        updated.preview_webp_url = updated.webp_image_url;
                    }

                    if (results.avif && results.avif.blob) {
                        updated.avif_image_url = URL.createObjectURL(results.avif.blob);
                        updated.avif_size = results.avif.size;
                        updated.preview_avif_url = updated.avif_image_url;
                    }

                    self.setCropData(breakpoint.breakpoint_id, updated);
                    self.showComparison(true);
                })
                .catch(function (error) {
                    self.isCompressing(false);
                    ajaxService.showError(error.message || $t('Failed to generate preview.'));
                });
        },

        /**
         * Generate images using browser-based compression
         *
         * @param {Object} breakpoint
         */
        generateImagesInBrowser: function (breakpoint) {
            var self = this;
            var cropData = this.getCropData(breakpoint.breakpoint_id);

            self.isCompressing(true);
            self.compressionProgress(0);
            self.compressionMessage($t('Preparing image...'));

            var targetWidth = breakpoint.target_width;
            var cropWidth = cropData.crop_width || 0;
            var cropHeight = cropData.crop_height || 0;

            // Calculate target height based on crop aspect ratio to avoid image stretching
            var targetHeight = (cropWidth > 0 && cropHeight > 0)
                ? Math.round(targetWidth * cropHeight / cropWidth)
                : breakpoint.target_height;

            var canvas = this.cropperManager.getCroppedCanvas(targetWidth, targetHeight);

            if (!canvas) {
                self.isCompressing(false);
                ajaxService.showError($t('Failed to get cropped canvas.'));
                return;
            }

            var options = {
                webpQuality: cropData.webp_quality || config.WEBP_QUALITY_DEFAULT,
                avifQuality: cropData.avif_quality || config.AVIF_QUALITY_DEFAULT,
                generateWebp: this.isWebpEnabled(cropData),
                generateAvif: this.isAvifEnabled(cropData),
                originalMimeType: 'image/jpeg'
            };

            imageCompressor.compressAllFormats(canvas, options, function (progress, message) {
                self.compressionProgress(progress);
                self.compressionMessage(message);
            })
                .then(function (results) {
                    self.compressionMessage($t('Uploading...'));
                    return self.uploadCompressedImages(breakpoint, results, cropData);
                })
                .then(function (response) {
                    self.isCompressing(false);
                    self.compressionProgress(100);

                    if (response.images) {
                        var updated = self.updateCropDataFromResponse(cropData, response.images, true);
                        self.setCropData(breakpoint.breakpoint_id, updated);
                        self.refreshComparison(breakpoint.breakpoint_id);
                    }

                    ajaxService.showSuccess(response.message || $t('Images generated.'));
                })
                .catch(function () {
                    self.isCompressing(false);
                    self.generateImagesOnServer(breakpoint);
                });
        },

        /**
         * Generate images using server-side processing
         *
         * @param {Object} breakpoint
         */
        generateImagesOnServer: function (breakpoint) {
            var self = this;
            var cropData = this.getCropData(breakpoint.breakpoint_id);

            self.isLoading(true);

            ajaxService.generateImages(this.generateUrl, {crop_id: cropData.crop_id})
                .then(function (response) {
                    self.isLoading(false);

                    if (response.images) {
                        var updated = self.updateCropDataFromResponse(cropData, response.images, false);
                        self.setCropData(breakpoint.breakpoint_id, updated);
                        self.refreshComparison(breakpoint.breakpoint_id);
                    }

                    ajaxService.showSuccess(response.message);
                })
                .catch(function (error) {
                    self.isLoading(false);
                    ajaxService.showError(error.message);
                });
        },

        /**
         * Generate images for all breakpoints using browser compression
         *
         * @return {Promise}
         */
        generateAllBreakpointImages: function () {
            var self = this;
            var toProcess = [];

            this.breakpoints().forEach(function (breakpoint) {
                var cropData = self.getCropData(breakpoint.breakpoint_id);
                if (cropData && cropData.crop_id) {
                    toProcess.push({breakpoint: breakpoint, cropData: cropData});
                }
            });

            if (toProcess.length === 0) {
                return Promise.resolve();
            }

            return toProcess.reduce(function (chain, item) {
                return chain.then(function () {
                    return self.generateBreakpointImageInBrowser(item.breakpoint, item.cropData);
                });
            }, Promise.resolve());
        },

        /**
         * Generate images only for specified breakpoint IDs
         *
         * @param {Array} breakpointIds
         * @return {Promise}
         */
        generateBreakpointImagesByIds: function (breakpointIds) {
            var self = this;
            var toProcess = [];

            this.breakpoints().forEach(function (breakpoint) {
                var id = String(breakpoint.breakpoint_id);
                if (breakpointIds.indexOf(id) === -1) {
                    return;
                }

                var cropData = self.getCropData(breakpoint.breakpoint_id);
                if (cropData && cropData.crop_id) {
                    toProcess.push({breakpoint: breakpoint, cropData: cropData});
                }
            });

            if (toProcess.length === 0) {
                return Promise.resolve();
            }

            return toProcess.reduce(function (chain, item) {
                return chain.then(function () {
                    return self.generateBreakpointImageInBrowser(item.breakpoint, item.cropData);
                });
            }, Promise.resolve());
        },

        /**
         * Generate images for a single breakpoint using browser compression
         *
         * @param {Object} breakpoint
         * @param {Object} cropData
         * @return {Promise}
         */
        generateBreakpointImageInBrowser: function (breakpoint, cropData) {
            var self = this;

            return new Promise(function (resolve) {
                var sourceImageUrl = self.getBreakpointSourceImageUrl(breakpoint.breakpoint_id);

                if (!sourceImageUrl) {
                    resolve();
                    return;
                }

                var img = new Image();
                img.crossOrigin = 'anonymous';

                img.onload = function () {
                    self.processImageOnCanvas(img, breakpoint, cropData)
                        .then(resolve)
                        .catch(function () {
                            self.generateOnServerFallback(cropData, breakpoint.breakpoint_id).then(resolve);
                        });
                };

                img.onerror = function () {
                    self.generateOnServerFallback(cropData, breakpoint.breakpoint_id).then(resolve);
                };

                img.src = sourceImageUrl;
            });
        },

        /**
         * Process image on canvas for browser compression
         *
         * @param {HTMLImageElement} img
         * @param {Object} breakpoint
         * @param {Object} cropData
         * @return {Promise}
         */
        processImageOnCanvas: function (img, breakpoint, cropData) {
            var self = this;
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');

            var cropX = cropData.crop_x || 0;
            var cropY = cropData.crop_y || 0;
            var cropWidth = cropData.crop_width || img.naturalWidth;
            var cropHeight = cropData.crop_height || img.naturalHeight;
            var targetWidth = breakpoint.target_width || cropWidth;

            // Always calculate target height based on crop aspect ratio to avoid image stretching
            var targetHeight = (cropWidth > 0 && cropHeight > 0)
                ? Math.round(targetWidth * cropHeight / cropWidth)
                : (breakpoint.target_height || cropHeight);

            canvas.width = targetWidth;
            canvas.height = targetHeight;

            ctx.drawImage(img, cropX, cropY, cropWidth, cropHeight, 0, 0, targetWidth, targetHeight);

            var options = {
                webpQuality: cropData.webp_quality || config.WEBP_QUALITY_DEFAULT,
                avifQuality: cropData.avif_quality || config.AVIF_QUALITY_DEFAULT,
                generateWebp: this.isWebpEnabled(cropData),
                generateAvif: this.isAvifEnabled(cropData),
                originalMimeType: 'image/jpeg'
            };

            return imageCompressor.compressAllFormats(canvas, options)
                .then(function (results) {
                    return self.uploadCompressedImages(breakpoint, results, cropData);
                })
                .then(function (response) {
                    if (response.images) {
                        var updated = self.updateCropDataFromResponse(cropData, response.images, true);
                        self.setCropData(breakpoint.breakpoint_id, updated);
                    }
                });
        },

        /**
         * Upload compressed images to server
         *
         * @param {Object} breakpoint
         * @param {Object} results
         * @param {Object} cropData
         * @returns {Promise}
         */
        uploadCompressedImages: function (breakpoint, results, cropData) {
            var formData = new FormData();

            formData.append('crop_id', cropData.crop_id);
            formData.append('crop_x', cropData.crop_x || 0);
            formData.append('crop_y', cropData.crop_y || 0);
            formData.append('crop_width', cropData.crop_width || 0);
            formData.append('crop_height', cropData.crop_height || 0);
            formData.append('webp_quality', cropData.webp_quality || config.WEBP_QUALITY_DEFAULT);
            formData.append('avif_quality', cropData.avif_quality || config.AVIF_QUALITY_DEFAULT);

            if (results.original && results.original.blob) {
                var ext = results.original.format === 'png' ? 'png' : 'jpg';
                formData.append('cropped_image', results.original.blob, 'cropped.' + ext);
            }

            if (results.webp && results.webp.blob) {
                formData.append('webp_image', results.webp.blob, 'cropped.webp');
            }

            if (results.avif && results.avif.blob) {
                formData.append('avif_image', results.avif.blob, 'cropped.avif');
            }

            return ajaxService.uploadCompressedImages(this.uploadCompressedUrl, formData);
        },

        // ==================== SOURCE IMAGE METHODS ====================

        /**
         * Get source image URL for a breakpoint
         *
         * @param {Number} breakpointId
         * @returns {String|null}
         */
        getBreakpointSourceImageUrl: function (breakpointId) {
            var images = this.breakpointImages();

            if (images[breakpointId] && images[breakpointId].url) {
                return images[breakpointId].url;
            }

            var cropData = this.getCropData(breakpointId);

            if (cropData && cropData.source_image_url) {
                return cropData.source_image_url;
            }

            return this.desktopImageUrl();
        },

        /**
         * Get source image file path for a breakpoint
         *
         * @param {Number} breakpointId
         * @returns {String|null}
         */
        getBreakpointSourceImageFile: function (breakpointId) {
            var images = this.breakpointImages();

            if (images[breakpointId] && images[breakpointId].file) {
                return images[breakpointId].file;
            }

            var cropData = this.getCropData(breakpointId);

            if (cropData && cropData.source_image) {
                return cropData.source_image;
            }

            return this.desktopImageFile || null;
        },

        /**
         * Check if breakpoint has a custom image
         *
         * @param {Number} breakpointId
         * @returns {Boolean}
         */
        hasCustomBreakpointImage: function (breakpointId) {
            var images = this.breakpointImages();
            return !!(images[breakpointId] && images[breakpointId].file);
        },

        /**
         * Check if any source image is available
         *
         * @param {Number} breakpointId
         * @returns {Boolean}
         */
        hasSourceImage: function (breakpointId) {
            return !!this.getBreakpointSourceImageUrl(breakpointId);
        },

        /**
         * Set custom image for a breakpoint
         *
         * @param {Number} breakpointId
         * @param {Object} imageData
         */
        setBreakpointImage: function (breakpointId, imageData) {
            var images = this.breakpointImages();
            images[breakpointId] = imageData;
            this.breakpointImages(Object.assign({}, images));

            var cropData = this.getCropData(breakpointId);
            cropData.custom_source_image = imageData.file;
            cropData.custom_source_image_url = imageData.url;
            this.setCropData(breakpointId, cropData);
        },

        /**
         * Clear custom image for a breakpoint
         *
         * @param {Number} breakpointId
         */
        clearBreakpointImage: function (breakpointId) {
            // Destroy cropper first to prevent visual flash
            this.destroyCropper();

            var images = this.breakpointImages();
            delete images[breakpointId];
            this.breakpointImages(Object.assign({}, images));

            var cropData = this.getCropData(breakpointId);
            cropData.custom_source_image = null;
            cropData.custom_source_image_url = null;
            cropData.source_image_url = null;
            cropData.source_image = null;
            cropData.crop_x = 0;
            cropData.crop_y = 0;
            cropData.crop_width = 0;
            cropData.crop_height = 0;
            this.setCropData(breakpointId, cropData);

            var breakpoint = this.activeBreakpoint();
            if (breakpoint && breakpoint.breakpoint_id === breakpointId) {
                this.autoSave();
            }
        },

        // ==================== DESKTOP IMAGE METHODS ====================

        /**
         * Subscribe to desktop image field changes
         */
        subscribeToDesktopImage: function () {
            var self = this;

            registry.async(this.desktopImageField)(function (imageField) {
                if (imageField && imageField.value) {
                    self.updateDesktopImageUrl(imageField.value());
                    imageField.value.subscribe(function (value) {
                        self.updateDesktopImageUrl(value);
                    });
                }
            });
        },

        /**
         * Update desktop image URL from field value
         *
         * @param {Array|Object} value
         */
        updateDesktopImageUrl: function (value) {
            var url = null;

            if (value && Array.isArray(value) && value.length > 0) {
                url = value[0].url || null;
                this.desktopImageFile = value[0].file || value[0].name || null;
            } else {
                this.desktopImageFile = null;
            }

            this.desktopImageUrl(url);
            this.destroyCropper();
        },

        // ==================== CROPPER METHODS ====================

        /**
         * Initialize cropper on image
         *
         * @param {HTMLElement} imageElement
         * @param {Object} breakpoint
         */
        initCropper: function (imageElement, breakpoint) {
            // Skip if cropper is already initialized for the same image and breakpoint
            if (this.cropperManager.isInitialized() &&
                this.currentCropperImage === imageElement.src &&
                this.currentCropperBreakpointId === breakpoint.breakpoint_id) {
                return;
            }

            this.currentCropperImage = imageElement.src;
            this.currentCropperBreakpointId = breakpoint.breakpoint_id;

            var savedCropData = this.getCropData(breakpoint.breakpoint_id);
            this.cropperManager.init(imageElement, breakpoint, savedCropData);
        },

        /**
         * Destroy cropper instance
         */
        destroyCropper: function () {
            if (this.cropperManager) {
                this.cropperManager.destroy();
                this.cropperInitialized(false);
                this.currentCropperImage = null;
                this.currentCropperBreakpointId = null;
            }
        },

        /**
         * Update crop values from cropper event
         *
         * @param {Number} breakpointId
         * @param {Object} detail
         */
        updateCropValues: function (breakpointId, detail) {
            var cropData = this.getCropData(breakpointId);

            cropData.crop_x = Math.round(detail.x);
            cropData.crop_y = Math.round(detail.y);
            cropData.crop_width = Math.round(detail.width);
            cropData.crop_height = Math.round(detail.height);

            this.setCropData(breakpointId, cropData, true);
        },

        // ==================== BREAKPOINT METHODS ====================

        /**
         * Set active breakpoint
         *
         * @param {Object} breakpoint
         */
        setActiveBreakpoint: function (breakpoint) {
            this.destroyCropper();
            this.activeBreakpoint(breakpoint);
        },

        /**
         * Check if breakpoint is active
         *
         * @param {Object} breakpoint
         * @returns {Boolean}
         */
        isActiveBreakpoint: function (breakpoint) {
            var active = this.activeBreakpoint();
            return active && active.breakpoint_id === breakpoint.breakpoint_id;
        },

        /**
         * Get dimension text for breakpoint
         *
         * @param {Object} breakpoint
         * @returns {String}
         */
        getDimensionText: function (breakpoint) {
            return breakpoint.target_width + 'x' + breakpoint.target_height + ' px';
        },

        // ==================== DATA PROVIDER METHODS ====================

        /**
         * Handle changes to responsive cropper data from provider
         *
         * @param {Object} data
         */
        onResponsiveCropperDataChange: function (data) {
            if (!data) {
                return;
            }

            if (data.breakpoints) {
                this.breakpoints(data.breakpoints);
            }

            if (data.crops) {
                this.crops(data.crops);
                this.loadBreakpointImagesFromCrops(data.crops);
                this.storeSavedCropsState();
            }

            if (data.banner_id) {
                this.bannerId(data.banner_id);
            }

            if (data.slider_id) {
                this.sliderId(data.slider_id);
            }

            if (data.breakpoints && data.breakpoints.length > 0 && !this.activeBreakpoint()) {
                this.setActiveBreakpoint(data.breakpoints[0]);
            }
        },

        /**
         * Load breakpoint images from crops data
         *
         * @param {Object} crops
         */
        loadBreakpointImagesFromCrops: function (crops) {
            var images = {};

            Object.keys(crops).forEach(function (breakpointId) {
                var cropData = crops[breakpointId];
                if (cropData.custom_source_image && cropData.custom_source_image_url) {
                    images[breakpointId] = {
                        file: cropData.custom_source_image,
                        url: cropData.custom_source_image_url
                    };
                }
            });

            this.breakpointImages(images);
        },

        /**
         * Handle slider change
         *
         * @param {Number} sliderId
         */
        onSliderChange: function (sliderId) {
            if (!sliderId || sliderId === this.sliderId()) {
                return;
            }

            this.sliderId(sliderId);
            this.loadBreakpointsForSlider(sliderId);
        },

        /**
         * Load breakpoints for a slider via AJAX
         *
         * @param {Number} sliderId
         */
        loadBreakpointsForSlider: function (sliderId) {
            var self = this;
            var url = this.saveUrl.replace('/save', '/breakpoints');

            ajaxService.loadBreakpoints(url, sliderId)
                .then(function (response) {
                    if (response.breakpoints) {
                        self.breakpoints(response.breakpoints);
                        if (response.breakpoints.length > 0) {
                            self.setActiveBreakpoint(response.breakpoints[0]);
                        }
                    }
                })
                .catch(function () {});
        },

        /**
         * Update crops data from server response
         *
         * @param {Object} cropsResponse
         */
        updateCropsFromResponse: function (cropsResponse) {
            var self = this;

            $.each(cropsResponse, function (breakpointId, images) {
                var cropData = self.getCropData(breakpointId) || {};
                var updated = self.updateCropDataFromResponse(cropData, images, false);
                self.setCropData(breakpointId, updated);
                self.refreshComparison(breakpointId);
            });
        },

        // ==================== QUALITY & TOGGLE METHODS ====================

        /**
         * Toggle WebP generation
         *
         * @param {Object} breakpoint
         */
        toggleWebP: function (breakpoint) {
            var cropData = this.getCropData(breakpoint.breakpoint_id);
            cropData.generate_webp = cropData.generate_webp === false;
            this.setCropData(breakpoint.breakpoint_id, cropData, true);
            this.autoSave();
        },

        /**
         * Toggle AVIF generation
         *
         * @param {Object} breakpoint
         */
        toggleAvif: function (breakpoint) {
            var cropData = this.getCropData(breakpoint.breakpoint_id);
            cropData.generate_avif = cropData.generate_avif !== true;
            this.setCropData(breakpoint.breakpoint_id, cropData, true);
            this.autoSave();
        },

        /**
         * Update WebP quality
         *
         * @param {Object} breakpoint
         * @param {Object} data
         * @param {Object} event
         */
        updateWebpQuality: function (breakpoint, data, event) {
            var value = parseInt(event.target.value, 10) || config.WEBP_QUALITY_DEFAULT;
            var cropData = this.getCropData(breakpoint.breakpoint_id);
            cropData.webp_quality = value;
            this.setCropData(breakpoint.breakpoint_id, cropData, true);
            this.syncQualityInputs(event.target, value);
            this.autoSave();
        },

        /**
         * Update AVIF quality
         *
         * @param {Object} breakpoint
         * @param {Object} data
         * @param {Object} event
         */
        updateAvifQuality: function (breakpoint, data, event) {
            var value = parseInt(event.target.value, 10) || config.AVIF_QUALITY_DEFAULT;
            var cropData = this.getCropData(breakpoint.breakpoint_id);
            cropData.avif_quality = value;
            this.setCropData(breakpoint.breakpoint_id, cropData, true);
            this.syncQualityInputs(event.target, value);
            this.autoSave();
        },

        /**
         * Sync quality slider and number input values
         *
         * @param {HTMLElement} sourceElement
         * @param {Number} value
         */
        syncQualityInputs: function (sourceElement, value) {
            var container = sourceElement.closest('.quality-slider');
            if (!container) {
                return;
            }

            var rangeInput = container.querySelector('input[type="range"]');
            var numberInput = container.querySelector('input[type="number"]');

            if (rangeInput && rangeInput !== sourceElement) {
                rangeInput.value = value;
            }

            if (numberInput && numberInput !== sourceElement) {
                numberInput.value = value;
            }
        },

        // ==================== COMPARISON & PREVIEW METHODS ====================

        /**
         * Subscribe to comparison toggle
         */
        subscribeToComparisonToggle: function () {
            var self = this;

            this.showComparison.subscribe(function (isVisible) {
                if (!isVisible) {
                    return;
                }

                var breakpoint = self.activeBreakpoint();
                if (!breakpoint) {
                    return;
                }

                var cropData = self.getCropData(breakpoint.breakpoint_id);
                if (cropData && cropData.cropped_image_url && !cropData.original_size) {
                    self.fetchImageSizes(breakpoint.breakpoint_id);
                }
            });
        },

        /**
         * Set comparison mode
         *
         * @param {String} mode
         */
        setComparisonMode: function (mode) {
            this.comparisonMode(mode);
        },

        /**
         * Refresh comparison section
         *
         * @param {Number} breakpointId
         */
        refreshComparison: function (breakpointId) {
            this.fetchImageSizes(breakpointId);
        },

        /**
         * Fetch all image sizes for a breakpoint
         *
         * @param {Number} breakpointId
         */
        fetchImageSizes: function (breakpointId) {
            var self = this;
            var existingData = this.getCropData(breakpointId);

            if (!existingData || !existingData.cropped_image_url) {
                return;
            }

            Promise.all([
                fileUtils.fetchFileSize(existingData.cropped_image_url),
                fileUtils.fetchFileSize(existingData.webp_image_url),
                fileUtils.fetchFileSize(existingData.avif_image_url)
            ]).then(function (sizes) {
                var cropData = Object.assign({}, self.getCropData(breakpointId));
                cropData.original_size = sizes[0];
                cropData.webp_size = sizes[1];
                cropData.avif_size = sizes[2];
                self.setCropData(breakpointId, cropData);
            });
        },

        /**
         * Generate live preview when quality changes
         *
         * @param {Object} breakpoint
         * @param {String} format
         */
        generateLivePreview: function (breakpoint, format) {
            var self = this;

            if (!this.useBrowserCompression || !this.cropperManager.isInitialized()) {
                return;
            }

            var cropData = this.getCropData(breakpoint.breakpoint_id);
            var quality = format === 'webp'
                ? (cropData.webp_quality || config.WEBP_QUALITY_DEFAULT)
                : (cropData.avif_quality || config.AVIF_QUALITY_DEFAULT);

            var targetWidth = breakpoint.target_width;
            var cropWidth = cropData.crop_width || 0;
            var cropHeight = cropData.crop_height || 0;

            // Calculate target height based on crop aspect ratio to avoid image stretching
            var targetHeight = (cropWidth > 0 && cropHeight > 0)
                ? Math.round(targetWidth * cropHeight / cropWidth)
                : breakpoint.target_height;

            var canvas = this.cropperManager.getCroppedCanvas(targetWidth, targetHeight);

            if (!canvas) {
                return;
            }

            var compressPromise = format === 'webp'
                ? imageCompressor.compressToWebP(canvas, quality)
                : imageCompressor.compressToAvif(canvas, quality);

            compressPromise.then(function (result) {
                var previews = self.previewUrls() || {};

                if (previews[format]) {
                    imageCompressor.revokePreviewUrl(previews[format].url);
                }

                previews[format] = {
                    url: imageCompressor.createPreviewUrl(result),
                    size: result.size,
                    formattedSize: imageCompressor.formatFileSize(result.size)
                };

                self.previewUrls(previews);
            }).catch(function () {});
        },

        /**
         * Clear preview URLs
         */
        clearPreviews: function () {
            var previews = this.previewUrls() || {};

            Object.keys(previews).forEach(function (format) {
                if (previews[format] && previews[format].url) {
                    imageCompressor.revokePreviewUrl(previews[format].url);
                }
            });

            this.previewUrls({});
        },

        // ==================== UPLOAD METHODS ====================

        /**
         * Trigger file input click for breakpoint image upload
         *
         * @param {Object} breakpoint
         */
        triggerBreakpointImageUpload: function (breakpoint) {
            var input = document.getElementById('breakpoint-image-upload-' + breakpoint.breakpoint_id);
            if (input) {
                input.click();
            }
        },

        /**
         * Handle breakpoint image file selection
         *
         * @param {Object} breakpoint
         * @param {Object} data
         * @param {Event} event
         */
        handleBreakpointImageUpload: function (breakpoint, data, event) {
            var self = this;
            var input = event.target;
            var file = input.files && input.files[0];

            if (!file) {
                return;
            }

            var validation = fileUtils.validateImageFile(file);

            if (!validation.valid) {
                ajaxService.showError(validation.error);
                input.value = '';
                return;
            }

            self.isUploading(true);

            var formData = new FormData();
            formData.append('breakpoint_image', file);
            formData.append('breakpoint_id', breakpoint.breakpoint_id);
            formData.append('banner_id', self.bannerId() || 0);

            ajaxService.uploadBreakpointImage(self.uploadBreakpointImageUrl, formData)
                .then(function (response) {
                    self.isUploading(false);
                    input.value = '';

                    self.setBreakpointImage(breakpoint.breakpoint_id, {
                        url: response.url,
                        file: response.file
                    });

                    self.destroyCropper();
                    self.autoSave();
                })
                .catch(function (error) {
                    self.isUploading(false);
                    input.value = '';
                    ajaxService.showError(error.message);
                });
        },

        // ==================== UTILITY METHODS ====================

        /**
         * Check browser compression support
         */
        checkBrowserCompressionSupport: function () {
            this.useBrowserCompression = imageCompressor.isWasmSupported();
        },

        /**
         * Format file size
         *
         * @param {Number} bytes
         * @returns {String}
         */
        formatFileSize: function (bytes) {
            return fileUtils.formatFileSize(bytes);
        },

        /**
         * Calculate savings percentage
         *
         * @param {Number} originalSize
         * @param {Number} optimizedSize
         * @returns {String}
         */
        calculateSavings: function (originalSize, optimizedSize) {
            return fileUtils.calculateSavings(originalSize, optimizedSize);
        }
    });
});
