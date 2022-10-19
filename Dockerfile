ARG PHP_VERSION=7.2

###
# Production
###
FROM ghcr.io/myparcelnl/php-xd:${PHP_VERSION} AS prod

COPY . ./

RUN apk add --no-cache \
    	nodejs \
    	npm \
    && npm install -g yarn

CMD ["sh", "/app/private/entrypoint.sh"]
