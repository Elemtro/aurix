# syntax=docker/dockerfile:1

FROM php:8.5-fpm-bookworm AS base

WORKDIR /var/www/aurix

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        acl \
        curl \
        git \
        unzip \
        zip \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        zip \
        intl \
        mbstring \
        pdo_mysql \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/app.ini

# dev
FROM base AS dev

COPY docker/php/conf.d/opcache.dev.ini $PHP_INI_DIR/conf.d/opcache.ini

USER www-data

# prod
FROM base AS prod

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/opcache.prod.ini $PHP_INI_DIR/conf.d/opcache.ini

COPY symfony_app/composer.json symfony_app/composer.lock symfony_app/symfony.lock ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist --optimize-autoloader

COPY --chown=www-data:www-data symfony_app/ .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

USER www-data

# nginx (prod)
FROM nginx:1.29-alpine AS nginx_prod

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=prod /var/www/aurix/public /var/www/aurix/public
