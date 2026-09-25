/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Loads a source image and renders a crop of it onto a canvas, in the browser.
 *
 * The image is fetched as a blob from the admin's own origin, so the canvas it is drawn on is never tainted and the
 * encoders can read its pixels; the blob's type tells which format the source is in. A source that cannot be fetched
 * or decoded rejects the returned promise.
 */
define([], function () {
    'use strict';

    /**
     * Decode a blob into something a canvas can draw
     *
     * @param {Blob} blob
     * @returns {Promise<ImageBitmap|HTMLImageElement>}
     */
    function decode(blob) {
        if (typeof window.createImageBitmap === 'function') {
            return window.createImageBitmap(blob);
        }

        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(blob),
                image = new Image();

            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve(image);
            };
            image.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('The image could not be decoded.'));
            };
            image.src = url;
        });
    }

    return {
        /**
         * Fetch and decode a source image
         *
         * @param {String} url
         * @returns {Promise<{image: (ImageBitmap|HTMLImageElement), width: Number, height: Number,
         *     mimeType: String}>}
         */
        load: function (url) {
            if (!url) {
                return Promise.reject(new Error('The crop has no source image.'));
            }

            return window.fetch(url, {credentials: 'same-origin'})
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('The image answered HTTP ' + response.status + '.');
                    }

                    return response.blob();
                })
                .then(function (blob) {
                    return decode(blob).then(function (image) {
                        return {
                            image: image,
                            width: image.naturalWidth || image.width,
                            height: image.naturalHeight || image.height,
                            mimeType: blob.type
                        };
                    });
                });
        },

        /**
         * Draw a crop area of a loaded image onto a new canvas of the given size
         *
         * @param {{image: (ImageBitmap|HTMLImageElement)}} source
         * @param {{x: Number, y: Number, width: Number, height: Number}} rect
         * @param {{width: Number, height: Number}} size
         * @returns {HTMLCanvasElement}
         */
        render: function (source, rect, size) {
            var canvas = document.createElement('canvas'),
                context;

            canvas.width = size.width;
            canvas.height = size.height;
            context = canvas.getContext('2d');
            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = 'high';
            context.drawImage(source.image, rect.x, rect.y, rect.width, rect.height, 0, 0, size.width, size.height);

            return canvas;
        },

        /**
         * Free the decoded image once it is drawn
         *
         * @param {{image: (ImageBitmap|HTMLImageElement)}} source
         * @returns {void}
         */
        release: function (source) {
            if (source && source.image && typeof source.image.close === 'function') {
                source.image.close();
            }
        }
    };
});
