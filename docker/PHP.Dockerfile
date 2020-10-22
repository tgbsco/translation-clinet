FROM php:7.4-fpm

RUN apt-get -y update && apt-get -y install curl git openssl libssl-dev libcurl4-openssl-dev zip zlib1g-dev libzip-dev libssh-dev  \
     && rm -rf /var/lib/apt/lists/* \
     && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
     && rm -rf /tmp/*

WORKDIR /app