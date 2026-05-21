<?php

/**
 * Hostinger: document root na raiz do projeto e URL pública com /public/.
 * Garante path interno rh/... (router) e REQUEST_URI com /public/... (URL pública).
 */
return function (): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (! str_starts_with($uri, '/public')) {
        return;
    }

    $_SERVER['OMEGA_REQUEST_USES_PUBLIC_URL'] = '1';
    $_SERVER['SCRIPT_NAME'] = '/public/index.php';
    $_SERVER['PHP_SELF'] = '/public/index.php';

    // Sempre normaliza: router precisa de rh/... e não public/rh/... (Hostinger → public/index.php).
    $path = substr(parse_url($uri, PHP_URL_PATH) ?? $uri, strlen('/public')) ?: '/';
    $query = parse_url($uri, PHP_URL_QUERY);
    $_SERVER['REQUEST_URI'] = $path.($query !== null && $query !== '' ? '?'.$query : '');
};
