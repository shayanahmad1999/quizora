/**
 * Quizora's only browser application module.
 * Bind behavior through data attributes; never through page-specific IDs/classes.
 * All writes share a FIFO queue. GET navigation is abortable; writes are not retried.
 */
(() => {
    'use strict';
    const $ = (attribute, root = document) => root.querySelector(`[${attribute}]`);
    const $$ = (attribute, root = document) => [...root.querySelectorAll(`[${attribute}]`)];
    const app = () => $('data-app');
    const token = () => $('data-csrf')?.content || '';
    const dirty = new Set();
    const failedSaves = new Set();
    const formPending = new WeakMap();
    const revisions = new WeakMap();
    const filterTimers = new WeakMap();
    const originalDisabled = new WeakMap();
    let writeTail = Promise.resolve();
    let pendingWrites = 0;
    let navigation = null;
    let navSequence = 0;
    let loading = 0;
    let countdownTimer = null;
    let activeUrl = location.href;
    let confirmResolve = null;

    class HttpError extends Error {
        constructor(message, status = 0, data = {}) {
            super(message); this.name = 'HttpError'; this.status = status; this.data = data;
        }
    }
    function localUrl(value) {
        const url = new URL(value, location.href);
        if (url.origin !== location.origin || !['http:', 'https:'].includes(url.protocol)) {
            throw new HttpError('This action must stay inside your workspace.');
        }
        return url;
    }
    function progress(start) {
        loading = Math.max(0, loading + (start ? 1 : -1));
        if ($('data-progress')) $('data-progress').hidden = loading === 0;
    }
    function updateToken(value) {
        if (typeof value !== 'string' || !value) return;
        if ($('data-csrf')) $('data-csrf').content = value;
        for (const form of $$('data-ajax')) {
            const field = form.elements?.namedItem('_token');
            if (field instanceof HTMLInputElement) field.value = value;
        }
    }
    async function request(url, options = {}) {
        const { responseType = 'json', timeout = 30000, signal, ...fetchOptions } = options;
        const controller = new AbortController();
        let timedOut = false;
        const externalAbort = () => controller.abort();
        if (signal?.aborted) controller.abort();
        signal?.addEventListener('abort', externalAbort, { once: true });
        const timeoutId = setTimeout(() => { timedOut = true; controller.abort(); }, timeout);
        const headers = new Headers(fetchOptions.headers || {});
        headers.set('X-Requested-With', 'XMLHttpRequest');
        headers.set('Accept', responseType === 'blob' ? 'text/csv, application/json' : 'application/json');
        headers.set('X-CSRF-TOKEN', token());
        try {
            const response = await fetch(localUrl(url), {
                ...fetchOptions, headers, credentials: 'same-origin', mode: 'same-origin',
                cache: 'no-store', signal: controller.signal,
            });
            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            if (responseType === 'blob' && response.ok && !isJson) {
                const disposition = response.headers.get('content-disposition') || '';
                const filename = disposition.match(/filename="?([^";]+)"?/i)?.[1] || 'export.csv';
                return { blob: await response.blob(), filename: filename.replace(/[^a-zA-Z0-9._-]/g, '_') };
            }
            const data = isJson ? await response.json() : {};
            updateToken(data.csrf_token);
            if (!response.ok) {
                const fallback = response.status === 419 ? 'Your session has expired. Sign in again before retrying this action.'
                    : response.status === 403 ? 'You do not have permission to perform this action.'
                    : response.status === 404 ? 'This record is no longer available.'
                    : response.status === 429 ? 'Too many requests. Please pause and try again.'
                    : 'The request could not be completed. Please try again or contact your administrator.';
                throw new HttpError(data.message || fallback, response.status, data);
            }
            if (!isJson) throw new HttpError('An unexpected response was received. Refresh the page and try again.', response.status);
            if (response.redirected && data.html) data.url = response.url;
            return data;
        } catch (error) {
            if (timedOut) throw new HttpError('The request timed out. Its outcome is unknown; check the record before retrying.');
            if (error.name === 'AbortError') throw error;
            if (error instanceof HttpError) throw error;
            throw new HttpError('Connection interrupted. Your last change may not be saved. Check your connection before retrying.');
        } finally {
            clearTimeout(timeoutId);
            signal?.removeEventListener('abort', externalAbort);
        }
    }
    function notify(message, kind = 'success') {
        if (!message || !$('data-notices')) return;
        const toast = document.createElement('div');
        toast.className = 'app-toast'; toast.dataset.kind = kind;
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        const content = document.createElement('span'); content.textContent = String(message);
        const close = document.createElement('button'); close.type = 'button';
        close.textContent = '\u00d7'; close.setAttribute('aria-label', 'Dismiss notification');
        close.addEventListener('click', () => toast.remove());
        toast.append(content, close); $('data-notices').append(toast);
        if (kind !== 'error') setTimeout(() => toast.remove(), 6000);
    }
    function clearErrors(form) {
        const box = $('data-errors', form);
        if (box) { box.replaceChildren(); box.hidden = true; }
        for (const control of form.elements) {
            control.classList?.remove('is-invalid'); control.removeAttribute?.('aria-invalid');
        }
    }
    async function showError(error, form = null) {
        if (error.name === 'AbortError') return;
        if (error.status === 422 && error.data.errors && form) {
            clearErrors(form);
            const box = $('data-errors', form);
            const messages = new Set();
            for (const [key, errors] of Object.entries(error.data.errors)) {
                for (const message of errors) messages.add(message);
                const name = key.replace(/\.([^.]+)/g, '[$1]');
                const field = form.elements.namedItem(name) || form.elements.namedItem(key) || form.elements.namedItem(`${key}[]`);
                const fields = field instanceof RadioNodeList ? [...field] : field ? [field] : [];
                fields.forEach(control => { control.classList.add('is-invalid'); control.setAttribute('aria-invalid', 'true'); });
            }
            if (box) {
                const list = document.createElement('ul');
                for (const message of messages) { const item = document.createElement('li'); item.textContent = message; list.append(item); }
                box.append(list); box.hidden = false; box.scrollIntoView({ block: 'nearest' });
            } else notify([...messages].join(' '), 'error');
            return;
        }
        notify(error.message, 'error');
        if ([401, 419].includes(error.status)) {
            dirty.clear(); failedSaves.clear();
            await navigate(error.data.redirect || $('data-login-url')?.content || '/login', { replace: true, skipGuard: true });
        } else if (error.status === 409 && error.data.redirect) {
            await navigate(error.data.redirect, { replace: true, skipGuard: true });
        }
    }
    function confirmAction(message) {
        const dialog = $('data-confirm-dialog');
        if (!dialog?.showModal) return Promise.resolve(window.confirm(message));
        if (confirmResolve) return Promise.resolve(false);
        $('data-confirm-message', dialog).textContent = message;
        return new Promise(resolve => {
            confirmResolve = resolve;
            dialog.showModal();
            const cancel = [...dialog.querySelectorAll('[data-confirm-answer]')].find(button => button.dataset.confirmAnswer === 'cancel');
            cancel?.focus();
        });
    }
    function resolveConfirm(accepted) {
        const resolve = confirmResolve; confirmResolve = null;
        $('data-confirm-dialog')?.close(); resolve?.(accepted);
    }
    function applyTheme(theme) {
        if (!theme || typeof theme !== 'object') return;
        for (const [field, variable] of Object.entries({ accent: '--accent', background: '--background', surface: '--surface', text: '--ink' })) {
            if (/^#[0-9a-f]{6}$/i.test(theme[field])) document.documentElement.style.setProperty(variable, theme[field]);
        }
        if (['sidebar', 'topbar', 'focus'].includes(theme.layout)) document.documentElement.dataset.layout = theme.layout;
        document.documentElement.dataset.bsTheme = theme.is_dark ? 'dark' : 'light';
    }
    function applyFragments(fragments) {
        if (!fragments || typeof fragments !== 'object') return;
        // Only named regions in the existing document can be replaced.
        for (const region of $$('data-region')) {
            if (typeof fragments[region.dataset.region] === 'string') region.innerHTML = fragments[region.dataset.region];
        }
    }
    async function flushWrites() {
        let current;
        do { current = writeTail; await current; } while (current !== writeTail);
    }
    function connectedMembers(set) {
        for (const item of set) if (!item.isConnected) set.delete(item);
        return [...set];
    }
    function focusSnapshot(form) {
        const control = document.activeElement;
        return control && control.form === form && control.name ? { name: control.name, start: control.selectionStart, end: control.selectionEnd } : null;
    }
    function restoreFocus(snapshot) {
        if (!snapshot) { $('data-page')?.focus({ preventScroll: true }); return; }
        const form = $('data-filter'); const control = form?.elements.namedItem(snapshot.name);
        if (!(control instanceof HTMLElement)) return;
        control.focus({ preventScroll: true });
        if (typeof control.setSelectionRange === 'function' && typeof snapshot.start === 'number') {
            try { control.setSelectionRange(snapshot.start, snapshot.end); } catch { /* Select/number inputs have no caret. */ }
        }
    }
    async function navigate(url, options = {}) {
        const { replace = false, history: useHistory = true, skipGuard = false, focus = null } = options;
        try {
            const target = localUrl(url);
            await flushWrites();
            if (!skipGuard && connectedMembers(failedSaves).length) {
                notify('An answer has not been saved. Use Save answer to retry before changing questions.', 'error'); return false;
            }
            if (!skipGuard && connectedMembers(dirty).some(form => !form.hasAttribute('data-autosave'))) {
                if (!await confirmAction('Leave this page and discard unsaved changes?')) return false;
            }
            navigation?.abort(); navigation = new AbortController();
            const sequence = ++navSequence;
            progress(true);
            try {
                const data = await request(target, { signal: navigation.signal });
                if (sequence !== navSequence) return false;
                if (data.redirect && !data.html) return navigate(data.redirect, { ...options, skipGuard: true });
                if (typeof data.html !== 'string') throw new HttpError('The page response did not include content.');
                const canonical = localUrl(data.url || target);
                app().innerHTML = data.html;
                dirty.clear(); failedSaves.clear();
                if (data.title) document.title = data.title;
                applyTheme(data.theme); updateToken(data.csrf_token);
                if (useHistory) window.history[replace ? 'replaceState' : 'pushState']({ quizora: true }, '', canonical);
                activeUrl = canonical.href;
                initializePage(); restoreFocus(focus);
                if (!focus) window.scrollTo({ top: 0, behavior: 'instant' });
                document.dispatchEvent(new CustomEvent('quizora:navigated', { detail: { url: canonical.href } }));
                return true;
            } finally { progress(false); }
        } catch (error) { await showError(error); return false; }
    }
    function formBusy(form, start) {
        const count = Math.max(0, (formPending.get(form) || 0) + (start ? 1 : -1));
        formPending.set(form, count);
        form.setAttribute('aria-busy', count ? 'true' : 'false');
        for (const control of form.querySelectorAll('[type="submit"]')) {
            if (start && !originalDisabled.has(control)) originalDisabled.set(control, control.disabled);
            if (count) control.disabled = true;
            else if (originalDisabled.has(control)) { control.disabled = originalDisabled.get(control); originalDisabled.delete(control); }
        }
    }
    function savedState(form, message, state) {
        const element = $('data-save-state', form);
        if (element) { element.textContent = message; element.dataset.state = state; }
    }
    async function submit(form, submitter = null, options = {}) {
        if (!(form instanceof HTMLFormElement)) throw new TypeError('Quizora.submit requires a form element.');
        const method = (form.method || 'get').toUpperCase();
        clearTimeout(filterTimers.get(form));
        if (method === 'GET') {
            const url = localUrl(form.action);
            url.search = new URLSearchParams(new FormData(form)).toString();
            return navigate(url, { replace: form.hasAttribute('data-filter'), focus: options.focus || focusSnapshot(form) });
        }
        const autosave = form.hasAttribute('data-autosave');
        if ((formPending.get(form) || 0) > 0 && !autosave) return false;
        if (!form.checkValidity()) { form.reportValidity(); return false; }
        if (form.dataset.confirm && !await confirmAction(form.dataset.confirm)) return false;
        const data = new FormData(form);
        if (submitter?.name) data.append(submitter.name, submitter.value);
        const revision = revisions.get(form) || 0;
        pendingWrites++; formBusy(form, true);
        savedState(form, 'Saving your answer...', 'saving');
        // Capture the user's selection now, then submit it in order. Do not read a later DOM state.
        const job = writeTail.then(async () => {
            progress(true); clearErrors(form);
            data.set('_token', token());
            try {
                if (form.hasAttribute('data-requires-saves') && connectedMembers(failedSaves).length) {
                    throw new HttpError('An answer has not been saved. Use Save answer to retry before submitting your quiz.');
                }
                const result = await request(form.action, { method, body: data });
                if ((revisions.get(form) || 0) === revision) {
                    dirty.delete(form); failedSaves.delete(form);
                    savedState(form, 'Your answer is saved.', 'saved');
                }
                if (form.isConnected) applyFragments(result.fragments);
                return result;
            } finally { pendingWrites--; formBusy(form, false); progress(false); }
        });
        // Error display runs outside this promise: redirects cannot deadlock on their own write.
        writeTail = job.catch(() => {});
        try {
            const result = await job;
            if (result.message && !form.hasAttribute('data-quiet')) notify(result.message);
            if (result.redirect) return navigate(result.redirect, { skipGuard: true });
            document.dispatchEvent(new CustomEvent('quizora:saved', { detail: { form, result } }));
            return true;
        } catch (error) {
            if (autosave) failedSaves.add(form);
            savedState(form, 'Not saved. Check the error and use Save answer to retry.', 'error');
            await showError(error, form); return false;
        }
    }
    async function download(url) {
        progress(true);
        try {
            await flushWrites();
            const result = await request(url, { responseType: 'blob', timeout: 120000 });
            if (!result.blob) throw new HttpError('The server did not return a download.');
            const objectUrl = URL.createObjectURL(result.blob);
            const anchor = document.createElement('a'); anchor.href = objectUrl; anchor.download = result.filename;
            document.body.append(anchor); anchor.click(); anchor.remove();
            setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
        } catch (error) { await showError(error); } finally { progress(false); }
    }
    function initializePage() {
        clearInterval(countdownTimer);
        const clocks = $$('data-countdown');
        if (!clocks.length) return;
        const start = performance.now();
        const states = clocks.map(clock => ({ clock, remaining: Date.parse(clock.dataset.deadline) - Date.parse(clock.dataset.serverNow), requested: false }));
        const tick = () => {
            for (const state of states) {
                if (!state.clock.isConnected || !Number.isFinite(state.remaining)) continue;
                const seconds = Math.max(0, Math.ceil((state.remaining - (performance.now() - start)) / 1000));
                state.clock.textContent = `${Math.floor(seconds / 60).toString().padStart(2, '0')}:${(seconds % 60).toString().padStart(2, '0')}`;
                state.clock.dataset.urgent = seconds < 60 ? 'true' : 'false';
                if (seconds === 0 && !state.requested) {
                    state.requested = true;
                    navigate(state.clock.dataset.expireUrl, { replace: true, skipGuard: true }).then(ok => {
                        if (!ok) setTimeout(() => { state.requested = false; }, 5000);
                    });
                }
            }
        };
        tick(); countdownTimer = setInterval(tick, 500);
    }
    function changed(event, immediate) {
        const control = event.target;
        if (!(control instanceof HTMLElement)) return;
        const form = control.closest('form[data-ajax]');
        if (!form) return;
        if (form.hasAttribute('data-filter')) {
            clearTimeout(filterTimers.get(form));
            const snapshot = focusSnapshot(form);
            filterTimers.set(form, setTimeout(() => { if (form.isConnected) submit(form, null, { focus: snapshot }); }, immediate ? 0 : 350));
        } else {
            dirty.add(form); revisions.set(form, (revisions.get(form) || 0) + 1);
            if (immediate && form.hasAttribute('data-autosave')) submit(form);
        }
    }
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax')) return;
        event.preventDefault(); submit(form, event.submitter);
    });
    document.addEventListener('input', event => changed(event, false));
    document.addEventListener('change', event => changed(event, true));
    document.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;
        const answer = event.target.closest('[data-confirm-answer]');
        if (answer) { event.preventDefault(); resolveConfirm(answer.dataset.confirmAnswer === 'confirm'); return; }
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('a[data-nav],a[data-download]');
        if (!link || link.target === '_blank') return;
        event.preventDefault();
        if (link.hasAttribute('data-download')) download(link.href); else navigate(link.href);
    });
    $('data-confirm-dialog')?.addEventListener('cancel', event => { event.preventDefault(); resolveConfirm(false); });
    window.addEventListener('popstate', async () => {
        const previous = activeUrl;
        if (!await navigate(location.href, { history: false })) window.history.pushState({ quizora: true }, '', previous);
    });
    window.addEventListener('beforeunload', event => {
        if (pendingWrites || connectedMembers(dirty).length || connectedMembers(failedSaves).length) {
            event.preventDefault(); event.returnValue = '';
        }
    });
    window.history.replaceState({ quizora: true }, '', location.href);
    initializePage();
    window.Quizora = Object.freeze({ request, submit, navigate, download, notify, HttpError });
})();
