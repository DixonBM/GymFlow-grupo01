FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json ./
RUN composer install --no-interaction --no-dev --prefer-dist --ignore-platform-req=ext-mongodb --no-scripts --no-progress

FROM php:8.3-apache
RUN pecl install mongodb && docker-php-ext-enable mongodb \
    && a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
COPY . .
COPY --from=dependencies /app/vendor ./vendor
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
