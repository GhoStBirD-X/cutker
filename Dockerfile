FROM node:20-alpine AS frontend

RUN apk add --no-cache \
    php84 php84-ctype php84-dom php84-fileinfo php84-gd php84-iconv \
    php84-mbstring php84-openssl php84-pdo php84-pdo_sqlite php84-phar php84-session \
    php84-simplexml php84-tokenizer php84-xml php84-xmlreader php84-xmlwriter php84-zip \
    php84-curl \
    && ln -sf /usr/bin/php84 /usr/bin/php
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
RUN npm ci && npm run build

FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
        sqlite \
        sqlite-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_sqlite mbstring zip gd bcmath dom xml simplexml xmlreader xmlwriter \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p database storage/framework/{cache,sessions,views} storage/logs \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/database /var/www/html/bootstrap/cache /var/www/html/public

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

FROM nginx:alpine AS web

COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
