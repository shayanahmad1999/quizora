# Quizora

**An administrator-managed question-and-answer platform built for Laravel 13, Blade, Bootstrap, and one global AJAX module.** Includes a server-connected Electron desktop client.

Create learner accounts, organize a question bank by subject, publish timed quizzes, review results, and give learners a fixed or randomized visual theme. There is no public registration, no frontend framework, and no web asset compilation step.

## Start on Windows

Use PHP 8.5 (PHP 8.3+ is supported), Composer 2, and the PHP extensions described in [Windows setup](docs/WINDOWS-QUICKSTART.md). Extract the project to a normal writable folder, for example `D:\my-project\quizora`.

```powershell
cd D:\my-project\quizora
powershell -ExecutionPolicy Bypass -File .\scripts\bootstrap.ps1 -Demo
php artisan serve
```

Open `http://127.0.0.1:8000`. The installer asks you to create the first super administrator. **There is no default login or hard-coded production password.** A password must have at least 12 characters, uppercase, lowercase, and a number.

The installer prepares the environment, installs Composer dependencies and local Bootstrap assets, creates an application key only when absent, applies migrations, seeds themes, and optionally loads sample questions. It does not use `migrate:fresh`, overwrite an existing `.env`, or recreate a Laravel application over your files.

`-Demo` adds five subjects, 40 questions, and five published example quizzes. Omit it for a clean question bank. It does not create learner accounts. Use **People > New account** after signing in.

For automatic finalization when learners close their browsers, run in another terminal:

```powershell
php artisan schedule:work
```

The server also checks expiry whenever an attempt is read, answered, or submitted. A closed browser never pauses the deadline.

## What is included

| Area | Implemented capabilities |
| --- | --- |
| Accounts | Admin-created learners and super administrators; active/inactive state; temporary passwords; mandatory initial password change; admin password reset; account editing/deletion. |
| Categories | Add, edit, search, paginate, activate/deactivate, and delete unused subject categories. |
| Question bank | Four answer choices, one correct answer, difficulty, explanation, category, active state, search and filtering. |
| Quiz studio | Category-based sampling, number of questions, duration, passing percentage, attempt limit, question/choice shuffling, draft/published state, all learners or selected learners, answer-review setting. |
| Learner workspace | Available quizzes, timed attempts, answer autosave, previous/next/question navigation, submit, results, and attempt history. |
| Appearance | Six starter palettes, three structural layouts, theme creation/editing, fixed per-user assignment, or random-at-login from an allowed pool. |
| Reporting | Paginated administrator results, learner/quiz/status filters, summary metrics, CSV export, and a management/attempt activity log. |
| Settings | Workspace name, login tagline, support email, and default theme. |
| Shared AJAX | Forms, edits, deletions, navigation, filtering, pagination, autosave, error display, confirmations, CSRF-token refresh, CSV downloads, and theme changes. |
| Desktop | Electron source, local-server or HTTPS-server configuration, isolated renderer, navigation restrictions, Windows installer build configuration. |

## One global AJAX module

All browser behavior is in `public/assets/js/ajax.js`. Blade marks capabilities with `data-*` attributes; there are no module-specific DOM-ID handlers, jQuery files, inline `onclick` code, React, Vue, Alpine, or Livewire.

```blade
<form action="{{ route('admin.categories.store') }}" method="post" data-ajax>
    @csrf
    <x-errors />
    <input name="name" required maxlength="100">
    <input name="color" type="hidden" value="#087F8C">
    <input name="is_active" type="hidden" value="1">
    <button type="submit">Save category</button>
</form>

<a href="{{ route('admin.categories.index', ['page' => 2]) }}" data-nav>
    Next page
</a>
```

Shared code still needs to locate marked forms and UI regions through data attributes. "Global" means **no separate per-page selectors or AJAX implementations**, not zero interaction with the DOM. CSS classes are used for visual styling, not to identify application actions.

The first browser request loads the document. Subsequent ordinary internal actions and links use fetch and server-rendered Blade fragments. A deliberate hard refresh, modified-click/new tab, browser startup, and a download's native save dialog remain normal browser behaviors.

See [the full AJAX contract](docs/ARCHITECTURE.md#global-ajax-contract) before adding modules.

## Theme behavior

- **Fixed:** choose a particular active theme on the user's account.
- **Random at login:** select an allowed pool, or leave it empty for all active themes. The server selects a theme at sign-in and keeps it stable through that session. An immediate repeat is avoided when there is more than one eligible theme.

The layouts are **Sidebar**, **Topbar**, and **Focus**. Theme records control the layout, accent, page background, surface color, text color, and dark/light mode. Random themes are not guaranteed to be unique across users; two users can receive the same theme. Administrators can create additional themes.

## Electron desktop client

The desktop client connects to this Laravel application; it does **not** bundle PHP, a local database, or offline synchronization.

After the web server is running:

```powershell
cd desktop
npm install --save-dev --save-exact electron@latest electron-builder@latest
npm start
```

For a Windows installer, configure `desktop/config.json` with the deployed HTTPS server URL, then run:

```powershell
npm run dist:win
```

The default URL is `http://127.0.0.1:8000` for local development. Remote plain HTTP is rejected. No Electron binary, signed installer, or automatic updater is shipped in this source archive. See [Desktop setup](desktop/README.md).

## Layout previews

[Sidebar](docs/previews/admin-sidebar.png) | [Topbar](docs/previews/admin-topbar.png) | [Focus](docs/previews/admin-focus.png) | [Sign-in](docs/previews/sign-in.png) | [Mobile](docs/previews/mobile-sidebar.png)

These are static CSS/markup fixtures, not screenshots of a running Laravel backend. See [preview notes](docs/previews/README.md).

## Architecture

```text
app/
  Console/Commands/       Installer, admin creation, expired-attempt finalization
  Domain/                Pure scoring logic
  Http/Controllers/      Thin HTTP orchestration; separate Admin controllers
  Http/Middleware/       Active-account, role, and security-header boundaries
  Http/Requests/         Server-side validation and administrator authorization
  Models/                Eloquent models and relationships
  Policies/              Attempt ownership
  Services/              Transactional quiz, user, theme, and audit operations
  Support/               Shared page responses and validated list filtering
bootstrap/               Laravel application configuration
config/                  Explicit framework configuration
resources/views/         Blade pages and reusable field/table/pagination components
public/assets/css/       Shared styles and three layout variants
public/assets/js/        The one global AJAX module
routes/                  Web endpoints and scheduled maintenance
scripts/                 Windows/Linux setup and Bootstrap publishing
storage/                 Runtime cache, views, sessions, and logs
database/               Migrations and repeatable seeders
desktop/                Electron client source and URL-policy tests
tests/                  PHPUnit tests, pure PHP checks, browser contract tests
docs/                   Setup, architecture, deployment, usage, test status
```

## Verification and release status

This archive is a **source-code implementation**, not a preinstalled or deployment-certified build.

Executed in the build environment:

- PHP syntax checks on all 70 non-Blade PHP files.
- 14 dependency-free scoring checks.
- 11 Electron URL-policy checks under Node.
- 17 Chromium checks against the actual global AJAX script and a **mock HTTP backend**.

Included but **not executed here**: 29 Laravel feature tests and 5 PHPUnit unit tests. Composer dependencies could not be installed in the build environment, which lacks network resolution and several required PHP extensions. Consequently, Laravel boot, Blade compilation, actual database migrations, live authentication/CSRF integration, and the Windows installer have not been verified by execution here. Electron packaging, signing, and native installation were also not executed.

Install the dependencies and run `composer test` before treating this as a validated application on your own environment. [TESTING.md](docs/TESTING.md) records exactly what the checks do and do not establish.

`vendor`, `node_modules`, application secrets, a prebuilt database, `composer.lock`, and `package-lock.json` are intentionally absent. Resolve dependencies on your machine, test them, and commit both generated lockfiles before team development or deployment. Laravel is constrained to `^13.0`; Electron dependencies are resolved and pinned by the command above rather than presented as an already-verified release.

## Scope boundaries

This version supports **single-answer, four-choice questions** and one subject per quiz. Scoring is one mark per correct answer, zero for incorrect or unanswered responses, with no negative marking. It is a practice/assessment workspace, not a proctoring or lockdown system.

It does not include essay marking, multiple-correct answers, a code-execution sandbox, question file uploads, spreadsheet question import, email delivery, MFA/SSO, subscription billing, offline attempt synchronization, scheduled examination windows, or a self-contained offline desktop server. Admin password reset is provided; self-service email reset is not.

Review display is immediate after an attempt is finalized when enabled. Disable it for assessments where later retakes or other learners must not see correct answers. Access-control and theme differences are separate concerns: a theme never grants permissions.

## Documentation

[Windows quick start](docs/WINDOWS-QUICKSTART.md) | [Usage walkthrough](docs/USAGE.md) | [Architecture / AJAX contract](docs/ARCHITECTURE.md) | [Deployment](docs/DEPLOYMENT.md) | [Testing](docs/TESTING.md) | [Desktop](desktop/README.md) | [Dependencies](docs/DEPENDENCIES.md)

## License

Application source is MIT licensed. Dependency licenses remain applicable. Bootstrap's MIT license is copied alongside its assets by `scripts/assets.php` after installation.
