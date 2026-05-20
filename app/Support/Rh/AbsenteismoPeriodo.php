<?php

namespace App\Support\Rh;

use App\Models\FrequenciaRegistro;
use Carbon\Carbon;

/**
 * Absenteísmo gerencial por horas: ausências (justificadas + injustificadas) ÷ horas previstas × 100.
 */
class AbsenteismoPeriodo
{
    /**
     * @return array<string, mixed>
     */
    public function calcular(Carbon|string $inicio, Carbon|string $fim, ?int $colaboradorId = null): array
    {
        return $this->calcularComEscopo($inicio, $fim, $colaboradorId, null);
    }

    /**
     * @param  list<string>  $identificadoresContrato
     * @return array<string, mixed>
     */
    public function calcularParaContrato(Carbon|string $inicio, Carbon|string $fim, array $identificadoresContrato): array
    {
        return $this->calcularComEscopo($inicio, $fim, null, $identificadoresContrato);
    }

    /**
     * @param  list<string>|null  $identificadoresContrato
     * @return array<string, mixed>
     */
    private function calcularComEscopo(
        Carbon|string $inicio,
        Carbon|string $fim,
        ?int $colaboradorId,
        ?array $identificadoresContrato
    ): array {
        $inicioCarbon = $inicio instanceof Carbon ? $inicio->copy()->startOfDay() : Carbon::parse($inicio)->startOfDay();
        $fimCarbon = $fim instanceof Carbon ? $fim->copy()->startOfDay() : Carbon::parse($fim)->startOfDay();

        if ($fimCarbon->lt($inicioCarbon)) {
            [$inicioCarbon, $fimCarbon] = [$fimCarbon, $inicioCarbon];
        }

        $inicioStr = $inicioCarbon->toDateString();
        $fimStr = $fimCarbon->toDateString();
        $diasPeriodo = max(1, $inicioCarbon->diffInDays($fimCarbon, false) + 1);

        $query = AbsenteismoPeriodoProcessador::queryRegistros($inicioStr, $fimStr, $colaboradorId, $identificadoresContrato);

        $processado = AbsenteismoPeriodoProcessador::processar($query->cursor());

        $escopo = 'efetivo';
        if ($colaboradorId !== null) {
            $escopo = 'colaborador';
        } elseif ($identificadoresContrato !== null && $identificadoresContrato !== []) {
            $escopo = 'contrato';
        }

        return AbsenteismoPeriodoProcessador::totaisParaResumoAbsenteismo(
            $processado['totais'],
            $inicioStr,
            $fimStr,
            $diasPeriodo,
            $colaboradorId,
            $escopo
        );
    }
}
