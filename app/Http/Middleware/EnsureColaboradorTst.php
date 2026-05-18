<?php

namespace App\Http\Middleware;

use App\Models\Colaborador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureColaboradorTst
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = session('tst_colaborador_id');

        if (! $id) {
            return redirect()->route('tst-campo.identificar');
        }

        $colaborador = Colaborador::query()
            ->whereKey($id)
            ->where('status', 'ativo')
            ->first();

        if (! $colaborador) {
            session()->forget('tst_colaborador_id');

            return redirect()
                ->route('tst-campo.identificar')
                ->withErrors(['identificacao' => 'Sessão expirada. Identifique-se novamente.']);
        }

        $request->attributes->set('colaborador_tst', $colaborador);

        return $next($request);
    }
}
