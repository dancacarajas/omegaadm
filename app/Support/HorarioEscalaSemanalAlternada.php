<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Revezamento de motoristas: semana ímpar seg/qua/sex vs ter/qui; semana par inverte.
 * Sábado e domingo sempre folga. Grupo 0 (fase 0) e grupo 1 (fase 1) em oposição.
 */
final class HorarioEscalaSemanalAlternada
{
    public const TEMPLATE_DIA_SEMANA = 1;

    public static function segundaInicioCiclo(HorarioEscala $escala): ?Carbon
    {
        if (! $escala->data_inicio_ciclo) {
            return null;
        }

        $inicio = Carbon::parse($escala->data_inicio_ciclo)->startOfDay();

        return $inicio->isoWeekday() === Carbon::MONDAY
            ? $inicio
            : $inicio->copy()->startOfWeek(Carbon::MONDAY);
    }

    public static function trabalhaNoDia(HorarioEscala $escala, CarbonInterface $data, int $grupo = 0): bool
    {
        $anchor = self::segundaInicioCiclo($escala);
        if ($anchor === null) {
            return false;
        }

        $alvo = Carbon::parse($data)->startOfDay();
        $diaSemana = (int) $alvo->isoWeekday();

        if ($diaSemana >= 6) {
            return false;
        }

        $diasDesdeInicio = (int) $anchor->diffInDays($alvo, false);
        $indiceSemana = intdiv($diasDesdeInicio, 7);
        $semanaMwf = ($indiceSemana % 2) === 0;

        if (($grupo % 2) === 1) {
            $semanaMwf = ! $semanaMwf;
        }

        if ($semanaMwf) {
            return in_array($diaSemana, [1, 3, 5], true);
        }

        return in_array($diaSemana, [2, 4], true);
    }

    public static function templateDia(HorarioEscala $escala): ?HorarioEscalaDia
    {
        return HorarioEscalaDia::query()
            ->where('horario_escala_id', $escala->id)
            ->where('dia_semana', self::TEMPLATE_DIA_SEMANA)
            ->first();
    }

    public static function diaNaData(Colaborador $colaborador, CarbonInterface|string $data): ?HorarioEscalaDia
    {
        $escala = $colaborador->horarioEscala;
        if ($escala === null || ! $escala->isRotativaSemanal()) {
            return null;
        }

        $carbon = $data instanceof CarbonInterface
            ? Carbon::parse($data)
            : Carbon::parse((string) $data);

        if (! self::trabalhaNoDia($escala, $carbon, (int) ($colaborador->horario_escala_ciclo_offset ?? 0))) {
            return null;
        }

        return self::templateDia($escala);
    }

    /**
     * @return array<int, string>
     */
    public static function rotuloDiasGrupo(int $grupo): array
    {
        $mwf = ($grupo % 2) === 0
            ? 'Sem. ímpar: seg, qua, sex · Sem. par: ter, qui'
            : 'Sem. ímpar: ter, qui · Sem. par: seg, qua, sex';

        return [
            0 => $mwf,
        ];
    }
}
