#!/bin/sh

set -e

PORT=${PORT:-80}

# Configure Apache port for Render
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf

sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" \
    /etc/apache2/sites-available/000-default.conf


# Laravel package discovery
php artisan package:discover --ansi


# Clear old cached configuration
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear


# Cache Laravel configuration
php artisan config:cache

php artisan route:cache

php artisan view:cache


# Run database migrations
php artisan migrate --force


# Start Apache
exec apache2-foreground