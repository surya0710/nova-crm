#!/usr/bin/env bash
# Idempotent Cloud Agent install for Nova CRM (Konnect Nex), a Laravel 12 app.
# Safe to run repeatedly and against a warm snapshot.
set -euo pipefail

cd "$(dirname "$0")/.."

# --- Ensure MySQL is running (needed for migrations during install) ---
sudo service mysql start >/dev/null 2>&1 || true
for i in $(seq 1 30); do
  if sudo mysqladmin ping >/dev/null 2>&1; then break; fi
  sleep 1
done

# --- Ensure root TCP access (empty password) + application databases ---
# App/tests connect as root@127.0.0.1 with an empty password (see .env.testing).
sudo mysql <<'SQL' >/dev/null 2>&1 || true
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED WITH mysql_native_password BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
CREATE DATABASE IF NOT EXISTS novacrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS novacrm_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
FLUSH PRIVILEGES;
SQL

# --- PHP dependencies ---
composer install --no-interaction --no-progress --prefer-dist

# --- Environment file ---
# Preserve an existing .env (warm snapshot); only create it on a fresh checkout.
if [ ! -f .env ]; then
  cp .env.example .env
fi

set_env() {
  # set_env KEY VALUE — update in place or append if missing.
  local key="$1" value="$2"
  if grep -qE "^${key}=" .env; then
    sed -i -E "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}

# Use MySQL for local development (README-recommended; required by the test suite).
set_env DB_CONNECTION mysql
set_env DB_HOST 127.0.0.1
set_env DB_PORT 3306
set_env DB_DATABASE novacrm
set_env DB_USERNAME root
set_env DB_PASSWORD ""

# Generate an app key only if one is not already set.
if ! grep -qE '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

# --- Front-end assets ---
npm ci
npm run build

# --- Database schema (forward-only; never fresh/refresh, per .cursor/rules) ---
php artisan migrate --force

# --- Public storage symlink (guarded: `storage:link` errors if it exists) ---
if [ ! -e public/storage ]; then
  php artisan storage:link
fi

# --- Seed demo data only on a fresh database (idempotent + non-destructive) ---
USER_COUNT="$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n1 | tr -dc '0-9')"
if [ -z "${USER_COUNT}" ] || [ "${USER_COUNT}" = "0" ]; then
  php artisan demo:seed-presentation
fi

php artisan config:clear >/dev/null 2>&1 || true

echo "Nova CRM install complete."
