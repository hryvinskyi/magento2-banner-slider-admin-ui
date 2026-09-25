/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * What the crop editor shows: tab captions, the active crop's panel, its format rows and the image comparison, with
 * the admin wording. It reads crop states and never changes them, except through the format rows' observables, whose
 * edits it hands to a callback.
 */
define([
    'ko',
    'mage/translate',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/format-support',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/file-size'
], function (ko, $t, formatSupport, fileSize) {
    'use strict';

    /**
     * The target size of a breakpoint, `1920 × 600 px` or `1920 × auto`
     *
     * @param {{width: Number, height: (Number|null)}} target
     * @returns {String}
     */
    function sizeText(target) {
        return target.height === null
            ? $t('%1 × auto').replace('%1', target.width)
            : $t('%1 × %2 px').replace('%1', target.width).replace('%2', target.height);
    }

    /**
     * How much smaller or larger an image is than the fallback image
     *
     * @param {Number|null} originalBytes
     * @param {Number|null} bytes
     * @returns {String}
     */
    function savingsText(originalBytes, bytes) {
        var percent = fileSize.savingsPercent(originalBytes, bytes);

        if (percent === null) {
            return '';
        }

        return percent >= 0
            ? $t('%1% smaller').replace('%1', percent)
            : $t('%1% larger').replace('%1', -percent);
    }

    /**
     * The label of an extra format of a crop
     *
     * @param {Object} state
     * @param {String} code
     * @returns {String}
     */
    function formatLabel(state, code) {
        var format = state.formats.filter(function (candidate) {
            return candidate.code === code;
        })[0];

        return format ? format.label : code.toUpperCase();
    }

    /**
     * A preview image the page can show
     *
     * @param {{format: String, blob: Blob}} image
     * @returns {{code: String, url: String, size: Number}}
     */
    function previewImage(image) {
        return {code: image.format, url: URL.createObjectURL(image.blob), size: image.blob.size};
    }

    return {
        sizeText: sizeText,

        /**
         * The preview of an encoded crop (see crop-encoding): its fallback image and its extra formats
         *
         * @param {{original: (String|null), images: Array<{format: String, blob: Blob}>}} result
         * @returns {{original: (Object|null), variants: Array<Object>}}
         */
        preview: function (result) {
            return {
                original: result.images.filter(function (image) {
                    return image.format === result.original;
                }).map(previewImage)[0] || null,
                variants: result.images.filter(function (image) {
                    return image.format !== result.original;
                }).map(previewImage)
            };
        },

        /**
         * Free the images of a preview
         *
         * @param {{original: (Object|null), variants: Array<Object>}|null} preview
         * @returns {void}
         */
        releasePreview: function (preview) {
            if (preview) {
                [preview.original].concat(preview.variants).filter(Boolean).forEach(function (image) {
                    URL.revokeObjectURL(image.url);
                });
            }
        },

        /**
         * The breakpoint tabs
         *
         * @param {Array<Object>} states
         * @param {Number|null} activeId
         * @param {function(Object): Boolean} isChanged
         * @returns {Array<{id: Number, name: String, sizeText: String, active: Boolean, changed: Boolean}>}
         */
        tabs: function (states, activeId, isChanged) {
            return states.map(function (state) {
                return {
                    id: state.id,
                    name: state.name,
                    sizeText: sizeText(state.target),
                    active: state.id === activeId,
                    changed: isChanged(state)
                };
            });
        },

        /**
         * The panel of the active crop
         *
         * @param {Object} state
         * @param {{path: (String|null), url: (String|null)}} source Its effective source image
         * @returns {Object}
         */
        panel: function (state, source) {
            var fallback = formatSupport.originalFormatFor(formatSupport.sourceFormat(null, source.path));

            return {
                sizeText: sizeText(state.target),
                mediaQuery: state.mediaQuery,
                usesOwnImage: !!state.sourceImage,
                hasSource: !!source.url,
                enabled: state.enabled,
                remove: state.remove,
                canRemove: state.hasStoredCrop && !state.remove,
                fallbackText: $t('Fallback image: %1').replace('%1', fallback.toUpperCase())
            };
        },

        /**
         * The extra format rows of a crop, with observables that write edits back to the crop
         *
         * @param {Object} state
         * @param {Object<String, Boolean>} browserFormats Which formats this browser can encode
         * @param {Function} onChange Called after every edit
         * @returns {Array<Object>}
         */
        formatRows: function (state, browserFormats, onChange) {
            return state.formats.map(function (format) {
                var inBrowser = !!browserFormats[format.code],
                    row = {
                        code: format.code,
                        label: format.label,
                        selected: ko.observable(format.selected),
                        quality: ko.observable(format.quality),
                        disabled: !inBrowser && !format.encodable && !format.selected,
                        note: inBrowser ? '' : (format.encodable
                            ? $t('Generated on save')
                            : $t('Neither this browser nor the server can encode this format'))
                    };

                row.selected.subscribe(function (selected) {
                    format.selected = !!selected;
                    onChange();
                });
                row.quality.subscribe(function (value) {
                    var quality = parseInt(value, 10);

                    if (quality >= 1 && quality <= 100) {
                        format.quality = quality;
                        onChange();
                    }
                });

                return row;
            });
        },

        /**
         * The images to compare: the browser preview when there is one, else the stored crop files
         *
         * @param {Object} state
         * @param {{original: (Object|null), variants: Array<Object>}|null} preview
         * @param {String|null} chosenCode Format shown against the fallback image
         * @returns {Object|null} Null when there is nothing to compare
         */
        comparison: function (state, preview, chosenCode) {
            var original = preview
                    ? preview.original
                    : (state.stored.originalUrl ? {url: state.stored.originalUrl, size: null} : null),
                variants = preview
                    ? preview.variants
                    : Object.keys(state.stored.variants)
                        .filter(function (code) {
                            return !!state.stored.variants[code];
                        })
                        .map(function (code) {
                            return {code: code, url: state.stored.variants[code], size: null};
                        }),
                items,
                current;

            if (!original || !variants.length) {
                return null;
            }
            items = variants.map(function (variant) {
                return {
                    code: variant.code,
                    label: formatLabel(state, variant.code),
                    url: variant.url,
                    sizeText: variant.size ? fileSize.format(variant.size) : '',
                    savingsText: savingsText(original.size, variant.size),
                    larger: !!(original.size && variant.size > original.size)
                };
            });
            current = items.filter(function (item) {
                return item.code === chosenCode;
            })[0] || items[0];

            return {
                fromPreview: !!preview,
                originalUrl: original.url,
                originalSizeText: original.size ? fileSize.format(original.size) : '',
                items: items.map(function (item) {
                    return Object.assign({active: item === current}, item);
                }),
                current: current
            };
        }
    };
});
