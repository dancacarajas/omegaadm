# Causa raiz — 404 em produção (Hostinger + `/public` na URL)

**Projeto:** omegaadm / omega286  
**Domínio:** `https://omegaadm.feston.net.br`  
**Sintoma:** `404 | NOT FOUND` em rotas dinâmicas RH (Benefícios, Movimentações / Alterar, etc.)  
**Localhost:** funciona (`http://127.0.0.1:2080/rh/...` sem `/public` no path)  
**Data:** maio/2026  

---

## 1. Conclusão executiva (para gestão e dev)

**Não é defeito isolado de Benefícios nem de Movimentações.**

Dois módulos com o mesmo padrão de falha em produção indicam **problema sistêmico de ambiente**:

| Fator | Efeito |
|-------|--------|
| Document root na **raiz** do projeto (`omegaadm`) | URL pública obrigada a usar `/public/...` |
| Laravel registra rotas como `rh/beneficios/{id}`, `rh/efetivo/movimentacao/{id}` | Router precisa receber path **sem** prefixo `public/` |
| Rewrite + `REQUEST_URI` + cache de rota/view | Rotas dinâmicas falham intermitente ou por módulo |
| Remendos por tela | Listagem pode abrir; **Alterar / Salvar / POST** quebram |

**Correção profissional (definitiva):** apontar o document root da Hostinger para `omegaadm/public` e usar `APP_URL=https://omegaadm.feston.net.br` **sem** `/public`. URLs ficam iguais ao localhost.

**Enquanto não mudar o document root:** é obrigatório que o fix de `/public` funcione para **todas** as rotas dinâmicas (não só um módulo). Ver seções 4 e 6.

Documentos por módulo (detalhe técnico):

- [DEV_BENEFICIOS_404_PRODUCAO.md](./DEV_BENEFICIOS_404_PRODUCAO.md)
- [DEV_MOVIMENTACOES_404_PRODUCAO.md](./DEV_MOVIMENTACOES_404_PRODUCAO.md)
- [HOSTINGER_DOCUMENT_ROOT.md](./HOSTINGER_DOCUMENT_ROOT.md)

---

## 2. Casos confirmados

### Benefícios

- URL produção: `/public/rh/beneficios/{id}`
- POST Salvar / Excluir / Vincular já foram alvo de correção (`Route::match` GET+POST, `fix-public-request-uri`, etc.)

### Movimentações (caso atual)

| Ambiente | URL botão **Alterar** | Resultado |
|----------|------------------------|-----------|
| **Produção** | `https://omegaadm.feston.net.br/public/rh/efetivo/movimentacao/2` | **404** |
| **Localhost** | `http://127.0.0.1:2080/rh/efetivo/movimentacao/2` | **200** + formulário |

- Listagem `/public/rh/efetivo/movimentacoes` reportada **OK** (layout e CSS corrigidos em `436330f`).
- Registro `mov_id=2` existe no banco (ex.: afastamento INSS, colaborador 34).
- Erros `pinComponent.js` / `chrome-extension` no console → **ignorar** (extensão do navegador).

---

## 3. O que o repositório já implementou (não repetir módulo a módulo)

Commits relevantes em `main` (ordem aproximada):

| Commit | Escopo |
|--------|--------|
| `930071f` / `3bb5a2d` | Normalização `REQUEST_URI` `/public` (benefícios) |
| `8224cda` | Movimentações: GET+POST mesma URL (padrão benefícios) |
| `e6b360b` | Path `rh/efetivo/movimentacao/{id}` |
| `2533484` / `436330f` | CSS/JS: `ForceRequestRootUrl` + `Vite` + `PublicWebBase` |
| `05aa59b` | Rotas movimentação no topo do grupo `rh` |
| `54500a1` | Documentação handoff movimentações |

**Código compartilhado (todos os módulos RH):**

- `bootstrap/fix-public-request-uri.php` — strip `/public` no path do router
- `index.php` (raiz) + `public/index.php` — carregam o fix
- `.htaccess` (raiz) — rewrite `/public/*` → `public/index.php`
- `app/Http/Middleware/ForceRequestRootUrl.php`
- `app/Support/PublicWebBase.php`
- `config/app.php` → `force_public_url` default `true` em `APP_ENV=production`

Se produção ainda retorna 404 com commit `>= 05aa59b`, o gargalo é **deploy, cache, rewrite ou document root** — não falta de rota no Git.

---

## 4. Solução definitiva (infra — prioridade máxima)

### Passos no hPanel Hostinger

1. **Websites** → site → **Document Root**
2. Alterar de `.../omegaadm` para `.../omegaadm/public`
3. No `.env` do servidor:

```env
APP_URL=https://omegaadm.feston.net.br
APP_FORCE_PUBLIC_URL=false
```

4. SSH:

```bash
cd ~/domains/feston.net.br/public_html/omegaadm
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
php artisan storage:link
```

### URLs esperadas após a mudança (iguais ao localhost)

```text
https://omegaadm.feston.net.br/rh/beneficios/1
https://omegaadm.feston.net.br/rh/efetivo/movimentacoes
https://omegaadm.feston.net.br/rh/efetivo/movimentacao/2
```

**Sem** `/public` na barra de endereço.

### Critérios de aceite (entrega final)

- [ ] Causa raiz confirmada (documentada neste arquivo ou comentário no ticket)
- [ ] Print ou texto de `php artisan route:list --path=efetivo/movimentacao`
- [ ] Retorno de `movimentacoes:diagnostico 2 --mov --http` com status **200**
- [ ] Teste real no navegador: Benefícios abrir + Salvar
- [ ] Teste real no navegador: Movimentações → Alterar → Salvar
- [ ] Nenhuma tela RH com 404 em ação editar/salvar/excluir (amostragem combinada)

---

## 5. Enquanto o document root não mudar (workaround)

Garantir no servidor:

1. Arquivos `bootstrap/fix-public-request-uri.php`, `.htaccess` raiz, `index.php` raiz **idênticos** ao `main` do Git
2. **Nunca** deixar `bootstrap/cache/routes-v7.php` desatualizado após deploy
3. `APP_FORCE_PUBLIC_URL=true` se links/CSS ainda saírem sem `/public`
4. Não usar `php artisan route:cache` em produção até o ambiente estar estável (ou sempre `route:clear` após deploy)

---

## 6. Checklist obrigatório em produção (copiar para o ticket)

Executar **no servidor**, na pasta do projeto (`omegaadm`):

```bash
cd ~/domains/feston.net.br/public_html/omegaadm
```

### 6.1 Commit atual

```bash
git fetch origin main
git pull origin main
git log -1 --oneline
```

**Esperado:** `05aa59b` ou mais recente (ex.: `54500a1` docs).

### 6.2 Limpar cache real

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
rm -f bootstrap/cache/routes*.php
```

### 6.3 Rota de movimentação existe?

```bash
php artisan route:list --path=efetivo/movimentacao
```

**Esperado:**

```text
GET|POST|HEAD  rh/efetivo/movimentacao/{movimentacao}  rh.efetivo.movimentacoes.edit
```

Se não aparecer → código antigo ou route cache.

### 6.4 Diagnóstico movimentações

```bash
php artisan movimentacoes:diagnostico --rota
php artisan movimentacoes:diagnostico --listar
php artisan movimentacoes:diagnostico 2 --mov
php artisan movimentacoes:diagnostico 2 --mov --http
```

| Comando | Esperado |
|---------|----------|
| `--rota` | `rh.efetivo.movimentacoes.edit: SIM` |
| `--listar` | tabela com `mov_id` reais |
| `2 --mov` | OK + URL `/public/rh/efetivo/movimentacao/2` |
| `2 --mov --http` | **status 200** |

### 6.5 Teste curl (sem sessão)

```bash
curl -sI "https://omegaadm.feston.net.br/public/rh/efetivo/movimentacao/2" | head -5
curl -sI "https://omegaadm.feston.net.br/public/rh/beneficios/1" | head -5
```

Interpretação:

| HTTP | Significado |
|------|-------------|
| **404** | Rota/rewrite não casa ou binding 404 |
| **302** / **301** | Rota existe; testar logado |
| **419** | POST sem CSRF (rota existe) |

Comparar: se benefícios retorna 302 e movimentação 404 → problema específico de rota deploy/cache; se **ambos 404** → rewrite/path global.

### 6.6 Debug no navegador (temporário)

Com `APP_DEBUG=true` (reverter depois):

```text
https://omegaadm.feston.net.br/public/rh/efetivo/movimentacao/2?debug_movimentacao=1
```

| Resultado | Conclusão |
|-----------|-----------|
| **`dd()` não aparece** | Requisição **não chega** em `ColaboradorMovimentacaoController@editar` → rota, rewrite, cache ou deploy |
| **`dd()` aparece** | Ver `path`, `request_uri`, `expected_route`; se `path` = `public/rh/...` → fix-public não aplicado |

Campos esperados no `dd()`:

| Campo | Valor esperado |
|-------|----------------|
| `path` | `rh/efetivo/movimentacao/2` |
| `expected_route` | `rh/efetivo/movimentacao/2` |

### 6.7 HTML da listagem (Inspecionar → Alterar)

Confirmar `href`:

```text
✅ .../public/rh/efetivo/movimentacao/2
```

**Não** deve ser URL antiga:

```text
❌ .../rh/movimentacoes/2/editar
❌ .../rh/efetivo/movimentacoes/2/editar
❌ .../rh/efetivo/35/movimentacoes/2/editar
```

Se o `href` estiver correto e o clique ainda 404 → servidor/rota; se `href` antigo → `view:clear` ou deploy incompleto.

### 6.8 Fix `/public` carregado?

```bash
test -f bootstrap/fix-public-request-uri.php && echo "fix OK"
grep -n "OMEGA_REQUEST_USES_PUBLIC_URL" bootstrap/fix-public-request-uri.php
head -5 index.php
head -25 public/index.php
```

`index.php` raiz deve incluir `fix-public-request-uri` antes de `public/index.php`.  
`public/index.php` deve incluir o mesmo `require` do fix.

### 6.9 Dados do servidor (responder no ticket)

1. Apache, LiteSpeed ou Nginx?
2. Document root atual: `omegaadm` ou `omegaadm/public`?
3. Existe `bootstrap/cache/routes-v7.php` após `route:clear`?
4. Saída completa dos comandos 6.3, 6.4 e 6.5

---

## 7. Árvore de decisão (dev)

```mermaid
flowchart TD
    A[404 em /public/rh/.../id] --> B{git log >= 05aa59b?}
    B -->|Não| C[git pull + clear caches]
    B -->|Sim| D{route:list tem efetivo/movimentacao?}
    D -->|Não| E[route:clear + rm bootstrap/cache/routes*]
    D -->|Sim| F{diagnostico --http 200?}
    F -->|Não| G{path no debug tem public/?}
    G -->|Sim| H[Corrigir fix-public / .htaccess / index.php]
    G -->|Não| I[Binding / registro inexistente]
    F -->|Sim| J{Browser ainda 404?}
    J -->|Sim| K[href HTML / cache browser / CDN]
    J -->|Não| L[Resolvido]
    H --> M{Ainda falha?}
    M -->|Sim| N[Mudar document root para omegaadm/public]
```

---

## 8. Mensagem pronta para o desenvolvedor (copiar/colar)

```text
Agora o mesmo tipo de problema apareceu em outra tela: Movimentações, botão Alterar.

Isso confirma que não é um defeito isolado de Benefícios. É um problema sistêmico de produção envolvendo URL com /public, rotas dinâmicas, rewrite/cache/deploy ou document root.

O caso atual:
Produção com erro:
https://omegaadm.feston.net.br/public/rh/efetivo/movimentacao/2

Localhost funciona:
http://127.0.0.1:2080/rh/efetivo/movimentacao/2

Preciso que você pare de tratar módulo por módulo e resolva a causa raiz do ambiente.

Documentação no repositório:
docs/DEV_PRODUCAO_CAUSA_RAIZ_HOSTINGER.md
docs/DEV_MOVIMENTACOES_404_PRODUCAO.md
docs/DEV_BENEFICIOS_404_PRODUCAO.md
docs/HOSTINGER_DOCUMENT_ROOT.md

Executar checklist completo em produção (seção 6 do DEV_PRODUCAO_CAUSA_RAIZ_HOSTINGER.md).

Conclusão: agora temos dois módulos com problema semelhante em produção. Não quero mais remendo por tela. Preciso da correção definitiva do ambiente.

A solução profissional é ajustar o document root da Hostinger para:
omegaadm/public

E deixar:
APP_URL=https://omegaadm.feston.net.br
Sem /public.

Depois disso, todas as URLs devem ficar sem /public, igual localhost:
https://omegaadm.feston.net.br/rh/beneficios/1
https://omegaadm.feston.net.br/rh/efetivo/movimentacao/2

Se não for possível mudar o document root agora, então precisa garantir que o fix de /public funcione para todas as rotas dinâmicas, não só Benefícios.

Entrega final:
- causa raiz confirmada;
- print/retorno do route:list;
- retorno do diagnóstico --http;
- confirmação de teste real no navegador;
- Benefícios funcionando;
- Movimentações funcionando;
- nenhuma tela RH com 404 em ação de editar/salvar/excluir.
```

---

## 9. Referência rápida — arquivos de infra

| Arquivo | Papel |
|---------|--------|
| `.htaccess` | Rewrite `/public/*` → `public/index.php` |
| `index.php` | Entrada raiz + fix URI |
| `public/index.php` | Front controller + fix URI |
| `bootstrap/fix-public-request-uri.php` | `OMEGA_REQUEST_USES_PUBLIC_URL`, strip `/public` do path |
| `app/Http/Middleware/ForceRequestRootUrl.php` | Base URL e Vite com `/public` |
| `config/app.php` | `force_public_url` |

---

*Atualizar este documento quando o document root for migrado ou quando o checklist 6 for executado com resultados.*
