FROM dunglas/frankenphp:latest-php8.2

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install pdo_mysql zip gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de PHP
RUN composer install --optimize-autoloader --no-dev

# Crear backup del vendor/ para desarrollo con volúmenes
RUN cp -r /var/www/html/vendor /tmp/vendor-backup

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copiar y hacer ejecutable el script de inicialización
COPY init-vendor.sh /usr/local/bin/init-vendor.sh
RUN chmod +x /usr/local/bin/init-vendor.sh

# Configurar FrankenPHP
ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_ENV=production

EXPOSE 80
EXPOSE 443

ENTRYPOINT ["/usr/local/bin/init-vendor.sh"]
CMD ["sh", "-c", "php artisan migrate:fresh --force --seed && frankenphp run --config /var/www/html/Caddyfile"]