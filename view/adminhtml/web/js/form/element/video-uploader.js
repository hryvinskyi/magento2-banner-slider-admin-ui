/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 */

define([
    'jquery',
    'Magento_Ui/js/form/element/image-uploader',
    'ko'
], function ($, ImageUploader, ko) {
    'use strict';

    return ImageUploader.extend({
        defaults: {
            previewTmpl: 'Hryvinskyi_BannerSliderAdminUi/form/element/uploader/video-preview',
            allowedExtensions: 'mp4 webm',
            maxFileSize: 104857600,
            isVideoFile: true,
            aspectRatio: '16:9',
            imports: {
                aspectRatio: '${ $.provider }:data.video_aspect_ratio'
            },
            listens: {
                aspectRatio: 'onAspectRatioChange'
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.isVideoFile = true;
            this.aspectRatioStyle = ko.observable(this.getAspectRatioStyle());
            return this;
        },

        /**
         * @inheritdoc
         */
        setInitialValue: function () {
            var value = this.getInitialValue(),
                self = this;

            // Ensure value is always an array
            if (!Array.isArray(value)) {
                value = value ? [value] : [];
            }

            // Convert string values to file objects
            value = value.map(function (item) {
                if (typeof item === 'string') {
                    var ext = item.split('.').pop().toLowerCase(),
                        mimeTypes = {
                            'mp4': 'video/mp4',
                            'webm': 'video/webm',
                            'ogg': 'video/ogg',
                            'mov': 'video/quicktime'
                        };

                    return {
                        name: item,
                        type: mimeTypes[ext] || 'video/mp4',
                        previewType: 'video'
                    };
                }

                return item;
            });

            value = value.map(this.processFile, this);

            this.initialValue = value.slice();
            this.value(value);
            this.on('value', this.onUpdate.bind(this));
            this.isUseDefault(this.disabled());

            return this;
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super();
            this.observe(['aspectRatio']);
            return this;
        },

        /**
         * Handle aspect ratio change
         *
         * @param {String} value
         */
        onAspectRatioChange: function (value) {
            if (value) {
                this.aspectRatio(value);
                this.aspectRatioStyle(this.getAspectRatioStyle());
            }
        },

        /**
         * Get CSS aspect-ratio style value
         *
         * @returns {String}
         */
        getAspectRatioStyle: function () {
            var ratio = this.aspectRatio() || this.aspectRatio || '16:9';
            if (typeof ratio === 'function') {
                ratio = ratio();
            }
            return ratio.replace(':', ' / ');
        },

        /**
         * Get aspect ratio as CSS property for inline style
         *
         * @returns {String}
         */
        getAspectRatioCss: function () {
            return 'aspect-ratio: ' + this.getAspectRatioStyle() + ';';
        },

        /**
         * Get preview type for the file
         *
         * @param {Object} file
         * @returns {String}
         */
        getFilePreviewType: function (file) {
            return 'video';
        },

        /**
         * Get preview template based on file type
         *
         * @param {Object} file
         * @returns {String}
         */
        getPreviewTmpl: function (file) {
            return this.previewTmpl;
        },

        /**
         * Check if file is a video
         *
         * @param {Object} file
         * @returns {Boolean}
         */
        isVideo: function (file) {
            var ext = this.getFileExtension(file);
            return ['mp4', 'webm', 'ogg', 'mov'].indexOf(ext.toLowerCase()) !== -1;
        },

        /**
         * Get file extension
         *
         * @param {Object} file
         * @returns {String}
         */
        getFileExtension: function (file) {
            var name = file.name || file.file || '';
            return name.split('.').pop();
        },

        /**
         * Get video MIME type based on extension
         *
         * @param {Object} file
         * @returns {String}
         */
        getVideoMimeType: function (file) {
            var ext = this.getFileExtension(file).toLowerCase();
            var mimeTypes = {
                'mp4': 'video/mp4',
                'webm': 'video/webm',
                'ogg': 'video/ogg',
                'mov': 'video/quicktime'
            };
            return mimeTypes[ext] || 'video/mp4';
        },

        /**
         * @inheritdoc
         */
        onPreviewLoad: function () {
            // Video doesn't need the same preview load handling as images
        },

        /**
         * Format file size for display
         *
         * @param {Number} bytes
         * @returns {String}
         */
        formatSize: function (bytes) {
            if (!bytes) {
                return '0 B';
            }

            var units = ['B', 'KB', 'MB', 'GB'];
            var i = 0;

            while (bytes >= 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }

            return bytes.toFixed(bytes < 10 && i > 0 ? 1 : 0) + ' ' + units[i];
        },

        /**
         * Get allowed file extensions in comma delimited format
         *
         * @returns {String}
         */
        getAllowedFileExtensionsInCommaDelimitedFormat: function () {
            var allowed = this.allowedExtensions || 'mp4 webm';
            return allowed.toUpperCase().split(' ').join(', ');
        }
    });
});
