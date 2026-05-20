#!/bin/bash
# Cole no Terminal SSH da Hostinger (hPanel > Sites > Terminal).
# Ajuste o caminho se a pasta do projeto for outra.

cd ~/domains/omegaadm.feston.net.br/public_html 2>/dev/null || cd ~/omegaadm 2>/dev/null || cd ~/public_html || exit 1

git pull origin main
php artisan migrate --force
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan route:list --path=beneficios/vinculos 2>/dev/null | head -5

echo "Pronto. Abra /rh/beneficios/1 com Ctrl+F5 e teste Salvar (URL deve usar /vinculos)."
