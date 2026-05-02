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
