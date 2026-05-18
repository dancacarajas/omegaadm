<?php

namespace App\Http\Middleware;

use App\Models\Colaborador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureColaboradorPonto
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = session('ponto_colaborador_id');

        if (! $id) {
            return redirect()->route('ponto.identificar');
        }

        $colaborador = Colaborador::query()
            ->whereKey($id)
            ->where('status', 'ativo')
            ->first();

        if (! $colaborador) {
            session()->forget('ponto_colaborador_id');

            return redirect()
                ->route('ponto.identificar')
                ->withErrors(['identificacao' => 'Sessão expirada. Identifique-se novamente.']);
        }

        $request->attributes->set('colaborador_ponto', $colaborador);

        return $next($request);
    }
}
