FROM php:7-fpm-alpine

RUN apk add --no-cache \
        $PHPIZE_DEPS \
        openssl-dev

RUN pecl install xdebug

COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

WORKDIR /app
