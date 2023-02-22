ARG PHP_VERSION=7.4
ARG ALPINE_VERSION=3.16

FROM ghcr.io/myparcelnl/php-xd:${PHP_VERSION}-alpine${ALPINE_VERSION} AS build

ARG NODE_VERSION=18

RUN apk update \
   && apk add --no-cache \
      nodejs>${NODE_VERSION} \
      npm \
    && npm install -g yarn  \
    && mkdir -p /tmp/.cache/yarn \
    && mkdir -p /tmp/.cache/composer \
    && yarn config set --home enableGlobalCache true \
    && yarn config set --home enableTelemetry 0 \
    && yarn config set --home globalFolder /tmp/.cache/yarn/cache \
    && composer config --global cache-dir /tmp/.cache/composer

VOLUME /tmp/.cache/yarn/cache

VOLUME /tmp/.cache/composer/cache

VOLUME /app/vendor

VOLUME /app/node_modules

CMD [ "make", "build" ]
