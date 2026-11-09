
FROM composer:2 AS dependencies

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-dev \
    --prefer-dist \
    --ignore-platform-req=ext-mongodb \
    --no-scripts \
    --no-progress


FROM php:8.3-apache

# Dependencias criptográficas necesarias para MongoDB.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libssl-dev \
        pkg-config \
    && yes '' | pecl install --configureoptions='with-mongodb-ssl="openssl"' mongodb \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .

COPY --from=dependencies /app/vendor ./vendor

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
