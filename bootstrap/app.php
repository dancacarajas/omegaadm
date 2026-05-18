<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsurePerfilPermissaoRota;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'installed' => EnsureInstalled::class,
            'perfil.rota' => EnsurePerfilPermissaoRota::class,
            'ponto.colaborador' => \App\Http\Middleware\EnsureColaboradorPonto::class,
            'tst.colaborador' => \App\Http\Middleware\EnsureColaboradorTst::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
