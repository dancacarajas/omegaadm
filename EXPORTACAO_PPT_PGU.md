# Exportacao PPT do PGU - Documentacao Completa

## 1) Objetivo

Este documento descreve toda a implementacao da exportacao de apresentacao PGU para PowerPoint (`.pptx`) no projeto `omega286`, incluindo:

- fluxo funcional da interface;
- fluxo tecnico backend (captura de slides e montagem do PPT);
- dependencias e requisitos de ambiente;
- parametros e contratos da rota;
- tratamento de erros;
- troubleshooting;
- implantacao em ambiente de producao.

---

## 2) Escopo da funcionalidade

A exportacao gera um arquivo `.pptx` com base em **screenshots PNG** de telas HTML dos slides PGU.

Slides exportados atualmente:

1. Capa (`/pgu-cover`)
2. Slide 1 - Visao Geral (`/pgu-slide`)
3. Slide 2 - Funcoes com PGU 100% (`/pgu-slide-2`)
4. Slide 3 - Principais Gargalos (`/pgu-slide-3`)
5. Slide 4 - Concentracao do Problema (`/pgu-slide-4`)
6. Slide 5 - Plano de Acao Executivo (`/pgu-slide-5`)

---

## 3) Mapeamento de arquivos (implementacao)

## Backend

- `app/Http/Controllers/PguDashboardController.php`
  - metodo principal: `exportarPowerPoint(Request $request)`
  - metodos auxiliares:
    - `bootAuxCaptureServer()`
    - `auxCaptureCandidatePorts()`
    - `resolvePhpCliBinary()`
    - `buildCaptureCookieHeader()`
    - `resolveChromeExecutablePath()`
    - `isPortOpen()`
  - metodos de dados por slide:
    - `buildPguExecutiveSlideVars()`
    - `buildPguSlide2Vars()`
    - `buildPguSlide3Vars()`
    - `buildPguSlide4Vars()`
    - `buildPguSlide5Vars()`

## Rotas

- `routes/web.php`
  - rota protegida:
    - `GET /exportar-pgu-powerpoint` (nome: `pgu.export.ppt`)
    - `GET /debug-pgu-powerpoint` (nome: `pgu.export.ppt.debug`)
  - rota publica (prefixo `publico`):
    - `GET /publico/exportar-pgu-powerpoint` (nome: `publico.pgu.export.ppt`)
    - `GET /publico/debug-pgu-powerpoint` (nome: `publico.pgu.export.ppt.debug`)

## Configuracao

- `config/pgu_export.php`
  - timeout, escala, viewport e mapeamento dos slides exportados.
- variaveis em `.env` / `.env.example`:
  - `PGU_EXPORT_CHROME_PATH`
  - `PGU_EXPORT_TIMEOUT`
  - `PGU_EXPORT_SCALE`
  - `PGU_EXPORT_KEEP_FILES`

## Frontend

- `resources/views/dashboard/pgu-apresentacao.blade.php`
  - botao "Exportar para PPT"
  - injeta `data-export-url` com a rota correta (publica/interna)
- `resources/js/pgu-dashboard.js`
  - shell `window.pguApresentacaoShell`
  - metodo `exportPpt()` monta e submete `form GET` para rota de exportacao com filtros

---

## 4) Fluxo funcional (usuario)

1. Usuario abre `Contratos > Apresentacao PGU`.
2. Seleciona filtros:
   - `contrato`
   - `competencia`
   - `data_limite_etapa_2` (opcional)
3. Clica em **Exportar para PPT**.
4. Frontend envia `GET` para rota de exportacao.
5. Backend renderiza/captura slides e monta `.pptx`.
6. Browser recebe download de:
   - `pgu-visao-executiva-<contrato>-<competencia>.pptx`

---

## 5) Fluxo tecnico detalhado (backend)

## 5.1 Entrada

Metodo: `PguDashboardController::exportarPowerPoint(Request $request)`

Parametros lidos da query:

- `contrato`
- `competencia`
- `data_limite_etapa_2`

Somente valores nao vazios entram em `http_build_query`.

## 5.2 Ajuste de timeout (correcao aplicada)

No inicio do metodo:

- `@set_time_limit(300)` (quando disponivel)
- `@ini_set('max_execution_time', '300')`

Motivo: exportacao pode exceder 60s (captura sequencial de 6 slides + processamento).

## 5.3 Preparacao de contexto de captura

- define `baseUrl` via request (`getSchemeAndHttpHost`);
- extrai e filtra cookies da requisicao via `buildCaptureCookieHeader()`;
- cria pasta temporaria unica em:
  - `storage/app/pgu-export/<uuid>`

## 5.4 Servidor auxiliar de captura

Metodo: `bootAuxCaptureServer()`

Comportamento:

- localiza binario PHP CLI (evita `php-cgi.exe`);
- sobe processo `php -S 127.0.0.1:<porta> -t public <router_laravel>`;
- tenta portas livres nas faixas:
  - `33000..33100`
  - `2081..2100`
- valida disponibilidade de porta com `fsockopen`.

Objetivo: ter um endpoint HTTP dedicado para o Chrome headless capturar os slides sem acoplar no mesmo ciclo do servidor atual.

## 5.5 Captura de slides com Browsershot

Para cada slide:

- monta URL com query dos filtros;
- gera PNG em `storage/app/pgu-export/<uuid>/<slide>.png`;
- configura Browsershot:
  - `windowSize(1366, 768)`
  - `deviceScaleFactor(2)`
  - `setDelay(500)`
  - `waitUntil=domcontentloaded`
  - `timeout=120000` (120s por slide)
  - `fullPage=false`
  - `omitBackground=false`
  - args Chrome:
    - `--disable-dev-shm-usage`
    - `--no-sandbox`
- tenta `setChromePath(...)` se encontrou executavel;
- injeta header `Cookie` quando aplicavel.

## 5.6 Montagem do PowerPoint

Biblioteca: `phpoffice/phppresentation`

Passos:

1. cria `PhpPresentation`;
2. remove slide padrao;
3. define layout 16:9 (`DocumentLayout::LAYOUT_SCREEN_16X9`);
4. para cada PNG:
   - cria slide;
   - adiciona imagem cobrindo 100% da area;
5. salva arquivo:
   - `storage/app/pgu-export/<uuid>/pgu-visao-executiva.pptx`

## 5.7 Download e cleanup

- nome do arquivo e sanitizado:
  - `pgu-visao-executiva-<contrato>-<competencia>.pptx`
- resposta:
  - `response()->download(...)->deleteFileAfterSend(true)`
- ao final:
  - para processo do servidor auxiliar (`$captureServer->stop(1)`).

---

## 6) Dependencias da funcionalidade

No `composer.json`:

- `spatie/browsershot`
- `phpoffice/phppresentation`

Requisitos operacionais:

- PHP 8.2+
- Node.js instalado (runtime que o Browsershot usa para acionar o Puppeteer)
- Chrome/Chromium acessivel no host

Resolucao de Chrome:

1. `PUPPETEER_EXECUTABLE_PATH` (env)
2. caminhos fixos no Windows (`Program Files`)
3. cache local do Puppeteer (`%USERPROFILE%\.cache\puppeteer`)

---

## 7) Contrato da rota de exportacao

## Endpoint

- `GET /exportar-pgu-powerpoint`

## Query params

- `contrato` (string, opcional, recomendado)
- `competencia` (string no formato `YYYY-MM`, opcional, recomendado)
- `data_limite_etapa_2` (string data `YYYY-MM-DD`, opcional)

## Resposta de sucesso

- status `200`
- download do `.pptx`

## Resposta de falha

- redireciona de volta (`back()`) com flash `error`:
  - falha ao subir servidor auxiliar;
  - timeout/falha no Chrome headless.

---

## 8) Observacoes de seguranca e sessao

- a captura reaproveita credenciais da sessao atual via header `Cookie`;
- `buildCaptureCookieHeader()` permite apenas cookies essenciais:
  - `omega286-session`
  - `remember_web_*`
  - `XSRF-TOKEN`
- evita carregar cookies irrelevantes na captura.

---

## 9) Troubleshooting detalhado

## 9.1 Erro: `Maximum execution time of 60 seconds exceeded`

Causa:

- timeout global da request menor que o tempo real de exportacao.

Estado atual:

- metodo de exportacao sobe limite para 300s.

Se persistir:

- aumentar para 420s/600s temporariamente;
- validar carga de CPU/memoria no host;
- validar tempo de render das rotas de slide.

## 9.2 Erro ao capturar slide (ProcessFailedException)

Checklist:

1. Chrome/Chromium instalado no host.
2. Caminho correto via `PUPPETEER_EXECUTABLE_PATH`.
3. Node funcional no host.
4. Rotas `/pgu-cover`, `/pgu-slide...` respondendo.
5. Sessao/cookies validos.

## 9.3 Servidor auxiliar nao sobe

Checklist:

1. PHP CLI disponivel (`php -v` no terminal do servidor).
2. Portas candidatas nao bloqueadas.
3. Permissao de escrita em `storage/app/tmp/process`.
4. Antivirus/politica do host nao bloqueando `php -S`.

## 9.4 Arquivo baixa vazio/corrompido

Checklist:

1. Verificar se PNGs foram gerados em `storage/app/pgu-export/<uuid>`.
2. Validar permissao de escrita em `storage`.
3. Confirmar espaco em disco.
4. Testar export com 1 contrato leve para isolar carga de dados.

---

## 10) Implantacao (producao) - passo a passo

## 10.1 Pre-requisitos do servidor

1. PHP 8.2+ com extensoes do projeto.
2. Composer instalado.
3. Node.js instalado (recomendado LTS).
4. Chrome/Chromium instalado e executavel.
5. Permissao de escrita:
   - `storage/`
   - `bootstrap/cache`

## 10.2 Deploy da aplicacao

Dentro da pasta do projeto:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```

Se houver alteracao de frontend:

```bash
npm install
npm run build
```

## 10.3 Variaveis de ambiente recomendadas

No `.env` de producao:

- opcional, mas recomendado:
  - `PUPPETEER_EXECUTABLE_PATH=/caminho/para/chrome`

Exemplos (ajustar ao host):

- Windows:
  - `C:\Program Files\Google\Chrome\Application\chrome.exe`
- Linux:
  - `/usr/bin/google-chrome`
  - `/usr/bin/chromium-browser`

## 10.4 Validacao pos-deploy

1. Login na aplicacao.
2. Abrir `Contratos > Apresentacao PGU`.
3. Exportar PPT com filtros reais.
4. Conferir:
   - download sem erro;
   - arquivo abre no PowerPoint;
   - 6 slides presentes;
   - conteudo condizente com a tela.

---

## 11) Monitoramento operacional

Monitorar no `storage/logs/laravel.log`:

- mensagens de falha ao iniciar servidor auxiliar;
- falhas de captura por slide (inclui `slide`, `url`, `chrome_path`);
- frequencia de timeout.

Indicadores recomendados:

- tempo medio de exportacao;
- taxa de sucesso por dia;
- tempo por slide (quando logado manualmente em debug).

---

## 12) Limites atuais e melhorias recomendadas

Limites do desenho atual:

- processo sincrono em request web;
- dependencia de browser headless no servidor;
- custo linear por quantidade de slides.

Melhoria recomendada (fase 2):

1. Migrar exportacao para **job em fila**:
   - request cria job e retorna status;
   - job gera PPT em background;
   - usuario baixa quando pronto.
2. Persistir resultado por hash de parametros (cache de exportacao).
3. Adicionar telemetria de tempo por etapa (render, captura, montagem).
4. Opcional: fallback para export HTML/PDF em degradacao.

---

## 13) Resumo executivo para repasse rapido

- A exportacao PPT usa captura de 6 telas HTML via Browsershot + Chrome headless.
- As imagens sao inseridas em PPT 16:9 com PHPPresentation.
- Houve falha por timeout de 60s; corrigido no metodo com limite local de 300s.
- Para implantar com estabilidade, garantir: PHP CLI + Node + Chrome + escrita em `storage`.
- Proximo passo estrutural recomendado: assicronizar via fila.
