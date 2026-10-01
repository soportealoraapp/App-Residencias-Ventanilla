#!/usr/bin/env bash
# Instalador de dependencias para despliegue en Vercel.
# Vercel limita installCommand a 256 caracteres, así que la lógica larga va aquí.
set -euo pipefail

# 1. Dependencias PHP (CodeIgniter 4 backend)
curl -sSfL -o composer-setup.php https://getcomposer.org/installer
php composer-setup.php --quiet --install-dir=/tmp --filename=composer
rm -f composer-setup.php
php -d memory_limit=-1 /tmp/composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
rm -f /tmp/composer

# 2. Build estatico de la App Agentes de Transito (Expo Web -> public/agentes/)
# Los assets de fuentes de iconos se generan durante el build y quedan en
# public/agentes/assets/ para que Vercel los sirva directamente.
cd mobile/agentes-transito
npm install --legacy-peer-deps
npx expo export -p web --output-dir ../../public/agentes --clear
cd ../..

echo "Build completo: PHP backend + App Agentes de Transito"
