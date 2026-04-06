#!/bin/sh
set -eu

cd /var/www/html

echo "Waiting for database..."
until php artisan migrate --force >/dev/null 2>&1; do
  sleep 2
done

php artisan db:seed --force

exec /usr/bin/supervisord -c /etc/supervisord.conf
