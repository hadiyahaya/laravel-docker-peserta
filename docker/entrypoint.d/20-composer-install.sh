#!/bin/sh
set -e

# The bind mount (.:/var/www/html) overlays whatever composer install already
# baked into the image at build time. If the host's vendor/ is missing or
# incomplete (fresh clone, or a wiped vendor/), install it here so `docker
# compose up -d --build` alone produces a working, host-visible vendor/ —
# no manual `composer install` step required.
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "vendor/autoload.php not found — running composer install..."
    cd /var/www/html
    composer install --no-interaction --prefer-dist --no-progress
fi
