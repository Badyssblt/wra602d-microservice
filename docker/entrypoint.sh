#!/usr/bin/env sh
set -e

cd /app

# Vendor (cas où un volume vide est monté en dev)
if [ ! -f vendor/autoload.php ]; then
    echo "[microservice] Installation des dépendances Composer…"
    composer install --no-interaction --prefer-dist --no-progress
fi

# Cache warmup
php bin/console cache:clear --no-interaction || true

exec "$@"
