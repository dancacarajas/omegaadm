<?php

return [
    'chrome_path' => env('PGU_EXPORT_CHROME_PATH') ?: env('PUPPETEER_EXECUTABLE_PATH'),
    'capture_base_url' => env('PGU_CAPTURE_BASE_URL'),
    'capture_host' => env('PGU_CAPTURE_HOST', '127.0.0.1'),
    'capture_aux_port' => env('PGU_CAPTURE_AUX_PORT'),
    'capture_aux_port_start' => (int) env('PGU_CAPTURE_AUX_PORT_START', 56080),
    'capture_aux_port_end' => (int) env('PGU_CAPTURE_AUX_PORT_END', 56150),
    'timeout' => (int) env('PGU_EXPORT_TIMEOUT', 180),
    'scale' => (int) env('PGU_EXPORT_SCALE', 2),
    'keep_files' => filter_var(env('PGU_EXPORT_KEEP_FILES', false), FILTER_VALIDATE_BOOLEAN),

    'viewport' => [
        'width' => 1366,
        'height' => 768,
    ],

    'slides' => [
        ['key' => 'cover', 'path' => '/pgu-capture/cover', 'filename' => '00-cover.png'],
        ['key' => 'slide-1', 'path' => '/pgu-capture/slide-1', 'filename' => '01-visao-geral.png'],
        ['key' => 'slide-2', 'path' => '/pgu-capture/slide-2', 'filename' => '02-funcoes-100.png'],
        ['key' => 'slide-3', 'path' => '/pgu-capture/slide-3', 'filename' => '03-gargalos.png'],
        ['key' => 'slide-4', 'path' => '/pgu-capture/slide-4', 'filename' => '04-pareto.png'],
        ['key' => 'slide-5', 'path' => '/pgu-capture/slide-5', 'filename' => '05-plano-acao.png'],
    ],
];

