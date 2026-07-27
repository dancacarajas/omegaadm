<?php

namespace App\Http\Middleware;

use App\Models\Colaborador;
use App\Support\PresencaObraService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureColaboradorPresencaObra
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = session('presenca_obra_colaborador_id');

        if (! $id) {
            return redirect()->route('presenca-obra.identificar');
        }

        $colaborador = Colaborador::query()
            ->whereKey($id)
            ->where('status', 'ativo')
            ->first();

        if (! $colaborador || ! app(PresencaObraService::class)->podeConfirmar($colaborador)) {
            session()->forget('presenca_obra_colaborador_id');

            return redirect()
                ->route('presenca-obra.identificar')
                ->withErrors([
                    'identificacao' => 'Sessão expirada ou acesso não liberado. Identifique-se novamente.',
                ]);
        }

        $request->attributes->set('colaborador_presenca_obra', $colaborador);

        return $next($request);
    }
}
