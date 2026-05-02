# Deploy OmegaADM na Hostinger

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
