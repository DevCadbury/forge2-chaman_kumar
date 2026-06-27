# ---- build PHP dependencies ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# ---- runtime ----
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Serve Laravel's public/ directory
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
