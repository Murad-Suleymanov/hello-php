#!/bin/sh
set -e

# PHP-FPM-i arxa planda, nginx-i ön planda işə sal.
php-fpm -D
exec nginx -g 'daemon off;'
