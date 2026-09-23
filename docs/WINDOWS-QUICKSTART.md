# Windows quick start

## 1. Put the project in its own folder

Extract the archive to `D:\my-project\quizora` or another writable directory. Work in the folder containing `artisan`, `composer.json`, and `scripts`.

Do not run `laravel new` or `composer create-project` inside this project. The Laravel application structure is already supplied. Do not run Artisan before installing the dependencies.

## 2. Check the PHP executable and Composer

```powershell
php -v
where.exe php
php --ini
composer --version
```

PHP 8.5 is suitable. Laravel 13 requires PHP 8.3 or newer. When you have a PHP switcher, select the required version and open a new terminal before running these checks. Composer and Artisan must use the same intended CLI PHP.

The local installer checks `ctype`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, and `xmlwriter`, plus your chosen database driver. Laravel and Composer can additionally report other platform requirements. `curl` and `zip` are useful for Composer downloads; some extensions are built into the Windows PHP distribution and should not be added as nonexistent DLL directives.

For the default SQLite database, enable the available SQLite PDO extension in the **loaded php.ini shown by `php --ini`**, not a different XAMPP installation's file. For example, under a PHP 8.5 folder:

```ini
extension_dir = "C:/src/php-8.5/ext"
extension=mbstring
extension=fileinfo
extension=pdo_sqlite
```

Use only directives for DLLs that actually exist in that PHP build. Confirm the result with `php -m`. Do not copy extension DLLs between incompatible PHP releases, architectures, or thread-safety builds. `scripts/prepare.php` reports the active executable and loaded configuration file, or names the missing extensions clearly.

Node.js is not needed to run the web application. It is only needed for the optional Electron client.

## 3. Run the installer

```powershell
cd D:\my-project\quizora
powershell -ExecutionPolicy Bypass -File .\scripts\bootstrap.ps1 -Demo
```

The execution-policy change applies to that child process, not a permanent machine-wide setting. The script checks exit codes and stops at the first failed step.

The order is:

1. Check PHP and database extensions; prepare `.env`, storage directories, and the SQLite file.
2. Run `composer install`, package discovery, and local Bootstrap asset publishing.
3. Clear cached configuration.
4. Generate an application key only when missing; migrate the database and seed the six themes.
5. With `-Demo`, add five subjects, 40 original demonstration questions, and five quizzes.
6. Prompt for the first active super administrator when one does not already exist.

Use a real email address you control for your admin identifier. The application does not send email. Enter a unique password with at least 12 characters, upper/lowercase, and a number. Password entry is hidden and is not passed as a command-line argument.

For a clean question bank, omit `-Demo`. For non-interactive dependency/database preparation, add `-SkipAdmin` and later run:

```powershell
php artisan quizora:admin
```

Rerunning the installer preserves `.env`, the application key, users, and existing records. Default seeders use first-or-create behavior. Back up data before any update; preservation is not a substitute for backups.

## 4. Start the web application

```powershell
php artisan serve
```

Open `http://127.0.0.1:8000` and sign in using the credentials you entered. Leave the server terminal open while using the browser or local Electron client.

In another terminal, optionally start the scheduler for timely expiry without an open learner browser:

```powershell
cd D:\my-project\quizora
php artisan schedule:work
```

Do not run Laravel's development server as a public production service.

## 5. Create and test a learner

Sign in as the super administrator and open **People**. Create a learner with a temporary password and select either a fixed theme or random-at-login behavior. Share the credentials privately.

Use a private browser window for the learner so the administrator session remains separate. On first login, the learner must replace the temporary password before accessing quizzes. Open **Explore quizzes**, start one, choose an answer, and confirm the save indicator before moving on. Submit the attempt and check **Reports** in the admin session.

See [USAGE.md](USAGE.md) for a complete example and theme settings.

## 6. Run the supplied test suite

After installation:

```powershell
composer test
```

PHPUnit uses a temporary in-memory SQLite database, not the normal development database. It requires `pdo_sqlite` even when your main database is PostgreSQL or MySQL. Do not set testing values in your production `.env`.

The source archive's Laravel tests have not been run in the build environment; a successful local run is an important acceptance step, not an optional claim already covered elsewhere.

## Optional PostgreSQL or MySQL setup

Before the first bootstrap, copy `.env.example` to `.env` and set the database details. Create an empty database on your server and enable its PHP PDO driver.

PostgreSQL example:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=quizora
DB_USERNAME=quizora_user
DB_PASSWORD="replace-with-your-own-password"
```

For MySQL, use `DB_CONNECTION=mysql`, the correct host/port, database, and credentials. Do not commit `.env` or use a database root user in production.

If using SQLite with a custom location, use an absolute path such as `D:/my-project/quizora/database/database.sqlite`. The default works with `DB_DATABASE` omitted. The installer creates the default file; a custom path must exist and be writable.

## Common setup problems

| Message | Resolution |
| --- | --- |
| `vendor/autoload.php` is missing | Run the bootstrap script or `composer install` after preflight; do not start with Artisan. |
| Missing `mbstring` or another extension | Edit the active CLI `php.ini` from `php --ini`, verify `extension_dir`, reopen the terminal, then run `php -m`. |
| `could not find driver` | Enable `pdo_sqlite`, `pdo_pgsql`, or `pdo_mysql` for the selected connection. |
| Composer cannot download packages | Check your internet/DNS and certificate configuration. Do not disable TLS verification or use `--ignore-platform-reqs` as a fix. |
| Page has no Bootstrap styles | Run `php scripts/assets.php` after Composer installation. Check that the web document root is `public`. |
| Session expired / HTTP 419 | Use one consistent host and port, sign in again, and check `APP_URL` and cookie settings. The client deliberately does not replay writes automatically. |
| SQLite database locked | Keep the database on a local disk, not a synced/network folder. For significant concurrent usage, move to a server database and perform concurrency tests. |
| Password rejected | Use at least 12 characters with upper/lowercase and a number. Re-enter the same confirmation. |
| CSS assets or routes fail under XAMPP | Point the Apache virtual host at `quizora/public`; never expose the project root. Using `php artisan serve` is the simplest local route. |

Do not fix setup errors with `migrate:fresh`, deletion of an existing SQLite file, or regeneration of an established `APP_KEY`. Those actions can destroy data or invalidate sessions/encrypted content.
