<?php

namespace App\Support;

use Illuminate\Http\Request;

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

        if (str_starts_with($request->getRequestUri(), '/public')) {
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
}
