#!/bin/sh
set -e

composer install --prefer-dist --no-progress --no-interaction

exec docker-php-entrypoint "$@"
