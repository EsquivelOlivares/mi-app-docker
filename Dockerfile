FROM php:8.2-apache

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_pgsql zip

# Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Copiar código
COPY app/ /var/www/html/

# Configurar Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html

# Puerto
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]