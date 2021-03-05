# Image page: <https://hub.docker.com/_/php>
FROM php:8.0-alpine

ENV COMPOSER_HOME="/tmp/composer"

# Image page: <https://hub.docker.com/_/composer>
COPY --from=composer:2.0.11 /usr/bin/composer /usr/bin/composer

RUN set -x \
    && apk add --no-cache binutils git \
    && apk add --no-cache --virtual .build-deps autoconf pkgconf make g++ gcc 1>/dev/null \
    # install xdebug (for testing with code coverage), but do not enable it
    && pecl install xdebug-3.0.3 1>/dev/null \
    && docker-php-ext-install sockets \
    && apk del .build-deps \
    && mkdir --parents --mode=777 /src ${COMPOSER_HOME}/cache/repo ${COMPOSER_HOME}/cache/files \
    && ln -s /usr/bin/composer /usr/bin/c \
    && composer --version \
    && php -v \
    && php -m

WORKDIR /src
