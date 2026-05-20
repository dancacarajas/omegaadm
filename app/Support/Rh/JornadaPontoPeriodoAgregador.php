<?php

namespace App\Support\Rh;

use Carbon\Carbon;

/**
 * Agrega jornada prevista, trabalhada e horas extras do período a partir da apuração de ponto (batidas + escala).
 */
final class JornadaPontoPeriodoAgregador
{
    /**
     * @param  list<string>|null  $identificadoresContrato
     * @return array{
     *     previstas_minutos: int,
     *     trabalhadas_minutos: int,
     *     extras_minutos: int,
     *     entrada_antecipada_minutos: int,
     *     saida_posterior_minutos: int,
     *     dias_com_extra: int,
     *     colaboradores_com_extra: int
     * }
     */
    public static function agregar(
        Carbon $periodoInicio,
        Carbon $periodoFim,
        ?array $identificadoresContrato = null
    ): array {
        $ini = $periodoInicio->copy()->startOfDay()->toDateString();
        $fim = $periodoFim->copy()->endOfDay()->toDateString();

        $query = AbsenteismoPeriodoProcessador::queryRegistros($ini, $fim, null, $identificadoresContrato);

        $previstas = 0;
        $trabalhadas = 0;
        $extras = 0;
        $entradaAntecipada = 0;
        $saidaPosterior = 0;
        $porColabExtra = [];
        $diasComExtra = 0;

        foreach ($query->cursor() as $registro) {
            $m = ApuracaoPontoMetricas::calcular($registro);
            if ($m['previstas'] <= 0) {
                continue;
            }

            $previstas += $m['previstas'];
            $trabalhadas += $m['trabalhadas'];
            $ext = $m['minutos_extras'];
            $extras += $ext;

            if ($ext > 0) {
                $diasComExtra++;
                $porColabExtra[$registro->colaborador_id] = true;
                $entradaAntecipada += $m['entrada_antecipada'];
                $saidaPosterior += $m['saida_posterior'];
            }
        }

        return [
            'previstas_minutos' => $previstas,
            'trabalhadas_minutos' => $trabalhadas,
            'extras_minutos' => $extras,
            'entrada_antecipada_minutos' => $entradaAntecipada,
            'saida_posterior_minutos' => $saidaPosterior,
            'dias_com_extra' => $diasComExtra,
            'colaboradores_com_extra' => count($porColabExtra),
        ];
    }

    /**
     * @return list<array{label: string, hours: float, valueLabel: string, pct: float, hex: string}>
     */
    public static function barrasHorasExtras(int $extrasMinutos, int $entradaAntecipadaMin, int $saidaPosteriorMin): array
    {
        if ($extrasMinutos <= 0) {
            return [
                [
                    'label' => 'Sem horas extras no período',
                    'hours' => 0.0,
                    'valueLabel' => '0h',
                    'pct' => 0.0,
                    'hex' => '#f3cfd9',
                ],
            ];
        }

        $saldo = max(0, $extrasMinutos - $entradaAntecipadaMin - $saidaPosteriorMin);
        $itens = [];

        if ($entradaAntecipadaMin > 0) {
            $itens[] = ['label' => 'Entrada antecipada (apurado)', 'min' => $entradaAntecipadaMin, 'hex' => '#600020'];
        }
        if ($saidaPosteriorMin > 0) {
            $itens[] = ['label' => 'Saída posterior (apurado)', 'min' => $saidaPosteriorMin, 'hex' => '#842244'];
        }
        if ($saldo > 0) {
            $itens[] = ['label' => 'Saldo extra (jornada)', 'min' => $saldo, 'hex' => '#c97d8f'];
        }

        if ($itens === []) {
            $itens[] = ['label' => 'Horas extras (apurado)', 'min' => $extrasMinutos, 'hex' => '#600020'];
        }

        $max = max(1, ...array_column($itens, 'min'));
        $barras = [];
        foreach ($itens as $item) {
            $h = round($item['min'] / 60, 1);
            $barras[] = [
                'label' => $item['label'],
                'hours' => $h,
                'valueLabel' => self::fmtHoras($h),
                'pct' => round(100 * $item['min'] / $max, 1),
                'hex' => $item['hex'],
            ];
        }

        return $barras;
    }

    public static function fmtHoras(float $horas): string
    {
        return number_format($horas, 1, ',', '.').'h';
    }

    public static function minutosParaHoras(int $minutos): float
    {
        return round($minutos / 60, 1);
    }
}
