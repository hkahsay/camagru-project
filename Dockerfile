FROM php:8.4-fpm-bookworm

WORKDIR /var/www/html

RUN docker-php-ext-install pdo_mysql

COPY . .

RUN mkdir -p storage/uploads \
    && chown -R www-data:www-data storage

EXPOSE 9000
