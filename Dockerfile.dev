FROM php:8.4-cli

# Instala dependencias necesarias
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Instala Xdebug (necesario para coverage)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . /app

# Instala dependencias de PHP
RUN composer install --no-interaction --prefer-dist
