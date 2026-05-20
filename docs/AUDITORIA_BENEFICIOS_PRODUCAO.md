# Auditoria: benefícios localhost vs produção

## Como funciona no localhost (`http://127.0.0.1:2080/rh/beneficios/1`)

| Item | Localhost |
|------|-----------|
| Servidor | `php artisan serve` (ou equivalente) com **document root = pasta `public/`** |
| URL no navegador | `/rh/beneficios/1` — **sem** `/public` |
| GET | Rota `rh.beneficios.show` → `BeneficioController@show` |
| POST (Vincular / Salvar / Excluir) | **Mesma URL** `POST /rh/beneficios/1` → `BeneficioController@show` delega para `BeneficioColaboradorController@store` |
| Apache | Não interfere; tudo vai para `public/index.php` |

## O que acontecia em produção (`https://omegaadm.feston.net.br/public/rh/beneficios/1`)

| Item | Produção (antes do fix) |
|------|-------------------------|
| Document root | Pasta do projeto (`omegaadm`), não só `public/` |
| URL no navegador | Inclui **`/public/`** no caminho |
| GET da tela | Funcionava (Apache/Laravel respondiam) |
| POST | Ia para URLs extras (`/colaboradores/12`, `/vinculos`, etc.) ou **não passava pelo `index.php`** → **404** do servidor |
| Formulários | HTML inválido (`<form>` dentro de `<table>`) + `action` gerado diferente da URL real |

**Não era migration.** A tabela `colaborador_beneficios` já existe se a lista aparece.

## Correções aplicadas (definitivas)

1. **`.htaccess` na raiz** — qualquer pedido (GET/POST) que não for arquivo real vai para `index.php` (igual fluxo local).
2. **`index.php` na raiz** — já existia; carrega `public/index.php`.
3. **Rota única** — `Route::match(['get','post'], 'beneficios/{beneficio}', ...)` (mesma URL para ler e salvar).
4. **Formulários** — `action="{{ request()->url() }}"` (POST exatamente para a URL que o navegador já abriu).
5. **Layout** — um `<form>` completo por colaborador (HTML válido, sem `form=""` entre células).
6. **Middleware `ForceRequestRootUrl`** — links e redirects respeitam `/public` quando o servidor usa subpasta.

## Deploy em produção (Hostinger)

```bash
cd ~/omegaadm
git pull origin main
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan route:list | grep "beneficios/{beneficio}"
```

Deve aparecer `GET|POST|HEAD  rh/beneficios/{beneficio}`.

### `.env` em produção (recomendado)

```env
APP_URL=https://omegaadm.feston.net.br/public
```

(Com a barra e o `/public` se a URL do site sempre mostra isso.)

### Teste após deploy

1. Aba anônima → RH → Benefícios → benefício 1.
2. F12 → Rede → Salvar.
3. Deve ser: **`POST`** para `.../rh/beneficios/1` (mesmo path do GET), status **302** (redirect), não 404.

### Ideal no Hostinger (opcional)

No hPanel, definir **document root** = `omegaadm/public`. Aí a URL fica `https://omegaadm.feston.net.br/rh/beneficios/1` (sem `/public`), igual ao localhost.
