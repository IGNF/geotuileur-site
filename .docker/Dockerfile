# Prod image
FROM php:8.2-apache

# ENV COMPOSER_ALLOW_SUPERUSER=1
RUN rm /etc/apt/preferences.d/no-debian-php

#----------------------------------------------------------------------
# Args and env vars
#----------------------------------------------------------------------
ARG http_proxy=""
ENV http_proxy=${http_proxy}
ENV HTTP_PROXY=${http_proxy}

ARG https_proxy=""
ENV https_proxy=${https_proxy}
ENV HTTPS_PROXY=${https_proxy}

ARG no_proxy=""
ENV no_proxy=${no_proxy}
ENV NO_PROXY=${no_proxy}

#----------------------------------------------------------------------
# Configure locale to fr_FR.UTF-8
# see also https://stackoverflow.com/a/41797247
#----------------------------------------------------------------------
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y locales \
    && sed -i -e 's/# en_US.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen \
    && dpkg-reconfigure --frontend=noninteractive locales \
    && update-locale LANG=fr_FR.UTF-8 \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ENV LANG fr_FR.UTF-8

#----------------------------------------------------------------------
# Install common tools
#----------------------------------------------------------------------
RUN apt-get update -qq \
    && apt-get install -y lsb-release make

#----------------------------------------------------------------------
# PHP Configuration & Extensions
#----------------------------------------------------------------------
COPY .docker/php.ini /usr/local/etc/php/conf.d/app.ini
RUN pear config-set php_ini /usr/local/etc/php/conf.d/app.ini

RUN apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip

RUN apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN apt-get install -y libxslt-dev \
    && docker-php-ext-install xsl

RUN docker-php-ext-install opcache

COPY --from=composer /usr/bin/composer /usr/bin/composer

#----------------------------------------------------------------------
# Install Nodejs
# https://github.com/nodejs/docker-node/blob/main/16/bullseye/Dockerfile
# https://stackoverflow.com/a/63108753
#----------------------------------------------------------------------
COPY --from=node:16 /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node:16 /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/bin/node /usr/local/bin/nodejs \
    && ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
RUN npm i -g npm yarn

#----------------------------------------------------------------------
# APT Cache Cleanup
#----------------------------------------------------------------------
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

#----------------------------------------------------------------------
# Configure apache
#----------------------------------------------------------------------
COPY .docker/apache-ports.conf /etc/apache2/ports.conf
COPY .docker/apache-security.conf /etc/apache2/conf-enabled/security.conf
COPY .docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite remoteip

#----------------------------------------------------------------------
# Install geotuileur-site (production specific)
#----------------------------------------------------------------------
COPY --chown=www-data:www-data . /opt/geotuileur-site
WORKDIR /opt/geotuileur-site
USER www-data

RUN export APP_ENV=prod \
    && APP_ENV=prod composer install --prefer-dist --no-dev --no-cache --optimize-autoloader --no-progress \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear \
    && php bin/console assets:install

RUN yarn install --production --frozen-lockfile \
    && yarn encore production \
    && yarn cache clean

EXPOSE 8000
