FROM php:8.4-apache

# Instala dependencias necesarias
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copia todos los archivos del proyecto
COPY . /var/www/html

# Copia el VirtualHost de Apache
COPY apache/vhost.conf /etc/apache2/sites-enabled/000-default.conf

# Activa mod_rewrite
RUN a2enmod rewrite

# Instala dependencias de PHP
RUN composer install --no-interaction --prefer-dist

# Ajusta permisos de las carpetas donde PHP necesita escribir
RUN chown -R www-data:www-data /var/www/html/resources/config \
    && chmod -R 775 /var/www/html/resources/config \
    && chown -R www-data:www-data /var/www/html/cache \
    && chmod -R 775 /var/www/html/cache

# Expone el puerto 80
EXPOSE 80

# Comando por defecto de Apache
CMD ["apache2-foreground"]
