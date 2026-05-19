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

    /** Diferença até este limite (min) entre jornada e batidas não conta como falta na apuração. */
    public static function toleranciaMinutosFalta(): int
    {
        return max(0, (int) env('RH_FREQUENCIA_TOLERANCIA_FALTA_MINUTOS', 10));
    }

    public static function faltaEfetivaMinutos(?int $minutosFalta): int
    {
        if ($minutosFalta === null || $minutosFalta <= 0) {
            return 0;
        }

        return $minutosFalta <= self::toleranciaMinutosFalta() ? 0 : $minutosFalta;
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

        if (app(EscalaPontoRegras::class)->diaAbonadoPorFolgaEscala($colaborador, $registro->data)) {
            return 0;
        }

        if (app(FeriadoPontoService::class)->diaAbonadoPorFeriado($registro->data)) {
            return 0;
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if (! $diaEscala) {
            return self::jornadaMinutosEsperados();
        }

        if (! self::diaEscalaTemJornada($diaEscala)) {
            return 0;
        }

        $ymd = $registro->data instanceof CarbonInterface
            ? $registro->data->format('Y-m-d')
            : Carbon::parse($registro->data)->format('Y-m-d');

        return self::minutosPrevistosEscalaDia($ymd, $diaEscala);
    }

    public static function minutosTrabalhados(FrequenciaRegistro $registro): int
    {
        if ($registro->data === null) {
            return 0;
        }

        $dia = $registro->data instanceof CarbonInterface
            ? $registro->data->format('Y-m-d')
            : Carbon::parse((string) $registro->data)->format('Y-m-d');

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

        $feriadoDia = app(FeriadoPontoService::class)->diaAbonadoPorFeriado($registro->data);
        $feriadoRegistro = ($registro->origem ?? '') === FeriadoPontoService::ORIGEM;

        if (($feriadoDia || $feriadoRegistro) && $trabalhadas === 0 && $status === 'justificado') {
            $feriado = app(FeriadoPontoService::class)->feriadoNaData($registro->data);
            $rotulo = $feriado?->rotuloPonto() ?? 'Feriado (abonado)';

            return [
                'trabalhadas' => 0,
                'trabalhadas_fmt' => '0h',
                'falta' => null,
                'falta_fmt' => $rotulo,
                'extras' => 0,
                'extras_fmt' => '0h',
                'jornada_esperada_minutos' => 0,
                'jornada_esperada_fmt' => 'Feriado',
            ];
        }

        if ($status === 'justificado') {
            $extras = self::minutosExtras($registro, $trabalhadas, $jornada);

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

        $folgaEscala = $registro->colaborador
            && app(EscalaPontoRegras::class)->diaAbonadoPorFolgaEscala($registro->colaborador, $registro->data);

        if ($folgaEscala && $trabalhadas === 0 && $status !== 'justificado') {
            return [
                'trabalhadas' => 0,
                'trabalhadas_fmt' => '0h',
                'falta' => null,
                'falta_fmt' => 'Folga (abonada)',
                'extras' => 0,
                'extras_fmt' => '0h',
                'jornada_esperada_minutos' => 0,
                'jornada_esperada_fmt' => 'Folga (escala)',
            ];
        }

        $falta = max(0, $jornada - $trabalhadas);
        $extras = self::minutosExtras($registro, $trabalhadas, $jornada);

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

    /**
     * Horas extras: saldo acima da jornada prevista + minutos fora da escala (entrada antes / saída depois).
     */
    public static function minutosExtras(FrequenciaRegistro $registro, ?int $trabalhadas = null, ?int $jornada = null): int
    {
        $trabalhadas ??= self::minutosTrabalhados($registro);
        $jornada ??= self::jornadaMinutosParaRegistro($registro);
        $saldo = max(0, $trabalhadas - $jornada);
        $foraEscala = self::minutosExtrasForaDaEscala($registro);

        return max($saldo, $foraEscala);
    }

    /**
     * Minutos de entrada antes do previsto ou saída final após o previsto (batida real vs. escala).
     */
    public static function minutosExtrasForaDaEscala(FrequenciaRegistro $registro): int
    {
        $colaborador = $registro->colaborador;
        if (! $colaborador) {
            return 0;
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if (! $diaEscala) {
            return 0;
        }

        // Dia sem saída final: não projeta horário da escala nem conta extra antecipada/tardia.
        if (self::horarioArmazenadoVazio($registro->saida_2)) {
            return 0;
        }

        $ymd = $registro->data instanceof CarbonInterface
            ? $registro->data->format('Y-m-d')
            : Carbon::parse($registro->data)->format('Y-m-d');

        $extras = 0;

        $previstoEntrada = self::normalizarHorarioBanco($diaEscala->entrada_1);
        $realEntrada = self::normalizarHorarioBanco($registro->entrada_1);
        if ($previstoEntrada !== null && $realEntrada !== null) {
            try {
                $a = Carbon::parse("{$ymd} {$previstoEntrada}");
                $b = Carbon::parse("{$ymd} {$realEntrada}");
                if ($b->lt($a)) {
                    $extras += (int) $b->diffInMinutes($a);
                }
            } catch (\Throwable) {
                // ignora horário inválido
            }
        }

        $previstoSaida = self::normalizarHorarioBanco($diaEscala->saida_2);
        $realSaida = self::normalizarHorarioBanco($registro->saida_2);
        if ($previstoSaida !== null && $realSaida !== null) {
            try {
                $a = Carbon::parse("{$ymd} {$previstoSaida}");
                $b = Carbon::parse("{$ymd} {$realSaida}");
                if ($b->gt($a)) {
                    $extras += (int) $a->diffInMinutes($b);
                }
            } catch (\Throwable) {
                // ignora horário inválido
            }
        }

        return $extras;
    }

    private static function diaEscalaTemJornada(HorarioEscalaDia $dia): bool
    {
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (! self::horarioArmazenadoVazio($dia->getAttribute($campo))) {
                return true;
            }
        }

        return false;
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
