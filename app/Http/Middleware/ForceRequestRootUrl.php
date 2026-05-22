<?php

namespace App\Http\Middleware;

use App\Support\PublicWebBase;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que links, formulários e @vite usem base https://dominio/public no Hostinger.
 */
class ForceRequestRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $root = PublicWebBase::rootUrl($request);

        if ($root !== null) {
            URL::forceRootUrl($root);

            Vite::createAssetPathsUsing(static function (string $path, ?bool $secure = null): string {
                return asset($path);
            });
        }

        return $next($request);
    }
}
