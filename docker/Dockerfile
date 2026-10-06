FROM serversideup/php:8.5-fpm-nginx

USER root

ARG USER_ID=1000
ARG GROUP_ID=1000

# Extra PHP extensions (pdo_mysql, redis, zip, pcntl and opcache are already included)
RUN install-php-extensions intl bcmath

# if need to install packages, use the following command
# Install Node.js v26
RUN apt-get update \
    && apt-get install -y curl ca-certificates gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_26.x | bash - \
    && apt-get install -y nodejs

# Custom PHP settings (the "zzz-" prefix makes it load after the image's defaults)
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/zzz-custom.ini

# Custom nginx settings (included inside the image's server { } block)
COPY docker/nginx/custom.conf /etc/nginx/server-opts.d/custom.conf


# Auto-install composer dependencies on container start if the bind-mounted
# vendor/ is missing (e.g. fresh clone) — see docker/app/entrypoint.d/20-composer-install.sh
COPY docker/entrypoint.d/20-composer-install.sh /etc/entrypoint.d/20-composer-install.sh
RUN chmod +x /etc/entrypoint.d/20-composer-install.sh

# Match www-data UID/GID to the host user so mounted volumes are writable
RUN docker-php-serversideup-set-id www-data ${USER_ID}:${GROUP_ID} \
    && docker-php-serversideup-set-file-permissions --owner ${USER_ID}:${GROUP_ID}

COPY --chown=www-data:www-data . /var/www/html

USER www-data

# Pass --build-arg COMPOSER_NO_DEV=--no-dev for production builds
ARG COMPOSER_NO_DEV=""

# Install Composer dependencies with caching
RUN --mount=type=cache,target=/tmp/composer-cache,uid=${USER_ID},gid=${GROUP_ID} \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    $COMPOSER_NO_DEV

# Install Node.js dependencies with caching and build the application
RUN --mount=type=cache,target=/tmp/npm-cache,uid=${USER_ID},gid=${GROUP_ID} \
    npm ci --cache /tmp/npm-cache \
    && npm run build
