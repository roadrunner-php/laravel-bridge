FROM composer:1.8.6 AS composer

FROM php:7.2.5-alpine

ENV \
    COMPOSER_ALLOW_SUPERUSER="1" \
    COMPOSER_HOME="/tmp/composer"

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN set -xe \
    && apk add --no-cache binutils git curl \
    && apk add --no-cache --virtual .build-deps autoconf pkgconf make g++ gcc \
    # install xdebug (for testing with code coverage), but do not enable it
    && pecl install xdebug-2.9.1 \
    && apk del .build-deps \
    && mkdir /src ${COMPOSER_HOME} \
    && composer global require 'hirak/prestissimo' --no-interaction --no-suggest --prefer-dist \
    && ln -s /usr/bin/composer /usr/bin/c \
    && chmod -R 777 ${COMPOSER_HOME} \
    && composer --version \
    && php -v \
    && php -m

WORKDIR /src

VOLUME ["/src"]
