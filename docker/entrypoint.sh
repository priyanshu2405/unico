#!/bin/sh
set -e

# Port configuration (Render assigns a dynamic $PORT env variable)
PORT="${PORT:-80}"

echo "==> Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure writable permissions for storage and bootstrap/cache
echo "==> Setting directory permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link --no-interaction || true

# Run database migrations if RUN_MIGRATIONS is set
if [ "$RUN_MIGRATIONS" = "true" ] || [ "$RUN_MIGRATIONS" = "1" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force --no-interaction || echo "Warning: Migrations failed. Verify database credentials."
fi

# Run seeders if SEED_DATABASE is set
if [ "$SEED_DATABASE" = "true" ] || [ "$SEED_DATABASE" = "1" ]; then
    echo "==> Seeding database accounts..."
    php artisan db:seed --class=TestAccountsSeeder --force --no-interaction || echo "Warning: Seeding failed."
fi

# Cache routes and config for production performance
if [ "$APP_ENV" = "production" ] && [ -n "$APP_KEY" ]; then
    echo "==> Optimizing Laravel cache for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> UniCou Laravel application ready. Starting Apache..."
exec apache2-foreground
