/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Turns one crop into the images the browser can encode for it.
 *
 * The source image is loaded, the crop area is taken as it is (kept inside the image) or, when there is none yet,
 * the default area for the breakpoint; the area is rendered at the breakpoint size and encoded as the fallback image
 * (see format-support for its format) and as every selected extra format this browser can encode.
 * Nothing here throws for a single image: an encoder that fails is reported in `failures`, a format the browser cannot
 * encode in `unavailable`, and a source that cannot be loaded in `loadError`. The server generates every image that
 * is missing from the post.
 *
 * The loader, the encoder registry and the base64 conversion are passed in, so this runs against fakes in tests.
 */
define([
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-geometry',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/format-support'
], function (geometry, formatSupport) {
    'use strict';

    /**
     * Encode a rendered crop as its fallback image and its extra formats, one after the other
     *
     * @param {*} canvas
     * @param {{original: String, variants: Array<{format: String, quality: Number}>}} request
     * @param {{isAvailable: Function, get: Function}} registry
     * @param {function(Blob): Promise<String>} toBase64
     * @returns {Promise<{original: String, images: Array<{format: String, blob: Blob, data: String}>,
     *     failures: Array<{format: String, error: Error}>, unavailable: Array<String>}>} `original` is the format
     *     code of the fallback image
     */
    function encodeCanvas(canvas, request, registry, toBase64) {
        var result = {original: request.original, images: [], failures: [], unavailable: []},
            jobs = [{format: request.original, quality: null}]
                .concat(formatSupport.variantsToEncode(request.variants, request.original));

        return jobs.reduce(function (chain, job) {
            return chain.then(function () {
                return registry.isAvailable(job.format).then(function (available) {
                    if (!available) {
                        result.unavailable.push(job.format);

                        return null;
                    }

                    return registry.get(job.format).encode(canvas, job.quality)
                        .then(function (blob) {
                            return toBase64(blob).then(function (data) {
                                result.images.push({format: job.format, blob: blob, data: data});
                            });
                        })
                        .catch(function (error) {
                            result.failures.push({format: job.format, error: error});
                        });
                });
            });
        }, Promise.resolve()).then(function () {
            return result;
        });
    }

    /**
     * Load, crop, render and encode one crop
     *
     * @param {Object} state Crop state (see crop-payload)
     * @param {{path: (String|null), url: (String|null), size: (Object|null)}} source The effective source image;
     *     `size` is its natural size when already known
     * @param {{loader: {load: Function, render: Function, release: Function}, registry: Object,
     *     toBase64: Function}} deps
     * @returns {Promise<{rect: (Object|null), original: (String|null), images: Array, failures: Array,
     *     unavailable: Array, loadError: (Error|null)}>} `rect` is the crop area to post; null when it cannot be
     *     known
     */
    function prepareCrop(state, source, deps) {
        var requests = state.formats
            .filter(function (format) {
                return format.selected;
            })
            .map(function (format) {
                return {format: format.code, quality: format.quality};
            });

        return deps.loader.load(source.url).then(function (image) {
            var rect = state.rect ? geometry.clampRect(state.rect, image) : geometry.initialRect(image, state.target),
                original = formatSupport.originalFormatFor(formatSupport.sourceFormat(image.mimeType, source.path));

            return Promise.resolve()
                .then(function () {
                    return deps.loader.render(image, rect, geometry.targetSize(state.target, rect));
                })
                .then(function (canvas) {
                    deps.loader.release(image);

                    return encodeCanvas(canvas, {original: original, variants: requests}, deps.registry, deps.toBase64);
                }, function (error) {
                    deps.loader.release(image);

                    return notPrepared(rect, error);
                })
                .then(function (encoded) {
                    return Object.assign({rect: rect, loadError: null}, encoded);
                });
        }, function (error) {
            var known = source.size ? geometry.initialRect(source.size, state.target) : null;

            return notPrepared(state.rect || known, error);
        });
    }

    /**
     * The result of a crop whose source could not be loaded or rendered
     *
     * @param {Object|null} rect
     * @param {Error} error
     * @returns {{rect: (Object|null), original: null, images: Array, failures: Array, unavailable: Array,
     *     loadError: Error}}
     */
    function notPrepared(rect, error) {
        return {rect: rect, original: null, images: [], failures: [], unavailable: [], loadError: error};
    }

    return {
        encodeCanvas: encodeCanvas,
        prepareCrop: prepareCrop
    };
});
