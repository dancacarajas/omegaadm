<?php

return [
    'base_url' => rtrim(env('SIGO_BASE_URL', 'http://sigo.omegaservice.com.br'), '/'),
    'login_path' => env('SIGO_LOGIN_PATH', '/SIGO/Login'),
    'novo_pm_path' => env('SIGO_PM_PATH', '/SIGO/PM/NovoPM'),
    'python' => env('SIGO_PYTHON') ?: (
        PHP_OS_FAMILY === 'Windows'
            && is_file($winPython = (getenv('LOCALAPPDATA') ?: '').'\Programs\Python\Python313\python.exe')
            ? $winPython
            : 'python'
    ),
    'script' => base_path('scripts/extrair_insumos_sigo.py'),
    'check_script' => base_path('scripts/sigo_check_deps.py'),
    'timeout_seconds' => (int) env('SIGO_EXTRACAO_TIMEOUT', 3600),
    'headless' => env('SIGO_HEADLESS', '1') !== '0',
];
