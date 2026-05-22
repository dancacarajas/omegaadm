<?php

namespace App\Support\Rh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Logs temporários para 404 de movimentação só com sessão (ativar com ?debug_movimentacao=1).
 */
final class MovimentacaoDebugTrace
{
    public static function enabled(?Request $request = null): bool
    {
        $request ??= request();

        return $request !== null && $request->boolean('debug_movimentacao');
    }

    public static function log(string $event, array $context = [], ?Request $request = null): void
    {
        $request ??= request();

        if (! static::enabled($request)) {
            return;
        }

        Log::info($event, array_merge([
            'path' => $request->path(),
            'uri' => $request->getRequestUri(),
            'route' => $request->route()?->getName(),
            'user_id' => $request->user()?->id,
        ], $context));
    }
}
