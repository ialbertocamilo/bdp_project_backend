#!/bin/bash

# Script para verificar el estado del contenedor y ejecutar pruebas de rendimiento

echo "╔════════════════════════════════════════════════════════════╗"
echo "║          VERIFICACIÓN Y PRUEBA DE RENDIMIENTO              ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que Docker está corriendo
echo "Verificando estado de los contenedores..."
if ! docker ps > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker no está disponible${NC}"
    exit 1
fi

# Verificar contenedor FrankenPHP
FRANKENPHP_STATUS=$(docker inspect --format='{{.State.Running}}' bdp-app-dev 2>/dev/null)
if [ "$FRANKENPHP_STATUS" != "true" ]; then
    echo -e "${RED}✗ Contenedor FrankenPHP no está corriendo${NC}"
    echo "Iniciando contenedores..."
    docker-compose up -d
    sleep 10
fi

# Verificar que el servidor está respondiendo
echo "Esperando que el servidor esté listo..."
for i in {1..30}; do
    if curl -s http://localhost:8000/api/test > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Servidor respondiendo correctamente${NC}"
        break
    fi
    echo -n "."
    sleep 1
done

echo ""
echo ""

# Verificar estado de salud del contenedor
HEALTH=$(docker inspect --format='{{.State.Health.Status}}' bdp-app-dev 2>/dev/null)
if [ "$HEALTH" = "healthy" ]; then
    echo -e "${GREEN}✓ Contenedor en estado 'healthy'${NC}"
elif [ "$HEALTH" = "unhealthy" ]; then
    echo -e "${YELLOW}⚠ Contenedor en estado 'unhealthy'${NC}"
    echo "  (Esto puede ser normal durante el inicio)"
else
    echo -e "${YELLOW}⚠ Estado de salud: $HEALTH${NC}"
fi

echo ""
echo "Estado del contenedor FrankenPHP:"
docker ps --filter "name=bdp-app-dev" --format "table {{.Names}}\t{{.Status}}"

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Ejecutar pruebas de rendimiento
ITERATIONS=${1:-10}
echo "Ejecutando pruebas de rendimiento con $ITERATIONS iteraciones..."
echo ""

node performance-test.js "$ITERATIONS"

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "Pruebas completadas."
