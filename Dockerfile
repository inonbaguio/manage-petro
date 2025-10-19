FROM php:8.3-fpm-alpine

# Install build dependencies for PECL extensions
RUN apk add --no-cache --virtual .build-deps \
    autoconf \
    g++ \
    make

# Install persistent dependencies
RUN apk add --no-cache \
    git \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install Redis extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Remove build dependencies to keep image small
RUN apk del .build-deps

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
