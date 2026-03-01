#!/bin/sh
set -e

chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

exec "$@"
