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
mkdir -p ../../public/agentes/fonts
cp -f node_modules/@expo/vector-icons/build/vendor/react-native-vector-icons/Fonts/*.ttf ../../public/agentes/fonts/ 2>/dev/null || true
cd ../..

# 3. Traduce al espanol el shell HTML generado por Expo (idioma, titulo y
#    mensaje que se ve sin JavaScript). Expo ignora app/+html.tsx en modo de
#    salida "single", asi que se ajusta el archivo ya generado.
INDEX_HTML="public/agentes/index.html"
if [ -f "$INDEX_HTML" ]; then
  sed -i 's/lang="en"/lang="es"/' "$INDEX_HTML"
  sed -i 's|<title>[^<]*</title>|<title>Agentes de Tránsito · Municipio de Uriangato</title>|' "$INDEX_HTML"
  sed -i 's|You need to enable JavaScript to run this app.|Necesitas habilitar JavaScript para usar esta aplicación.|' "$INDEX_HTML"
  sed -i 's|<!-- Use static rendering with Expo Router to support running without JavaScript. -->|<!-- Renderizado inicial: la interfaz aparece al cargar JavaScript. -->|' "$INDEX_HTML"
  sed -i 's|<!-- The root element for your Expo app. -->|<!-- Contenedor raíz de la aplicación. -->|' "$INDEX_HTML"
fi

echo "Build completo: PHP backend + App Agentes de Transito"
