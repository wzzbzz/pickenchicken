#!/bin/bash
# setup.sh — Run once on a fresh Ubuntu 24.04 DigitalOcean droplet
# Usage: bash setup.sh
set -e

echo "=== Picken' Chicken — Server Setup ==="

# ── System ───────────────────────────────────────────────
apt-get update && apt-get upgrade -y
apt-get install -y curl git unzip nginx certbot python3-certbot-nginx \
    redis-server postgresql postgresql-contrib software-properties-common

# ── PHP 8.3 ─────────────────────────────────────────────
add-apt-repository ppa:ondrej/php -y && apt-get update
apt-get install -y php8.3 php8.3-fpm php8.3-pgsql php8.3-xml php8.3-curl \
    php8.3-mbstring php8.3-intl php8.3-zip php8.3-redis php8.3-cli

# ── Composer ────────────────────────────────────────────
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# ── Node 20 ─────────────────────────────────────────────
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

# ── PostgreSQL ───────────────────────────────────────────
DB_PASS=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 24)
sudo -u postgres psql -c "CREATE USER chicken WITH PASSWORD '$DB_PASS';"
sudo -u postgres psql -c "CREATE DATABASE pickenchicken OWNER chicken;"
echo ">>> DB password: $DB_PASS  — save this!"

# ── Redis ────────────────────────────────────────────────
systemctl enable redis-server && systemctl start redis-server

# ── App directory ────────────────────────────────────────
mkdir -p /var/www/pickenchicken
echo ""
echo "=== Setup complete. DB_PASS=$DB_PASS ==="
echo "Next: run deploy.sh"
