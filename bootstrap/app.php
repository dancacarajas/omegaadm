<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsurePerfilPermissaoRota;
use App\Http\Middleware\ForceRequestRootUrl;
use App\Http\Middleware\TraceMovimentacaoDebug;
use App\Support\Rh\MovimentacaoDebugTrace;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $middleware->web(prepend: [
            ForceRequestRootUrl::class,
        ]);
        $middleware->alias([
            'installed' => EnsureInstalled::class,
            'perfil.rota' => EnsurePerfilPermissaoRota::class,
            'ponto.colaborador' => \App\Http\Middleware\EnsureColaboradorPonto::class,
            'tst.colaborador' => \App\Http\Middleware\EnsureColaboradorTst::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request): ?Response {
            if (! MovimentacaoDebugTrace::enabled($request)) {
                return null;
            }

            MovimentacaoDebugTrace::log('MOVIMENTACAO HTTP 404', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ], $request);

            return null;
        });

        $exceptions->report(function (\Throwable $e): void {
            $request = request();
            if ($request === null || ! MovimentacaoDebugTrace::enabled($request)) {
                return;
            }

            if (! $e instanceof HttpExceptionInterface || $e->getStatusCode() !== 404) {
                return;
            }

            MovimentacaoDebugTrace::log('MOVIMENTACAO EXCECAO 404', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ], $request);
        });
    })->create();
