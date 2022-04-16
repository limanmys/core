FROM php:7.3.33-fpm-buster
ARG TIMEZONE

COPY php.ini /usr/local/etc/php/conf.d/docker-php-config.ini

RUN apt-get update && apt-get install -y \
    gnupg \
    g++ \
    procps \
    openssl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev  \
    libonig-dev \
    libxslt1-dev \
    libpq-dev \
    acl \
    && echo 'alias sf="php bin/console"' >> ~/.bashrc

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN /bin/bash -c composer install --ignore-platform-req=ext-snmp --ignore-platform-req=ext-zip --ignore-platform-req=ext-gd

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"


WORKDIR /var/www/liman

# RUN composer update
