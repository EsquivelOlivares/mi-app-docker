FROM php:8.2-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Instalar extensiones PHP esenciales
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    mbstring \
    xml \
    bcmath

# 3. Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# 4. Habilitar mod_rewrite para Apache
RUN a2enmod rewrite headers

# 5. Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Crear directorio de trabajo y copiar archivos
WORKDIR /var/www/html

# 7. Copiar solo composer.json primero para caché eficiente
COPY app/composer.json /var/www/html/

# 8. Instalar dependencias PHP si composer.json existe
RUN if [ -f "/var/www/html/composer.json" ]; then \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi

# 9. Copiar el resto de la aplicación
COPY app/ /var/www/html/

# 10. Si existe vendor de composer, optimizar autoload
RUN if [ -f "/var/www/html/composer.json" ]; then \
    composer dump-autoload --optimize; \
    fi

# 11. Configurar Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 12. Configurar archivo de virtual host para Apache
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# 13. Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/storage/logs \
    && chmod -R 777 /var/www/html/storage

# 14. Exponer puerto
EXPOSE 80

# 15. Script de inicio personalizado
COPY docker/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# 16. Comando para iniciar Apache con variables de entorno
CMD ["docker-entrypoint.sh"]