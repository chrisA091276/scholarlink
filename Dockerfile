FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates unzip libicu-dev libzip-dev libonig-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath intl zip gd pcntl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
COPY --from=assets /build/public/build ./public/build
COPY deploy/render/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/render/ports.conf /etc/apache2/ports.conf
COPY deploy/render/php.ini /usr/local/etc/php/conf.d/scholarlink.ini

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=10000

RUN mkdir -p storage/app/private storage/app/public storage/fonts \
        storage/framework/cache/data storage/framework/sessions storage/framework/views \
        storage/logs bootstrap/cache \
    && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
CMD ["/bin/sh", "/var/www/html/deploy/render/start.sh", "web"]
