/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The crop editor's data contract with the banner form: what it receives and what it posts.
 *
 * Received: the editor config (endpoints, extra formats, default formats and qualities, size limits), the slider the
 * breakpoints belong to, and one entry per breakpoint with its stored crop. The entries are turned into plain crop
 * states:
 * `{id, name, identifier, mediaQuery, target: {width, height|null}, hasStoredCrop, rect|null, sourceImage|null,
 * sourceUrl|null, sourceSize|null, enabled, remove, formats: [{code, label, encodable, selected, quality}],
 * stored: {originalUrl|null, variants: {code: url}}}`.
 *
 * Posted (`responsive_crops`, a JSON list): `{breakpoint_id, remove, enabled, source_image|null,
 * crop: {x, y, width, height}|null, formats: [{format, quality}], encoded: [{format, data}]}`, where `crop` holds
 * whole pixels and is null only for a removal, and `data` is plain base64. Browser-encoded images are optional: the
 * server generates what is missing. The whole post is kept under 80 % of the server's request size limit by dropping
 * encoded images, largest first; an image larger than the image upload limit is dropped before that, because the
 * server would refuse it anyway.
 */
define([], function () {
    'use strict';

    var POST_BUDGET_SHARE = 0.8,
        REASON_TOO_LARGE = 'too_large',
        REASON_POST_LIMIT = 'post_limit';

    /**
     * A whole number from a number or a numeric string; null otherwise
     *
     * @param {*} value
     * @returns {Number|null}
     */
    function toInt(value) {
        var number = typeof value === 'string' && value.trim() !== '' ? Number(value) : value;

        return typeof number === 'number' && Number.isInteger(number) ? number : null;
    }

    /**
     * A boolean from a posted or received flag (`true`, `1`, `'1'`, …); the fallback when absent
     *
     * @param {*} value
     * @param {Boolean} fallback
     * @returns {Boolean}
     */
    function toFlag(value, fallback) {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }

        return value === true || value === 1 || value === '1' || value === 'true';
    }

    /**
     * A non-empty string, or null
     *
     * @param {*} value
     * @returns {String|null}
     */
    function toText(value) {
        return typeof value === 'string' && value !== '' ? value : null;
    }

    /**
     * A crop rectangle of whole pixels, or null when the value is not one
     *
     * @param {*} value
     * @returns {{x: Number, y: Number, width: Number, height: Number}|null}
     */
    function toRect(value) {
        var rect;

        if (!value || typeof value !== 'object') {
            return null;
        }
        rect = {x: toInt(value.x), y: toInt(value.y), width: toInt(value.width), height: toInt(value.height)};

        return rect.x !== null && rect.y !== null && rect.x >= 0 && rect.y >= 0 && rect.width > 0 && rect.height > 0
            ? rect
            : null;
    }

    /**
     * The editor config, normalised; an extra format without a valid default quality is not offered
     *
     * @param {Object} raw
     * @returns {Object}
     */
    function parseConfig(raw) {
        var source = raw || {},
            qualities = source.defaultQuality || {},
            formats = (Array.isArray(source.formats) ? source.formats : [])
                .map(function (format) {
                    var code = toText(format && format.code),
                        quality = code === null ? null : toInt(qualities[code]);

                    return code === null || quality === null || quality < 1 || quality > 100 ? null : {
                        code: code,
                        mime: toText(format.mime),
                        label: toText(format.label) || code.toUpperCase(),
                        encodable: toFlag(format.encodable, false),
                        defaultQuality: quality
                    };
                })
                .filter(Boolean),
            codes = formats.map(function (format) {
                return format.code;
            });

        return {
            breakpointsUrl: toText(source.breakpointsUrl),
            imageUploadUrl: toText(source.imageUploadUrl),
            imageUploadField: toText(source.imageUploadField),
            formats: formats,
            defaultFormats: (Array.isArray(source.defaultFormats) ? source.defaultFormats : [])
                .filter(function (code) {
                    return codes.indexOf(code) !== -1;
                }),
            maxUploadBytes: Math.max(0, toInt(source.maxUploadBytes) || 0),
            maxPostBytes: Math.max(0, toInt(source.maxPostBytes) || 0)
        };
    }

    /**
     * The crop state of one received breakpoint entry, or null when it has no valid id or target width
     *
     * @param {Object} raw
     * @param {Object} config Parsed config
     * @returns {Object|null}
     */
    function parseBreakpoint(raw, config) {
        var id = toInt(raw && raw.breakpoint_id),
            width = toInt(raw && raw.target_width),
            height = toInt(raw && raw.target_height),
            hasStoredCrop = !!(raw && raw.crop),
            variants = {},
            qualities = {};

        if (id === null || id < 1 || width === null || width < 1) {
            return null;
        }
        (Array.isArray(raw.variants) ? raw.variants : []).forEach(function (variant) {
            var code = toText(variant && variant.format);

            if (code !== null) {
                variants[code] = toText(variant.url);
                qualities[code] = toInt(variant.quality);
            }
        });

        return {
            id: id,
            name: toText(raw.name) || String(id),
            identifier: toText(raw.identifier) || '',
            mediaQuery: toText(raw.media_query) || '',
            target: {width: width, height: height !== null && height > 0 ? height : null},
            hasStoredCrop: hasStoredCrop,
            rect: toRect(raw.crop),
            sourceImage: toText(raw.source_image),
            sourceUrl: toText(raw.source_url),
            sourceSize: null,
            enabled: toFlag(raw.enabled, true),
            remove: false,
            formats: config.formats.map(function (format) {
                var stored = Object.prototype.hasOwnProperty.call(variants, format.code),
                    quality = stored ? qualities[format.code] : null;

                return {
                    code: format.code,
                    label: format.label,
                    encodable: format.encodable,
                    selected: hasStoredCrop ? stored : config.defaultFormats.indexOf(format.code) !== -1,
                    quality: quality !== null && quality >= 1 && quality <= 100 ? quality : format.defaultQuality
                };
            }),
            stored: {originalUrl: toText(raw.cropped_url), variants: variants}
        };
    }

    /**
     * The slider whose breakpoints the received editor state holds, as text; null for none
     *
     * A state that does not name its slider is taken to hold the breakpoints of the form's slider (`fallback`).
     *
     * @param {*} cropperData The received `responsive_cropper` value
     * @param {*} fallback The form's slider
     * @returns {String|null}
     */
    function stateSliderId(cropperData, fallback) {
        var named = cropperData !== null && typeof cropperData === 'object' && 'slider_id' in cropperData,
            id = toInt(named ? cropperData.slider_id : fallback);

        return id !== null && id > 0 ? String(id) : null;
    }

    /**
     * The crop states of the received breakpoint entries, in their order
     *
     * @param {Array} rawList
     * @param {Object} config Parsed config
     * @returns {Array<Object>}
     */
    function parseBreakpoints(rawList, config) {
        return (Array.isArray(rawList) ? rawList : [])
            .map(function (raw) {
                return parseBreakpoint(raw, config);
            })
            .filter(Boolean);
    }

    /**
     * A resolver from media paths to URLs, learned from known path/URL pairs; it answers null when no pair revealed
     * the media base URL
     *
     * @param {Array<{path: (String|null), url: (String|null)}>} pairs
     * @returns {function(String): (String|null)}
     */
    function mediaUrlResolver(pairs) {
        var base = null;

        pairs.some(function (pair) {
            var path = toText(pair.path),
                url = toText(pair.url);

            if (path !== null && url !== null && url.length > path.length
                && url.slice(url.length - path.length) === path
            ) {
                base = url.slice(0, url.length - path.length);
            }

            return base !== null;
        });

        return function (path) {
            return base === null || toText(path) === null ? null : base + path;
        };
    }

    /**
     * Apply the crop changes of a post the server refused (kept without its encoded images) to the states
     *
     * A changed own source image is applied only when its URL can be resolved, since the editor must show it.
     *
     * @param {Array<Object>} states Mutated in place
     * @param {String|null} json The refused `responsive_crops` value
     * @param {function(String): (String|null)} resolveUrl
     * @returns {Array<Number>} Ids of the restored breakpoints
     */
    function restorePending(states, json, resolveUrl) {
        var entries,
            restored = [];

        try {
            entries = typeof json === 'string' && json !== '' ? JSON.parse(json) : [];
        } catch (error) {
            return restored;
        }
        (Array.isArray(entries) ? entries : []).forEach(function (entry) {
            var id = toInt(entry && entry.breakpoint_id),
                state = states.filter(function (candidate) {
                    return candidate.id === id;
                })[0],
                source,
                url,
                requested;

            if (!state) {
                return;
            }
            source = toText(entry.source_image);
            url = source === null ? null : resolveUrl(source);
            if (source === null || url !== null) {
                state.sourceImage = source;
                state.sourceUrl = url;
            }
            state.remove = toFlag(entry.remove, false);
            state.enabled = toFlag(entry.enabled, true);
            state.rect = toRect(entry.crop) || state.rect;
            requested = Array.isArray(entry.formats) ? entry.formats : [];
            state.formats.forEach(function (format) {
                var match = requested.filter(function (request) {
                        return request && request.format === format.code;
                    })[0],
                    quality = match ? toInt(match.quality) : null;

                format.selected = !!match;
                if (quality !== null && quality >= 1 && quality <= 100) {
                    format.quality = quality;
                }
            });
            restored.push(state.id);
        });

        return restored;
    }

    /**
     * What a changed crop posts: `remove` when it is marked for removal or its stored crop lost its source image,
     * `skip` when there is nothing to crop and nothing stored, `save` otherwise
     *
     * @param {Object} state
     * @param {Boolean} hasSource Whether an effective source image exists
     * @returns {String}
     */
    function planAction(state, hasSource) {
        if (state.remove || (!hasSource && state.hasStoredCrop)) {
            return 'remove';
        }

        return hasSource ? 'save' : 'skip';
    }

    /**
     * The selected extra formats of a state as format requests
     *
     * @param {Object} state
     * @returns {Array<{format: String, quality: Number}>}
     */
    function formatRequests(state) {
        return state.formats
            .filter(function (format) {
                return format.selected;
            })
            .map(function (format) {
                return {format: format.code, quality: format.quality};
            });
    }

    /**
     * The post entry that removes a breakpoint's crop
     *
     * @param {Object} state
     * @returns {Object}
     */
    function removalEntry(state) {
        return {
            breakpoint_id: state.id,
            remove: true,
            enabled: !!state.enabled,
            source_image: state.sourceImage,
            crop: null,
            formats: [],
            encoded: []
        };
    }

    /**
     * The post entry that saves a breakpoint's crop
     *
     * @param {Object} state
     * @param {{x: Number, y: Number, width: Number, height: Number}} rect Crop area in whole pixels
     * @param {Array<{format: String, data: String}>} encoded Browser-encoded images, plain base64
     * @returns {Object}
     * @throws {TypeError} When the crop area is not made of whole pixels
     */
    function saveEntry(state, rect, encoded) {
        var crop = toRect(rect);

        if (crop === null || crop.x !== rect.x || crop.y !== rect.y || crop.width !== rect.width
            || crop.height !== rect.height
        ) {
            throw new TypeError('The crop area of breakpoint ' + state.id + ' must be given in whole pixels.');
        }

        return {
            breakpoint_id: state.id,
            remove: false,
            enabled: !!state.enabled,
            source_image: state.sourceImage,
            crop: crop,
            formats: formatRequests(state),
            encoded: (encoded || []).map(function (image) {
                return {format: image.format, data: image.data};
            })
        };
    }

    /**
     * The number of bytes a string takes in UTF-8
     *
     * @param {String} text
     * @returns {Number}
     */
    function utf8Length(text) {
        var bytes = 0,
            index,
            code;

        for (index = 0; index < text.length; index++) {
            code = text.charCodeAt(index);
            if (code < 0x80) {
                bytes += 1;
            } else if (code < 0x800) {
                bytes += 2;
            } else if (code >= 0xD800 && code <= 0xDBFF && index + 1 < text.length) {
                bytes += 4;
                index++;
            } else {
                bytes += 3;
            }
        }

        return bytes;
    }

    /**
     * The size in bytes of form data as it is posted, without one field; 0 when it cannot be measured
     *
     * @param {Object} data
     * @param {String} without
     * @returns {Number}
     */
    function formBytes(data, without) {
        var rest = Object.assign({}, data);

        delete rest[without];
        try {
            return utf8Length(JSON.stringify(rest));
        } catch (error) {
            return 0;
        }
    }

    /**
     * The number of bytes plain base64 decodes to
     *
     * @param {String} data
     * @returns {Number}
     */
    function decodedSize(data) {
        var padding = data.slice(-2) === '==' ? 2 : (data.slice(-1) === '=' ? 1 : 0);

        return Math.max(0, Math.floor(data.length * 3 / 4) - padding);
    }

    /**
     * The post entries with the encoded images that would break a server limit removed
     *
     * @param {Array<Object>} entries Post entries (left unchanged)
     * @param {{maxPostBytes: Number, maxUploadBytes: Number, otherBytes: Number}} limits `otherBytes` is the size of
     *     the rest of the form post; a limit of 0 means none
     * @returns {{entries: Array<Object>, dropped: Array<{breakpointId: Number, format: String, reason: String}>,
     *     fits: Boolean}} `fits` is false when the post stays over the budget even without encoded images
     */
    function fitBudget(entries, limits) {
        var dropped = [],
            budget = limits.maxPostBytes > 0 ? Math.floor(limits.maxPostBytes * POST_BUDGET_SHARE) : 0,
            kept = entries.map(function (entry) {
                var copy = Object.assign({}, entry);

                copy.encoded = entry.encoded.filter(function (image) {
                    if (limits.maxUploadBytes > 0 && decodedSize(image.data) > limits.maxUploadBytes) {
                        dropped.push({
                            breakpointId: entry.breakpoint_id,
                            format: image.format,
                            reason: REASON_TOO_LARGE
                        });

                        return false;
                    }

                    return true;
                });

                return copy;
            }),
            size = function () {
                return (limits.otherBytes || 0) + utf8Length(JSON.stringify(kept));
            },
            largest;

        while (budget > 0 && size() > budget) {
            largest = null;
            kept.forEach(function (entry) {
                entry.encoded.forEach(function (image) {
                    if (largest === null || image.data.length > largest.image.data.length) {
                        largest = {entry: entry, image: image};
                    }
                });
            });
            if (largest === null) {
                break;
            }
            largest.entry.encoded = largest.entry.encoded.filter(function (image) {
                return image !== largest.image;
            });
            dropped.push({
                breakpointId: largest.entry.breakpoint_id,
                format: largest.image.format,
                reason: REASON_POST_LIMIT
            });
        }

        return {entries: kept, dropped: dropped, fits: budget === 0 || size() <= budget};
    }

    return {
        REASON_TOO_LARGE: REASON_TOO_LARGE,
        REASON_POST_LIMIT: REASON_POST_LIMIT,
        parseConfig: parseConfig,
        parseBreakpoints: parseBreakpoints,
        stateSliderId: stateSliderId,
        mediaUrlResolver: mediaUrlResolver,
        restorePending: restorePending,
        planAction: planAction,
        removalEntry: removalEntry,
        saveEntry: saveEntry,
        utf8Length: utf8Length,
        formBytes: formBytes,
        decodedSize: decodedSize,
        fitBudget: fitBudget
    };
});
