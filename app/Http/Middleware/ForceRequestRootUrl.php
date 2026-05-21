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
        $host = $request->getSchemeAndHttpHost();
        $scriptName = (string) $request->server->get('SCRIPT_NAME', '');

        // Hostinger: fix-public-request-uri normaliza o path do router (rh/...) mas CSS/JS
        // (@vite, asset()) precisam da base https://dominio/public
        $usaBasePublic = filter_var(config('app.force_public_url'), FILTER_VALIDATE_BOOLEAN)
            || $request->server->get('OMEGA_REQUEST_USES_PUBLIC_URL') === '1'
            || str_starts_with($request->getRequestUri(), '/public')
            || str_contains($scriptName, '/public/index.php');

        $root = $usaBasePublic
            ? $host.'/public'
            : rtrim($host.$request->getBaseUrl(), '/');

        if ($root !== '') {
            URL::forceRootUrl($root);
        }

        return $next($request);
    }
}
