FROM dunglas/frankenphp:latest-php8.3

WORKDIR /app

# Instalar extensiones PHP necesarias (usando el helper de FrankenPHP)
RUN install-php-extensions gd zip pdo_mysql

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

EXPOSE 80 443

CMD ["frankenphp", "php-server", "--root", "/app/public"]
