#!/bin/bash
# deploy.sh — Pull latest code and deploy
# Usage: bash deploy.sh
set -e

APP_DIR="/var/www/pickenchicken"
REPO="https://github.com/wzzbzz/pickenchicken.git"

echo "=== Deploying Picken' Chicken ==="

# ── Clone or pull ────────────────────────────────────────
if [ -d "$APP_DIR/.git" ]; then
    echo "Pulling latest..."
    cd "$APP_DIR" && git pull origin main
else
    echo "Cloning repo..."
    git clone "$REPO" "$APP_DIR"
    cd "$APP_DIR"
fi

# ── API ──────────────────────────────────────────────────
cd "$APP_DIR/api"
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod

# ── Frontend ─────────────────────────────────────────────
cd "$APP_DIR/frontend"
npm ci
npm run build

# ── Permissions ──────────────────────────────────────────
chown -R www-data:www-data "$APP_DIR/api/var"
chmod -R 775 "$APP_DIR/api/var"

# ── Restart services ─────────────────────────────────────
systemctl reload nginx
systemctl restart php8.3-fpm

echo "=== Deploy complete ==="
