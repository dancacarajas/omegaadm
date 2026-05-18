<?php

namespace App\Support;

use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscalaDia;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class FrequenciaCalculo
{
    /** Jornada líquida esperada (minutos), padrão 8h — ajuste com RH_FREQUENCIA_JORNADA_MINUTOS no .env */
    public static function jornadaMinutosEsperados(): int
    {
        return max(1, (int) env('RH_FREQUENCIA_JORNADA_MINUTOS', 480));
    }

    /**
     * Jornada esperada (minutos) para o registro: escala do colaborador no dia, senão env padrão.
     */
    public static function jornadaMinutosParaRegistro(FrequenciaRegistro $registro): int
    {
        $colaborador = $registro->colaborador;
        if (! $colaborador) {
            return self::jornadaMinutosEsperados();
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if (! $diaEscala) {
            return self::jornadaMinutosEsperados();
        }

        $ymd = $registro->data instanceof CarbonInterface
            ? $registro->data->format('Y-m-d')
            : Carbon::parse($registro->data)->format('Y-m-d');

        return self::minutosPrevistosEscalaDia($ymd, $diaEscala);
    }

    public static function minutosTrabalhados(FrequenciaRegistro $registro): int
    {
        $data = $registro->data;
        if (! $data instanceof CarbonInterface) {
            return 0;
        }

        $dia = $data->format('Y-m-d');

        return self::segmentoMinutos($dia, $registro->entrada_1, $registro->saida_1)
            + self::segmentoMinutos($dia, $registro->entrada_2, $registro->saida_2);
    }

    /**
     * @return array{trabalhadas: int, trabalhadas_fmt: string, falta: int|null, falta_fmt: string, extras: int, extras_fmt: string, jornada_esperada_minutos: int, jornada_esperada_fmt: string}
     */
    /**
     * Resumo usando horários da escala nos campos ainda vazios (útil antes de gravar ou quando o registro é parcial).
     */
    public static function resumoComFallbackEscala(FrequenciaRegistro $registro): array
    {
        $clone = clone $registro;
        $dia = $registro->colaborador?->horarioEscalaDiaNaData($registro->data);
        if ($dia) {
            foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
                if (self::horarioArmazenadoVazio($clone->getAttribute($campo))) {
                    $prev = $dia->getAttribute($campo);
                    if (! self::horarioArmazenadoVazio($prev)) {
                        $clone->setAttribute($campo, $prev);
                    }
                }
            }
        }

        return self::resumo($clone);
    }

    public static function horarioArmazenadoVazio(mixed $valor): bool
    {
        if ($valor === null) {
            return true;
        }

        $s = trim((string) $valor);

        return $s === '';
    }

    public static function resumo(FrequenciaRegistro $registro): array
    {
        $jornada = self::jornadaMinutosParaRegistro($registro);
        $jornadaFmt = $jornada === 0 ? 'Folga (escala)' : self::formatarMinutos($jornada);
        $trabalhadas = self::minutosTrabalhados($registro);
        $status = $registro->status ?? 'falta';

        if ($status === 'justificado') {
            $extras = max(0, $trabalhadas - $jornada);

            return [
                'trabalhadas' => $trabalhadas,
                'trabalhadas_fmt' => self::formatarMinutos($trabalhadas),
                'falta' => null,
                'falta_fmt' => '—',
                'extras' => $extras,
                'extras_fmt' => self::formatarMinutos($extras),
                'jornada_esperada_minutos' => $jornada,
                'jornada_esperada_fmt' => $jornadaFmt,
            ];
        }

        $falta = max(0, $jornada - $trabalhadas);
        $extras = max(0, $trabalhadas - $jornada);

        return [
            'trabalhadas' => $trabalhadas,
            'trabalhadas_fmt' => self::formatarMinutos($trabalhadas),
            'falta' => $falta,
            'falta_fmt' => $falta > 0 ? self::formatarMinutos($falta) : '0h',
            'extras' => $extras,
            'extras_fmt' => $extras > 0 ? self::formatarMinutos($extras) : '0h',
            'jornada_esperada_minutos' => $jornada,
            'jornada_esperada_fmt' => $jornadaFmt,
        ];
    }

    public static function formatarMinutos(int $minutos): string
    {
        if ($minutos <= 0) {
            return '0h';
        }

        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return sprintf('%dh %02dmin', $h, $m);
    }

    /** Formato relógio para cartão de ponto (ex.: 09:00). */
    public static function formatarMinutosRelogio(int $minutos): string
    {
        if ($minutos <= 0) {
            return '00:00';
        }

        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    private static function minutosPrevistosEscalaDia(string $diaYmd, HorarioEscalaDia $dia): int
    {
        return self::segmentoMinutos($diaYmd, $dia->entrada_1, $dia->saida_1)
            + self::segmentoMinutos($diaYmd, $dia->entrada_2, $dia->saida_2);
    }

    private static function segmentoMinutos(string $dia, mixed $inicio, mixed $fim): int
    {
        if ($inicio === null || $inicio === '' || $fim === null || $fim === '') {
            return 0;
        }

        $hi = self::normalizarHora($inicio);
        $hf = self::normalizarHora($fim);

        if ($hi === null || $hf === null) {
            return 0;
        }

        try {
            $a = Carbon::parse("{$dia} {$hi}");
            $b = Carbon::parse("{$dia} {$hf}");
        } catch (\Throwable) {
            return 0;
        }

        if ($b->lte($a)) {
            return 0;
        }

        return (int) $a->diffInMinutes($b);
    }

    public static function normalizarHorarioBanco(mixed $valor): ?string
    {
        return self::normalizarHora($valor);
    }

    private static function normalizarHora(mixed $valor): ?string
    {
        if ($valor instanceof CarbonInterface) {
            return $valor->format('H:i:s');
        }

        $s = trim((string) $valor);
        if ($s === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $s, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
        }

        return null;
    }
}
