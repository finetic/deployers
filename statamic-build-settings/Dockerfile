# syntax=docker/dockerfile:1

ARG PHP_RUNTIME_IMAGE=docker.finetic.dev/finetic/runtime/frankenphp:frankenphp-php8.5

# Shared PHP runtime; also used to validate Composer platform requirements.
ARG NODE_IMAGE=node:26
ARG FRONTEND_SOURCE=frontend_standard
ARG APP_SOURCE=app_standard

FROM ${PHP_RUNTIME_IMAGE} AS base
WORKDIR /app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN --mount=type=bind,source=docker,target=/tmp/statamic-config \
    install-statamic-php-config /tmp/statamic-config/php.ini

# Only system installation needs root. Application builds and runtime use UID 33.
RUN mkdir -p /data/caddy /config/caddy \
    && chown www-data:www-data /app /data/caddy /config/caddy
USER www-data

# Install dependencies only when their manifests or local addons change.
FROM base AS composer
ARG COMPOSER_GIT=0
USER root
RUN if [ "$COMPOSER_GIT" = 1 ]; then \
        apt-get update && apt-get install -y --no-install-recommends git \
        && rm -rf /var/lib/apt/lists/*; \
    fi
USER www-data
COPY --chown=www-data:www-data composer.json composer.lock ./
COPY --chown=www-data:www-data addons/ ./addons/
RUN --mount=type=bind,from=composer:2,source=/usr/bin/composer,target=/usr/local/bin/composer \
    --mount=type=secret,id=composer_auth,target=/app/auth.json,uid=33,gid=33,required=true \
    --mount=type=cache,id=composer-www-data,sharing=locked,target=/tmp/composer-cache,uid=33,gid=33 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --no-autoloader

FROM ${NODE_IMAGE} AS node_dependencies
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --prefer-offline --no-audit --no-fund

FROM node_dependencies AS frontend_standard
COPY . .

FROM node_dependencies AS frontend_lvo
COPY vite.config.* postcss.config.* ./
COPY resources/ ./resources/
COPY public/ ./public/

FROM ${FRONTEND_SOURCE} AS node_builder
ARG THEME
RUN if [ -n "$THEME" ]; then THEME="$THEME" npm run build; else npm run build; fi

FROM composer AS app_code
COPY --chown=www-data:www-data . .

FROM app_code AS app_standard
COPY --from=node_builder --chown=www-data:www-data /app/public/build/ ./public/build/

FROM composer AS app_waimo
COPY --chown=www-data:www-data addons/ ./addons/
COPY --chown=www-data:www-data app/ ./app/
COPY --chown=www-data:www-data bootstrap/ ./bootstrap/
COPY --chown=www-data:www-data config/ ./config/
COPY --chown=www-data:www-data database/ ./database/
COPY --chown=www-data:www-data lang/ ./lang/
COPY --chown=www-data:www-data public/ ./public/
COPY --chown=www-data:www-data resources/ ./resources/
COPY --chown=www-data:www-data routes/ ./routes/
COPY --chown=www-data:www-data artisan please composer.json composer.lock ./

FROM ${APP_SOURCE} AS app_builder
ARG LEGACY_PUBLIC_STORAGE=0
ARG CLEAN_BUILD_CACHE=0
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/app/public \
        storage/app/private \
        storage/debugbar \
        storage/logs \
        storage/statamic \
        bootstrap/cache \
    && if [ "$LEGACY_PUBLIC_STORAGE" = 1 ]; then \
        mkdir -p public/storage/framework/cache/data public/storage/framework/sessions \
            public/storage/framework/testing public/storage/framework/views public/storage/app/public \
            public/storage/debugbar public/storage/logs public/storage/statamic; \
    fi
RUN --mount=type=bind,from=composer:2,source=/usr/bin/composer,target=/usr/local/bin/composer \
    composer dump-autoload --no-dev --optimize --no-scripts --no-interaction \
    && composer check-platform-reqs --no-dev \
    && php artisan package:discover --ansi \
    && php artisan statamic:install --ansi \
    && if [ "$CLEAN_BUILD_CACHE" = 1 ]; then rm -f bootstrap/cache/config.php public/hot; fi

# Existing deployer builds this target; Composer and Node stay in build stages.
FROM base AS prod-default-entrypoint
ARG PERSIST_ADDON_SETTINGS=0
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY --from=app_builder --chown=www-data:www-data /app/ /app/
RUN if [ "$PERSIST_ADDON_SETTINGS" = 1 ]; then \
        mv resources/addons addon-settings-defaults \
        && ln -s ../storage/statamic/addon-settings resources/addons; \
    fi

FROM prod-default-entrypoint AS prod
COPY --chmod=755 docker/prod/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint

FROM prod AS prod-themed
COPY --from=node_builder --chown=www-data:www-data /app/public/build/ /app/public/build/

FROM prod-themed AS prod-waimo
ARG GIT_REVISION
LABEL org.opencontainers.image.revision="${GIT_REVISION}"
RUN mkdir -p /app/public/images /app/public/files

# Puppet configures the web healthcheck for Waimo CMS.
HEALTHCHECK NONE
