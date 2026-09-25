/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * The banner form's crop editor: one tab per breakpoint of the banner's slider, a crop box on the crop's source
 * image, the extra formats and qualities, an in-browser preview, and the form's Save buttons.
 *
 * It wires the crop state (crop-editor-model) to the page. On save it posts, in `responsive_crops`, what
 * crop-submission prepares from the changed crops. Every failure is shown to the admin; a failed encoding never
 * blocks the save, the server generates what is missing.
 */
define([
    'uiComponent',
    'ko',
    'mage/translate',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-payload',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-editor-model',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-encoding',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-submission',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/crop-canvas',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/encoders',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/cropper-adapter',
    'Hryvinskyi_BannerSliderAdminUi/js/cropper/file-size',
    'Hryvinskyi_BannerSliderAdminUi/js/form/components/crop-editor-view',
    'Hryvinskyi_BannerSliderAdminUi/js/form/components/crop-editor-messages',
    'Hryvinskyi_BannerSliderAdminUi/js/service/http-client'
], function (
    Component, ko, $t, payload, editorModel, cropEncoding, cropSubmission, cropCanvas, encoders, cropperAdapter,
    fileSize, view, messages, httpClient
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_BannerSliderAdminUi/form/responsive-cropper',
            allowedImageTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'],
            imports: {
                cropperData: '${ $.provider }:data.responsive_cropper',
                pendingCrops: '${ $.provider }:data.responsive_crops',
                sliderId: '${ $.provider }:data.slider_id',
                bannerImage: '${ $.provider }:data.image'
            },
            listens: {
                sliderId: 'onSliderChange',
                bannerImage: 'onBannerImageChange'
            },
            modules: {
                form: '${ $.ns }.${ $.ns }',
                source: '${ $.provider }'
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.settings = payload.parseConfig({
                breakpointsUrl: this.breakpointsUrl,
                imageUploadUrl: this.imageUploadUrl,
                imageUploadField: this.imageUploadField,
                formats: this.formats,
                defaultFormats: this.defaultFormats,
                defaultQuality: this.defaultQuality,
                maxUploadBytes: this.maxUploadBytes,
                maxPostBytes: this.maxPostBytes
            });
            this.model = editorModel.create(this.settings);
            this.model.setBannerImage(this.bannerImage);
            this.http = httpClient.forPage();
            this.encoding = {loader: cropCanvas, registry: encoders.registry, toBase64: encoders.toBase64};
            this.cropBox = null;
            this.breakpointsRequest = 0;
            this.loadedSliderId = payload.stateSliderId(this.cropperData, this.sliderId);
            this.showStates(this.cropperData ? this.cropperData.breakpoints : [], true);
            encoders.registry.availability(this.settings.formats.map(function (format) {
                return format.code;
            })).then(this.browserFormats);
            this.ready = true;
            this.onSliderChange(this.sliderId);

            return this;
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super();
            this.revision = ko.observable(0);
            this.statesList = ko.observableArray([]);
            this.activeId = ko.observable(null);
            this.busyText = ko.observable('');
            this.browserFormats = ko.observable({});
            this.previews = ko.observable({});
            this.comparisonCode = ko.observable(null);
            this.active = ko.pureComputed(function () {
                var id = this.activeId();

                return this.statesList().filter(function (state) {
                    return state.id === id;
                })[0] || null;
            }, this);
            this.activeSourceUrl = ko.pureComputed(function () {
                var state = this.active();

                this.revision();

                return state && !state.remove ? this.model.sourceOf(state).url || '' : '';
            }, this);
            this.activeSourceUrl.subscribe(this.detachCropper, this, 'beforeChange');
            this.tabs = ko.pureComputed(function () {
                this.revision();

                return view.tabs(this.statesList(), this.activeId(), this.model.isChanged);
            }, this);
            this.panel = ko.pureComputed(function () {
                var state = this.active();

                this.revision();

                return state ? view.panel(state, this.model.sourceOf(state)) : null;
            }, this);
            this.formatRows = ko.pureComputed(function () {
                var state = this.active();

                return state ? view.formatRows(state, this.browserFormats(), this.touch.bind(this)) : [];
            }, this);
            this.comparison = ko.pureComputed(function () {
                var state = this.active();

                return state ? view.comparison(state, this.previews()[state.id] || null, this.comparisonCode()) : null;
            }, this);

            return this;
        },

        /**
         * Load received breakpoint entries into the editor and show the first tab
         *
         * @param {Array} rawList
         * @param {Boolean} fromForm True for the form's own data, false for a newly chosen slider's breakpoints
         * @returns {void}
         */
        showStates: function (rawList, fromForm) {
            this.detachCropper();
            Object.keys(this.previews()).forEach(function (id) {
                view.releasePreview(this.previews()[id]);
            }, this);
            this.previews({});
            this.model.load(rawList, fromForm, fromForm ? this.pendingCrops : null);
            this.statesList(this.model.states);
            this.activeId(this.model.states.length ? this.model.states[0].id : null);
            this.touch();
        },

        /**
         * Tell the bindings that a crop changed
         *
         * @returns {void}
         */
        touch: function () {
            this.revision(this.revision() + 1);
        },

        /**
         * Load the breakpoints of a newly chosen slider; also run once on start, in case the breakpoints the form
         * came with belong to another slider than its slider field
         *
         * @param {String|Number} sliderId
         * @returns {void}
         */
        onSliderChange: function (sliderId) {
            var request;

            if (!this.ready || !sliderId || String(sliderId) === String(this.loadedSliderId)) {
                return;
            }
            this.loadedSliderId = sliderId;
            request = ++this.breakpointsRequest;
            this.runBusy(
                $t('Loading the breakpoints of the slider...'),
                this.http.getJson(this.settings.breakpointsUrl, {slider_id: sliderId})
            ).then(function (answer) {
                if (request === this.breakpointsRequest) {
                    this.showStates(answer && answer.breakpoints, false);
                }
            }.bind(this), function (error) {
                if (request === this.breakpointsRequest) {
                    this.showStates([], false);
                    messages.failure($t('The breakpoints of the selected slider could not be loaded.'), error);
                }
            }.bind(this));
        },

        /**
         * Crops cut from the banner image start over when it is replaced or removed
         *
         * @returns {void}
         */
        onBannerImageChange: function () {
            var reset;

            if (!this.ready) {
                return;
            }
            reset = this.model.setBannerImage(this.bannerImage);
            if (reset.length) {
                this.detachCropper();
                reset.forEach(function (state) {
                    this.clearPreview(state.id);
                }, this);
                this.touch();
            }
        },

        /**
         * @param {{id: Number}} tab
         * @returns {void}
         */
        selectTab: function (tab) {
            this.detachCropper();
            this.activeId(tab.id);
        },

        /**
         * Put the crop box on the active crop's source image once it has loaded
         *
         * @param {Object} data
         * @param {Event} event
         * @returns {void}
         */
        attachCropper: function (data, event) {
            var state = this.active();

            this.detachCropper();
            if (state) {
                this.cropBox = cropperAdapter.attach(event.target, {
                    target: state.target,
                    rect: state.rect,
                    onChange: function (rect) {
                        state.rect = rect;
                        this.touch();
                    }.bind(this)
                });
            }
        },

        /**
         * @returns {void}
         */
        detachCropper: function () {
            if (this.cropBox) {
                this.cropBox.destroy();
                this.cropBox = null;
            }
        },

        /**
         * Apply a change to the active crop and refresh the view
         *
         * @param {function(Object): void} change
         * @returns {Boolean} True, so a bound checkbox keeps its default action
         */
        editActive: function (change) {
            var state = this.active();

            if (state) {
                change.call(this, state);
                this.touch();
            }

            return true;
        },

        /**
         * @param {Object} data
         * @param {Event} event
         * @returns {Boolean}
         */
        toggleEnabled: function (data, event) {
            return this.editActive(function (state) {
                state.enabled = event.target.checked;
            });
        },

        /**
         * Mark the active crop for removal on save, or take that back
         *
         * @param {Boolean} removed
         * @returns {void}
         */
        setRemoved: function (removed) {
            this.detachCropper();
            this.editActive(function (state) {
                state.remove = removed;
            });
        },

        /**
         * Give the active crop another source image; null cuts it from the banner image again
         *
         * @param {Object} state
         * @param {String|null} path
         * @param {String|null} url
         * @param {Object|null} size
         * @returns {void}
         */
        changeSource: function (state, path, url, size) {
            this.detachCropper();
            this.model.setSource(state, path, url, size);
            this.clearPreview(state.id);
            this.touch();
        },

        /**
         * @returns {void}
         */
        useBannerImage: function () {
            if (this.active()) {
                this.changeSource(this.active(), null, null, null);
            }
        },

        /**
         * Upload the chosen file as the active crop's own source image
         *
         * @param {Object} data
         * @param {Event} event
         * @returns {void}
         */
        uploadOwnImage: function (data, event) {
            var file = event.target.files && event.target.files[0],
                state = this.active(),
                limit = this.settings.maxUploadBytes,
                body = new FormData();

            event.target.value = '';
            if (!file || !state) {
                return;
            }
            if (this.allowedImageTypes.indexOf(file.type) === -1) {
                messages.show([$t('Choose a JPEG, PNG, GIF, WebP or AVIF image.')]);

                return;
            }
            if (limit > 0 && file.size > limit) {
                messages.show([$t('The image is larger than %1.').replace('%1', fileSize.format(limit))]);

                return;
            }
            body.append(this.settings.imageUploadField, file, file.name);
            this.runBusy($t('Uploading the image...'), this.http.postForm(this.settings.imageUploadUrl, body))
                .then(function (answer) {
                    var failed = !answer || answer.error || !answer.file || !answer.url;

                    if (failed) {
                        messages.show([answer && answer.error ? answer.error : $t('The image could not be uploaded.')]);

                        return;
                    }
                    this.changeSource(state, String(answer.file), String(answer.url),
                        answer.width > 0 && answer.height > 0 ? {width: answer.width, height: answer.height} : null);
                }.bind(this), function (error) {
                    messages.failure($t('The image could not be uploaded.'), error);
                }.bind(this));
        },

        /**
         * Encode the active crop in the browser and compare it with its fallback image
         *
         * @returns {void}
         */
        previewActive: function () {
            var state = this.active();

            if (!state || this.busyText()) {
                return;
            }
            this.runBusy(
                $t('Preparing the preview...'),
                cropEncoding.prepareCrop(state, this.model.sourceOf(state), this.encoding)
            ).then(function (result) {
                var previews = Object.assign({}, this.previews());

                if (!result.loadError) {
                    view.releasePreview(previews[state.id]);
                    previews[state.id] = view.preview(result);
                    this.previews(previews);
                }
                messages.notices(cropSubmission.encodingNotices(state, result), this.model.nameOf);
            }.bind(this), function (error) {
                messages.failure($t('The preview could not be prepared.'), error);
            });
        },

        /**
         * Drop a crop's preview and free its images
         *
         * @param {Number} id
         * @returns {void}
         */
        clearPreview: function (id) {
            var previews = Object.assign({}, this.previews());

            if (previews[id]) {
                view.releasePreview(previews[id]);
                delete previews[id];
                this.previews(previews);
            }
        },

        /**
         * "Save": prepare the changed crops, then submit the form
         *
         * @returns {void}
         */
        submitForm: function () {
            this.submit({});
        },

        /**
         * "Save and Continue Edit": prepare the changed crops, then submit the form and come back to it
         *
         * @returns {void}
         */
        submitFormAndContinue: function () {
            this.submit({back: 'edit'});
        },

        /**
         * Prepare the changed crops, put them in the form data and submit the form
         *
         * An invalid form is submitted at once, so the form shows its errors and nothing is encoded in vain.
         *
         * @param {Object} params Extra request parameters
         * @returns {void}
         */
        submit: function (params) {
            var form = this.form(),
                save = function () {
                    form.save(true, params);
                };

            if (!form || this.busyText()) {
                return;
            }
            form.validate();
            if (form.additionalInvalid || this.source().get('params.invalid')) {
                save();

                return;
            }
            this.runBusy($t('Preparing the responsive images...'), cropSubmission.collect(this.model.states, {
                tracker: this.model.tracker,
                bannerPath: this.model.banner.path,
                sourceOf: this.model.sourceOf,
                encoding: this.encoding
            })).then(function (collected) {
                var post = cropSubmission.fit(collected, {
                    maxPostBytes: this.settings.maxPostBytes,
                    maxUploadBytes: this.settings.maxUploadBytes,
                    otherBytes: payload.formBytes(this.source().get('data'), 'responsive_crops')
                });

                if (!post.fits) {
                    messages.show([$t('The form is too large to send. Shorten the custom content and try again.')]);

                    return;
                }
                this.source().set('data.responsive_crops', JSON.stringify(post.entries));
                messages.notices(post.notices, this.model.nameOf, save);
            }.bind(this), function (error) {
                messages.failure($t('The responsive images could not be prepared.'), error);
            });
        },

        /**
         * Show the busy overlay with the given text until the promise settles
         *
         * @param {String} text
         * @param {Promise} promise
         * @returns {Promise}
         */
        runBusy: function (text, promise) {
            var idle = this.busyText.bind(this, '');

            this.busyText(text);

            return promise.then(function (value) {
                idle();

                return value;
            }, function (error) {
                idle();
                throw error;
            });
        }
    });
});
