#!/bin/bash
# Cole no Terminal SSH da Hostinger (hPanel > Sites > Terminal).
# Ajuste o caminho se a pasta do projeto for outra.

cd ~/domains/omegaadm.feston.net.br/public_html || cd ~/public_html || exit 1

git pull origin main
php artisan migrate --force
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear

echo "Pronto. Teste salvar um vinculo em /rh/beneficios/1"
