#!/usr/bin/env bash
set -euo pipefail

# CONFIG — change these if needed
APP_DIR="/var/www/html/master-backend"
BRANCH="main"                       # branch to deploy
ENV_FILE=".env"                     # env file name in APP_DIR
DEPLOY_USER="www-data"              # owner for files after deploy
MAINTENANCE=true                    # true = enable maintenance mode during deploy
BACKUP_DB=false                     # optional DB backup toggle (not implemented here)

# Push local changes to remote repo first
echo ">>> Pushing local changes to origin..."
git push origin "$BRANCH"

# Deploy process starts
cd "$APP_DIR"

echo "=== Deploy: $(date) ==="
echo "User: $(whoami) | Dir: $APP_DIR | Branch: $BRANCH"

# Fetch latest
git fetch --all --prune
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

# Optionally enable maintenance mode (safe for DB migrations)
# if [ "$MAINTENANCE" = true ]; then
#   echo "Entering maintenance mode..."
#   php artisan down --no-interaction || true
# fi

# Composer install
echo "Installing composer dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run DB migrations (force in production)
echo "Running migrations..."
php artisan migrate --force

# Permissions & caches
echo "Setting permissions and warming caches..."
chown -R "$DEPLOY_USER":"$DEPLOY_USER" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reload Octane gracefully (preferred)
echo "Reloading Octane (graceful)..."
php artisan octane:reload || {
  echo "octane:reload failed — falling back to systemctl restart"
  sudo systemctl restart octane.service
}

# Exit maintenance mode if we enabled it
if [ "$MAINTENANCE" = true ]; then
  echo "Bringing app back up..."
  php artisan up
fi

echo "Deploy finished: $(date)"
