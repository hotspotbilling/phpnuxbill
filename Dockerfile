FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       bcmath curl gd intl mbstring mysqli pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN printf '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/phpnuxbill.conf \
    && a2enconf phpnuxbill

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html
EXPOSE 80
