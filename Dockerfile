
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       bcmath gd intl mysqli pdo_mysql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

RUN if [ ! -d /var/www/html/pages ] && [ -d /var/www/html/pages_template ]; then \
        cp -a /var/www/html/pages_template /var/www/html/pages; \
    fi \
    && mkdir -p /var/www/html/ui/cache /var/www/html/ui/compiled \
    && chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

EXPOSE 80
