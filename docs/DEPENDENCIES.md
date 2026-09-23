# Dependencies and provenance

## Web

- Laravel framework: Composer constraint `^13.0`, PHP constraint `^8.3`.
- Bootstrap: `twbs/bootstrap` version `5.3.8`, installed through Composer and served locally after `scripts/assets.php` runs.
- PHPUnit: `^12.5` for development tests, with Mockery and Collision.
- No jQuery, frontend SPA framework, runtime CDN, npm web bundle, or Vite build is needed.
- CSS artwork, application favicon, and desktop icons were created for this project. No external font file is redistributed. The CSS uses a system-font fallback stack.

Laravel's current major-version requirements were checked against its official [13.x release notes](https://laravel.com/docs/13.x/releases). Bootstrap's package version was selected from its official [download documentation](https://getbootstrap.com/docs/5.3/getting-started/download/). Do not interpret a compatible version range as an installed and tested dependency tree.

## Desktop and optional browser tests

Electron and electron-builder are initially marked `latest` because no packages could be resolved in this build environment. The documented installation command saves exact resolved versions, and the generated lockfile must be reviewed and committed. This avoids inventing a specific current version or supplying a fabricated lockfile.

The optional browser contract tests use Playwright for Python, recorded in `tests/browser/requirements.txt`. Pin a tested environment in CI if those tests become part of a reproducible release pipeline.

## Excluded generated files

The archive does not contain `vendor/`, `node_modules/`, runtime databases, generated Bootstrap vendor assets, real credentials, `composer.lock`, or `package-lock.json`. These must be generated appropriately on the target development system. Never commit `.env`, private keys, local database contents, or credentials entered during installation.

## Licensing

Application code is MIT licensed under the root LICENSE. Laravel, Bootstrap, Electron, and each transitive dependency retain their own licenses. `scripts/assets.php` publishes Bootstrap's LICENSE next to its browser CSS. Preserve dependency licenses in redistributed packages and inspect the resolved dependency inventory before distribution.
