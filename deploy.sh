#!/usr/bin/env bash
set -euo pipefail

# CONFIG — change these if needed
APP_DIR="/var/www/html/master-backend"
BRANCH="main"                       # branch to deploy
DEPLOY_USER="www-data"              # owner for files after deploy
MAINTENANCE=true                    # true = enable maintenance mode during deploy
COMPOSER_BIN="/usr/bin/composer"    # path to composer
PHP_BIN="/usr/bin/php"              # path to php

# move to app dir
cd "$APP_DIR"

echo "=== Deploy: $(date) ==="
echo "User: $(whoami) | Dir: $APP_DIR | Branch: $BRANCH"

# Ensure working tree is clean (optional safety)
git reset --hard
git clean -fd || true

# Fetch latest from origin and update branch
echo "Fetching and updating from origin/$BRANCH..."
git fetch origin --prune
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

# Optionally enable maintenance mode (safe for DB migrations)
# if [ "$MAINTENANCE" = true ]; then
#   echo "Entering maintenance mode..."
#   $PHP_BIN artisan down --no-interaction || true
# fi

# Composer install (no-dev in production)
echo "Installing composer dependencies..."
$COMPOSER_BIN install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run DB migrations (force in production)
echo "Running migrations..."
$PHP_BIN artisan migrate --force

# Permissions & caches
echo "Setting permissions and warming caches..."
chown -R "$DEPLOY_USER":"$DEPLOY_USER" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

$PHP_BIN artisan config:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear

$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Reload Octane gracefully (preferred). If fails, fallback to systemd restart.
echo "Reloading Octane (graceful)..."
$PHP_BIN artisan octane:reload || {
  echo "octane:reload failed — falling back to systemctl restart"
  sudo systemctl restart octane.service
}

# Exit maintenance mode if we enabled it
if [ "$MAINTENANCE" = true ]; then
  echo "Bringing app back up..."
  $PHP_BIN artisan up
fi

echo "Deploy finished: $(date)"
