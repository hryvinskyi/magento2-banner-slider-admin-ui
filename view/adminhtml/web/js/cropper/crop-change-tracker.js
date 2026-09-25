/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Which breakpoints' crops changed since the form was loaded.
 *
 * A crop is compared on two fingerprints:
 * - its images: the effective source (its own image, else the banner image), the crop area and the selected extra
 *   formats with their qualities; a change means new crop files are needed;
 * - its flags: shown on the storefront, marked for removal.
 * A crop that uses the banner image therefore changes when the banner image changes, and is unchanged again when
 * the admin puts the same image back. A breakpoint the tracker has no baseline for counts as changed.
 */
define([], function () {
    'use strict';

    /**
     * The fingerprints of a crop state
     *
     * @param {Object} state Crop state (see crop-payload)
     * @param {String|null} bannerImage Media path of the banner image
     * @returns {{images: String, flags: String}}
     */
    function fingerprint(state, bannerImage) {
        var rect = state.rect,
            formats = state.formats
                .filter(function (format) {
                    return format.selected;
                })
                .map(function (format) {
                    return format.code + ':' + format.quality;
                })
                .sort();

        return {
            images: JSON.stringify([
                state.sourceImage || bannerImage || null,
                rect ? [rect.x, rect.y, rect.width, rect.height] : null,
                formats
            ]),
            flags: JSON.stringify([!!state.enabled, !!state.remove])
        };
    }

    /**
     * A tracker with no baselines
     *
     * @returns {Object}
     */
    function create() {
        var baselines = {};

        /**
         * The baseline of a breakpoint, or null
         *
         * @param {Number} id
         * @returns {{images: String, flags: String}|null}
         */
        function baseline(id) {
            return Object.prototype.hasOwnProperty.call(baselines, String(id)) ? baselines[String(id)] : null;
        }

        return {
            /**
             * Take the current state of a breakpoint's crop as its unchanged state
             *
             * @param {Number} id Breakpoint id
             * @param {Object} state
             * @param {String|null} bannerImage
             * @returns {void}
             */
            remember: function (id, state, bannerImage) {
                baselines[String(id)] = fingerprint(state, bannerImage);
            },

            /**
             * Drop every baseline
             *
             * @returns {void}
             */
            forgetAll: function () {
                baselines = {};
            },

            /**
             * Whether anything about the crop changed
             *
             * @param {Number} id
             * @param {Object} state
             * @param {String|null} bannerImage
             * @returns {Boolean}
             */
            hasChanged: function (id, state, bannerImage) {
                var before = baseline(id),
                    now = fingerprint(state, bannerImage);

                return before === null || before.images !== now.images || before.flags !== now.flags;
            },

            /**
             * Whether the crop needs new files: its images changed and it is not being removed
             *
             * @param {Number} id
             * @param {Object} state
             * @param {String|null} bannerImage
             * @returns {Boolean}
             */
            imagesChanged: function (id, state, bannerImage) {
                var before = baseline(id);

                return !state.remove && (before === null || before.images !== fingerprint(state, bannerImage).images);
            }
        };
    }

    return {
        create: create
    };
});
