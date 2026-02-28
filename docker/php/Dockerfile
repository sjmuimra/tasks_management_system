FROM php:8.3-fpm-alpine

# System dependencies + build tools required by pecl
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

# PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    pcntl \
    bcmath

# Redis extension via PECL (autoconf + g++ + make are now available)
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
