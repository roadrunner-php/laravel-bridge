# Image page: <https://hub.docker.com/_/php>
FROM php:8.2.0-alpine

ENV COMPOSER_HOME="/tmp/composer"

# Image page: <https://hub.docker.com/_/composer>
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Image page: <https://hub.docker.com/r/spiralscout/roadrunner>
COPY --from=spiralscout/roadrunner:2.12.1 /usr/bin/rr /usr/bin/rr

RUN set -x \
    && apk add --no-cache binutils git \
    && apk add --no-cache --virtual .build-deps autoconf pkgconf make g++ gcc linux-headers 1>/dev/null \
    # install xdebug (for testing with code coverage), but do not enable it
    && pecl install xdebug-3.2.0 \
    # install PHP extensions (CFLAGS usage reason - https://bit.ly/3ALS5NU)
    && CFLAGS="$CFLAGS -D_GNU_SOURCE" docker-php-ext-install -j$(nproc) sockets pcntl \
    && apk del .build-deps \
    && mkdir --parents --mode=777 /src ${COMPOSER_HOME}/cache/repo ${COMPOSER_HOME}/cache/files \
    && ln -s /usr/bin/composer /usr/bin/c \
    && composer --version \
    && php -v \
    && php -m

WORKDIR /src
