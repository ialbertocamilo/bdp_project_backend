#!/bin/bash

# Cambiar al directorio de trabajo
cd /var/www/html

# Verificar si vendor/ existe y está completo
if [ ! -d "vendor" ] || [ -z "$(ls -A vendor)" ]; then
    echo "Directorio vendor/ no existe o está vacío. Restaurando desde backup..."
    
    # Verificar si existe el backup
    if [ -d "/tmp/vendor-backup" ] && [ ! -z "$(ls -A /tmp/vendor-backup)" ]; then
        echo "Restaurando vendor/ desde backup..."
        cp -r /tmp/vendor-backup vendor/
    else
        echo "Backup no encontrado. Ejecutando composer install..."
        composer install --optimize-autoloader
    fi
else
    echo "Directorio vendor/ existe y no está vacío."
    
    # Verificar si nunomaduro/collision está disponible
    if [ ! -d "vendor/nunomaduro/collision" ]; then
        echo "Dependencia nunomaduro/collision no encontrada. Reinstalando dependencias..."
        rm -rf vendor/
        composer install --optimize-autoloader
    fi
fi

echo "Inicialización de vendor/ completada."

# Ejecutar el comando original de FrankenPHP
exec "$@"