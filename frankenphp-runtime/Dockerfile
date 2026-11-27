# syntax=docker/dockerfile:1
ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1-php8.5-trixie
FROM ${FRANKENPHP_IMAGE}

# PhpRedis's PIE source archive omits bundled LZF. Use Debian's LZF library.
RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    --mount=type=tmpfs,target=/tmp/pie \
    install-php-extensions intl exif zip gd bcmath pdo_mysql mbstring xml gmp \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        ghostscript imagemagick libheif-plugin-aomenc libmagickcore-7.q16-10-extra libmagickwand-7.q16-10 \
        liblz4-1 liblzf1 libzstd1 \
    && saved_packages="$(apt-mark showmanual)" \
    && apt-get install -y --no-install-recommends liblz4-dev liblzf-dev libmagickwand-dev libzstd-dev libtool unzip \
    && mkdir -p /tmp/pie/include \
    && ln -s /usr/include/liblzf/lzf.h /tmp/pie/include/lzf.h \
    && export COMPOSER_ALLOW_SUPERUSER=1 XDG_CONFIG_HOME=/tmp/pie XDG_CACHE_HOME=/tmp/pie \
    && pie install --no-interaction imagick/imagick:3.8.1 \
    && pie install --no-interaction phpredis/phpredis:6.3.0 \
        --enable-redis-lzf --with-liblzf=/tmp/pie --enable-redis-lz4 --with-liblz4=yes --enable-redis-zstd \
    && apt-mark auto liblz4-dev liblzf-dev libmagickwand-dev libzstd-dev libtool unzip \
    && apt-mark manual $saved_packages \
    && apt-get autoremove -y --purge \
    && rm -rf /var/lib/apt/lists/*

# Apply these defaults only in Statamic application images.
COPY statamic.ini /usr/local/share/statamic/php.ini
COPY --chmod=755 install-statamic-php-config /usr/local/bin/install-statamic-php-config
