<?php

/**
 * Hostinger: a URL no navegador inclui /public/ (ex.: /public/rh/beneficios/1),
 * mas as rotas Laravel são registradas como rh/beneficios/1.
 * Normaliza REQUEST_URI antes do bootstrap.
 */
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (str_starts_with($uri, '/public')) {
    $path = substr($uri, strlen('/public')) ?: '/';
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $_SERVER['REQUEST_URI'] = $path.($query !== '' ? '?'.$query : '');
}

require __DIR__.'/public/index.php';
