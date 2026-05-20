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

    $_SERVER['SCRIPT_NAME'] = '/public/index.php';
    $_SERVER['PHP_SELF'] = '/public/index.php';

    $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $viaPublicIndex = str_ends_with($scriptFile, '/public/index.php');

    if (! $viaPublicIndex) {
        $path = substr($uri, strlen('/public')) ?: '/';
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $_SERVER['REQUEST_URI'] = $path.($query !== '' ? '?'.$query : '');
    }
};
