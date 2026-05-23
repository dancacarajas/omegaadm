<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Hostinger: document root na raiz do projeto e URL pública com /public/.
 */
final class PublicWebBase
{
    public static function shouldUse(Request $request): bool
    {
        if (filter_var(config('app.force_public_url'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if ($request->server->get('OMEGA_REQUEST_USES_PUBLIC_URL') === '1') {
            return true;
        }

        $path = parse_url($request->getRequestUri(), PHP_URL_PATH) ?? $request->getRequestUri();
        if (preg_match('#^/public(?:/|$)#', $path)) {
            return true;
        }

        $scriptName = (string) $request->server->get('SCRIPT_NAME', '');
        if (str_contains($scriptName, '/public/index.php')) {
            return true;
        }

        $referer = (string) $request->headers->get('referer', '');
        if (str_contains($referer, '/public/')) {
            return true;
        }

        return false;
    }

    public static function rootUrl(Request $request): ?string
    {
        if (! static::shouldUse($request)) {
            return null;
        }

        return rtrim($request->getSchemeAndHttpHost(), '/').'/public';
    }

    /**
     * URL absoluta de arquivo em public/ (e-mails, PDF, CLI — sem Request HTTP).
     */
    public static function assetUrl(string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if (! str_ends_with(strtolower($base), '/public')
            && filter_var(config('app.force_public_url'), FILTER_VALIDATE_BOOLEAN)) {
            $base .= '/public';
        }

        return $base.'/'.ltrim($path, '/');
    }

    /**
     * URL assinada acessível em produção (Hostinger: link com /public/, assinatura sem /public/).
     *
     * O bootstrap fix-public-request-uri.php remove /public do path visto pelo Laravel;
     * a assinatura deve ser calculada nesse path interno, e o link no e-mail mantém /public/.
     */
    public static function temporarySignedRouteWithPublicPrefix(
        string $routeName,
        CarbonInterface $expiration,
        array $parameters = [],
    ): string {
        $root = rtrim((string) config('app.url'), '/');
        if (str_ends_with(strtolower($root), '/public')) {
            $root = substr($root, 0, -7);
        }

        URL::forceRootUrl($root);

        $signed = URL::temporarySignedRoute($routeName, $expiration, $parameters);

        return self::inserirPublicNoPath($signed);
    }

    public static function inserirPublicNoPath(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        if (! str_starts_with($path, '/public')) {
            $path = '/public'.($path === '/' ? '' : $path);
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.$path.$query;
    }
}
