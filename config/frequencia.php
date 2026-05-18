<?php

return [
    'afd' => [
        'cnpj' => env('AFD_EMPRESA_CNPJ', ''),
        'razao_social' => env('AFD_EMPRESA_RAZAO_SOCIAL', env('APP_NAME', 'Omega')),
    ],
    'empresa' => [
        'razao_social' => env('AFD_EMPRESA_RAZAO_SOCIAL', 'OMEGA SERVICOS E MONTAGENS INDUSTRIAIS LTDA'),
        'nome_fantasia' => env('EMPRESA_NOME_FANTASIA', 'OMEGA SERVICE'),
    ],
];
