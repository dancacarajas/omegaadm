<?php

namespace App\Support\Rh;

use App\Models\FrequenciaRegistro;
use Carbon\Carbon;

/**
 * Taxa de absenteísmo no período: somente faltas injustificadas (status falta)
 * sobre dias com jornada registrada (presente, falta ou incompleto).
 * Folgas e justificativas (abono, atestado, mobilização etc.) não entram no cálculo.
 */
class AbsenteismoPeriodo
{
    /** @var list<string> */
    private const STATUS_BASE = ['presente', 'falta', 'incompleto'];

    /**
     * @return array{
     *     inicio: string,
     *     fim: string,
     *     dias: int,
     *     ausencias: int,
     *     base: int,
     *     taxa: float,
     *     colaborador_id: int|null,
     *     escopo: string
     * }
     */
    public function calcular(Carbon|string $inicio, Carbon|string $fim, ?int $colaboradorId = null): array
    {
        $inicioCarbon = $inicio instanceof Carbon ? $inicio->copy()->startOfDay() : Carbon::parse($inicio)->startOfDay();
        $fimCarbon = $fim instanceof Carbon ? $fim->copy()->startOfDay() : Carbon::parse($fim)->startOfDay();

        if ($fimCarbon->lt($inicioCarbon)) {
            [$inicioCarbon, $fimCarbon] = [$fimCarbon, $inicioCarbon];
        }

        $inicioStr = $inicioCarbon->toDateString();
        $fimStr = $fimCarbon->toDateString();
        $diasPeriodo = max(1, $inicioCarbon->diffInDays($fimCarbon, false) + 1);

        $baseQuery = FrequenciaRegistro::query()
            ->whereDate('data', '>=', $inicioStr)
            ->whereDate('data', '<=', $fimStr)
            ->whereHas('colaborador', function ($q) use ($colaboradorId) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
                if ($colaboradorId !== null) {
                    $q->where('id', $colaboradorId);
                }
            });

        $ausencias = (clone $baseQuery)->where('status', 'falta')->count();
        $base = (clone $baseQuery)->whereIn('status', self::STATUS_BASE)->count();

        return [
            'inicio' => $inicioStr,
            'fim' => $fimStr,
            'dias' => $diasPeriodo,
            'ausencias' => $ausencias,
            'base' => $base,
            'taxa' => $base > 0 ? round(($ausencias / $base) * 100, 1) : 0.0,
            'colaborador_id' => $colaboradorId,
            'escopo' => $colaboradorId !== null ? 'colaborador' : 'efetivo',
        ];
    }
}
