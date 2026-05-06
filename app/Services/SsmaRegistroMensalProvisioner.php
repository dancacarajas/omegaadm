<?php

namespace App\Services;

use App\Models\SsmaRegistroMensal;
use Carbon\Carbon;

class SsmaRegistroMensalProvisioner
{
    /** Cria rascunho vazio (etapas nulas) se ainda não existir registro para o 1º dia do mês. */
    public static function provision(Carbon $competenciaMes): bool
    {
        $d = $competenciaMes->copy()->startOfMonth()->toDateString();

        if (SsmaRegistroMensal::query()->whereDate('competencia', $d)->exists()) {
            return false;
        }

        SsmaRegistroMensal::create([
            'competencia' => $d,
            'titulo' => null,
            'responsavel' => null,
            'status' => 'rascunho',
            'etapas' => null,
        ]);

        return true;
    }
}
