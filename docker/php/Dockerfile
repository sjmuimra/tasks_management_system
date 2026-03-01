FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    autoconf \
    g++ \
    make \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    mysql-client

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    pcntl \
    bcmath

RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN deluser www-data \
    && addgroup -g 1000 www-data \
    && adduser -u 1000 -G www-data -s /bin/sh -D www-data

RUN sed -i 's/user = www-data/user = www-data/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = www-data/' /usr/local/etc/php-fpm.d/www.conf

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

USER www-data

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
