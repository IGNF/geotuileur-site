### backend.dockerfile

# Base image
FROM php:7.4-apache

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN rm /etc/apt/preferences.d/no-debian-php

ARG http_proxy=""
ENV http_proxy=${http_proxy}
ENV HTTP_PROXY=${http_proxy}

ARG https_proxy=""
ENV https_proxy=${https_proxy}
ENV HTTPS_PROXY=${https_proxy}

ARG no_proxy=""
ENV no_proxy=${no_proxy}
ENV NO_PROXY=${no_proxy}

# Common tools
RUN apt-get update -qq
RUN apt-get install -qy \
    git \
    gnupg \
    unzip \
    make \
    php-dev \
    zip \ 
    gdal-bin

COPY --from=composer /usr/bin/composer /usr/bin/composer

# PHP Configuration & Extensions
RUN apt-get update
COPY .docker/php.ini /usr/local/etc/php/conf.d/app.ini

RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

RUN apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

RUN apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN apt-get install -y libxslt-dev \
    && docker-php-ext-install xsl

RUN docker-php-ext-install opcache

## Needed for pecl to succeed
RUN pear config-set php_ini /usr/local/etc/php/conf.d/app.ini
RUN if [ "${http_proxy}" != "" ]; then \
    pear config-set http_proxy ${http_proxy} \
    ;fi
RUN pecl install xdebug-3.1.3 \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Cypress system/native dependencies
RUN apt-get update -qq && \
    apt-get install -qy libgtk2.0-0 libgtk-3-0 libgbm-dev libnotify-dev libgconf-2-4 libnss3 libxss1 libasound2 libxtst6 xauth xvfb

# Nodejs
# https://github.com/nodejs/docker-node/blob/main/14/bullseye/Dockerfile
# https://stackoverflow.com/a/63108753
COPY --from=node:14 /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node:14 /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
RUN npm i -g yarn

# APT Cache Cleanup
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Apache Configuration
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/apache.conf /etc/apache2/conf-available/z-app.conf

RUN a2enmod rewrite remoteip && \
    a2enconf z-app

RUN useradd -ms /bin/bash app_user

RUN chown -R app_user .
# RUN chmod 777 -R .

USER app_user
