/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Crop rectangles and the pixel size a crop is rendered at.
 *
 * A breakpoint target is `{width, height}`; a null height means "keep the crop's aspect ratio". Rectangles are
 * `{x, y, width, height}` in whole pixels of the source image. The rendered size follows the server's rule: the
 * target width, and the target height or, when it is open, the height that keeps the rectangle's aspect ratio at that
 * width (rounded, at least 1). The server checks browser-encoded crops against the same size.
 */
define([], function () {
    'use strict';

    /**
     * Whether a value is a crop rectangle of whole pixels with a positive size
     *
     * @param {*} rect
     * @returns {Boolean}
     */
    function isRect(rect) {
        return !!rect
            && [rect.x, rect.y, rect.width, rect.height].every(Number.isInteger)
            && rect.x >= 0
            && rect.y >= 0
            && rect.width > 0
            && rect.height > 0;
    }

    /**
     * The pixel size a crop is rendered at for a breakpoint target
     *
     * @param {{width: Number, height: (Number|null)}} target
     * @param {Object|null} rect
     * @returns {{width: Number, height: Number}|null} Null when the target leaves the height open and there is no rect
     */
    function targetSize(target, rect) {
        if (target.height !== null) {
            return {width: target.width, height: target.height};
        }
        if (!isRect(rect)) {
            return null;
        }

        return {width: target.width, height: Math.max(1, Math.round(target.width * rect.height / rect.width))};
    }

    /**
     * The aspect ratio a crop box must keep for a target; NaN when the target leaves it free
     *
     * @param {{width: Number, height: (Number|null)}} target
     * @returns {Number}
     */
    function aspectRatio(target) {
        return target.height ? target.width / target.height : NaN;
    }

    /**
     * A rectangle rounded to whole pixels and moved/shrunk to lie inside the image
     *
     * @param {{x: Number, y: Number, width: Number, height: Number}} rect
     * @param {{width: Number, height: Number}} image Natural size of the source image
     * @returns {{x: Number, y: Number, width: Number, height: Number}}
     */
    function clampRect(rect, image) {
        var width = Math.min(Math.max(1, Math.round(rect.width)), image.width),
            height = Math.min(Math.max(1, Math.round(rect.height)), image.height);

        return {
            x: Math.min(Math.max(0, Math.round(rect.x)), image.width - width),
            y: Math.min(Math.max(0, Math.round(rect.y)), image.height - height),
            width: width,
            height: height
        };
    }

    /**
     * The default crop of an image for a target: the largest centred rectangle with the target's aspect ratio, or the
     * whole image when the target leaves the height open
     *
     * @param {{width: Number, height: Number}} image Natural size of the source image
     * @param {{width: Number, height: (Number|null)}} target
     * @returns {{x: Number, y: Number, width: Number, height: Number}}
     */
    function initialRect(image, target) {
        var ratio = aspectRatio(target),
            width = image.width,
            height = image.height;

        if (!isNaN(ratio)) {
            height = Math.round(width / ratio);
            if (height > image.height) {
                height = image.height;
                width = Math.min(image.width, Math.round(height * ratio));
            }
        }
        width = Math.max(1, width);
        height = Math.max(1, height);

        return {
            x: Math.floor((image.width - width) / 2),
            y: Math.floor((image.height - height) / 2),
            width: width,
            height: height
        };
    }

    return {
        isRect: isRect,
        targetSize: targetSize,
        aspectRatio: aspectRatio,
        clampRect: clampRect,
        initialRect: initialRect
    };
});
