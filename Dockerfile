# PHP Docker Image
FROM php:8.3-cli

WORKDIR /app

# APCu extension (proseslər arası metrics üçün)
RUN pecl install apcu \
    && docker-php-ext-enable apcu \
    && echo "apc.enable_cli=1" > /usr/local/etc/php/conf.d/apcu-cli.ini

# Application
COPY index.php metrics.php ./

# Port
EXPOSE 8080

# Run (built-in server, index.php router kimi)
CMD ["php", "-S", "0.0.0.0:8080", "index.php"]
