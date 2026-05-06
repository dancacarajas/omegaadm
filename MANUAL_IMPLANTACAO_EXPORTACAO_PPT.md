# Manual Completo de Implantacao - Exportacao PPT PGU

## 1. Objetivo

Este manual define o processo oficial para implantar a exportacao de PPT do PGU em qualquer ambiente (Hostinger compartilhada, homologacao, producao e novos modulos do sistema).

O objetivo e garantir:

- previsibilidade no deploy;
- zero dependencia de Node/Chrome no servidor para gerar PPT;
- saida fiel dos 6 slides;
- procedimento padrao reutilizavel para futuras exportacoes.

---

## 2. Arquitetura implantada (estado atual)

A exportacao de PPT foi implementada no frontend (browser do usuario), nao no backend.

### 2.1 Como funciona

1. Usuario abre a tela `Apresentacao PGU`.
2. Clica em `Exportar para PPT`.
3. O frontend percorre os 6 slides.
4. Cada slide e capturado com `html2canvas`.
5. O arquivo `.pptx` e montado com `pptxgenjs`.
6. O download acontece no navegador do usuario.

### 2.2 Vantagens para Hostinger compartilhada

- nao precisa `shell_exec`;
- nao precisa Chrome headless no servidor;
- nao precisa processo Node rodando no host;
- reduz falhas de timeout no backend.

---

## 3. Escopo da exportacao

Slides incluidos:

1. Capa
2. Visao Geral PGU
3. Funcoes com PGU 100%
4. Principais Gargalos
5. Concentracao do Problema
6. Plano de Acao Executivo

---

## 4. Arquivos-chave da funcionalidade

## Frontend

- `resources/js/pgu-dashboard.js`
  - `pguApresentacaoShell()`
  - `exportPpt()`
  - `captureCurrentSlidePng()`
  - `waitForUiRender()`
- `resources/views/dashboard/pgu-apresentacao.blade.php`
  - botao `Exportar para PPT`
  - shell de apresentacao e estrutura visual dos slides

## Dependencias JS

- `package.json`
  - `html2canvas`
  - `pptxgenjs`

## Backend (dados)

- `app/Http/Controllers/PguDashboardController.php`
  - alimenta os dados dos slides renderizados na pagina

Observacao: existem rotas antigas de exportacao backend no projeto, mas o fluxo oficial de producao da funcionalidade e o frontend.

---

## 5. Pre-requisitos por ambiente

## 5.1 Local (desenvolvimento)

- PHP 8.2+
- Composer
- Node.js + npm
- Banco configurado

## 5.2 Servidor Hostinger (producao)

- PHP 8.2+ com extensoes do projeto
- Composer
- acesso SSH
- permissao de escrita em `storage` e `bootstrap/cache`

Importante: para este modelo de exportacao PPT, o servidor nao precisa gerar imagens nem montar PPT.

---

## 6. Fluxo oficial de implantacao

## 6.1 Etapa A - preparar release local

No repositorio local:

```bash
npm run build
git add .
git commit -m "feat/fix: <descricao da entrega>"
git push origin main
```

Checklist da etapa A:

- build de frontend executou sem erro;
- arquivos em `public/build` foram atualizados;
- commit foi enviado ao GitHub (nao apenas local).

## 6.2 Etapa B - atualizar servidor

No servidor:

```bash
cd ~/domains/feston.net.br/public_html/omegaadm
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```

## 6.3 Etapa C - validacao funcional

1. Entrar em `Apresentacao PGU`.
2. Selecionar contrato/competencia reais.
3. Clicar em `Exportar para PPT`.
4. Confirmar que:
   - baixa arquivo `.pptx`;
   - os 6 slides existem;
   - layout esta fiel (sem distorcao);
   - textos e graficos correspondem ao filtro escolhido.

---

## 7. Validacao tecnica pos-deploy (obrigatoria)

## 7.1 Conferir versao no servidor

```bash
cd ~/domains/feston.net.br/public_html/omegaadm
git rev-parse HEAD
git rev-parse origin/main
```

Os dois hashes devem ser iguais.

## 7.2 Conferir se nao faltou push

No ambiente local:

```bash
git status -sb
```

Se aparecer `ahead 1` (ou mais), o push nao foi feito.

## 7.3 Conferir cache no navegador

- usar `Ctrl + F5` apos deploy;
- se houver CDN/proxy, invalidar cache estatico quando necessario.

---

## 8. Procedimento de rollback

Se a release apresentar regressao:

1. identificar hash estavel anterior;
2. no servidor:

```bash
cd ~/domains/feston.net.br/public_html/omegaadm
git checkout <hash-estavel>
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
```

3. corrigir no branch principal e fazer novo deploy.

Observacao: se houver migration destrutiva em outra entrega, tratar rollback de banco separadamente.

---

## 9. Troubleshooting rapido

## Sintoma: "Already up to date" mas nada mudou

Causa comum: commit nao foi enviado ao GitHub.

Acao:

```bash
git push origin main
```

Depois repetir `git pull` no servidor.

## Sintoma: layout do PPT sai amassado

Causas comuns:

- assets antigos no servidor (`public/build` desatualizado);
- navegador com cache antigo.

Acao:

1. rodar `npm run build` local;
2. commitar e push dos assets;
3. `git pull` no servidor;
4. `php artisan optimize:clear`;
5. `Ctrl + F5` no browser.

## Sintoma: botao exporta mas arquivo nao baixa

Checklist:

- console do navegador (erros JS);
- permissao de download no browser;
- bloqueio por extensao de seguranca;
- validar se os slides renderizam normalmente antes do clique.

---

## 10. Padrao para novas implantacoes de exportacao no sistema

Ao replicar para outros modulos (RH, Financeiro, etc), seguir este padrao:

1. manter exportacao no frontend quando possivel;
2. definir dimensao fixa de slide (16:9) na captura;
3. aguardar render completo (fontes/graficos) antes da captura;
4. buildar e versionar assets de producao;
5. validar em ambiente alvo com dados reais;
6. documentar no mesmo formato deste manual.

Template minimo por novo modulo:

- objetivo;
- escopo dos slides;
- arquivos alterados;
- pre-requisitos;
- comandos de deploy;
- validacao funcional;
- troubleshooting.

---

## 11. Comandos oficiais (copiar e colar)

## Local

```bash
npm run build
git add .
git commit -m "chore: release exportacao ppt"
git push origin main
```

## Servidor Hostinger

```bash
ssh -p 65002 u482227589@82.198.236.39
cd ~/domains/feston.net.br/public_html/omegaadm
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```

---

## 12. Responsabilidade operacional

Antes de encerrar qualquer deploy de exportacao PPT, e obrigatorio confirmar:

- commit no GitHub;
- servidor no mesmo hash;
- exportacao baixando o arquivo corretamente;
- fidelidade visual dos slides em PowerPoint.

Sem esses quatro itens validados, o deploy nao deve ser considerado concluido.
