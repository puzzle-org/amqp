FROM php:5.6-cli

RUN pecl install xdebug-2.5.0 \
    && docker-php-ext-enable xdebug
    
RUN apt-get update \
    && apt-get install -y zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Due to issue https://github.com/docker-library/php/issues/233
# Crappy workaround
RUN docker-php-ext-install zlib; exit 0
RUN cp /usr/src/php/ext/zlib/config0.m4 /usr/src/php/ext/zlib/config.m4
RUN docker-php-ext-install zlib

COPY php.ini /usr/local/etc/php/conf.d
