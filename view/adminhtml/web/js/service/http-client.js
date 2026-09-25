/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * JSON requests to the module's admin endpoints.
 *
 * - GET relies on the admin secret key already in the URL and never sends the form key.
 * - POST sends a FormData body with the form key added to it, never in the query string.
 * - Every request carries `isAjax=true` in its query string. Magento then answers an ended admin session with
 *   `ajaxExpired`, and a refused secret key or form key with `{error: true, message}`, instead of a sign-in page or
 *   a redirect; the form key check reads the flag from the query string only, so it is never sent in the body.
 * Failures reject with an Error whose `kind` is `network` (no answer), `http` (error status, with `status`),
 * `invalid` (the answer is not JSON), `expired` (the admin session ended) or `refused` (the request was refused, with
 * Magento's own message). Wording belongs to the caller.
 */
define([], function () {
    'use strict';

    var HEADERS = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        AJAX_QUERY = {isAjax: 'true'};

    /**
     * A URL with query parameters appended after `?` or `&`
     *
     * @param {String} url
     * @param {Object<String, *>} params
     * @returns {String}
     */
    function withQuery(url, params) {
        var query = Object.keys(params || {})
            .map(function (name) {
                return encodeURIComponent(name) + '=' + encodeURIComponent(String(params[name]));
            })
            .join('&');

        if (query === '') {
            return url;
        }

        return url + (url.indexOf('?') === -1 ? '?' : '&') + query;
    }

    /**
     * An Error of the given kind
     *
     * @param {String} kind
     * @param {String} message
     * @param {Object} [details]
     * @returns {Error}
     */
    function failure(kind, message, details) {
        var error = new Error(message);

        error.kind = kind;

        return Object.assign(error, details || {});
    }

    /**
     * A client on the given fetch function and form key source
     *
     * @param {{fetch: Function, formKey: function(): String}} options
     * @returns {{getJson: Function, postForm: Function}}
     */
    function create(options) {
        /**
         * Send a request and read its JSON answer
         *
         * @param {String} url
         * @param {Object} init
         * @returns {Promise<Object>}
         */
        function send(url, init) {
            return Promise.resolve()
                .then(function () {
                    return options.fetch(url, Object.assign({credentials: 'same-origin', headers: HEADERS}, init));
                })
                .catch(function (error) {
                    throw failure('network', error && error.message ? error.message : 'The request failed.');
                })
                .then(function (response) {
                    if (!response.ok) {
                        throw failure('http', 'The server answered HTTP ' + response.status + '.', {
                            status: response.status
                        });
                    }

                    return response.json().catch(function () {
                        throw failure('invalid', 'The server answer is not JSON.');
                    });
                })
                .then(function (body) {
                    if (body && body.ajaxExpired) {
                        throw failure('expired', 'The admin session has expired.');
                    }
                    if (body && body.error === true) {
                        throw failure('refused', typeof body.message === 'string' && body.message !== ''
                            ? body.message
                            : 'The request was refused.');
                    }

                    return body;
                });
        }

        return {
            /**
             * GET a JSON answer
             *
             * @param {String} url
             * @param {Object<String, *>} [params]
             * @returns {Promise<Object>}
             */
            getJson: function (url, params) {
                return send(withQuery(url, Object.assign({}, params || {}, AJAX_QUERY)), {method: 'GET'});
            },

            /**
             * POST a form body (the form key is added) and read the JSON answer
             *
             * @param {String} url
             * @param {FormData} body
             * @returns {Promise<Object>}
             */
            postForm: function (url, body) {
                body.append('form_key', options.formKey());

                return send(withQuery(url, AJAX_QUERY), {method: 'POST', body: body});
            }
        };
    }

    return {
        withQuery: withQuery,
        create: create,

        /**
         * The client of the current admin page
         *
         * @returns {{getJson: Function, postForm: Function}}
         */
        forPage: function () {
            return create({
                fetch: window.fetch.bind(window),

                /**
                 * @returns {String}
                 */
                formKey: function () {
                    return window.FORM_KEY;
                }
            });
        }
    };
});
