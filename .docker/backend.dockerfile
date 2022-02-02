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
RUN apt-get update -qq && \
    apt-get install -qy \
    git \
    gnupg \
    unzip \
    make \
    php-dev \
    zip \ 
    gdal-bin && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
    apt-get update && apt-get install -y yarn && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

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

# Apache Configuration
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/apache.conf /etc/apache2/conf-available/z-app.conf

RUN a2enmod rewrite remoteip && \
    a2enconf z-app

RUN useradd -ms /bin/bash app_user

RUN chown -R app_user .
# RUN chmod 777 -R .

USER app_user
