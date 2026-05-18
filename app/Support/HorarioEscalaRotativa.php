<?php

namespace App\Support;

use App\Models\HorarioEscala;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Posição no ciclo rotativo (0-based) a partir da data âncora e deslocamento do colaborador.
 */
final class HorarioEscalaRotativa
{
    public static function indiceDiaCiclo(
        HorarioEscala $escala,
        CarbonInterface $data,
        int $offsetColaborador = 0
    ): ?int {
        $ciclo = (int) ($escala->ciclo_dias ?? 0);
        if ($ciclo < 1 || ! $escala->data_inicio_ciclo) {
            return null;
        }

        $inicio = Carbon::parse($escala->data_inicio_ciclo)->startOfDay();
        $alvo = Carbon::parse($data)->startOfDay();
        $diff = (int) $inicio->diffInDays($alvo, false);
        $pos = (($diff % $ciclo) + $ciclo) % $ciclo;
        $comOffset = ($pos + max(0, $offsetColaborador)) % $ciclo;

        return $comOffset + 1;
    }
}
