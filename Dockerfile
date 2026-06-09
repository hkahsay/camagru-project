FROM php:8.4-fpm-bookworm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY . .

RUN mkdir -p storage/uploads \
    && chown -R www-data:www-data storage

EXPOSE 9000
