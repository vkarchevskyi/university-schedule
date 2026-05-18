FROM dunglas/frankenphp:1-php8.4-alpine AS base

WORKDIR /app

RUN install-php-extensions \
        opcache \
        pdo_pgsql \
        sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1 \
    FRANKENPHP_CONFIG="worker ./public/index.php"

FROM base AS vendor-prod

COPY rest-api/composer.json rest-api/composer.lock rest-api/symfony.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts

FROM base AS vendor-dev

ENV APP_ENV=dev

COPY rest-api/composer.json rest-api/composer.lock rest-api/symfony.lock ./
RUN composer install \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts

FROM base AS prod

COPY --chown=www-data:www-data rest-api/ .
COPY --from=vendor-prod --chown=www-data:www-data /app/vendor ./vendor
COPY docker/rest-api/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY docker/rest-api/Caddyfile /etc/caddy/Caddyfile

RUN mkdir -p var/cache var/log config/jwt \
    && chown -R www-data:www-data var config/jwt \
    && chmod +x /usr/local/bin/docker-entrypoint

USER www-data

EXPOSE 80

ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

FROM prod AS dev

ENV APP_ENV=dev

COPY --from=vendor-dev --chown=www-data:www-data /app/vendor ./vendor
