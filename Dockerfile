FROM dunglas/frankenphp:latest-php8.3

WORKDIR /app

# Instalar extensiones PHP necesarias (usando el helper de FrankenPHP)
RUN install-php-extensions pdo_mysql gd zip

# Instalar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar solo los archivos de dependencias primero (para caché de Docker)
COPY composer.json composer.lock* ./

# Instalar dependencias (incluyendo dev para desarrollo)
RUN composer install --no-interaction --prefer-dist --ignore-platform-reqs || true

# Copiar el resto del código
COPY . .

# Establecer permisos correctos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Copy php.ini with opcache configuration
COPY php.ini /usr/local/etc/php/conf.d/opcache.ini

# Install opcache extension for better performance
RUN install-php-extensions opcache || true

EXPOSE 80 443

CMD ["frankenphp", "run"]
