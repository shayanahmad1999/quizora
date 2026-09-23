# Deployment and operational checklist

This is an implementation handoff, not a security certification. Run the full Laravel tests and real-browser acceptance tests on the intended runtime before public use.

## Runtime and database

Use a maintained PHP release compatible with Laravel 13, with all Composer platform requirements and the selected PDO driver enabled. PHP 8.5 is the intended local setup. Place the app behind an HTTPS-capable web server/PHP-FPM or equivalent managed hosting runtime. Point the document root to `public/`, never to the repository root.

SQLite is the default for simple local setup. For meaningful simultaneous assessments, use PostgreSQL or MySQL and test transaction/lock behavior, request throughput, backups, and migrations on that exact engine. Database configuration entries are included; actual execution against these engines was not performed in the build environment.

## First deployment

Install and test dependencies in development/CI first. Commit a verified `composer.lock`; resolve and pin Electron separately if distributing the desktop client. Do not let every production deployment select a fresh dependency set.

Create a new production `.env` from the example, set the database connection, and supply at minimum:

```dotenv
APP_NAME=Quizora
APP_ENV=production
APP_DEBUG=false
APP_URL=https://quiz.example.com
APP_TIMEZONE=UTC
DISPLAY_TIMEZONE=Asia/Karachi
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Use the real hostname, not the example domain. Preserve the application key on every future deployment. Set `SESSION_DOMAIN` only when needed and only to a domain you control. Ensure PHP and the operating system have reliable time synchronization.

Once the environment, database, and writable directories exist, run:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan quizora:admin
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`key:generate` above is **only for a brand-new deployment with no key**. Never regenerate a live key as part of a routine release. If using an already configured environment, keep its key and omit that command. Do not seed demo questions in production.

The local `quizora:install` command intentionally refuses production use. Composer's asset hook copies Bootstrap from the installed vendor package to `public/assets/vendor`. When a deployment intentionally disables Composer scripts, run the documented asset publishing and package discovery separately rather than silently deploying missing CSS.

Ensure `storage/`, `bootstrap/cache/`, and the generated public asset directory are writable by the appropriate deployment/web users. Do not give the entire project world-write permissions. Protect `.env`, storage contents, backups, and the database from public serving.

## Scheduler

Run Laravel's scheduler every minute from the application root, using the correct PHP executable and deployment user:

```cron
* * * * * cd /var/www/quizora && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Adjust paths to your server. The scheduled command is `quizora:expire`. Monitor scheduler health: missed runs delay background finalization, although server-side attempt requests still enforce deadlines. Do not treat the browser countdown as an authority.

The default cache store is file-based and the default session store is database-backed. Run only one scheduler for this single-server configuration. A multi-server deployment requires a deliberate shared cache/locking design, consistent session storage, and operational tests; those changes are not automatically supplied by choosing PostgreSQL.

## Web and session protection

Enforce HTTPS and secure cookies. Disable debug output. The application sets a same-origin content policy, anti-framing, and related response headers; also configure required headers at the web server/reverse proxy so framework-level exceptions and static responses receive appropriate protection.

When behind a reverse proxy, explicitly configure trusted proxy addresses and forwarded-header behavior. Do not trust arbitrary client-supplied forwarding headers. Confirm secure cookie behavior and URL generation on the deployed HTTPS origin.

Login and mutation throttles are configured, but the default file cache is local to a server. Plan shared rate-limiting storage before adding replicas. Apply an appropriate network/application firewall policy and monitor failed authentication, exception rates, and unusual exports. The current activity log is not a complete security event collection system.

## Assessment and privacy review

Use least-privilege administrators and unique passwords. Review whether correct-answer disclosure is appropriate: enabled answer review is immediate after an individual result, including when later retakes are allowed. Disable it for restricted assessments.

Back up the database consistently, and protect the app key and deployment secrets separately. Verify that a restore actually works. A copy of a live SQLite file in WAL mode is not automatically a consistent backup; use an appropriate database backup procedure.

Soft deletion preserves history. Define a retention and permanent-erasure policy for learners, responses, and audit entries before collecting sensitive personal data. No legal compliance certification or automated privacy-erasure workflow is implied.

## Routine releases

Back up and test migrations on a staging copy. Deploy the reviewed source and lockfile, install locked production dependencies, apply migrations, refresh configuration/routes/views, verify scheduler health, and smoke-test sign-in, access restrictions, answers, result scoring, and CSV export. Keep a rollback plan appropriate to the schema changes; code rollback alone may not reverse a database migration safely.

Do not run `migrate:fresh`, demo seeding, unattended Composer updates, or key regeneration as routine production deployment steps.

## Desktop distribution

Configure the desktop client to the same HTTPS origin before building. Resolve and pin dependencies, run Node tests, build on the target platform, sign the installer with your own certificate, and test a clean install/uninstall. A local development server address is not suitable for a distributed learner installer unless every learner machine is deliberately running its own server.

The supplied Electron wrapper has no automatic updater, offline database, offline answer queue, kiosk policy, anti-cheat enforcement, or bundled PHP runtime. Operational ownership of client updates and code signing remains with the distributor.
