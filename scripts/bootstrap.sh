#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
command -v php >/dev/null 2>&1 || { echo "PHP is not in PATH." >&2; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "Composer is not in PATH." >&2; exit 1; }
php scripts/prepare.php
composer install --prefer-dist --no-interaction
php artisan config:clear
php artisan quizora:install "$@"
