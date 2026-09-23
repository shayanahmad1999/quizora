'use strict';
function validateServerUrl(value) {
    if (typeof value !== 'string' || !value.trim()) throw new Error('Configure a serverUrl in desktop/config.json.');
    const url = new URL(value);
    const loopback = ['127.0.0.1', 'localhost', '[::1]'].includes(url.hostname);
    if (url.username || url.password) throw new Error('Credentials must not be embedded in the server URL.');
    if (url.protocol !== 'https:' && !(url.protocol === 'http:' && loopback)) {
        throw new Error('Remote workspaces require HTTPS. HTTP is allowed only for local development.');
    }
    return url;
}
function isTrustedNavigation(value, server) {
    try { const url = new URL(value); return ['https:', 'http:'].includes(url.protocol) && !url.username && !url.password && url.origin === server.origin; }
    catch { return false; }
}
module.exports = { validateServerUrl, isTrustedNavigation };
