#!/usr/bin/env sh

composer install --no-dev --no-interaction --no-progress --optimize-autoloader --prefer-dist

yarn install

yarn run build

exec "$@"
