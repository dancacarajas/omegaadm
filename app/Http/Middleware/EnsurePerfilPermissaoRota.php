<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePerfilPermissaoRota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $user->loadMissing('perfil');

        $routeName = $request->route()?->getName();
        $modulo = $this->moduloParaNomeRota($routeName);

        if ($modulo === null) {
            return $next($request);
        }

        if ($user->temQualquerPermissaoNoModulo($modulo)) {
            return $next($request);
        }

        if ($routeName === 'dashboard') {
            $destino = $user->urlInicialAposLogin();
            if ($destino !== null && $destino !== route('dashboard')) {
                return redirect()->to($destino);
            }
        }

        abort(403, 'Seu perfil não tem permissão para acessar este módulo.');
    }

    private function moduloParaNomeRota(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }

        if ($name === 'dashboard') {
            return 'dashboard';
        }

        if (str_starts_with($name, 'rh.')) {
            return 'rh';
        }

        if (str_starts_with($name, 'usuarios.') || str_starts_with($name, 'perfis.')) {
            return 'acessos';
        }

        if (str_starts_with($name, 'veiculos.')) {
            return 'veiculos';
        }

        if (str_starts_with($name, 'sesmt.')) {
            return 'sesmt';
        }

        if (str_starts_with($name, 'contratos.')) {
            return 'contratos';
        }

        if (str_starts_with($name, 'patrimonial.')) {
            return 'patrimonial';
        }

        if (str_starts_with($name, 'medicao.')) {
            return 'medicao';
        }

        if (str_starts_with($name, 'rdo.')) {
            return 'rdo';
        }

        return null;
    }
}
