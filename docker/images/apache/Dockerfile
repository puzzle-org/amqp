FROM php:5.6-cli

RUN echo 'APT::Install-Recommends "0";' >>/etc/apt/apt.conf.d/99-recommends && \
    echo 'APT::Install-Suggests "0";' >>/etc/apt/apt.conf.d/99-suggests

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
    apt-get install -y automake \
                       build-essential \
                       libtool \
                       zlib1g-dev \
                       unzip && \
    apt-get -y autoremove && \
    rm -rf /var/lib/apt/lists/*

# Due to issue https://github.com/docker-library/php/issues/233
# Crappy workaround
RUN docker-php-ext-install zlib; exit 0
RUN cp /usr/src/php/ext/zlib/config0.m4 /usr/src/php/ext/zlib/config.m4
RUN docker-php-ext-install zlib zip

ENV RABBITMQ_VERSION 0.8.0

RUN cd /tmp && \
    curl --stderr - -L -O https://github.com/alanxz/rabbitmq-c/releases/download/v${RABBITMQ_VERSION}/rabbitmq-c-${RABBITMQ_VERSION}.tar.gz && \
    tar xf rabbitmq-c-${RABBITMQ_VERSION}.tar.gz && \
    cd rabbitmq-c-${RABBITMQ_VERSION} && \
    autoreconf -i && \
    ./configure && \
    make && \
    make install && \
    cd /tmp && \
    rm -rf rabbitmq-c-${RABBITMQ_VERSION} && \
    rm rabbitmq-c-${RABBITMQ_VERSION}.tar.gz

RUN pecl install xdebug amqp-1.7.0 && \
    docker-php-ext-enable xdebug amqp

WORKDIR /var/www/puzzle-amqp

COPY php.ini /usr/local/etc/php/conf.d

