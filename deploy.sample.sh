#!/usr/bin/env bash
set -euo pipefail

# Ejemplo: ejecutar desde /home/USER/mi-tienda-pesca
git pull --ff-only

# Sincronizar public_html del repo a la public_html real
# rsync -a --delete public_html/ /home/USER/public_html/

# Instalar dependencias (si usás composer, descomentar)
# composer install --no-dev --optimize-autoloader
# php -r "opcache_reset();"
echo "Deploy OK"
