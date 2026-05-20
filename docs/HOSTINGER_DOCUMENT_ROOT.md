# Hostinger — Document Root correto para Laravel

## Problema

Site acessado como `https://dominio/public/rh/...` com document root na **raiz do projeto** → Laravel recebe path `public/rh/...` → **404** em POST/GET.

## Solução recomendada (5 minutos)

1. **hPanel** → **Websites** → selecionar o site.
2. **Avançado** → **Configuração do site** / **Document Root**.
3. Alterar de:
   ```text
   .../omegaadm
   ```
   para:
   ```text
   .../omegaadm/public
   ```
4. No `.env` do servidor:
   ```env
   APP_URL=https://omegaadm.feston.net.br
   ```
   (sem `/public` no final)
5. SSH:
   ```bash
   cd ~/omegaadm   # ou o caminho real exibido no pwd
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

6. Testar: `https://omegaadm.feston.net.br/rh/beneficios/1`

## Se não puder mudar o document root agora

Manter URL com `/public` e garantir deploy do `.htaccess` mais recente (rewrite para `public/index.php`).

Ver `docs/DEV_BENEFICIOS_404_PRODUCAO.md`.
