#!/usr/bin/env bash
set -e

# Render injects PORT; default to 80 locally.
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Cache config/routes and apply migrations on boot.
php artisan config:cache || true
php artisan route:cache || true
php artisan migrate --force || true

# Seed once if the database is empty (safe to skip on later deploys).
if [ "${RUN_SEED:-false}" = "true" ]; then
  php artisan db:seed --force || true
fi

exec apache2-foreground
