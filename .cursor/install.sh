#!/usr/bin/env bash
# Idempotent Cloud Agent install for Nova CRM (Konnect Nex), a Laravel 12 app.
# Safe to run repeatedly and against a warm snapshot.
set -euo pipefail

cd "$(dirname "$0")/.."

# --- Ensure MySQL server is installed, running, and reachable over TCP ---
# The migrations require MySQL (they query information_schema, so SQLite is not
# supported). This must bring MySQL up reliably on a fresh pod/build.
echo "==> Ensuring MySQL is available"

# Install the server if the base image/snapshot does not already provide it.
if ! command -v mysqld >/dev/null 2>&1 && [ ! -x /usr/sbin/mysqld ]; then
  echo "==> mysqld not found; installing mysql-server"
  sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mysql-server
fi

# The runtime socket directory lives on a tmpfs that is empty on a fresh boot.
sudo mkdir -p /var/run/mysqld
sudo chown -R mysql:mysql /var/run/mysqld

start_mysql() {
  sudo service mysql start 2>&1 || true
  # Fallback if the init script reported success but nothing is listening.
  if ! pgrep -x mysqld >/dev/null 2>&1; then
    echo "==> service start did not yield a running mysqld; trying mysqld_safe"
    sudo bash -c 'nohup mysqld_safe --skip-syslog >/var/log/mysql/mysqld_safe.out 2>&1 &' || true
  fi
}

mysql_ready() {
  # App connects over TCP as root@127.0.0.1, so verify TCP specifically.
  mysqladmin --protocol=tcp -h 127.0.0.1 -P 3306 -u root ping >/dev/null 2>&1 \
    || mysql --protocol=tcp -h 127.0.0.1 -P 3306 -u root -e 'SELECT 1' >/dev/null 2>&1 \
    || sudo mysqladmin ping >/dev/null 2>&1
}

start_mysql
for i in $(seq 1 60); do
  if mysql_ready; then break; fi
  sleep 1
  if [ "$i" = "20" ]; then echo "==> MySQL still not ready after 20s; retrying start"; start_mysql; fi
done

if ! mysql_ready; then
  echo "==> ERROR: MySQL failed to become ready. Diagnostics follow:"
  echo "--- sudo -n check ---"; sudo -n true 2>&1 && echo "sudo OK" || echo "sudo FAILED"
  echo "--- mysqld processes ---"; pgrep -a mysqld || echo "(none)"
  echo "--- /var/run/mysqld ---"; ls -la /var/run/mysqld 2>&1 || true
  echo "--- error log tail ---"; sudo tail -n 40 /var/log/mysql/error.log 2>&1 || true
  exit 1
fi
echo "==> MySQL is ready"

# --- Ensure root TCP access (empty password) + application databases ---
# App/tests connect as root@127.0.0.1 with an empty password (see .env.testing).
# Run via the local socket (root uses auth_socket by default on a fresh install).
sudo mysql <<'SQL'
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
