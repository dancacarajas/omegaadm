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

    public static function registroTemPontoCompleto(FrequenciaRegistro $registro): bool
    {
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (self::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                return false;
            }
        }

        return true;
    }

    public static function faltaEfetivaMinutos(?int $minutosFalta, ?int $toleranciaMinutos = null): int
    {
        if ($minutosFalta === null || $minutosFalta <= 0) {
            return 0;
        }

        $limite = $toleranciaMinutos ?? self::toleranciaMinutosFalta();

        return $minutosFalta <= $limite ? 0 : $minutosFalta;
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
    public static function registroTemAlgumaBatida(FrequenciaRegistro $registro): bool
    {
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (! self::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Preenche intervalo vazio (almoço) com horários da escala para calcular horas trabalhadas corretamente.
     */
    public static function deveCompletarIntervaloComEscala(FrequenciaRegistro $registro, int $trabalhadasBrutas): bool
    {
        $colaborador = $registro->colaborador;
        if ($colaborador === null) {
            return false;
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if ($diaEscala === null) {
            return false;
        }

        if (! self::registroTemBatidaCompletaEntradaSaidaFinal($registro)) {
            return false;
        }

        $escalaTemIntervalo = ! self::horarioArmazenadoVazio($diaEscala->saida_1)
            && ! self::horarioArmazenadoVazio($diaEscala->entrada_2);

        if (! $escalaTemIntervalo) {
            return false;
        }

        return self::horarioArmazenadoVazio($registro->saida_1)
            || self::horarioArmazenadoVazio($registro->entrada_2);
    }

    /** Entrada inicial e saída final preenchidas (não presume jornada só com batidas parciais). */
    public static function registroTemBatidaCompletaEntradaSaidaFinal(FrequenciaRegistro $registro): bool
    {
        return ! self::horarioArmazenadoVazio($registro->entrada_1)
            && ! self::horarioArmazenadoVazio($registro->saida_2);
    }

    /**
     * Resumo usado na apuração, absenteísmo e frequência — única fonte com fallback de escala quando aplicável.
     */
    public static function resumoParaApuracao(FrequenciaRegistro $registro): array
    {
        $resumo = self::resumo($registro);
        $status = (string) ($registro->status ?? 'falta');

        if (! in_array($status, ['presente', 'incompleto'], true)) {
            return $resumo;
        }

        $trabalhadasBrutas = (int) ($resumo['trabalhadas'] ?? 0);
        if (! self::deveCompletarIntervaloComEscala($registro, $trabalhadasBrutas)) {
            return $resumo;
        }

        return self::resumoComFallbackEscala($registro);
    }

    /**
     * Só preenche intervalo de almoço ausente (saida_1 / entrada_2) — não fabrica entrada/saída do dia.
     */
    public static function resumoComFallbackEscala(FrequenciaRegistro $registro): array
    {
        $clone = clone $registro;
        $dia = $registro->colaborador?->horarioEscalaDiaNaData($registro->data);
        if ($dia) {
            foreach (['saida_1', 'entrada_2'] as $campo) {
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
     * Horas extras: somente saldo trabalhado acima da jornada (após tolerância diária).
     */
    public static function minutosExtras(FrequenciaRegistro $registro, ?int $trabalhadas = null, ?int $jornada = null): int
    {
        $trabalhadas ??= self::minutosTrabalhados($registro);
        $jornada ??= self::jornadaMinutosParaRegistro($registro);
        $saldo = max(0, $trabalhadas - $jornada);

        return self::faltaEfetivaMinutos($saldo > 0 ? $saldo : null, self::toleranciaMinutosFalta());
    }

    /** Entrada antes do previsto (informativo; não vira extra automaticamente). */
    public static function minutosEntradaAntecipada(FrequenciaRegistro $registro): int
    {
        $colaborador = $registro->colaborador;
        if ($colaborador === null) {
            return 0;
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if ($diaEscala === null) {
            return 0;
        }

        $ymd = self::ymdRegistro($registro);
        $previsto = self::normalizarHorarioBanco($diaEscala->entrada_1);
        $real = self::primeiraEntradaRegistro($registro);
        if ($previsto === null || $real === null) {
            return 0;
        }

        try {
            $a = Carbon::parse("{$ymd} {$previsto}");
            $b = Carbon::parse("{$ymd} {$real}");
        } catch (\Throwable) {
            return 0;
        }

        if ($b->gte($a)) {
            return 0;
        }

        return (int) $b->diffInMinutes($a);
    }

    /** Saída após o previsto (última saída válida vs. escala). */
    public static function minutosSaidaPosterior(FrequenciaRegistro $registro): int
    {
        $colaborador = $registro->colaborador;
        if ($colaborador === null) {
            return 0;
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if ($diaEscala === null) {
            return 0;
        }

        $ymd = self::ymdRegistro($registro);
        $previsto = self::ultimaSaidaPrevistaEscala($diaEscala);
        $real = self::ultimaSaidaRegistro($registro);
        if ($previsto === null || $real === null) {
            return 0;
        }

        try {
            $a = Carbon::parse("{$ymd} {$previsto}");
            $b = Carbon::parse("{$ymd} {$real}");
        } catch (\Throwable) {
            return 0;
        }

        if ($b->lte($a)) {
            return 0;
        }

        return (int) $a->diffInMinutes($b);
    }

    public static function primeiraEntradaRegistro(FrequenciaRegistro $registro): ?string
    {
        $ymd = self::ymdRegistro($registro);
        $candidatos = [];
        foreach (['entrada_1', 'entrada_2'] as $campo) {
            $h = self::normalizarHorarioBanco($registro->getAttribute($campo));
            if ($h !== null) {
                $candidatos[] = Carbon::parse("{$ymd} {$h}");
            }
        }

        if ($candidatos === []) {
            return null;
        }

        return collect($candidatos)->min()->format('H:i:s');
    }

    public static function ultimaSaidaRegistro(FrequenciaRegistro $registro): ?string
    {
        $ymd = self::ymdRegistro($registro);
        $candidatos = [];
        foreach (['saida_1', 'saida_2'] as $campo) {
            $h = self::normalizarHorarioBanco($registro->getAttribute($campo));
            if ($h !== null) {
                $candidatos[] = Carbon::parse("{$ymd} {$h}");
            }
        }

        if ($candidatos === []) {
            return null;
        }

        return collect($candidatos)->max()->format('H:i:s');
    }

    private static function ultimaSaidaPrevistaEscala(HorarioEscalaDia $diaEscala): ?string
    {
        $saidas = array_filter([
            self::normalizarHorarioBanco($diaEscala->saida_1),
            self::normalizarHorarioBanco($diaEscala->saida_2),
        ]);

        if ($saidas === []) {
            return null;
        }

        return collect($saidas)->sort()->last();
    }

    private static function ymdRegistro(FrequenciaRegistro $registro): string
    {
        return $registro->data instanceof CarbonInterface
            ? $registro->data->format('Y-m-d')
            : Carbon::parse((string) $registro->data)->format('Y-m-d');
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

    /** Atraso na entrada em relação à escala do colaborador (minutos). */
    public static function minutosAtrasoRegistro(FrequenciaRegistro $registro): int
    {
        $colaborador = $registro->colaborador;
        if ($colaborador === null) {
            return 0;
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if ($diaEscala === null) {
            return 0;
        }

        $ymd = $registro->data instanceof CarbonInterface
            ? $registro->data->format('Y-m-d')
            : Carbon::parse((string) $registro->data)->format('Y-m-d');

        $previsto = self::normalizarHorarioBanco($diaEscala->entrada_1);
        $real = self::primeiraEntradaRegistro($registro);
        if ($previsto === null || $real === null) {
            return 0;
        }

        try {
            $a = Carbon::parse("{$ymd} {$previsto}");
            $b = Carbon::parse("{$ymd} {$real}");
        } catch (\Throwable) {
            return 0;
        }

        if ($b->lte($a)) {
            return 0;
        }

        return (int) $a->diffInMinutes($b);
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
