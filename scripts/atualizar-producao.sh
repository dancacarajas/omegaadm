#!/bin/bash
# Terminal SSH Hostinger — pasta do projeto (ajuste se necessário)
cd ~/omegaadm 2>/dev/null || cd ~/domains/omegaadm.feston.net.br/public_html 2>/dev/null || cd ~/public_html || exit 1

git pull origin main
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

echo "--- Rota gestão benefício (deve ser GET|POST) ---"
php artisan route:list --path=beneficios/ 2>/dev/null | head -8

echo ""
echo "--- Benefícios no banco (diagnóstico 404 GET logado) ---"
php artisan beneficios:diagnostico 1 2>/dev/null || true

echo ""
echo "Pronto. Teste pela LISTAGEM /public/rh/beneficios → Ver → Salvar (não force /beneficios/1 sem ID existir)."
