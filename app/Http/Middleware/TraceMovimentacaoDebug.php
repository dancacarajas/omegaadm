<?php

namespace App\Http\Middleware;

use App\Support\Rh\MovimentacaoDebugTrace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rastreia requisições com ?debug_movimentacao=1 (antes/depois do pipeline).
 */
class TraceMovimentacaoDebug
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! MovimentacaoDebugTrace::enabled($request)) {
            return $next($request);
        }

        MovimentacaoDebugTrace::log('MOVIMENTACAO PIPELINE INICIO', [
            'method' => $request->method(),
            'authenticated' => $request->user() !== null,
            'route_params' => $request->route()?->parameters() ?? [],
        ], $request);

        $response = $next($request);

        MovimentacaoDebugTrace::log('MOVIMENTACAO PIPELINE FIM', [
            'status' => $response->getStatusCode(),
        ], $request);

        return $response;
    }
}
