# Use an official PHP runtime
FROM php:8.1-apache

# Enable Apache modules
RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    locales \
    locales-all \
    libgeoip-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    zlib1g-dev \
    libmagickwand-dev; \
    pecl install imagick; \
    docker-php-ext-enable imagick;

# Install any extensions you need
RUN docker-php-ext-install mysqli pdo pdo_mysql gettext bcmath zip intl gd

COPY docker/apache/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/upload.ini /usr/local/etc/php/conf.d/upload.ini

# Install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN chown -R www-data:www-data /var/www/html

# Copy the source code in /www into the container at /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html

USER www-data
RUN composer install
