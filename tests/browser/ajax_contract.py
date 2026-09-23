#!/usr/bin/env python3
"""Test the real ajax.js in Chromium against an explicitly mocked HTTP contract.

Not a Laravel integration test. Run separately from `composer test`:
    python -m pip install -r tests/browser/requirements.txt
    python -m playwright install chromium
    python tests/browser/ajax_contract.py
Set CHROMIUM_PATH to an existing browser executable when needed.
"""
from __future__ import annotations

import json
import os
import threading
import time
from email.parser import BytesParser
from email.policy import default
from html import escape
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlparse

from playwright.sync_api import sync_playwright, expect

ROOT = Path(__file__).resolve().parents[2]
STATE = {"writes": [], "fail_answer": False, "active": 0, "max_active": 0}
LOCK = threading.Lock()


def form(action: str, extra: str = "", fields: str = "") -> str:
    return (f'<form method="post" action="{action}" data-ajax {extra}>'
            '<input type="hidden" name="_token" value="initial-token">'
            '<div data-errors hidden role="alert"></div>' + fields +
            '<button type="submit">Save</button></form>')


def page_body(path: str, query: dict[str, list[str]]) -> str:
    nav = ('<nav><a data-nav href="/catalog">Catalog</a> '
           '<a data-nav href="/form">New record</a> '
           '<a data-nav href="/attempt">Attempt</a></nav>')
    if path == '/form':
        body = '<h1>New record</h1>' + form('/save', fields='<label>Name<input name="title" required></label>')
    elif path == '/validation':
        body = '<h1>Validation</h1>' + form('/invalid', fields='<input name="title" value="bad">')
    elif path == '/delete':
        body = '<h1>Delete</h1>' + form('/delete', 'data-confirm="Delete this record?"', '<input type="hidden" name="_method" value="DELETE">')
    elif path == '/attempt':
        fields = ('<label><input name="selected_option" type="radio" value="a">Alpha</label>'
                  '<label><input name="selected_option" type="radio" value="b">Beta</label>'
                  '<label><input name="selected_option" type="radio" value="c">Gamma</label>'
                  '<input name="_method" type="hidden" value="PATCH">'
                  '<span data-save-state>Choose an answer</span>')
        body = '<h1>Attempt</h1>' + form('/answer', 'data-autosave data-quiet', fields)
        body += '<div data-region="summary">None saved</div><a data-nav href="/next">Next question</a>'
        body += form('/finish', 'data-requires-saves').replace('>Save</button>', '>Finish</button>')
    elif path == '/rotation':
        body = '<h1>Token rotation</h1>' + form('/rotate') + form('/echo')
    elif path == '/session':
        body = '<h1>Session</h1>' + form('/unauthenticated')
    elif path == '/server-error':
        body = '<h1>Failure</h1>' + form('/error')
    elif path == '/timer':
        now = time.time()
        body = '<h1>Timed attempt</h1><strong data-countdown data-server-now="{}" data-deadline="{}" data-expire-url="/expired"></strong>'.format(
            time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime(now)),
            time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime(now + 2)))
    elif path == '/catalog':
        q = escape(query.get('q', [''])[0], quote=True)
        current_page = escape(query.get('page', ['1'])[0])
        body = ('<h1>Catalog</h1><form action="/catalog" method="get" data-ajax data-filter>'
                f'<label>Search<input name="q" value="{q}"></label>'
                '<button type="submit">Filter</button></form>'
                f'<p data-current-page>{current_page}</p><p data-query>{q}</p>'
                '<a data-nav href="/catalog?page=2">Page two</a> '
                '<a data-download href="/export">Export</a>')
    else:
        body = f'<h1>{escape(path.strip("/").replace("-", " ").title() or "Home")}</h1>'
    return nav + f'<main data-page tabindex="-1">{body}</main>'


def document(body: str) -> str:
    return ('<!doctype html><html><head><meta charset="utf-8">'
            '<meta data-csrf content="initial-token"><meta data-login-url content="/login">'
            '<title>Contract fixture</title><script defer src="/assets/js/ajax.js"></script>'
            '<style>body{font:16px sans-serif;padding:2rem}nav{margin-bottom:2rem}a,button{margin:8px}label,input{margin:5px}[hidden]{display:none!important}</style>'
            '</head><body><div data-progress hidden>Loading</div><div data-notices></div>'
            f'<div data-app>{body}</div><dialog data-confirm-dialog>'
            '<p data-confirm-message></p><button data-confirm-answer="cancel">Cancel</button>'
            '<button data-confirm-answer="confirm">Continue</button></dialog></body></html>')


class Handler(BaseHTTPRequestHandler):
    def log_message(self, *args):
        return

    def send(self, status: int, body: str | bytes | dict, content_type: str = 'application/json'):
        if isinstance(body, dict):
            body = json.dumps(body)
        if isinstance(body, str):
            body = body.encode()
        self.send_response(status)
        self.send_header('Content-Type', content_type)
        self.send_header('Content-Length', str(len(body)))
        self.send_header('Cache-Control', 'no-store')
        if content_type == 'text/csv':
            self.send_header('Content-Disposition', 'attachment; filename="results.csv"')
        self.end_headers()
        try:
            self.wfile.write(body)
        except (BrokenPipeError, ConnectionResetError):
            pass  # Deliberately aborted GET requests are part of the tests.

    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == '/favicon.ico':
            return self.send(204, b'', 'image/x-icon')
        if parsed.path == '/assets/js/ajax.js':
            return self.send(200, (ROOT / 'public/assets/js/ajax.js').read_bytes(), 'application/javascript')
        if parsed.path == '/export':
            return self.send(200, 'Name,Score\nFixture,100\n', 'text/csv')
        if parsed.path == '/slow':
            time.sleep(.5)
        body = page_body(parsed.path, parse_qs(parsed.query))
        if self.headers.get('X-Requested-With') == 'XMLHttpRequest':
            return self.send(200, {'html': body, 'title': parsed.path, 'url': self.path,
                                  'theme': {'accent': '#665599', 'background': '#f5f4f8',
                                            'surface': '#ffffff', 'text': '#192d3d',
                                            'layout': 'topbar', 'is_dark': False}})
        return self.send(200, document(body), 'text/html')

    def do_POST(self):
        raw = self.rfile.read(int(self.headers.get('Content-Length', 0)))
        message = BytesParser(policy=default).parsebytes(
            ('Content-Type: ' + self.headers.get('Content-Type', '') + '\r\nMIME-Version: 1.0\r\n\r\n').encode() + raw)
        fields = {}
        if message.is_multipart():
            for part in message.iter_parts():
                fields[part.get_param('name', header='Content-Disposition')] = part.get_payload(decode=True).decode()
        else:
            fields = {k: v[0] for k, v in parse_qs(raw.decode()).items()}
        with LOCK:
            STATE['writes'].append({'path': self.path, 'fields': fields,
                                    'token': self.headers.get('X-CSRF-TOKEN'),
                                    'ajax': self.headers.get('X-Requested-With')})
            STATE['active'] += 1
            STATE['max_active'] = max(STATE['max_active'], STATE['active'])
        try:
            if self.path == '/answer':
                time.sleep(.15)
                if STATE['fail_answer']:
                    return self.send(500, {'message': 'Fixture save failed.'})
                return self.send(200, {'fragments': {'summary': '<strong>Saved ' + escape(fields.get('selected_option', '')) + '</strong>'}})
            if self.path == '/invalid':
                return self.send(422, {'message': 'Invalid data', 'errors': {'title': ['A better title is required.', 'A better title is required.']}})
            if self.path == '/rotate':
                return self.send(200, {'csrf_token': 'rotated-token', 'message': 'Token rotated.'})
            if self.path == '/unauthenticated':
                return self.send(401, {'message': 'Session expired.', 'redirect': '/login'})
            if self.path == '/error':
                return self.send(500, {'message': 'Fixture server error.'})
            if self.path == '/finish':
                return self.send(200, {'redirect': '/finished'})
            if self.path in ['/save', '/delete']:
                time.sleep(.08)
                return self.send(200, {'redirect': '/catalog', 'message': 'Record saved.'})
            return self.send(200, {'message': 'Saved.'})
        finally:
            with LOCK:
                STATE['active'] -= 1


def run():
    server = ThreadingHTTPServer(('127.0.0.1', 0), Handler)
    threading.Thread(target=server.serve_forever, daemon=True).start()
    base = f'http://127.0.0.1:{server.server_port}'
    passed = []
    javascript_errors = []
    try:
        with sync_playwright() as p:
            kwargs = {'headless': True}
            if os.environ.get('CHROMIUM_PATH'):
                kwargs['executable_path'] = os.environ['CHROMIUM_PATH']
            browser = p.chromium.launch(**kwargs)
            context = browser.new_context(accept_downloads=True)
            page = context.new_page()
            page.on('pageerror', lambda error: javascript_errors.append(str(error)))

            def check(name, callback):
                callback()
                passed.append(name)
                print('PASS ' + name, flush=True)

            def fresh(path):
                page.goto(base + path)
                page.wait_for_function('Boolean(window.Quizora)')

            def navigate(path):
                assert page.evaluate('(path) => Quizora.navigate(path, {skipGuard: true})', path)

            def create():
                fresh('/form')
                page.evaluate('window.__documentMarker = 123')
                page.locator('[name="title"]').fill('A new category')
                page.get_by_role('button', name='Save', exact=True).click()
                expect(page.locator('h1')).to_have_text('Catalog')
                assert page.evaluate('window.__documentMarker') == 123
                last = STATE['writes'][-1]
                assert last['fields']['title'] == 'A new category'
                assert last['ajax'] == 'XMLHttpRequest' and last['token'] == 'initial-token'
            check('Delegated form creation and AJAX redirect without document reload', create)

            def validation():
                navigate('/validation')
                page.get_by_role('button', name='Save', exact=True).click()
                expect(page.locator('[data-errors] li')).to_have_count(1)
                expect(page.locator('[name="title"]')).to_have_attribute('aria-invalid', 'true')
                assert page.url.endswith('/validation')
            check('422 errors are deduplicated and connected to form controls', validation)

            def deletion():
                navigate('/delete')
                count = len(STATE['writes'])
                page.get_by_role('button', name='Save', exact=True).click()
                page.get_by_role('button', name='Cancel', exact=True).click()
                page.wait_for_timeout(80)
                assert len(STATE['writes']) == count
                page.get_by_role('button', name='Save', exact=True).click()
                page.get_by_role('button', name='Continue', exact=True).click()
                expect(page.locator('h1')).to_have_text('Catalog')
                assert STATE['writes'][-1]['fields']['_method'] == 'DELETE'
            check('Delete confirmation cancellation and method spoofing', deletion)

            def filters():
                field = page.locator('[name="q"]')
                field.fill('phy & chemistry')
                expect(page.locator('[data-query]')).to_have_text('phy & chemistry')
                expect(page.locator('[name="q"]')).to_be_focused()
                assert parse_qs(urlparse(page.url).query)['q'] == ['phy & chemistry']
                page.get_by_role('link', name='Page two', exact=True).click()
                expect(page.locator('[data-current-page]')).to_have_text('2')
                page.go_back()
                expect(page.locator('[data-query]')).to_have_text('phy & chemistry')
            check('Debounced filtering, caret restoration, AJAX pagination and browser back', filters)

            def autosave():
                navigate('/attempt')
                STATE['writes'].clear()
                STATE['max_active'] = 0
                page.evaluate('''() => {
                    const form = document.querySelector('[data-autosave]');
                    for (const value of ['a','b','c']) {
                        const input = [...form.elements].find(x => x.name === 'selected_option' && x.value === value);
                        input.checked = true;
                        input.dispatchEvent(new Event('change', {bubbles:true}));
                    }
                    document.querySelector('a[href="/next"]').click();
                }''')
                expect(page.locator('h1')).to_have_text('Next')
                assert [row['fields']['selected_option'] for row in STATE['writes']] == ['a', 'b', 'c']
                assert STATE['max_active'] == 1
            check('Rapid answer changes are saved FIFO; navigation waits for every save', autosave)

            def failed_save():
                navigate('/attempt')
                STATE['fail_answer'] = True
                page.locator('[value="a"]').check()
                expect(page.locator('[data-save-state]')).to_have_attribute('data-state', 'error')
                page.get_by_role('link', name='Next question').click()
                page.wait_for_timeout(120)
                assert page.url.endswith('/attempt')
                STATE['fail_answer'] = False
                page.get_by_role('button', name='Save', exact=True).click()
                expect(page.locator('[data-save-state]')).to_have_attribute('data-state', 'saved')
                expect(page.locator('[data-region="summary"]')).to_have_text('Saved a')
                page.get_by_role('link', name='Next question').click()
                expect(page.locator('h1')).to_have_text('Next')
            check('Failed autosave blocks navigation until an explicit successful retry', failed_save)

            def failed_finalization():
                navigate('/attempt')
                STATE['fail_answer'] = True
                before = len([x for x in STATE['writes'] if x['path'] == '/finish'])
                page.evaluate("""() => {
                    const input = document.querySelector('[value="b"]');
                    input.checked = true;
                    input.dispatchEvent(new Event('change', {bubbles:true}));
                    Quizora.submit(document.querySelector('form[action="/finish"]'));
                }""")
                expect(page.get_by_text('An answer has not been saved. Use Save answer to retry before submitting your quiz.', exact=True)).to_be_visible()
                assert len([x for x in STATE['writes'] if x['path'] == '/finish']) == before
                assert page.url.endswith('/attempt')
                STATE['fail_answer'] = False
                page.get_by_role('button', name='Save', exact=True).click()
                expect(page.locator('[data-save-state]')).to_have_attribute('data-state', 'saved')
                page.get_by_role('button', name='Finish', exact=True).click()
                expect(page.locator('h1')).to_have_text('Finished')
            check('Final submission cannot discard an answer whose queued save failed', failed_finalization)

            def rotation():
                navigate('/rotation')
                page.locator('form[action="/rotate"] button').click()
                expect(page.locator('[data-csrf]')).to_have_attribute('content', 'rotated-token')
                page.locator('form[action="/echo"] button').click()
                page.wait_for_function('document.querySelector("form[action=\'/echo\']").getAttribute("aria-busy") === "false"')
                row = STATE['writes'][-1]
                assert row['token'] == 'rotated-token' and row['fields']['_token'] == 'rotated-token'
            check('Rotated CSRF token reaches both the request header and form body', rotation)

            def dirty_guard():
                navigate('/form')
                page.locator('[name="title"]').fill('Unsaved')
                page.get_by_role('link', name='Catalog', exact=True).click()
                page.get_by_role('button', name='Cancel', exact=True).click()
                assert page.url.endswith('/form')
                page.get_by_role('link', name='Catalog', exact=True).click()
                page.get_by_role('button', name='Continue', exact=True).click()
                expect(page.locator('h1')).to_have_text('Catalog')
            check('Unsaved regular forms require confirmation before navigation', dirty_guard)

            def session_failure():
                navigate('/session')
                before = len([x for x in STATE['writes'] if x['path'] == '/unauthenticated'])
                page.get_by_role('button', name='Save', exact=True).click()
                expect(page.locator('h1')).to_have_text('Login')
                page.wait_for_timeout(80)
                after = len([x for x in STATE['writes'] if x['path'] == '/unauthenticated'])
                assert after == before + 1
            check('401 redirects to login without replaying the failed mutation', session_failure)

            def server_failure():
                navigate('/server-error')
                page.get_by_role('button', name='Save', exact=True).click()
                expect(page.get_by_text('Fixture server error.', exact=True)).to_be_visible()
                expect(page.get_by_role('button', name='Save', exact=True)).to_be_enabled()
                expect(page.locator('[data-progress]')).to_be_hidden()
            check('500 errors restore form controls and clear progress state', server_failure)

            def superseded():
                page.evaluate("Quizora.navigate('/slow'); setTimeout(() => Quizora.navigate('/fast'), 30);")
                expect(page.locator('h1')).to_have_text('Fast')
                page.wait_for_timeout(650)
                expect(page.locator('h1')).to_have_text('Fast')
            check('A stale GET response cannot overwrite newer navigation', superseded)

            def download():
                navigate('/catalog')
                with page.expect_download() as info:
                    page.get_by_role('link', name='Export', exact=True).click()
                assert info.value.suggested_filename == 'results.csv'
                assert 'Fixture,100' in Path(info.value.path()).read_text()
                assert page.url.endswith('/catalog')
            check('CSV is fetched through the shared download function', download)

            def theme():
                assert page.evaluate('document.documentElement.dataset.layout') == 'topbar'
                assert page.evaluate("document.documentElement.style.getPropertyValue('--accent')") == '#665599'
                assert page.evaluate('document.documentElement.dataset.bsTheme') == 'light'
            check('Server-returned theme updates the shell without a document reload', theme)

            def origin():
                result = page.evaluate("Quizora.request('https://example.invalid/private').then(() => false).catch(e => e.message)")
                assert 'inside your workspace' in result
            check('Cross-origin AJAX requests are rejected before fetch', origin)

            def timer():
                navigate('/timer')
                expect(page.locator('[data-countdown]')).to_have_attribute('data-urgent', 'true')
                expect(page.locator('h1')).to_have_text('Expired', timeout=6000)
            check('Countdown expiry refreshes the server result page', timer)

            def no_errors():
                assert javascript_errors == [], javascript_errors
            check('No uncaught JavaScript errors across the contract scenarios', no_errors)
            browser.close()
    finally:
        server.shutdown()
        server.server_close()
    print(f'\n{len(passed)} browser contract checks passed. Backend responses were mocked.')


if __name__ == '__main__':
    run()
