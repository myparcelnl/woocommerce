ARG PHP_VERSION=7.4
ARG ALPINE_VERSION=3.16

FROM ghcr.io/myparcelnl/php-xd:${PHP_VERSION}-alpine${ALPINE_VERSION} AS build

ARG NODE_VERSION=16.0.0

COPY ./includes                 ./includes
COPY ./templates                ./templates
COPY ./src                      ./src
COPY ./woocommerce-myparcel.php ./woocommerce-myparcel.php
COPY ./private                  ./private

RUN apk update \
   && apk add --no-cache \
      nodejs>${NODE_VERSION} \
      npm \
    && npm install -g yarn

ENV YARN_CACHE_FOLDER=/usr/local/yarn-cache

# Create volume for yarn cache
VOLUME /usr/local/yarn-cache

# Create volume for composercache
VOLUME /root/.composer/cache

CMD ["sh", "/app/private/entrypoint.sh"]
