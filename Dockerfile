# Usamos PHP 8.2 con Apache (ligero)
FROM php:8.2-apache

# Instalar extensiones de PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Habilitar módulo de Apache para reescribir URLs
RUN a2enmod rewrite

# Copiar nuestra aplicación al contenedor
COPY app/ /var/www/html/

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html

# Puerto que expone Apache
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]