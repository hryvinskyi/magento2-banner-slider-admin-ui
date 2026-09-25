/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The crop editor's state: the crop of every breakpoint, the banner image they fall back to, and what changed.
 *
 * - The form's own breakpoints are loaded with their stored crops as the unchanged state; a refused save's crop
 *   changes are then applied on top, so they are posted again.
 * - The breakpoints of a newly chosen slider have no crop for the banner yet, so each of them is a change as soon
 *   as there is an image to cut it from.
 * - Replacing or removing the banner image clears the area of every crop cut from it, so each starts over from the
 *   default area of its breakpoint; giving a crop its own image, or taking it away, does the same for that crop.
 * - A banner image without a stored path (a file the uploader did not upload itself) is an unknown source, not a
 *   removed image: the crops keep the image they had, so no crop is removed for it. The server refuses such a file.
 */
define([
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-payload',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-change-tracker'
], function (payload, changeTracker) {
    'use strict';

    var NO_SOURCE = {path: null, url: null, size: null};

    /**
     * The banner image as a crop source, from the image uploader value (a list of uploaded files)
     *
     * @param {*} uploaderValue
     * @returns {{path: (String|null), url: (String|null), size: (Object|null)}|null} Null when the uploader holds a
     *     file without a stored path, whose source is unknown
     */
    function bannerSourceOf(uploaderValue) {
        var file = Array.isArray(uploaderValue) && uploaderValue.length ? uploaderValue[0] : null;

        if (!file || typeof file !== 'object') {
            return NO_SOURCE;
        }
        if (!file.file) {
            return null;
        }

        return {
            path: String(file.file),
            url: file.url ? String(file.url) : null,
            size: file.width > 0 && file.height > 0 ? {width: file.width, height: file.height} : null
        };
    }

    /**
     * An editor state for the given parsed config
     *
     * @param {Object} settings Parsed editor config (crop-payload)
     * @returns {Object}
     */
    function create(settings) {
        var tracker = changeTracker.create(),
            model = {
                states: [],
                banner: NO_SOURCE,
                tracker: tracker
            };

        /**
         * The crop of a breakpoint
         *
         * @param {Number|null} id
         * @returns {Object|null}
         */
        model.byId = function (id) {
            return model.states.filter(function (state) {
                return state.id === id;
            })[0] || null;
        };

        /**
         * The name of a breakpoint, for messages; its id when it is not loaded
         *
         * @param {Number} id
         * @returns {String}
         */
        model.nameOf = function (id) {
            var state = model.byId(id);

            return state ? state.name : String(id);
        };

        /**
         * The effective source image of a crop: its own image, else the banner image
         *
         * @param {Object} state
         * @returns {{path: (String|null), url: (String|null), size: (Object|null)}}
         */
        model.sourceOf = function (state) {
            return state.sourceImage
                ? {path: state.sourceImage, url: state.sourceUrl, size: state.sourceSize}
                : model.banner;
        };

        /**
         * Whether anything about a crop changed since it was loaded
         *
         * @param {Object} state
         * @returns {Boolean}
         */
        model.isChanged = function (state) {
            return tracker.hasChanged(state.id, state, model.banner.path);
        };

        /**
         * Replace the crops with received breakpoint entries
         *
         * @param {Array} rawList
         * @param {Boolean} fromForm True for the form's own data, false for a newly chosen slider's breakpoints
         * @param {String|null} pendingJson The crop changes of a refused save (form data only)
         * @returns {void}
         */
        model.load = function (rawList, fromForm, pendingJson) {
            var states = payload.parseBreakpoints(rawList, settings),
                resolveUrl;

            tracker.forgetAll();
            states.forEach(function (state) {
                tracker.remember(state.id, state, fromForm ? model.banner.path : null);
            });
            model.states = states;
            if (fromForm) {
                resolveUrl = payload.mediaUrlResolver([{path: model.banner.path, url: model.banner.url}].concat(
                    states.map(function (state) {
                        return {path: state.sourceImage, url: state.sourceUrl};
                    })
                ));
                payload.restorePending(states, pendingJson, resolveUrl);
            }
        };

        /**
         * Take a new banner image uploader value; an unknown source leaves the banner image as it was
         *
         * @param {*} uploaderValue
         * @returns {Array<Object>} The crops cut from the banner image, when the image changed; else none
         */
        model.setBannerImage = function (uploaderValue) {
            var next = bannerSourceOf(uploaderValue),
                changed;

            if (next === null) {
                return [];
            }
            changed = next.path !== model.banner.path;
            model.banner = next;
            if (!changed) {
                return [];
            }

            return model.states.filter(function (state) {
                if (state.sourceImage) {
                    return false;
                }
                state.rect = null;

                return true;
            });
        };

        /**
         * Give a crop another source image (null: the banner image); its area starts over
         *
         * @param {Object} state
         * @param {String|null} path
         * @param {String|null} url
         * @param {Object|null} size Natural size, when known
         * @returns {void}
         */
        model.setSource = function (state, path, url, size) {
            state.sourceImage = path;
            state.sourceUrl = path === null ? null : url;
            state.sourceSize = path === null ? null : size;
            state.rect = null;
        };

        return model;
    }

    return {
        bannerSourceOf: bannerSourceOf,
        create: create
    };
});
