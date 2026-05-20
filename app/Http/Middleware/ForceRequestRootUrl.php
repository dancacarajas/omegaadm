<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que links e formulários usem a mesma base da requisição (ex.: /public no Hostinger).
 */
class ForceRequestRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->runningInConsole()) {
            $host = $request->getSchemeAndHttpHost();
            // Produção Hostinger: links devem manter /public na URL pública
            $root = str_starts_with($request->getRequestUri(), '/public')
                ? $host.'/public'
                : rtrim($host.$request->getBaseUrl(), '/');

            if ($root !== '') {
                URL::forceRootUrl($root);
            }
        }

        return $next($request);
    }
}
