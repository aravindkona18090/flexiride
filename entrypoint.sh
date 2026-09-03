#!/bin/sh
set -e

PORT="${PORT:-80}"
sed -i "s/Listen [0-9]*/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache on port $PORT..."
exec apache2-foreground
