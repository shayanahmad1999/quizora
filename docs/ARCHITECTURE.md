# Architecture and extension guide

## Chosen shape

Quizora is a Laravel monolith with session authentication and server-rendered Blade. The browser has one small application layer: `public/assets/js/ajax.js`. It asks Laravel for JSON envelopes containing server-rendered page HTML, never executable JavaScript from a response.

```text
Browser / Electron renderer
    -> delegated data-* action
    -> shared request / navigation / write queue
    -> Laravel web middleware and session/CSRF boundary
    -> active-account and role/ownership authorization
    -> FormRequest validation
    -> controller -> transactional service -> Eloquent/database
    <- PageResponse JSON + rendered Blade fragment
    <- shell, theme, validation state, notification, or download
```

Business rules remain on the server. A disabled button, hidden navigation entry, theme choice, or client-side input constraint is not an authorization mechanism.

## Core relationships

`Category` owns bank questions and quizzes. Each quiz samples active questions from one category when an attempt starts. `Quiz` has optional assigned users through `quiz_user`. `User` has optional allowed random themes through `theme_user`, plus fixed/current-theme preferences.

`Attempt` belongs to one learner and one quiz. It records the immutable assessment settings and has many `AttemptQuestion` snapshots. Snapshots store their answer-choice display order as a JSON **array of key/text pairs**, not JSON object property order, so the order is explicit across database engines. Source question choices remain a keyed map.

`Setting` is a singleton. `AuditLog` records selected administrative and attempt lifecycle actions. Users, categories, quizzes, and questions support soft deletion; attempt relationships use archived source rows where needed. No generic delete cascade silently destroys the assessment history.

The platform has one workspace; it is **not** a multi-company/multi-tenant product. Roles are validated strings, not PHP-backed enums requiring a separate seed-value mapping.

## Service boundaries

| Component | Responsibility |
| --- | --- |
| `QuizAttemptService` | Start/reuse attempts, enforce availability and limits, snapshot questions, validate owned snapshot IDs, persist choices, enforce server expiry, finalize results transactionally. |
| `ScoreCalculator` | Dependency-free score computation and exact-threshold pass decisions. |
| `UserService` | Hash-aware account writes, session version changes, theme assignment, administrator guardrails, transaction/lock ordering. |
| `ThemeService` | Fixed assignment, random-at-login pool, immediate-repeat avoidance, session stability, fallback theme. |
| `AuditService` | Explicit action descriptions without logging submitted credentials or entire request bodies. |
| `PageResponse` | Initial document versus AJAX shell response, shared theme/settings data, redirect-style mutation responses. |
| `ListFilters` | Validated pagination/search inputs and parameter-bound search. Column names are supplied by application code, never accepted from the user. |

Simple category/question/theme CRUD stays small. Use a dedicated service when a module introduces meaningful invariants, transactions, or lifecycle behavior; do not add abstraction layers merely to rename Eloquent calls.

## Global AJAX contract

The browser uses semantic elements and shared data attributes, not module IDs or class-based event selectors. Components can retain CSS classes for styling. Prefer a native form element with a correct action/method over a custom API invocation.

| Marker | Meaning |
| --- | --- |
| `data-ajax` | Submit through the one global form handler. |
| `data-nav` | Follow an internal link through AJAX page navigation. |
| `data-filter` | GET form with debounced input/immediate select filtering and focus restoration. |
| `data-confirm="..."` | Show the shared accessible confirmation dialog before submitting. |
| `data-autosave` | Serialize answer changes automatically on change; preserve the choice captured at the time of the event. |
| `data-requires-saves` | Refuse finalization while an answer save has failed; pending saves still run first. |
| `data-quiet` | Suppress routine success toasts, useful for autosave. |
| `data-errors` | Validation summary for this form; messages are inserted as text, deduplicated, and associated with field names. |
| `data-save-state` | Current autosave state inside the form. |
| `data-region="name"` | Server-rendered fragment target within the current page. |
| `data-download` | Fetch a file through the shared download helper. |
| `data-countdown` | Countdown with `data-deadline`, `data-server-now`, and `data-expire-url`. |
| `data-app`, `data-page` | Shell replacement boundary and focus target. |
| `data-csrf`, `data-login-url` | Document metadata used by the shared request layer. |

### Create and update

```blade
<form method="post" action="{{ route('admin.questions.update', $question) }}" data-ajax>
    @csrf
    @method('PUT')
    <x-errors />
    {{-- Render the validated question fields here. --}}
    <button type="submit">Save changes</button>
</form>
```

No `updateQuestion()`, `saveCategory()`, or per-page DOM lookup is needed. Actual forms in `resources/views/admin/forms` contain the full required inputs and reusable field/toggle components.

### Delete

```blade
<form method="post" action="{{ route('admin.questions.destroy', $question) }}"
      data-ajax data-confirm="Delete this question? Existing attempts are retained.">
    @csrf
    @method('DELETE')
    <x-errors />
    <button type="submit">Delete</button>
</form>
```

### Filter and paginate

```blade
<form method="get" action="{{ route('admin.questions.index') }}" data-ajax data-filter>
    <input name="q" value="{{ request('q') }}" placeholder="Search questions">
    <button type="submit">Apply</button>
</form>
<a href="{{ $records->nextPageUrl() }}" data-nav>Next</a>
```

Use the reusable pagination component for actual production lists; it handles unavailable previous/next pages and renders the correct query string. Filtering is performed by the database, not by downloading every account or question into JavaScript.

### Server envelopes

A GET page request carries `Accept: application/json` and `X-Requested-With: XMLHttpRequest`:

```json
{
  "url": "http://127.0.0.1:8000/admin/questions?page=2",
  "title": "Question bank | Quizora",
  "html": "<div class=\"workspace\">...rendered Blade...</div>",
  "theme": {"name":"Ocean","layout":"sidebar","accent":"#087F8C","background":"#F3F6F8","surface":"#FFFFFF","text":"#192D3D","is_dark":false},
  "csrf_token": "session-token"
}
```

A successful normal write:

```json
{"redirect":"/admin/questions","message":"Question created.","csrf_token":"session-token"}
```

A successful autosave can replace named regions without replacing the question form:

```json
{"fragments":{"attempt-summary":"<div>Updated progress...</div>"}}
```

Validation uses Laravel's HTTP 422 shape:

```json
{"message":"The given data was invalid.","errors":{"prompt":["A question prompt is required."]}}
```

401/419 takes the user to sign-in without automatically retrying a write. 409 can request the mandatory password-change page. 403/404/429/5xx produce an appropriate error. Ordinary CRUD remains compatible with normal form POSTs where practical, but the complete application assumes JavaScript is available for its interactive workflow.

### Concurrency and failure behavior

All writes share one FIFO promise queue. Each autosave captures FormData immediately and sends it in order. Navigation waits for the current and newly appended writes to finish. A failed answer save blocks ordinary question navigation until an explicit successful retry. Final submission is also blocked through `data-requires-saves` when an answer save has failed. Server timeouts and network errors do not cause automatic write retries because the server may already have committed the first operation.

GET navigation is abortable and uses a sequence guard so an older response cannot overwrite a newer page. Filtering replaces the current history entry; ordinary navigation pushes it. Browser back/forward is handled. Regular dirty forms require confirmation before leaving, and pending saves activate the browser's unload warning.

The shared APIs remain available for truly special cases:

```javascript
await Quizora.submit(formElement);
await Quizora.navigate('/quizzes');
await Quizora.download('/admin/reports/export');
```

`Quizora.request()` is low-level transport and does not itself serialize application writes. Use `submit()` for form writes to preserve the queue, validation display, and save-state semantics.

## Assessment integrity

Start requests lock the learner and quiz in a transaction, reuse an active attempt, and enforce server availability and attempt limits. Answer and finalize requests lock the attempt. Submission is idempotent after finalization. Attempts use UUID route keys and ownership policies, but the UUID is not treated as a substitute for authorization.

Correct answers and explanations are not rendered into an active learner attempt response. Scores supplied by the browser are ignored. The server grades from stored snapshots and choices. Expiry is checked on server reads/writes and by `quizora:expire` on the scheduler. Expired finalization records the actual deadline, rather than the later scheduler run time.

These lock-based rules must be validated on the intended deployment database under realistic concurrency. SQLite does not provide the same row-lock behavior as server databases; it is a convenient local default, not evidence that a high-concurrency deployment has passed load tests.

## Authentication and authorization

There is no public registration. New/reset temporary passwords require replacement. Server-side FormRequests authorize admin-managed actions, admin middleware protects route groups, and attempt policies enforce ownership. Login regenerates the session and token; logout invalidates them. Edits to accounts increment an authentication version, allowing old sessions to be rejected on subsequent requests.

A five-failure email/IP login limiter operates alongside a route throttle. No full plaintext credentials are logged. There is no MFA, SSO, email verification workflow, or self-service password-reset email implementation in this version.

## Adding a new module

Add a migration/model with explicit ownership and delete behavior. Add a FormRequest for validation and authorization, a controller returning `PageResponse`, and a service only for meaningful business rules. Render Blade fields, filters, and pagination with the existing data attributes. Add the navigation entry and write feature tests for both permitted and forbidden roles.

No new JavaScript should be needed for standard CRUD. Do not permit a new module to weaken same-origin fetch rules, introduce raw trusted HTML from learner input, or make client-side role checks the only control.

## References

Framework design references: Laravel 13 [release notes](https://laravel.com/docs/13.x/releases), [authentication](https://laravel.com/docs/13.x/authentication), [validation](https://laravel.com/docs/13.x/validation), and [query locking](https://laravel.com/docs/13.x/queries#pessimistic-locking). Desktop boundaries follow Electron's [security guidance](https://www.electronjs.org/docs/latest/tutorial/security).
