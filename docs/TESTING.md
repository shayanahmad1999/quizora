# Verification record

Build-environment verification date: **22 September 2026**.

This file distinguishes executed checks from supplied tests that still require dependencies and a working Laravel runtime. Passing a syntax check or mocked browser test does not establish that the Laravel application boots or that its database/security integration is correct.

## Executed checks

| Check | Result | What it establishes |
| --- | --- | --- |
| `php -l` on all 70 non-Blade PHP files | Passed | PHP syntax under PHP 8.4.23; not framework method compatibility or Blade compilation. |
| `php tests/domain-smoke.php` | 14 checks passed | Actual pure PHP scoring behavior, threshold boundaries, invalid thresholds, empty attempts, and iterable inputs. |
| `node --test desktop/tests/*.test.cjs` | 11 checks passed | Actual Electron URL-policy helper behavior under Node 22.16.0; not native Electron execution. |
| Chromium browser contract suite | 17 checks passed | Actual production `ajax.js` running in Chromium 144 against a local mocked HTTP server. |
| JavaScript `node --check` | Passed | Syntax of the production AJAX module and Electron CommonJS source/tests. |
| JSON and PHPUnit XML parse | Passed | Configuration/document syntax, not dependency resolution or runtime correctness. |
| Installer preflight negative path | Passed | Reports unavailable CLI extensions and exits before attempting Artisan or modifying an environment. |
| Static visual fixtures | Rendered | Sidebar, topbar, focus, sign-in, and mobile CSS layouts. Fixture content had no page-level horizontal overflow at the checked viewports. Not live Laravel screenshots. |

Raw results are preserved in `docs/verification/`. Preview caveats are in `docs/previews/README.md`.

## What the 17 browser checks cover

1. A delegated form write and AJAX redirect, without replacing the browser document.
2. HTTP 422 message deduplication and field error state.
3. Delete confirmation cancel/confirm and Laravel-style method spoofing.
4. Debounced filtering, focus restoration, pagination, and browser back.
5. Rapid answer changes saved FIFO, with navigation waiting for all writes.
6. Failed autosave blocking navigation until explicit successful retry.
7. Final submission blocked when the preceding queued answer save failed.
8. Rotated CSRF tokens applied to both the header and form body.
9. Unsaved regular-form leave confirmation.
10. HTTP 401 transition to sign-in without replaying a failed write.
11. HTTP 500 recovery of controls and progress state.
12. Superseded GET responses unable to overwrite a newer page.
13. Shared CSV fetch/download behavior.
14. Theme updates without reloading the document.
15. Cross-origin AJAX requests rejected before fetch.
16. Countdown expiry requesting the result page.
17. No uncaught JavaScript errors across those scenarios.

The mock backend intentionally supplies deterministic responses. These tests do **not** exercise Laravel CSRF verification, authentication, policies, routing, queries, locks, migrations, actual Blade rendering, or persisted results.

## Included but not executed in the build environment

**29 Laravel feature tests** in `tests/Feature/WorkspaceTest.php` cover intended admin/learner boundaries, account creation, forced password replacement, active/versioned sessions, login throttling, administrator protection, theme rules, management-screen rendering, quiz access and limits, snapshot behavior, ownership, late answers, idempotent finalization, review visibility, export escaping, and repeatable seeds.

**5 PHPUnit unit tests** in `tests/Unit/ScoreCalculatorTest.php` cover scoring separately through PHPUnit. The 14 independent domain checks above exercised the same pure scoring class without needing Composer.

No Composer install, Laravel boot, real database migration, Blade compilation, full-stack browser login, live assessment flow, Windows PowerShell bootstrap execution, Electron application launch, native packaging/signing, or installer installation was completed in the build environment. It had PHP 8.4.23 but lacked mbstring, DOM/XML, PDO database drivers, Composer, and working external hostname resolution. The source archive therefore has no resolved dependency lockfiles or installed dependencies.

There has been no multi-user load test, penetration test, accessibility audit, backup-restore exercise, or database-engine concurrency certification. The code is supplied for installation and acceptance testing, not with a production-readiness guarantee.

## Run the Laravel suite after installation

```powershell
composer test
```

The PHPUnit configuration uses an in-memory SQLite database with test-only settings, not the normal application database. Enable `pdo_sqlite` for the test runner even when the main application uses a different database. Do not use the test APP_KEY or relaxed bcrypt rounds in production.

A successful PHPUnit run should include 29 feature methods and 5 unit methods, subject to any updates you make. If a test fails, keep the failure output and fix the underlying issue rather than disabling authorization checks or marking tests skipped to create a green result.

## Run independent checks

From the project root:

```powershell
php tests/domain-smoke.php
node --test desktop/tests/*.test.cjs
```

To run the optional browser contract suite:

```powershell
python -m pip install -r tests/browser/requirements.txt
python -m playwright install chromium
python tests/browser/ajax_contract.py
```

The browser script starts its own loopback mock server and shuts it down after the tests. It does not need the Laravel server or a database. An existing compatible Chromium executable can be selected using the `CHROMIUM_PATH` environment variable. A corporate browser policy that forbids loopback navigation will prevent this test from running; use an approved test-browser environment.

## Acceptance tests still required

Install on the target Windows/PHP/Composer environment and run all PHPUnit tests. Check actual first-admin creation, private-window learner login, required password change, every CRUD screen, combined filters and pagination, answer saving, failed-connection recovery, finalization, and CSV downloads.

Test two real sessions with attempts near the deadline, simultaneous Start requests, a disabled account, a password reset, foreign attempt IDs, and attempts on your chosen production database. Verify the application key and database remain unchanged across a second bootstrap. Check the scheduler while all learner browsers are closed.

For Electron, start against the real server, test login/session persistence and unavailable-server recovery, produce the installer on Windows, and verify a clean install/uninstall. Repeat for any other desktop operating system you plan to distribute.

Retest after pinning the actual resolved dependency versions. Commit the generated lockfiles and record the tested PHP, database, Laravel, Node, Electron, and operating-system versions.
