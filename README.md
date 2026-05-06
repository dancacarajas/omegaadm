# Omega286

Base inicial do sistema em Laravel 12 com PHP 8.2+, MySQL 8, Blade, Tailwind CSS e Vite.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL 8.4
- Blade
- Tailwind CSS 4
- Vite 7

## Ambiente local

Suba o banco de dados:

```bash
docker compose up -d
```

Instale as dependencias e prepare a aplicacao:

```bash
composer install
cmd /c npm install
php artisan key:generate
php artisan migrate
cmd /c npm run build
```

Rode a aplicacao e o Vite em terminais separados:

```bash
php artisan serve
cmd /c npm run dev
```

Aplicacao: http://127.0.0.1:8000

Adminer: http://127.0.0.1:8081

Credenciais do banco:

- Servidor no Adminer: `mysql`
- Host local para Laravel: `127.0.0.1`
- Banco: `omega286`
- Usuario: `omega286`
- Senha: `secret`
- Root: `root`

## Comandos uteis

```bash
php artisan test
cmd /c npm run build
docker compose down
```

## Exportacao PPT do PGU (troubleshooting)

Documentacao completa da implementacao e implantacao:

- `EXPORTACAO_PPT_PGU.md`

Rota de exportacao:

- `GET /exportar-pgu-powerpoint`
- Controller: `App\Http\Controllers\PguDashboardController@exportarPowerPoint`

### Sintoma observado

Em ambiente local, a exportacao podia falhar com erro 500:

- `Maximum execution time of 60 seconds exceeded`

### Causa

A exportacao gera o arquivo PPT a partir de capturas de tela de varios slides (cover + 5 slides) usando Chrome headless via `Browsershot`.
Como as capturas sao sequenciais na mesma requisicao HTTP, alguns cenarios ultrapassam o timeout padrao de 60 segundos do PHP.

### Ajuste aplicado

No inicio do metodo `exportarPowerPoint`, foi aumentado o tempo maximo de execucao apenas para essa acao:

- `set_time_limit(300)`
- `ini_set('max_execution_time', '300')`

Objetivo: dar tempo suficiente para finalizar as capturas e montar o `.pptx` sem interromper a requisicao.

### Validacao rapida

1. Acesse a tela de apresentacao do PGU.
2. Execute a exportacao para PPT.
3. Confirme download do arquivo `pgu-visao-executiva-<contrato>-<competencia>.pptx`.
4. Se falhar novamente, valide:
   - se o Chrome/Chromium esta instalado e acessivel;
   - se nao ha bloqueio de CPU/memoria no host;
   - se as rotas `/pgu-cover`, `/pgu-slide`, `/pgu-slide-2`, `/pgu-slide-3`, `/pgu-slide-4` e `/pgu-slide-5` respondem normalmente.

### Nota para manutencao

Se a exportacao ficar pesada com o crescimento dos dados, considerar migrar para job em fila (processamento assincrono), evitando que a requisicao web fique aberta por muito tempo.
