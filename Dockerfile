FROM composer:2 AS composer

FROM node:22-bookworm-slim AS node
RUN npm install --global pnpm@9.15.0

FROM wordpress:php8.3-apache AS development

ARG LOCAL_UID=1000
ARG LOCAL_GID=1000

RUN apt-get update \
    && apt-get install --yes --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && ln -s /usr/local/lib/node_modules/pnpm/bin/pnpm.cjs /usr/local/bin/pnpm \
    && ln -s /usr/local/lib/node_modules/pnpm/bin/pnpx.cjs /usr/local/bin/pnpx

COPY docker/php-development.ini /usr/local/etc/php/conf.d/zz-development.ini

# Docker initializes the dependency volumes with these directory permissions.
RUN mkdir -p /var/www/html/wp-content/plugins/techbyit-smtp/vendor \
             /var/www/html/wp-content/plugins/techbyit-smtp/node_modules \
             /var/www/html/wp-content/plugins/techbyit-smtp/dist \
    && chown -R "${LOCAL_UID}:${LOCAL_GID}" /var/www/html/wp-content/plugins/techbyit-smtp

# The inherited WordPress entrypoint initializes the site in this directory.
WORKDIR /var/www/html
