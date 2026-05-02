<?php

namespace App\Http\Middleware;

use App\Support\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Installation::complete()) {
            return redirect()->route('install.index');
        }

        return $next($request);
    }
}
