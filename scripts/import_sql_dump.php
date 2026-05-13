<?php

/**
 * Importa um dump .sql (phpMyAdmin / mysqldump) na base definida por DATABASE_URL ou DB_* no .env.
 * Uso: php scripts/import_sql_dump.php caminho/para/ficheiro.sql
 *
 * Requer PDO mysql com MYSQL_ATTR_MULTI_STATEMENTS. Ajuste max_allowed_packet no MySQL se o ficheiro for grande.
 */

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($argc < 2) {
    fwrite(STDERR, "Uso: php scripts/import_sql_dump.php <ficheiro.sql>\n");

    exit(1);
}

$path = $argv[1];
if (! is_readable($path)) {
    fwrite(STDERR, "Ficheiro não legível: {$path}\n");

    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false || $sql === '') {
    fwrite(STDERR, "Ficheiro vazio ou erro de leitura.\n");

    exit(1);
}

$config = config('database.connections.mysql');
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $config['host'] ?? '127.0.0.1',
    $config['port'] ?? '3306',
    $config['database'] ?? '',
    $config['charset'] ?? 'utf8mb4',
);

$pdo = new PDO(
    $dsn,
    (string) ($config['username'] ?? ''),
    (string) ($config['password'] ?? ''),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]
);

echo "A importar para: {$config['database']} …\n";

$pdo->exec('SET NAMES utf8mb4');
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec($sql);
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

echo "Importação concluída.\n";
