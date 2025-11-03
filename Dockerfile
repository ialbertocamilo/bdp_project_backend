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

# Configure Caddyfile with OPcache enabled
RUN mkdir -p /etc/frankenphp && cat > /etc/frankenphp/Caddyfile << 'EOF'
:80

root /app/public
php_server {
    # Enable OPcache for performance
    php_ini opcache.enable=1
    php_ini opcache.enable_cli=1
    php_ini opcache.memory_consumption=256
    php_ini opcache.interned_strings_buffer=16
    php_ini opcache.max_accelerated_files=20000
    php_ini opcache.max_wasted_percentage=10
    php_ini opcache.validate_timestamps=1
    php_ini opcache.revalidate_freq=0
    php_ini opcache.consistency_checks=0

    # PHP memory settings
    php_ini memory_limit=512M
    php_ini max_execution_time=300
    php_ini upload_max_filesize=100M
    php_ini post_max_size=100M
}
EOF

EXPOSE 80 443

CMD ["frankenphp"]
