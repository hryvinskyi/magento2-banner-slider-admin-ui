/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/*
 * Behaviour tests for the admin crop editor's pure modules. Plain node, no dependencies:
 * `node Test/Js/run.mjs`. Prints `N passed`, exits non-zero when a test fails.
 *
 * The modules are AMD; a minimal `define` shim loads them from view/adminhtml/web/js/ and resolves their
 * dependencies by module id. Anything outside this module (Magento UI, jQuery, Knockout, translation) has no place
 * in a pure module, so the shim refuses it.
 */

import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {dirname, join} from 'node:path';
import {fileURLToPath} from 'node:url';

const packageDir = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const MODULE_PREFIX = 'Hryvinskyi_BannerSliderAdminUi/js/';
const moduleCache = new Map();

/**
 * Load an AMD module of this package by its id
 *
 * @param {string} id
 * @return {*}
 */
function requireModule(id) {
    if (moduleCache.has(id)) {
        return moduleCache.get(id);
    }
    if (!id.startsWith(MODULE_PREFIX)) {
        throw new Error(`A pure module may not depend on "${id}".`);
    }
    const file = join(packageDir, 'view/adminhtml/web/js', id.slice(MODULE_PREFIX.length) + '.js');
    let definition = null;
    const define = (dependencies, factory) => {
        definition = typeof dependencies === 'function'
            ? {dependencies: [], factory: dependencies}
            : {dependencies, factory};
    };
    define.amd = {};
    new Function('define', readFileSync(file, 'utf8'))(define);
    const exported = definition.factory(...definition.dependencies.map(requireModule));
    moduleCache.set(id, exported);

    return exported;
}

const load = (name) => requireModule(MODULE_PREFIX + name);
const geometry = load('cropper/crop-geometry');
const payload = load('cropper/crop-payload');
const changeTracker = load('cropper/crop-change-tracker');
const formatSupport = load('cropper/format-support');
const fileSize = load('cropper/file-size');
const cropEncoding = load('cropper/crop-encoding');
const cropSubmission = load('cropper/crop-submission');
const editorModel = load('cropper/crop-editor-model');
const httpClient = load('service/http-client');
const valueRules = load('validation/value-rules');

const tests = [];

/**
 * Register a test
 *
 * @param {string} name
 * @param {Function} body
 */
function test(name, body) {
    tests.push({name, body});
}

/* ------------------------------------------------------------------------------------------------------------ */
/* Fixtures                                                                                                      */
/* ------------------------------------------------------------------------------------------------------------ */

const CONFIG = payload.parseConfig({
    breakpointsUrl: 'https://admin.test/banner_slider/responsivecrop/breakpoints/key/abc/',
    imageUploadUrl: 'https://admin.test/banner_slider/breakpoint/imageupload/key/def/',
    imageUploadField: 'breakpoint_image',
    formats: [
        {code: 'avif', mime: 'image/avif', label: 'AVIF', encodable: false},
        {code: 'webp', mime: 'image/webp', label: 'WEBP', encodable: true}
    ],
    defaultFormats: ['webp'],
    defaultQuality: {webp: 85, avif: 80},
    maxUploadBytes: 1000,
    maxPostBytes: 0
});

/**
 * A received breakpoint entry
 *
 * @param {Object} overrides
 * @return {Object}
 */
function rawBreakpoint(overrides = {}) {
    return {
        breakpoint_id: 3,
        name: 'Desktop',
        identifier: 'desktop',
        media_query: '(min-width: 1024px)',
        min_width: 1024,
        target_width: 1920,
        target_height: 800,
        sort_order: 0,
        enabled: true,
        crop: null,
        source_image: null,
        source_url: null,
        variants: [],
        cropped_url: null,
        ...overrides
    };
}

/**
 * A crop state
 *
 * @param {Object} rawOverrides
 * @param {Object} stateOverrides
 * @return {Object}
 */
function state(rawOverrides = {}, stateOverrides = {}) {
    return Object.assign(payload.parseBreakpoints([rawBreakpoint(rawOverrides)], CONFIG)[0], stateOverrides);
}

const STORED_CROP = {
    crop: {x: 10, y: 20, width: 1200, height: 500},
    variants: [{format: 'avif', quality: 60, url: 'https://media.test/media/banner_slider/responsive/1/a.avif'}],
    cropped_url: 'https://media.test/media/banner_slider/responsive/1/a.jpg'
};

/* ------------------------------------------------------------------------------------------------------------ */
/* crop-geometry                                                                                                 */
/* ------------------------------------------------------------------------------------------------------------ */

test('geometry: a fixed target keeps its size and ratio', () => {
    const target = {width: 1920, height: 800};

    assert.deepEqual(geometry.targetSize(target, {x: 0, y: 0, width: 300, height: 300}), {width: 1920, height: 800});
    assert.equal(geometry.aspectRatio(target), 2.4);
});

test('geometry: a null target height follows the crop ratio, rounded and at least 1', () => {
    const target = {width: 768, height: null};

    assert.deepEqual(geometry.targetSize(target, {x: 0, y: 0, width: 1000, height: 333}), {width: 768, height: 256});
    assert.deepEqual(geometry.targetSize(target, {x: 0, y: 0, width: 5000, height: 1}), {width: 768, height: 1});
    assert.equal(geometry.targetSize(target, null), null);
    assert.ok(Number.isNaN(geometry.aspectRatio(target)));
});

test('geometry: clamp rounds to whole pixels and keeps the rect inside the image', () => {
    const image = {width: 1000, height: 500};

    assert.deepEqual(geometry.clampRect({x: 900.6, y: -3, width: 200.4, height: 120.5}, image),
        {x: 800, y: 0, width: 200, height: 121});
    assert.deepEqual(geometry.clampRect({x: 5, y: 5, width: 4000, height: 0}, image),
        {x: 0, y: 5, width: 1000, height: 1});
});

test('geometry: the initial rect is the largest centred area with the target ratio', () => {
    assert.deepEqual(geometry.initialRect({width: 2000, height: 2000}, {width: 1920, height: 800}),
        {x: 0, y: 583, width: 2000, height: 833});
    assert.deepEqual(geometry.initialRect({width: 1000, height: 200}, {width: 400, height: 400}),
        {x: 400, y: 0, width: 200, height: 200});
    assert.deepEqual(geometry.initialRect({width: 640, height: 480}, {width: 320, height: null}),
        {x: 0, y: 0, width: 640, height: 480});
});

test('geometry: isRect accepts only whole, positive, non-negative rects', () => {
    assert.equal(geometry.isRect({x: 0, y: 0, width: 1, height: 1}), true);
    assert.equal(geometry.isRect({x: 0.5, y: 0, width: 1, height: 1}), false);
    assert.equal(geometry.isRect({x: -1, y: 0, width: 1, height: 1}), false);
    assert.equal(geometry.isRect(null), false);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* crop-payload                                                                                                  */
/* ------------------------------------------------------------------------------------------------------------ */

test('payload: config keeps formats with a valid default quality and known default formats', () => {
    const config = payload.parseConfig({
        formats: [{code: 'webp', encodable: 1}, {code: 'jxl'}, {code: 'avif', encodable: '0'}],
        defaultFormats: ['jxl', 'avif'],
        defaultQuality: {webp: 85, avif: '80', jxl: 0},
        maxUploadBytes: '2048',
        maxPostBytes: -5
    });

    assert.deepEqual(config.formats.map((format) => [format.code, format.label, format.encodable, format.defaultQuality]),
        [['webp', 'WEBP', true, 85], ['avif', 'AVIF', false, 80]]);
    assert.deepEqual(config.defaultFormats, ['avif']);
    assert.equal(config.maxUploadBytes, 2048);
    assert.equal(config.maxPostBytes, 0);
});

test('payload: a breakpoint without a crop selects the default formats at default quality', () => {
    const crop = state();

    assert.equal(crop.hasStoredCrop, false);
    assert.equal(crop.rect, null);
    assert.equal(crop.enabled, true);
    assert.deepEqual(crop.target, {width: 1920, height: 800});
    assert.deepEqual(crop.formats.map((format) => [format.code, format.selected, format.quality]),
        [['avif', false, 80], ['webp', true, 85]]);
});

test('payload: a stored crop selects exactly its stored variants with their quality', () => {
    const crop = state({...STORED_CROP, target_height: null, enabled: false});

    assert.equal(crop.hasStoredCrop, true);
    assert.deepEqual(crop.rect, {x: 10, y: 20, width: 1200, height: 500});
    assert.equal(crop.enabled, false);
    assert.equal(crop.target.height, null);
    assert.deepEqual(crop.formats.map((format) => [format.code, format.selected, format.quality]),
        [['avif', true, 60], ['webp', false, 85]]);
    assert.equal(crop.stored.originalUrl, STORED_CROP.cropped_url);
});

test('payload: entries without an id or target width are dropped', () => {
    assert.equal(payload.parseBreakpoints([rawBreakpoint({breakpoint_id: null}), rawBreakpoint({target_width: 0}),
        'nonsense'], CONFIG).length, 0);
});

test('payload: a save entry has the contract shape with integer rect and only selected formats', () => {
    const crop = state({source_image: 'banner_slider/image/own.png'});
    const entry = payload.saveEntry(crop, {x: 1, y: 2, width: 300, height: 125}, [
        {format: 'png', data: 'AAAA', blob: {}},
        {format: 'webp', data: 'BBBB'}
    ]);

    assert.deepEqual(entry, {
        breakpoint_id: 3,
        remove: false,
        enabled: true,
        source_image: 'banner_slider/image/own.png',
        crop: {x: 1, y: 2, width: 300, height: 125},
        formats: [{format: 'webp', quality: 85}],
        encoded: [{format: 'png', data: 'AAAA'}, {format: 'webp', data: 'BBBB'}]
    });
});

test('payload: a save entry refuses a rect that is not whole pixels', () => {
    assert.throws(() => payload.saveEntry(state(), {x: 1.5, y: 0, width: 10, height: 10}, []), TypeError);
    assert.throws(() => payload.saveEntry(state(), {x: '1', y: 0, width: 10, height: 10}, []), TypeError);
});

test('payload: a removal entry carries the remove flag and no crop, formats or images', () => {
    assert.deepEqual(payload.removalEntry(state(STORED_CROP)), {
        breakpoint_id: 3,
        remove: true,
        enabled: true,
        source_image: null,
        crop: null,
        formats: [],
        encoded: []
    });
});

test('payload: planAction removes marked crops and stored crops that lost their source', () => {
    assert.equal(payload.planAction(state({}, {remove: true}), true), 'remove');
    assert.equal(payload.planAction(state(STORED_CROP), false), 'remove');
    assert.equal(payload.planAction(state(), false), 'skip');
    assert.equal(payload.planAction(state(), true), 'save');
});

test('payload: the media URL resolver learns the base from a known pair', () => {
    const resolve = payload.mediaUrlResolver([
        {path: null, url: null},
        {path: 'banner_slider/image/a.jpg', url: 'https://media.test/media/banner_slider/image/a.jpg'}
    ]);

    assert.equal(resolve('banner_slider/image/b.png'), 'https://media.test/media/banner_slider/image/b.png');
    assert.equal(payload.mediaUrlResolver([])('x.png'), null);
});

test('payload: a refused post restores rects, formats, flags and resolvable sources', () => {
    const states = [state(STORED_CROP), state({breakpoint_id: 4, name: 'Mobile'})];
    const resolve = payload.mediaUrlResolver([{path: 'p/a.jpg', url: 'https://m.test/media/p/a.jpg'}]);
    const restored = payload.restorePending(states, JSON.stringify([
        {breakpoint_id: 3, remove: false, enabled: false, source_image: 'banner_slider/image/own.png',
            crop: {x: 0, y: 0, width: 50, height: 20}, formats: [{format: 'webp', quality: 70}]},
        {breakpoint_id: 4, remove: true, enabled: true, source_image: null, crop: null, formats: []},
        {breakpoint_id: 99, remove: true}
    ]), resolve);

    assert.deepEqual(restored, [3, 4]);
    assert.deepEqual(states[0].rect, {x: 0, y: 0, width: 50, height: 20});
    assert.equal(states[0].enabled, false);
    assert.equal(states[0].sourceImage, 'banner_slider/image/own.png');
    assert.equal(states[0].sourceUrl, 'https://m.test/media/banner_slider/image/own.png');
    assert.deepEqual(states[0].formats.map((format) => [format.code, format.selected, format.quality]),
        [['avif', false, 60], ['webp', true, 70]]);
    assert.equal(states[1].remove, true);
    assert.deepEqual(payload.restorePending(states, '{broken', resolve), []);
});

test('payload: a restored own source whose URL cannot be resolved is not applied', () => {
    const states = [state()];

    payload.restorePending(states, JSON.stringify([{breakpoint_id: 3, source_image: 'banner_slider/image/x.png',
        crop: {x: 0, y: 0, width: 5, height: 5}, formats: []}]), payload.mediaUrlResolver([]));

    assert.equal(states[0].sourceImage, null);
    assert.deepEqual(states[0].rect, {x: 0, y: 0, width: 5, height: 5});
});

test('payload: utf8Length and decodedSize measure what the server receives', () => {
    assert.equal(payload.utf8Length('aé€😀'), 1 + 2 + 3 + 4);
    assert.equal(payload.decodedSize('QUJD'), 3);
    assert.equal(payload.decodedSize('QUI='), 2);
    assert.equal(payload.decodedSize('QQ=='), 1);
    assert.equal(payload.formBytes({a: 'x', skip: 'yyyy'}, 'skip'), '{"a":"x"}'.length);
});

/**
 * A save entry with encoded images of the given data lengths
 *
 * @param {number} id
 * @param {Object<string, number>} lengths
 * @return {Object}
 */
function encodedEntry(id, lengths) {
    return {
        breakpoint_id: id,
        remove: false,
        enabled: true,
        source_image: null,
        crop: {x: 0, y: 0, width: 10, height: 10},
        formats: [],
        encoded: Object.keys(lengths).map((format) => ({format, data: 'A'.repeat(lengths[format])}))
    };
}

test('payload: the size budget drops the largest encoded images first and keeps everything else', () => {
    const entries = [encodedEntry(1, {png: 300, webp: 100}), encodedEntry(2, {jpeg: 400, avif: 50})];
    const baseline = payload.utf8Length(JSON.stringify(entries));
    const withoutLargestTwo = baseline - 700;
    const result = payload.fitBudget(entries, {
        maxPostBytes: Math.ceil((withoutLargestTwo + 20) / 0.8),
        maxUploadBytes: 0,
        otherBytes: 0
    });

    assert.deepEqual(result.dropped, [
        {breakpointId: 2, format: 'jpeg', reason: payload.REASON_POST_LIMIT},
        {breakpointId: 1, format: 'png', reason: payload.REASON_POST_LIMIT}
    ]);
    assert.deepEqual(result.entries.map((entry) => entry.encoded.map((image) => image.format)), [['webp'], ['avif']]);
    assert.deepEqual(result.entries.map((entry) => entry.crop), [entries[0].crop, entries[1].crop]);
    assert.equal(result.fits, true);
    assert.equal(entries[0].encoded.length, 2, 'the given entries are left unchanged');
});

test('payload: the budget counts the rest of the form and reports a post that cannot fit', () => {
    const entries = [encodedEntry(1, {png: 10})];
    const result = payload.fitBudget(entries, {maxPostBytes: 100, maxUploadBytes: 0, otherBytes: 500});

    assert.equal(result.fits, false);
    assert.deepEqual(result.entries[0].encoded, []);
});

test('payload: no post limit keeps every image; images over the upload limit are dropped first', () => {
    const unlimited = payload.fitBudget([encodedEntry(1, {png: 5000})], {maxPostBytes: 0, maxUploadBytes: 0});
    const capped = payload.fitBudget([encodedEntry(1, {png: 1400, webp: 1200})],
        {maxPostBytes: 0, maxUploadBytes: 1000, otherBytes: 0});

    assert.equal(unlimited.entries[0].encoded.length, 1);
    assert.equal(unlimited.fits, true);
    assert.deepEqual(capped.dropped, [{breakpointId: 1, format: 'png', reason: payload.REASON_TOO_LARGE}]);
    assert.deepEqual(capped.entries[0].encoded.map((image) => image.format), ['webp']);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* crop-change-tracker                                                                                           */
/* ------------------------------------------------------------------------------------------------------------ */

test('tracker: an untouched crop is unchanged; a moved rect changes its images', () => {
    const tracker = changeTracker.create();
    const crop = state(STORED_CROP);

    tracker.remember(3, crop, 'banner_slider/image/a.jpg');
    assert.equal(tracker.hasChanged(3, crop, 'banner_slider/image/a.jpg'), false);
    crop.rect = {x: 11, y: 20, width: 1200, height: 500};
    assert.equal(tracker.hasChanged(3, crop, 'banner_slider/image/a.jpg'), true);
    assert.equal(tracker.imagesChanged(3, crop, 'banner_slider/image/a.jpg'), true);
});

test('tracker: a new banner image changes only the crops cut from it, and putting it back undoes that', () => {
    const tracker = changeTracker.create();
    const fromBanner = state(STORED_CROP);
    const ownImage = state({...STORED_CROP, breakpoint_id: 4, source_image: 'banner_slider/image/own.png'});

    tracker.remember(3, fromBanner, 'old.jpg');
    tracker.remember(4, ownImage, 'old.jpg');
    assert.equal(tracker.hasChanged(3, fromBanner, 'new.jpg'), true);
    assert.equal(tracker.hasChanged(4, ownImage, 'new.jpg'), false);
    assert.equal(tracker.hasChanged(3, fromBanner, 'old.jpg'), false);
});

test('tracker: flags alone change the crop but not its images; removal needs no images', () => {
    const tracker = changeTracker.create();
    const crop = state(STORED_CROP);

    tracker.remember(3, crop, null);
    crop.enabled = false;
    assert.equal(tracker.hasChanged(3, crop, null), true);
    assert.equal(tracker.imagesChanged(3, crop, null), false);
    crop.formats[1].selected = true;
    crop.remove = true;
    assert.equal(tracker.imagesChanged(3, crop, null), false);
});

test('tracker: quality edits count; a crop without baseline is changed; forgetAll drops baselines', () => {
    const tracker = changeTracker.create();
    const crop = state();

    assert.equal(tracker.hasChanged(3, crop, null), true);
    tracker.remember(3, crop, null);
    crop.formats[1].quality = 84;
    assert.equal(tracker.imagesChanged(3, crop, null), true);
    crop.formats[1].quality = 85;
    assert.equal(tracker.hasChanged(3, crop, null), false);
    crop.formats[0].quality = 10;
    assert.equal(tracker.hasChanged(3, crop, null), false, 'an unselected format does not count');
    tracker.forgetAll();
    assert.equal(tracker.hasChanged(3, crop, null), true);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* format-support                                                                                                */
/* ------------------------------------------------------------------------------------------------------------ */

test('format support: the fallback format is JPEG for a JPEG source and PNG for every other source', () => {
    const cases = [
        ['image/jpeg', 'a.jpg', 'jpeg'],
        ['image/png', 'a.png', 'png'],
        ['image/gif', 'a.gif', 'png'],
        ['image/webp', 'a.webp', 'png'],
        ['image/avif', 'a.avif', 'png'],
        ['', 'legacy/A.JPEG', 'jpeg'],
        ['', 'legacy/photo.jpe?v=2', 'jpeg'],
        ['', 'unknown.bmp', 'png'],
        [null, null, 'png']
    ];

    cases.forEach(([mime, path, expected]) => {
        assert.equal(formatSupport.originalFormatFor(formatSupport.sourceFormat(mime, path)), expected,
            `${mime} ${path}`);
    });
    assert.equal(formatSupport.sourceFormat('image/png', 'mislabelled.jpg'), 'png', 'the MIME type wins');
});

test('format support: a variant in the fallback format is not encoded twice, nor a repeated one', () => {
    assert.deepEqual(formatSupport.variantsToEncode([
        {format: 'png', quality: 90},
        {format: 'webp', quality: 80},
        {format: 'webp', quality: 70},
        {format: 'avif', quality: 60}
    ], 'png'), [{format: 'webp', quality: 80}, {format: 'avif', quality: 60}]);
});

test('format support: an unknown code resolves to an explicit unsupported encoder', async () => {
    const registry = formatSupport.createRegistry({
        webp: {available: () => Promise.resolve(true), encode: () => Promise.resolve('blob')}
    });

    assert.equal(registry.has('webp'), true);
    assert.equal(registry.has('jxl'), false);
    assert.equal(await registry.isAvailable('jxl'), false);
    assert.equal(await registry.get('jxl').available(), false);
    await assert.rejects(registry.get('jxl').encode({}, 80),
        (error) => error.name === 'UnsupportedFormatError' && error.formatCode === 'jxl');
    assert.equal(await registry.get('webp').encode({}, 80), 'blob');
    assert.deepEqual(await registry.availability(['webp', 'jxl']), {webp: true, jxl: false});
});

test('format support: a failing availability check counts as unavailable; a malformed encoder is refused', async () => {
    const registry = formatSupport.createRegistry({
        avif: {available: () => { throw new Error('probe failed'); }, encode: () => Promise.resolve()}
    });

    assert.equal(await registry.isAvailable('avif'), false);
    assert.throws(() => formatSupport.createRegistry({webp: {encode: () => null}}), TypeError);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* file-size                                                                                                     */
/* ------------------------------------------------------------------------------------------------------------ */

test('file size: formatting uses one convention for every unit', () => {
    assert.equal(fileSize.format(0), '0 B');
    assert.equal(fileSize.format(-5), '0 B');
    assert.equal(fileSize.format('nope'), '0 B');
    assert.equal(fileSize.format(512), '512 B');
    assert.equal(fileSize.format(1536), '1.5 KB');
    assert.equal(fileSize.format(5 * 1024 * 1024), '5.0 MB');
    assert.equal(fileSize.format(3 * 1024 ** 4), '3072.0 GB');
});

test('file size: savings are positive when the file is smaller, negative when larger, null when unknown', () => {
    assert.equal(fileSize.savingsPercent(1000, 250), 75);
    assert.equal(fileSize.savingsPercent(1000, 1234), -23.4);
    assert.equal(fileSize.savingsPercent(0, 100), null);
    assert.equal(fileSize.savingsPercent(100, null), null);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* crop-encoding and crop-submission, against fakes                                                              */
/* ------------------------------------------------------------------------------------------------------------ */

/**
 * A fake encoder registry: `webp` fails, `avif` is unavailable, everything else encodes to `{format}`
 *
 * @param {string[]} calls Receives `code:quality` for every encode
 * @return {Object}
 */
function fakeRegistry(calls) {
    const encoder = (code, fails) => ({
        available: () => Promise.resolve(true),
        encode: (canvas, quality) => {
            calls.push(`${code}:${quality}`);

            return fails ? Promise.reject(new Error(`${code} failed`)) : Promise.resolve({format: code, size: 10});
        }
    });

    return formatSupport.createRegistry({
        jpeg: encoder('jpeg', false),
        png: encoder('png', false),
        webp: encoder('webp', true),
        avif: {available: () => Promise.resolve(false), encode: () => Promise.reject(new Error('never'))}
    });
}

/**
 * A fake image loader
 *
 * @param {Object} image `{width, height, mimeType}` or an Error to reject with
 * @param {Object[]} renders Receives the render arguments
 * @return {Object}
 */
function fakeLoader(image, renders) {
    return {
        load: () => (image instanceof Error ? Promise.reject(image) : Promise.resolve(image)),
        render: (source, rect, size) => {
            renders.push({rect, size});

            return {canvas: true};
        },
        release: () => undefined
    };
}

const toBase64 = (blob) => Promise.resolve(`b64:${blob.format}`);

test('encoding: the fallback image comes first, then every selected variant; failures do not stop it', async () => {
    const calls = [];
    const result = await cropEncoding.encodeCanvas({}, {
        original: 'png',
        variants: [{format: 'png', quality: 1}, {format: 'webp', quality: 70}, {format: 'avif', quality: 50}]
    }, fakeRegistry(calls), toBase64);

    assert.deepEqual(calls, ['png:null', 'webp:70']);
    assert.equal(result.original, 'png');
    assert.deepEqual(result.images.map((image) => [image.format, image.data]), [['png', 'b64:png']]);
    assert.deepEqual(result.failures.map((failure) => failure.format), ['webp']);
    assert.deepEqual(result.unavailable, ['avif']);
});

test('encoding: a crop without an area gets the default area and is rendered at the target size', async () => {
    const renders = [];
    const crop = state({target_height: null}, {rect: null});
    crop.formats.forEach((format) => { format.selected = false; });
    const result = await cropEncoding.prepareCrop(crop, {path: 'banner_slider/image/a.jpg', url: 'u', size: null}, {
        loader: fakeLoader({width: 1000, height: 500, mimeType: 'image/jpeg'}, renders),
        registry: fakeRegistry([]),
        toBase64
    });

    assert.deepEqual(result.rect, {x: 0, y: 0, width: 1000, height: 500});
    assert.deepEqual(renders[0].size, {width: 1920, height: 960});
    assert.equal(result.original, 'jpeg');
    assert.deepEqual(result.images.map((image) => image.format), ['jpeg']);
    assert.equal(result.loadError, null);
});

test('encoding: a source that does not load keeps the stored area, or derives it from a known size', async () => {
    const deps = {loader: fakeLoader(new Error('404'), []), registry: fakeRegistry([]), toBase64};
    const stored = await cropEncoding.prepareCrop(state(STORED_CROP), {path: 'a.jpg', url: 'u', size: null}, deps);
    const sized = await cropEncoding.prepareCrop(state(), {path: 'a.jpg', url: 'u', size: {width: 2400, height: 800}},
        deps);
    const unknown = await cropEncoding.prepareCrop(state(), {path: 'a.jpg', url: 'u', size: null}, deps);

    assert.deepEqual(stored.rect, STORED_CROP.crop);
    assert.equal(stored.loadError.message, '404');
    assert.deepEqual(sized.rect, {x: 240, y: 0, width: 1920, height: 800});
    assert.equal(unknown.rect, null);
    assert.deepEqual(unknown.images, []);
});

test('encoding: a render failure keeps the area and reports the error', async () => {
    const loader = fakeLoader({width: 100, height: 100, mimeType: 'image/png'}, []);
    loader.render = () => { throw new Error('canvas too large'); };
    const result = await cropEncoding.prepareCrop(state(), {path: 'a.png', url: 'u', size: null},
        {loader, registry: fakeRegistry([]), toBase64});

    assert.equal(result.loadError.message, 'canvas too large');
    assert.deepEqual(result.rect, {x: 0, y: 29, width: 100, height: 42});
});

test('submission: posts only changed crops, with removals, flag-only saves and encoded images', async () => {
    const tracker = changeTracker.create();
    const banner = 'banner_slider/image/a.jpg';
    const untouched = state({...STORED_CROP, breakpoint_id: 1});
    const removed = state({...STORED_CROP, breakpoint_id: 2});
    const disabled = state({...STORED_CROP, breakpoint_id: 3});
    const moved = state({...STORED_CROP, breakpoint_id: 4});
    const nothingToCut = state({breakpoint_id: 5, source_image: null});
    const states = [untouched, removed, disabled, moved, nothingToCut];
    states.forEach((crop) => tracker.remember(crop.id, crop, banner));
    removed.remove = true;
    disabled.enabled = false;
    moved.rect = {x: 0, y: 0, width: 960, height: 400};
    nothingToCut.enabled = false;

    const post = await cropSubmission.collect(states, {
        tracker,
        bannerPath: banner,
        sourceOf: (crop) => (crop.id === 5 ? {path: null, url: null, size: null} : {path: banner, url: 'u', size: null}),
        encoding: {
            loader: fakeLoader({width: 2000, height: 1000, mimeType: 'image/jpeg'}, []),
            registry: fakeRegistry([]),
            toBase64
        }
    });

    assert.deepEqual(post.entries.map((entry) => [entry.breakpoint_id, entry.remove, entry.encoded.length]),
        [[2, true, 0], [3, false, 0], [4, false, 1]]);
    assert.deepEqual(post.entries[1].crop, STORED_CROP.crop);
    assert.equal(post.entries[1].enabled, false);
    assert.deepEqual(post.notices, [], 'a format this browser cannot encode is left to the server without a notice');
});

test('submission: encoding failures, unknown areas and budget drops become notices', async () => {
    const tracker = changeTracker.create();
    const withWebp = state({breakpoint_id: 7});
    const noArea = state({breakpoint_id: 8});
    [withWebp, noArea].forEach((crop) => tracker.remember(crop.id, crop, null));

    const post = await cropSubmission.collect([withWebp, noArea], {
        tracker,
        bannerPath: 'banner_slider/image/a.jpg',
        sourceOf: () => ({path: 'banner_slider/image/a.jpg', url: 'u', size: null}),
        encoding: {
            loader: {
                load: () => Promise.resolve({width: 100, height: 100, mimeType: 'image/jpeg'}),
                render: () => ({}),
                release: () => undefined
            },
            registry: fakeRegistry([]),
            toBase64
        }
    });
    noArea.rect = null;
    const failedLoad = await cropSubmission.collect([noArea], {
        tracker,
        bannerPath: 'banner_slider/image/a.jpg',
        sourceOf: () => ({path: 'x', url: 'u', size: null}),
        encoding: {loader: fakeLoader(new Error('gone'), []), registry: fakeRegistry([]), toBase64}
    });
    const fitted = cropSubmission.fit(post, {maxPostBytes: 0, maxUploadBytes: 1, otherBytes: 0});

    assert.deepEqual(post.notices, [
        {kind: cropSubmission.NOT_ENCODED, breakpointId: 7, format: 'webp'},
        {kind: cropSubmission.NOT_ENCODED, breakpointId: 8, format: 'webp'}
    ]);
    assert.deepEqual(post.entries.map((entry) => entry.breakpoint_id), [7, 8]);
    assert.equal(withWebp.rect.width, 100, 'a prepared crop keeps the area it was posted with');
    assert.deepEqual(failedLoad.entries, []);
    assert.deepEqual(failedLoad.notices, [
        {kind: cropSubmission.SOURCE_NOT_LOADED, breakpointId: 8, format: null},
        {kind: cropSubmission.NO_AREA, breakpointId: 8, format: null}
    ]);
    assert.deepEqual(fitted.notices.slice(2), [
        {kind: payload.REASON_TOO_LARGE, breakpointId: 7, format: 'jpeg'},
        {kind: payload.REASON_TOO_LARGE, breakpointId: 8, format: 'jpeg'}
    ]);
    assert.equal(fitted.fits, true);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* crop-editor-model                                                                                             */
/* ------------------------------------------------------------------------------------------------------------ */

test('model: a new banner image clears the areas of crops cut from it and marks them changed', () => {
    const model = editorModel.create(CONFIG);
    model.setBannerImage([{file: 'banner_slider/image/a.jpg', url: 'https://m.test/media/banner_slider/image/a.jpg'}]);
    model.load([rawBreakpoint(STORED_CROP), rawBreakpoint({...STORED_CROP, breakpoint_id: 4,
        source_image: 'banner_slider/image/own.png', source_url: 'https://m.test/media/banner_slider/image/own.png'})],
    true, null);
    const [fromBanner, ownImage] = model.states;

    assert.equal(model.isChanged(fromBanner), false);
    assert.deepEqual(model.setBannerImage([{file: 'banner_slider/image/a.jpg'}]), [], 'the same image is no change');
    assert.deepEqual(model.setBannerImage([{file: 'banner_slider/image/b.jpg', width: 800, height: 600}]), [fromBanner]);
    assert.equal(fromBanner.rect, null);
    assert.deepEqual(ownImage.rect, STORED_CROP.crop);
    assert.equal(model.isChanged(fromBanner), true);
    assert.equal(model.isChanged(ownImage), false);
    assert.deepEqual(model.sourceOf(fromBanner), {path: 'banner_slider/image/b.jpg', url: null,
        size: {width: 800, height: 600}});
    assert.equal(model.nameOf(4), 'Desktop');
    assert.equal(model.nameOf(40), '40');
});

test('model: a newly chosen slider has no crops yet, so its breakpoints change once there is an image', () => {
    const model = editorModel.create(CONFIG);
    model.setBannerImage([{file: 'banner_slider/image/a.jpg', url: 'https://m.test/media/banner_slider/image/a.jpg'}]);
    model.load([rawBreakpoint()], false, JSON.stringify([{breakpoint_id: 3, remove: true}]));

    assert.equal(model.states[0].remove, false, 'a refused post applies only to the form\'s own breakpoints');
    assert.equal(model.isChanged(model.states[0]), true);
});

test('model: the form restores a refused post; setSource resets the area', () => {
    const model = editorModel.create(CONFIG);
    model.setBannerImage([{file: 'p/a.jpg', url: 'https://m.test/media/p/a.jpg'}]);
    model.load([rawBreakpoint(STORED_CROP)], true, JSON.stringify([{breakpoint_id: 3, enabled: false,
        crop: {x: 1, y: 1, width: 10, height: 10}, formats: []}]));
    const [crop] = model.states;

    assert.equal(crop.enabled, false);
    assert.equal(model.isChanged(crop), true);
    model.setSource(crop, 'banner_slider/image/own.png', 'https://m.test/own.png', {width: 5, height: 5});
    assert.equal(crop.rect, null);
    assert.deepEqual(model.sourceOf(crop), {path: 'banner_slider/image/own.png', url: 'https://m.test/own.png',
        size: {width: 5, height: 5}});
    model.setSource(crop, null, 'ignored', {width: 1, height: 1});
    assert.deepEqual([crop.sourceImage, crop.sourceUrl, crop.sourceSize], [null, null, null]);
    assert.equal(editorModel.bannerSourceOf('nonsense').path, null);
});

test('model: a banner image without a stored path is an unknown source; no crop is reset or removed', async () => {
    const model = editorModel.create(CONFIG);
    model.setBannerImage([{file: 'banner_slider/image/a.jpg', url: 'https://m.test/media/banner_slider/image/a.jpg'}]);
    model.load([rawBreakpoint(STORED_CROP)], true, null);
    const [crop] = model.states;
    const galleryPick = [{name: 'g.jpg', url: 'https://m.test/media/wysiwyg/g.jpg', type: 'image/jpeg'}];

    assert.equal(editorModel.bannerSourceOf(galleryPick), null);
    assert.deepEqual(model.setBannerImage(galleryPick), []);
    assert.equal(model.banner.path, 'banner_slider/image/a.jpg');
    assert.deepEqual(crop.rect, STORED_CROP.crop);
    assert.equal(model.isChanged(crop), false);
    const post = await cropSubmission.collect(model.states, {
        tracker: model.tracker,
        bannerPath: model.banner.path,
        sourceOf: model.sourceOf,
        encoding: {loader: fakeLoader({width: 2000, height: 1000, mimeType: 'image/jpeg'}, []),
            registry: fakeRegistry([]), toBase64}
    });
    assert.deepEqual(post.entries, [], 'no removal is planned for an unknown source');
    assert.deepEqual(editorModel.bannerSourceOf([]), {path: null, url: null, size: null},
        'an empty uploader is no image');
});

test('payload: the editor state names its slider; a state that does not falls back to the form slider', () => {
    assert.equal(payload.stateSliderId({slider_id: 3, breakpoints: []}, '5'), '3');
    assert.equal(payload.stateSliderId({slider_id: null, breakpoints: []}, '5'), null);
    assert.equal(payload.stateSliderId({breakpoints: []}, '5'), '5');
    assert.equal(payload.stateSliderId(undefined, ''), null);
    assert.equal(payload.stateSliderId({slider_id: 'x'}, '5'), null);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* http-client and value rules                                                                                   */
/* ------------------------------------------------------------------------------------------------------------ */

/**
 * A fake fetch that answers with the given response and records its calls
 *
 * @param {Object|Error} answer
 * @param {Object[]} calls
 * @return {Function}
 */
function fakeFetch(answer, calls) {
    return (url, init) => {
        calls.push({url, init});

        return answer instanceof Error ? Promise.reject(answer) : Promise.resolve(answer);
    };
}

const jsonResponse = (body, status = 200) => ({
    ok: status < 400,
    status,
    json: () => (body instanceof Error ? Promise.reject(body) : Promise.resolve(body))
});

test('http: GET appends parameters and isAjax after ? or & and sends no form key', async () => {
    const calls = [];
    const client = httpClient.create({fetch: fakeFetch(jsonResponse({breakpoints: []}), calls), formKey: () => 'FK'});

    assert.deepEqual(await client.getJson('https://a.test/x/key/abc/', {slider_id: 5}), {breakpoints: []});
    assert.equal(calls[0].url, 'https://a.test/x/key/abc/?slider_id=5&isAjax=true');
    await client.getJson('https://a.test/x?a=1');
    assert.equal(calls[1].url, 'https://a.test/x?a=1&isAjax=true', 'an expired session answers ajaxExpired');
    assert.equal(calls[0].init.method, 'GET');
    assert.equal(httpClient.withQuery('https://a.test/x?a=1', {b: 'c d'}), 'https://a.test/x?a=1&b=c%20d');
    assert.equal(httpClient.withQuery('https://a.test/x', {}), 'https://a.test/x');
    assert.ok(!calls[0].url.includes('form_key'));
});

test('http: POST puts the form key in the body and isAjax in the query', async () => {
    const calls = [];
    const fields = [];
    const body = {append: (name, value) => fields.push([name, value])};
    const client = httpClient.create({fetch: fakeFetch(jsonResponse({file: 'x'}), calls), formKey: () => 'FK'});

    await client.postForm('https://a.test/upload/key/abc/', body);
    assert.deepEqual(fields, [['form_key', 'FK']]);
    assert.equal(calls[0].url, 'https://a.test/upload/key/abc/?isAjax=true');
    assert.equal(calls[0].init.method, 'POST');
    assert.equal(calls[0].init.body, body);
});

test('http: failures reject with their kind', async () => {
    const kindOf = (answer) => httpClient.create({fetch: fakeFetch(answer, []), formKey: () => ''})
        .getJson('u').then(() => 'none', (error) => [error.kind, error.status]);

    assert.deepEqual(await kindOf(new TypeError('offline')), ['network', undefined]);
    assert.deepEqual(await kindOf(jsonResponse({}, 500)), ['http', 500]);
    assert.deepEqual(await kindOf(jsonResponse(new SyntaxError('html'))), ['invalid', undefined]);
    assert.deepEqual(await kindOf(jsonResponse({ajaxExpired: 1})), ['expired', undefined]);
    assert.deepEqual(await kindOf(jsonResponse({error: true, message: 'Invalid Secret Key.'})), ['refused', undefined]);
    assert.equal(await httpClient.create({fetch: fakeFetch(jsonResponse({error: true}), []), formKey: () => ''})
        .getJson('u').catch((error) => error.message), 'The request was refused.');
    assert.deepEqual(await httpClient.create({fetch: fakeFetch(jsonResponse({error: 'Too big.'}), []),
        formKey: () => ''}).postForm('u', {append: () => {}}), {error: 'Too big.'}, 'an upload refusal is an answer');
});

test('value rules: link URLs, identifiers, aspect ratios, locations and CSS lengths match the server', () => {
    ['https://a.test/x', 'http://a', 'MAILTO:x@y.z', 'tel:+1', '/sale.html', '#top', '?q=1', 'page.html']
        .forEach((value) => assert.equal(valueRules.isLinkUrl(value), true, value));
    ['javascript:alert(1)', 'data:text/html,x', 'ftp://a', 'a\u0007b']
        .forEach((value) => assert.equal(valueRules.isLinkUrl(value), false, value));
    assert.equal(valueRules.isIdentifier('mobile_2x'), true);
    assert.equal(valueRules.isIdentifier('-mobile'), false);
    assert.equal(valueRules.isIdentifier('Mobile'), false);
    assert.equal(valueRules.isIdentifier('a'.repeat(51)), false);
    assert.deepEqual(valueRules.parseAspectRatio(' 16 : 9 '), {width: 16, height: 9});
    assert.equal(valueRules.isAspectRatio('2.35:1'), false);
    assert.equal(valueRules.isAspectRatio('101:1'), false);
    assert.equal(valueRules.isAspectRatio('0:1'), false);
    assert.equal(valueRules.isLocation('home-top_1'), true);
    assert.equal(valueRules.isLocation('home top'), false);
    assert.equal(valueRules.isCssLength('1.5rem'), true);
    assert.equal(valueRules.isCssLength('16'), false);
    assert.equal(valueRules.isCssLength('-4px'), false);
});

/* ------------------------------------------------------------------------------------------------------------ */
/* Run                                                                                                           */
/* ------------------------------------------------------------------------------------------------------------ */

let passed = 0;
const failures = [];

test('templates: a field error is shown by the field wrapper, never by looping over the error text', () => {
    const templates = join(dirname(fileURLToPath(import.meta.url)), '../../view/adminhtml/web/template/form/element');
    for (const name of ['slider-select.html', 'uploader/upload-only.html']) {
        const html = readFileSync(join(templates, name), 'utf8');
        assert.ok(!/data:\s*error\b/.test(html), `${name} must not iterate over the error string`);
    }
});

for (const {name, body} of tests) {
    try {
        await body();
        passed++;
    } catch (error) {
        failures.push({name, error});
    }
}

failures.forEach(({name, error}) => {
    console.error(`FAIL ${name}\n${error && error.stack ? error.stack : error}\n`);
});
console.log(`${passed} passed` + (failures.length ? `, ${failures.length} failed` : ''));
process.exitCode = failures.length ? 1 : 0;
