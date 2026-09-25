/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The banner's local video uploader: previews the file as a video in the banner's aspect ratio (the preset, or the
 * custom ratio while the select is on its custom choice). The accepted file types and the size limit come from the
 * form, the same ones the server enforces.
 */
define([
    'Magento_Ui/js/form/element/image-uploader',
    'ko',
    'Hryvinskyi_BannerSliderAdminUi/js/validation/value-rules',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/file-size'
], function (ImageUploader, ko, valueRules, fileSize) {
    'use strict';

    var VIDEO_TYPES = {mp4: 'video/mp4', m4v: 'video/x-m4v', webm: 'video/webm'};

    return ImageUploader.extend({
        defaults: {
            previewTmpl: 'Hryvinskyi_BannerSliderAdminUi/form/element/uploader/video-preview',
            customChoice: 'custom',
            aspectRatioChoice: '',
            customAspectRatio: '',
            imports: {
                aspectRatioChoice: '${ $.provider }:data.video_aspect_ratio',
                customAspectRatio: '${ $.provider }:data.video_custom_aspect_ratio'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super();
            this.observe(['aspectRatioChoice', 'customAspectRatio']);

            /**
             * The CSS aspect ratio of the preview (`16 / 9`); empty while the chosen ratio is not valid
             */
            this.aspectRatioStyle = ko.pureComputed(function () {
                var choice = this.aspectRatioChoice(),
                    text = choice === this.customChoice ? this.customAspectRatio() : choice,
                    ratio = valueRules.parseAspectRatio(text);

                return ratio ? ratio.width + ' / ' + ratio.height : '';
            }, this);

            return this;
        },

        /**
         * Every file of this uploader previews as a video
         *
         * @returns {String}
         */
        getFilePreviewType: function () {
            return 'video';
        },

        /**
         * @returns {String}
         */
        getPreviewTmpl: function () {
            return this.previewTmpl;
        },

        /**
         * The MIME type of a video file, from its type or else its extension; null when it is not MP4, M4V or WebM
         *
         * @param {{type: (String|undefined), name: (String|undefined), file: (String|undefined)}} file
         * @returns {String|null}
         */
        getVideoMimeType: function (file) {
            var name = String(file.name || file.file || ''),
                extension = name.slice(name.lastIndexOf('.') + 1).toLowerCase(),
                known = Object.keys(VIDEO_TYPES).map(function (key) {
                    return VIDEO_TYPES[key];
                });

            if (known.indexOf(file.type) !== -1) {
                return file.type;
            }

            return Object.prototype.hasOwnProperty.call(VIDEO_TYPES, extension) ? VIDEO_TYPES[extension] : null;
        },

        /**
         * A video has no image preview to measure
         *
         * @returns {void}
         */
        onPreviewLoad: function () {},

        /**
         * @param {Number} bytes
         * @returns {String}
         */
        formatSize: function (bytes) {
            return fileSize.format(bytes);
        }
    });
});
