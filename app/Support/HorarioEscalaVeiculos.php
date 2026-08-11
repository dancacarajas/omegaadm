<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class HorarioEscalaVeiculos
{
    public const TEMPLATE_DIA_SEMANA = 1;

    public const POSICOES = 4;

    private const PADRAO = [
        ['micro' => 0, 'caminhonete' => 1],
        ['micro' => 2, 'caminhonete' => 3],
        ['micro' => 1, 'caminhonete' => 0],
        ['micro' => 3, 'caminhonete' => 2],
    ];

    public static function indiceDiaUtil(HorarioEscala $escala, CarbonInterface|string $data): ?int
    {
        if (! $escala->data_inicio_ciclo) {
            return null;
        }

        $inicio = Carbon::parse($escala->data_inicio_ciclo)->startOfDay();
        $alvo = Carbon::parse($data)->startOfDay();

        if ((int) $alvo->isoWeekday() >= 6) {
            return null;
        }

        $passo = $inicio->lte($alvo) ? 1 : -1;
        $cursor = $inicio->copy();
        $uteis = 0;

        while (! $cursor->isSameDay($alvo)) {
            $cursor->addDays($passo);
            if ((int) $cursor->isoWeekday() < 6) {
                $uteis += $passo;
            }
        }

        return $uteis;
    }

    /**
     * @return array{micro:int,caminhonete:int}|null
     */
    public static function posicaoNaData(HorarioEscala $escala, CarbonInterface|string $data): ?array
    {
        $indice = self::indiceDiaUtil($escala, $data);
        if ($indice === null) {
            return null;
        }

        $pos = (($indice % self::POSICOES) + self::POSICOES) % self::POSICOES;

        return self::PADRAO[$pos];
    }

    public static function veiculoNaData(HorarioEscala $escala, CarbonInterface|string $data, int $offset): string
    {
        $padrao = self::posicaoNaData($escala, $data);
        if ($padrao === null) {
            return 'folga';
        }

        $offset = (($offset % self::POSICOES) + self::POSICOES) % self::POSICOES;

        return match ($offset) {
            $padrao['micro'] => 'micro_onibus',
            $padrao['caminhonete'] => 'caminhonete',
            default => 'folga',
        };
    }

    public static function trabalhaNoDia(HorarioEscala $escala, CarbonInterface|string $data, int $offset): bool
    {
        return self::veiculoNaData($escala, $data, $offset) !== 'folga';
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
        if ($escala === null || ! $escala->isRotativaVeiculos()) {
            return null;
        }

        if (! self::trabalhaNoDia($escala, $data, (int) ($colaborador->horario_escala_ciclo_offset ?? 0))) {
            return null;
        }

        return self::templateDia($escala);
    }
}
