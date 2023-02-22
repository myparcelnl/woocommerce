ARG PHP_VERSION=7.4
ARG ALPINE_VERSION=3.16

FROM ghcr.io/myparcelnl/php-xd:${PHP_VERSION}-alpine${ALPINE_VERSION} AS build

ARG NODE_VERSION=18

RUN apk update \
   && apk add --no-cache \
      nodejs>${NODE_VERSION} \
      npm \
  && npm install -g yarn

COPY ./private/entrypoint.sh /entrypoint.sh

CMD [ "/entrypoint.sh" ]
