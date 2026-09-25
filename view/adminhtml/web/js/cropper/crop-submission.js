/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Decides what the banner form posts for its crops and prepares it.
 *
 * Only crops that changed since the form loaded are posted, one after the other:
 * - a crop marked for removal, or a stored crop whose source image is gone, posts a removal;
 * - a crop with nothing to cut from and nothing stored posts nothing;
 * - a crop whose images changed is encoded in the browser (see crop-encoding) and posted with what could be encoded;
 * - a crop whose flags alone changed is posted with its area and no images;
 * - a crop whose area cannot be known (its source neither loads nor has a known size) is not posted.
 * Everything the admin should know comes back as notices `{kind, breakpointId, format|null}`; the caller words them.
 * Finally the post is fitted to the server limits (see crop-payload), and every dropped image becomes a notice too.
 */
define([
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-payload',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-encoding'
], function (payload, cropEncoding) {
    'use strict';

    var SOURCE_NOT_LOADED = 'source_not_loaded',
        NOT_ENCODED = 'not_encoded',
        NO_AREA = 'no_area';

    /**
     * A notice about a crop
     *
     * @param {String} kind
     * @param {Number} breakpointId
     * @param {String|null} format
     * @returns {{kind: String, breakpointId: Number, format: (String|null)}}
     */
    function notice(kind, breakpointId, format) {
        return {kind: kind, breakpointId: breakpointId, format: format};
    }

    /**
     * The notices of what could not be done in the browser for a prepared crop
     *
     * @param {Object} state
     * @param {Object} result Result of crop-encoding's prepareCrop()
     * @returns {Array<Object>}
     */
    function encodingNotices(state, result) {
        var notices = result.failures.map(function (failure) {
            return notice(NOT_ENCODED, state.id, failure.format);
        });

        return result.loadError ? [notice(SOURCE_NOT_LOADED, state.id, null)].concat(notices) : notices;
    }

    /**
     * Add the post entry of one changed crop
     *
     * @param {Object} state
     * @param {Object} context See collect()
     * @param {{entries: Array<Object>, notices: Array<Object>}} post
     * @returns {Promise<Object>} The post
     */
    function collectOne(state, context, post) {
        var source = context.sourceOf(state),
            action = payload.planAction(state, !!source.url);

        if (action === 'remove') {
            post.entries.push(payload.removalEntry(state));
        }
        if (action !== 'save') {
            return Promise.resolve(post);
        }
        if (!context.tracker.imagesChanged(state.id, state, context.bannerPath)) {
            if (state.rect) {
                post.entries.push(payload.saveEntry(state, state.rect, []));
            }

            return Promise.resolve(post);
        }

        return cropEncoding.prepareCrop(state, source, context.encoding).then(function (result) {
            post.notices = post.notices.concat(encodingNotices(state, result));
            if (!result.rect) {
                post.notices.push(notice(NO_AREA, state.id, null));

                return post;
            }
            state.rect = result.rect;
            post.entries.push(payload.saveEntry(state, result.rect, result.images));

            return post;
        });
    }

    /**
     * The post entries of every changed crop, with notices
     *
     * @param {Array<Object>} states Crop states; a prepared crop keeps the area it was posted with
     * @param {{tracker: Object, bannerPath: (String|null), sourceOf: Function, encoding: Object}} context
     *     `encoding` holds crop-encoding's dependencies (loader, registry, toBase64)
     * @returns {Promise<{entries: Array<Object>, notices: Array<Object>}>}
     */
    function collect(states, context) {
        return states
            .filter(function (state) {
                return context.tracker.hasChanged(state.id, state, context.bannerPath);
            })
            .reduce(function (chain, state) {
                return chain.then(function (post) {
                    return collectOne(state, context, post);
                });
            }, Promise.resolve({entries: [], notices: []}));
    }

    /**
     * Fit a collected post to the server limits
     *
     * @param {{entries: Array<Object>, notices: Array<Object>}} post
     * @param {{maxPostBytes: Number, maxUploadBytes: Number, otherBytes: Number}} limits
     * @returns {{entries: Array<Object>, notices: Array<Object>, fits: Boolean}}
     */
    function fit(post, limits) {
        var fitted = payload.fitBudget(post.entries, limits);

        return {
            entries: fitted.entries,
            fits: fitted.fits,
            notices: post.notices.concat(fitted.dropped.map(function (dropped) {
                return notice(dropped.reason, dropped.breakpointId, dropped.format);
            }))
        };
    }

    return {
        SOURCE_NOT_LOADED: SOURCE_NOT_LOADED,
        NOT_ENCODED: NOT_ENCODED,
        NO_AREA: NO_AREA,
        encodingNotices: encodingNotices,
        collect: collect,
        fit: fit
    };
});
