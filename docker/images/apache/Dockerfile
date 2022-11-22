FROM php:8.1-cli

RUN echo 'APT::Install-Recommends "0";' >>/etc/apt/apt.conf.d/99-recommends && \
    echo 'APT::Install-Suggests "0";' >>/etc/apt/apt.conf.d/99-suggests

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
    apt-get install -y automake \
                       build-essential \
                       libtool \
                       zlib1g-dev \
                       unzip \
                       cmake && \
    apt-get -y autoremove && \
    rm -rf /var/lib/apt/lists/*

ENV RABBITMQ_VERSION 0.11.0

RUN cd /tmp && \
    curl --stderr - -L -O https://github.com/alanxz/rabbitmq-c/archive/refs/tags/v${RABBITMQ_VERSION}.tar.gz && \
    tar xf v${RABBITMQ_VERSION}.tar.gz && \
    cd rabbitmq-c-${RABBITMQ_VERSION} && \
    mkdir build && \
    cd build && \
    cmake .. && \
    cmake --build . && \
    cd /tmp && \
    rm -rf rabbitmq-c-${RABBITMQ_VERSION} && \
    rm v${RABBITMQ_VERSION}.tar.gz

RUN pecl install xdebug amqp-1.11.0 && \
    docker-php-ext-enable xdebug amqp

WORKDIR /var/www/puzzle-amqp

COPY php.ini /usr/local/etc/php/conf.d

