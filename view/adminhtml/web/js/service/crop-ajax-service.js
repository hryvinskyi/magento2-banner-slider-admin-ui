/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, $t, alert) {
    'use strict';

    return {
        /**
         * Save crop data to server
         *
         * @param {string} url
         * @param {Object} data
         * @returns {Promise<Object>}
         */
        saveCropData: function (url, data) {
            return new Promise(function (resolve, reject) {
                data.form_key = window.FORM_KEY;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message || $t('An error occurred.')));
                        }
                    },
                    error: function (xhr, status, error) {
                        reject(new Error($t('An error occurred while saving.') + ' ' + error));
                    }
                });
            });
        },

        /**
         * Generate images on server for single crop or all banner crops
         *
         * @param {string} url
         * @param {Object} data - {crop_id} for single or {banner_id} for all
         * @returns {Promise<Object>}
         */
        generateImages: function (url, data) {
            return new Promise(function (resolve, reject) {
                data.form_key = window.FORM_KEY;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message || $t('An error occurred.')));
                        }
                    },
                    error: function (xhr, status, error) {
                        reject(new Error($t('An error occurred while generating images.') + ' ' + error));
                    }
                });
            });
        },

        /**
         * Generate images without rejecting on failure (for batch operations)
         *
         * @param {string} url
         * @param {Object} data
         * @returns {Promise<Object>}
         */
        generateImagesQuiet: function (url, data) {
            return new Promise(function (resolve) {
                data.form_key = window.FORM_KEY;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function (response) {
                        resolve(response);
                    },
                    error: function () {
                        resolve({success: false});
                    }
                });
            });
        },

        /**
         * Upload browser-compressed images to server
         *
         * @param {string} url
         * @param {FormData} formData
         * @returns {Promise<Object>}
         */
        uploadCompressedImages: function (url, formData) {
            return new Promise(function (resolve, reject) {
                formData.append('form_key', window.FORM_KEY);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message || $t('Upload failed')));
                        }
                    },
                    error: function (xhr, status, error) {
                        reject(new Error($t('Upload request failed: ') + error));
                    }
                });
            });
        },

        /**
         * Upload breakpoint custom image
         *
         * @param {string} url
         * @param {FormData} formData
         * @returns {Promise<Object>}
         */
        uploadBreakpointImage: function (url, formData) {
            return new Promise(function (resolve, reject) {
                formData.append('form_key', window.FORM_KEY);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(new Error(response.message || $t('Upload failed.')));
                        }
                    },
                    error: function (xhr, status, error) {
                        reject(new Error($t('An error occurred while uploading the image.') + ' ' + error));
                    }
                });
            });
        },

        /**
         * Load breakpoints for a slider
         *
         * @param {string} url
         * @param {number} sliderId
         * @returns {Promise<Object>}
         */
        loadBreakpoints: function (url, sliderId) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        slider_id: sliderId,
                        form_key: window.FORM_KEY
                    },
                    success: function (response) {
                        resolve(response);
                    },
                    error: function (xhr, status, error) {
                        reject(new Error($t('Failed to load breakpoints.') + ' ' + error));
                    }
                });
            });
        },

        /**
         * Show error alert
         *
         * @param {string} message
         */
        showError: function (message) {
            alert({
                title: $t('Error'),
                content: message
            });
        },

        /**
         * Show success alert
         *
         * @param {string} message
         */
        showSuccess: function (message) {
            alert({
                title: $t('Success'),
                content: message
            });
        },

        /**
         * Show warning alert
         *
         * @param {string} message
         */
        showWarning: function (message) {
            alert({
                title: $t('Warning'),
                content: message
            });
        }
    };
});
