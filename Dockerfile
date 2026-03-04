# Imagen base con Apache y PHP 8.2
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
   git curl libpng-dev libonig-dev libxml2-dev zip unzip \
   && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Configurar permisos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 🔧 Configuración de Apache
# Habilitar mod_rewrite
RUN a2enmod rewrite

# Cambiar DocumentRoot a public/
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Permitir .htaccess en public/
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Exponer puerto 80
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]

# Configuración personalizada de PHP
RUN echo "memory_limit=2048M" > /usr/local/etc/php/conf.d/memory-limit.ini \
   && echo "upload_max_filesize=500M" > /usr/local/etc/php/conf.d/upload-max-filesize.ini \
   && echo "post_max_size=500M" > /usr/local/etc/php/conf.d/post-max-size.ini \
   && echo "max_execution_time=3600" > /usr/local/etc/php/conf.d/max-execution-time.ini



# FROM php:8.2-fpm

# # Instalar dependencias del sistema
# RUN apt-get update && apt-get install -y \
#    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
#    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# # Instalar Composer
# COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# # Directorio de trabajo
# WORKDIR /var/www

# # Copiar archivos del proyecto
# COPY . .

# # Instalar dependencias de Laravel
# RUN composer install --no-dev --optimize-autoloader

# # Configurar permisos
# RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# EXPOSE 9000

# CMD ["php-fpm"]
