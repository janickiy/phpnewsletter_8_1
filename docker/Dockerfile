FROM php:8.4-apache

ARG WWWUSER=1000
ARG WWWGROUP=1000

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        default-mysql-client \
        git \
        gosu \
        unzip \
        zip \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        gd \
        intl \
        mbstring \
        exif \
        mysqli \
        opcache \
        pcntl \
        pdo_mysql \
        soap \
        zip; \
    a2enmod rewrite headers expires; \
    groupmod -o -g "${WWWGROUP}" www-data; \
    usermod -o -u "${WWWUSER}" -g www-data www-data; \
    mkdir -p /tmp/composer; \
    chown -R www-data:www-data /tmp/composer; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-laravel.ini
COPY docker/entrypoint.sh /usr/local/bin/laravel-entrypoint

RUN chmod +x /usr/local/bin/laravel-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["laravel-entrypoint"]
CMD ["apache2-foreground"]
