#!/bin/bash
# Verificação rápida em produção (sem login: 302 GET, 419 POST = rota OK; 404 = path errado)
set -e
BASE="${1:-https://omegaadm.feston.net.br/public/rh/beneficios/1}"

echo "=== GET $BASE ==="
curl -sI -X GET "$BASE" | head -5

echo ""
echo "=== POST $BASE (sem CSRF: esperado 419, não 404) ==="
curl -sI -X POST "$BASE" | head -5

echo ""
echo "Se POST retornar 404, path/rewrite ainda incorreto."
echo "Se POST retornar 419 ou 302, Laravel casou a rota rh/beneficios/{id}."
