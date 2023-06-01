#!/bin/sh
set -e

cp .env.dist .env
composer install --prefer-dist --no-progress --no-interaction

exec docker-php-entrypoint "$@"
