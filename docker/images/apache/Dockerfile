FROM php:5.6-cli

RUN echo 'APT::Install-Recommends "0";' >>/etc/apt/apt.conf.d/99-recommends && \
    echo 'APT::Install-Suggests "0";' >>/etc/apt/apt.conf.d/99-suggests

RUN mv /etc/apt/sources.list /etc/apt/sources.list.orig && \
    echo 'deb http://archive.debian.org/debian/ stretch main contrib non-free' >> /etc/apt/sources.list; \
    echo 'deb http://archive.debian.org/debian/ stretch-proposed-updates main contrib non-free' >> /etc/apt/sources.list; \
    echo 'deb http://archive.debian.org/debian-security stretch/updates main contrib non-free' >> /etc/apt/sources.list; \
    apt-get update --allow-unauthenticated && apt-get install --allow-unauthenticated debian-archive-keyring

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
                       unzip && \
    apt-get -y autoremove && \
    rm -rf /var/lib/apt/lists/*

# Due to issue https://github.com/docker-library/php/issues/233
# Crappy workaround
RUN docker-php-ext-install zlib; exit 0
RUN cp /usr/src/php/ext/zlib/config0.m4 /usr/src/php/ext/zlib/config.m4
RUN docker-php-ext-install zlib zip

RUN pecl install xdebug-2.5.0 && \
    docker-php-ext-enable xdebug

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

RUN pecl install amqp-1.11.0 && \
    docker-php-ext-enable amqp

WORKDIR /var/www/puzzle-amqp

COPY php.ini /usr/local/etc/php/conf.d
