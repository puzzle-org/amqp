FROM php:8.5-cli

RUN echo 'APT::Install-Recommends "0";' >>/etc/apt/apt.conf.d/99-recommends && \
    echo 'APT::Install-Suggests "0";' >>/etc/apt/apt.conf.d/99-suggests

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
    apt-get install -y automake \
                       build-essential \
                       libtool \
                       zlib1g-dev \
                       unzip \
                       cmake \
                       libssl-dev \
                       librabbitmq-dev && \
    apt-get -y autoremove && \
    rm -rf /var/lib/apt/lists/*

ENV RABBITMQ_VERSION 0.11.0

RUN pecl install xdebug amqp && \
    docker-php-ext-enable xdebug amqp

WORKDIR /var/www/puzzle-amqp

COPY php.ini /usr/local/etc/php/conf.d
