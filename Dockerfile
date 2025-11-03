FROM dunglas/frankenphp:latest-php8.2

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

# PHP Performance Optimization Configuration
RUN echo "[PHP]\n\
memory_limit=512M\n\
max_execution_time=300\n\
upload_max_filesize=100M\n\
post_max_size=100M\n\
realpath_cache_size=4096K\n\
realpath_cache_ttl=600\n\
" > /usr/local/etc/php/conf.d/performance.ini

EXPOSE 80 443

CMD ["frankenphp", "php-server", "--root", "/app/public"]
