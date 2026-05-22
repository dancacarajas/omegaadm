# Hostinger — Document Root correto para Laravel

**Handoff completo (404 Benefícios + Movimentações + checklist):** [DEV_PRODUCAO_CAUSA_RAIZ_HOSTINGER.md](./DEV_PRODUCAO_CAUSA_RAIZ_HOSTINGER.md)

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
   php artisan storage:link
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

   **Foto de perfil:** após `git pull`, as imagens são servidas pela rota
   `/public/rh/efetivo/{id}/foto` (não depende de `storage:link`). Teste logado:
   `https://omegaadm.feston.net.br/public/rh/efetivo/1/foto`

6. Testar: `https://omegaadm.feston.net.br/rh/beneficios/1`

7. Após alterar CSS/JS do extrato de benefícios (ou qualquer view Tailwind), no servidor:
   ```bash
   git pull origin main
   npm run build   # se Node estiver disponível no SSH; senão confira se `public/build` veio no pull
   php artisan view:clear
   php artisan optimize:clear
   ```

## Se não puder mudar o document root agora

Manter URL com `/public` e garantir deploy do `.htaccess` mais recente (rewrite para `public/index.php`).

Com `APP_ENV=production`, o sistema **já força** links e `@vite` para `https://seu-dominio/public/...` (CSS/JS). Só desative se mudar o document root para `omegaadm/public`:

```env
APP_FORCE_PUBLIC_URL=false
```

Após cada deploy com CSS quebrado:

```bash
git pull origin main
php artisan config:clear
php artisan view:clear
php artisan optimize:clear
# confira se existe public/build/manifest.json no servidor
```

Ver `docs/DEV_BENEFICIOS_404_PRODUCAO.md`.
