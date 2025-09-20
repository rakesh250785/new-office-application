#!/usr/bin/env bash
set -euo pipefail

# CONFIG — change these if needed
APP_DIR="/var/www/html/master-backend"
BRANCH="main"                       # branch to deploy
DEPLOY_USER="www-data"              # owner for files after deploy
MAINTENANCE=false                   # true = enable maintenance mode during deploy
COMPOSER_BIN="/usr/bin/composer"    # path to composer
PHP_BIN="/usr/bin/php"              # path to php
TAIL_LOGS=false                     # true -> tail octane logs at the end (blocks)

cd "$APP_DIR"

echo "=== Deploy: $(date -u) ==="
echo "User: $(whoami) | Dir: $APP_DIR | Branch: $BRANCH"

# --- Safety: mark safe.directory if git complains about dubious ownership ---
# Only add if needed (silently ignore errors)
git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true

# Backup .env (always): safe, cheap, and avoids surprises
ENV_BACKUP="$(mktemp /tmp/env.backup.XXXXXX)"
if [ -f .env ]; then
  cp .env "$ENV_BACKUP"
  echo ".env backed up to $ENV_BACKUP"
else
  echo "No .env file found to back up."
fi

# Ensure working tree is clean (staged/untracked changes will be removed)
# but keep a copy of untracked files listing for debugging
git status --porcelain > "$ENV_BACKUP.status" || true
echo "Git status snapshot saved to $ENV_BACKUP.status"

echo "Resetting local changes..."
git reset --hard
git clean -fd || true

# Fetch latest from origin and reset to remote branch
echo "Fetching and updating from origin/$BRANCH..."
git fetch origin --prune
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

# If .env was tracked in git this would have been overwritten by the reset.
# Restore our backup copy to ensure we keep local secrets/configs.
if [ -f "$ENV_BACKUP" ] && [ -s "$ENV_BACKUP" ]; then
  cp "$ENV_BACKUP" .env
  echo ".env restored from backup"
fi

# Optional: enable maintenance mode (run as deploy user to match permission context)
if [ "$MAINTENANCE" = true ]; then
  echo "Entering maintenance mode..."
  sudo -u "$DEPLOY_USER" $PHP_BIN artisan down --no-interaction || true
fi

# Install composer dependencies as deploy user (avoid owner mismatches)
echo "Installing composer dependencies..."
sudo -u "$DEPLOY_USER" $COMPOSER_BIN install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run migrations (ensure run as deploy user or CI user that has DB access)
echo "Running migrations..."
sudo -u "$DEPLOY_USER" $PHP_BIN artisan migrate --force

# Permissions & caches
echo "Setting permissions and warming caches..."
chown -R "$DEPLOY_USER":"$DEPLOY_USER" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

# Clear & build caches
sudo -u "$DEPLOY_USER" $PHP_BIN artisan config:clear
sudo -u "$DEPLOY_USER" $PHP_BIN artisan route:clear
sudo -u "$DEPLOY_USER" $PHP_BIN artisan view:clear

sudo -u "$DEPLOY_USER" $PHP_BIN artisan config:cache
sudo -u "$DEPLOY_USER" $PHP_BIN artisan route:cache
sudo -u "$DEPLOY_USER" $PHP_BIN artisan view:cache

# Reload Octane gracefully, fall back to restart
echo "Reloading Octane (graceful)..."
if ! sudo -u "$DEPLOY_USER" $PHP_BIN artisan octane:reload; then
  echo "octane:reload failed — falling back to systemctl restart"
  sudo systemctl restart octane.service
fi

# Ensure Octane systemd service is configured
echo "Ensuring Octane service is active..."
sudo systemctl daemon-reload || true
sudo systemctl enable --now octane.service || true

# Bring app back up if maintenance was enabled
if [ "$MAINTENANCE" = true ]; then
  echo "Bringing app back up..."
  sudo -u "$DEPLOY_USER" $PHP_BIN artisan up || true
fi

echo "Deploy finished: $(date -u)"

# Optional: tail logs (non-blocking by default)
if [ "$TAIL_LOGS" = true ]; then
  echo "Tailing Octane logs (Ctrl+C to exit)..."
  sudo journalctl -u octane.service -f
else
  echo "Skipping log tail. Set TAIL_LOGS=true to tail octane logs at the end."
fi
