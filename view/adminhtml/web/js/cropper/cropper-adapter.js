/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Puts a Cropper.js crop box on a loaded image and reports the crop area in whole source pixels.
 *
 * The box keeps the breakpoint's aspect ratio (free when the breakpoint leaves the height open) and stays inside the
 * image. It opens on the given area or, when there is none, on the breakpoint's default area, which is reported at
 * once. Afterwards every move or resize the admin finishes is reported; opening the box never reports a stored area,
 * so merely viewing a crop does not change it.
 */
define([
    'Hryvinskyi_BannerSliderAdminUi/js/lib/cropper.min',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-geometry'
], function (Cropper, geometry) {
    'use strict';

    return {
        /**
         * Attach a crop box to an image element whose source has loaded
         *
         * @param {HTMLImageElement} image
         * @param {{target: Object, rect: (Object|null), onChange: function(Object)}} options
         * @returns {{destroy: Function}}
         */
        attach: function (image, options) {
            var natural = {width: image.naturalWidth, height: image.naturalHeight},
                ready = false,
                cropper = new Cropper(image, {
                    aspectRatio: geometry.aspectRatio(options.target),
                    viewMode: 1,
                    autoCropArea: 1,
                    checkOrientation: false,
                    zoomable: false,
                    movable: false,
                    rotatable: false,
                    scalable: false,

                    /**
                     * Show the stored or default area
                     */
                    ready: function () {
                        var rect = options.rect
                            ? geometry.clampRect(options.rect, natural)
                            : geometry.initialRect(natural, options.target);

                        cropper.setData(rect);
                        ready = true;
                        if (!options.rect) {
                            options.onChange(rect);
                        }
                    },

                    /**
                     * Report the area the admin chose
                     */
                    cropend: function () {
                        if (ready) {
                            options.onChange(geometry.clampRect(cropper.getData(true), natural));
                        }
                    }
                });

            return {
                /**
                 * Remove the crop box
                 *
                 * @returns {void}
                 */
                destroy: function () {
                    cropper.destroy();
                }
            };
        }
    };
});
