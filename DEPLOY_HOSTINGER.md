# Deploy OmegaADM na Hostinger

## Antes de cada deploy (local)

Sempre que alterar Blade/CSS/JS, gere os assets de producao e envie para o GitHub (o painel Hostinger so puxa o repositorio; sem isso o site fica com CSS antigo e o layout quebra):

```bash
npm run build
git add public/build resources/css
git commit -m "chore: rebuild vite"
git push origin main
```

## Repositorio

- Repository: `https://github.com/dancacarajas/omegaadm.git`
- Branch: `main`
- Diretorio de producao: `/home/u482227589/domains/feston.net.br/public_html/omegaadm`
- URL esperada: `https://omegaadm.feston.net.br`

## Deploy pelo painel Git da Hostinger

1. Acesse o painel da Hostinger em **Git**.
2. Crie o deploy com:
   - Repository: `https://github.com/dancacarajas/omegaadm.git`
   - Branch: `main`
   - Directory: `omegaadm` quando o contexto for `public_html`.
3. A pasta de destino precisa estar vazia. Se existir apenas `default.php`, mova ou exclua esse arquivo antes do deploy.
4. Depois do clone, rode no terminal SSH dentro da pasta do projeto:

```bash
composer install --no-dev --optimize-autoloader
php artisan storage:link
```

### O log de deploy mostra Composer mas o site nao muda?

Em muitos relatorios da Hostinger o log termina em **"Installing dependencies"** / **"Deployment end"** e **nao aparece** `git pull` nem `git fetch`. Isso pode significar que o servidor **nao atualizou os ficheiros PHP/Blade** do GitHub — ficou o codigo antigo em disco.

**Confirme no SSH** (na pasta do projeto, ex.: `public_html/omegaadm`):

```bash
git fetch origin
git rev-parse HEAD
git rev-parse origin/main
```

Os dois hashes devem coincidir com o ultimo commit em `https://github.com/dancacarajas/omegaadm/commits/main/`. Se `HEAD` for antigo:

```bash
git pull origin main
php artisan view:clear
php artisan optimize:clear
```

No browser, na pagina **Editar vaga** → **Ver codigo-fonte** (Ctrl+U) e procure por `omegaadm-rh-finish-guard:v2`. Se **nao existir**, o Blade em producao ainda e antigo (deploy nao puxou o repositorio).

**Dica:** no painel Git da Hostinger, procure uma acao explicita de **Pull** / **Update from Git** alem do passo que so corre Composer.

Se a hospedagem bloquear `storage:link`, o instalador continua funcionando, mas uploads publicos podem exigir ajuste manual no painel.

## Primeiro acesso

Abra `https://omegaadm.feston.net.br/install`.

O instalador vai pedir:

- Nome do sistema
- URL de producao
- Host, porta, banco, usuario e senha do MySQL
- Nome, e-mail e senha do administrador master

Banco criado no painel:

- Database: `u482227589_omegaadm`
- User: `u482227589_omegaadm`

Ao confirmar, o sistema:

- testa a conexao com o banco;
- cria o arquivo `.env`;
- executa as migrations;
- cria o usuario administrador master;
- marca `APP_INSTALLED=true`.

Depois disso, o acesso normal passa a ser pela tela de login.
