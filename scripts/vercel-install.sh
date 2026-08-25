#!/usr/bin/env bash
# Instalador de dependencias para despliegue en Vercel.
# Vercel limita installCommand a 256 caracteres, así que la lógica larga va aquí.
set -euo pipefail

curl -sSfL -o composer-setup.php https://getcomposer.org/installer
php composer-setup.php --quiet --install-dir=/tmp --filename=composer
rm -f composer-setup.php
php -d memory_limit=-1 /tmp/composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
rm -f /tmp/composer
