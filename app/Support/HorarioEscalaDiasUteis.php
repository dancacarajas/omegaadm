<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Rodízio diário em dias úteis (seg–sex): uma posição por dia útil.
 * Sábado e domingo são folga e não avançam o ciclo. Feriados não deslocam a sequência.
 */
final class HorarioEscalaDiasUteis
{
    public const TEMPLATE_DIA_SEMANA = 1;

    public const POSICOES_MIN = 2;

    public const POSICOES_MAX = 14;

    public const POSICOES_PADRAO = 4;

    public static function quantidadePosicoes(HorarioEscala $escala): int
    {
        $qtd = (int) ($escala->ciclo_dias ?? self::POSICOES_PADRAO);

        return max(self::POSICOES_MIN, min(self::POSICOES_MAX, $qtd));
    }

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

    /**
     * Índice do dia útil a partir da segunda-feira inicial (0 = primeira segunda).
     * Retorna null em sábado/domingo ou sem âncora.
     */
    public static function indiceDiaUtil(
        HorarioEscala $escala,
        CarbonInterface|string $data
    ): ?int {
        $anchor = self::segundaInicioCiclo($escala);
        if ($anchor === null) {
            return null;
        }

        $alvo = Carbon::parse($data)->startOfDay();
        $diaSemana = (int) $alvo->isoWeekday();

        if ($diaSemana >= 6) {
            return null;
        }

        $diasDesdeInicio = (int) $anchor->diffInDays($alvo, false);
        $semanasCompletas = (int) floor($diasDesdeInicio / 7);

        return $semanasCompletas * 5 + ($diaSemana - 1);
    }

    public static function posicaoNaData(
        HorarioEscala $escala,
        CarbonInterface|string $data
    ): ?int {
        $indice = self::indiceDiaUtil($escala, $data);
        if ($indice === null) {
            return null;
        }

        $quantidade = self::quantidadePosicoes($escala);

        return (($indice % $quantidade) + $quantidade) % $quantidade;
    }

    public static function trabalhaNoDia(
        HorarioEscala $escala,
        CarbonInterface|string $data,
        int $posicao
    ): bool {
        $responsavel = self::posicaoNaData($escala, $data);
        if ($responsavel === null) {
            return false;
        }

        $quantidade = self::quantidadePosicoes($escala);
        $posicaoNorm = (($posicao % $quantidade) + $quantidade) % $quantidade;

        return $responsavel === $posicaoNorm;
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
        if ($escala === null || ! $escala->isRotativaDiasUteis()) {
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
}
