# Symfony 8.0 / PHP 8.4 — FrankenPHP (pas de BDD, pas de JWT)
FROM dunglas/frankenphp:1-php8.4

ENV SERVER_NAME=":80"
WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY . .
RUN composer run-script post-install-cmd --no-interaction || true

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
