# PHP Docker Image (nginx + php-fpm)
FROM php:8.3-fpm

WORKDIR /app

# APCu extension (proseslər arası metrics üçün)
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# nginx (tətbiqi servis etmək və FPM status səhifəsini açmaq üçün)
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx \
    && rm -rf /var/lib/apt/lists/*

# Application
COPY index.php metrics.php ./

# PHP-FPM pool: status səhifəsini aktivləşdir
COPY docker/www-status.conf /usr/local/etc/php-fpm.d/zzz-status.conf

# nginx site config (8080-də dinləyir, /status FPM-ə yönlənir)
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Entrypoint: php-fpm + nginx
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# metrics.php bu URL-dən FPM status oxuyur
ENV PHP_FPM_STATUS_URL=http://127.0.0.1:8080/status

EXPOSE 8080

CMD ["entrypoint.sh"]
