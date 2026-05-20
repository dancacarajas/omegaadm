<?php

namespace App\Support\Rh;

use App\Models\FrequenciaRegistro;
use App\Support\FrequenciaCalculo;

/**
 * Métricas diárias da apuração (fonte única para cartão, absenteísmo e folha).
 *
 * Desconto = horas_falta (déficit após tolerância). Atraso bruto é informativo; não somar em falta+atraso.
 */
final class ApuracaoPontoMetricas
{
    /**
     * @return array{
     *     trabalhadas: int,
     *     previstas: int,
     *     minutos_falta: int,
     *     atraso_bruto: int,
     *     atraso_descontavel: int,
     *     minutos_extras: int,
     *     entrada_antecipada: int,
     *     saida_posterior: int,
     *     dia_falta_integral: bool,
     *     tem_ocorrencia_falta: bool,
     *     total_desconto: int
     * }
     */
    public static function calcular(FrequenciaRegistro $registro): array
    {
        $resumo = FrequenciaCalculo::resumoParaApuracao($registro);
        $previstas = (int) ($resumo['jornada_esperada_minutos'] ?? 0);
        $trabalhadas = (int) ($resumo['trabalhadas'] ?? 0);
        $tolerancia = FrequenciaCalculo::toleranciaMinutosFalta();

        $saldoFalta = max(0, $previstas - $trabalhadas);
        $minutosFalta = FrequenciaCalculo::faltaEfetivaMinutos(
            $saldoFalta > 0 ? $saldoFalta : null,
            $tolerancia
        );

        $atrasoBruto = FrequenciaCalculo::minutosAtrasoRegistro($registro);
        $atrasoDescontavel = 0;
        if ($minutosFalta > 0 && $atrasoBruto > 0) {
            $atrasoDescontavel = min($atrasoBruto, $minutosFalta);
        }

        $saldoExtra = max(0, $trabalhadas - $previstas);
        $minutosExtras = FrequenciaCalculo::faltaEfetivaMinutos(
            $saldoExtra > 0 ? $saldoExtra : null,
            $tolerancia
        );

        $entradaAntecipada = FrequenciaCalculo::minutosEntradaAntecipada($registro);
        $saidaPosterior = FrequenciaCalculo::minutosSaidaPosterior($registro);

        $temBatidas = FrequenciaCalculo::registroTemAlgumaBatida($registro);
        $status = (string) ($registro->status ?? 'falta');

        $diaFaltaIntegral = $previstas > 0 && (
            (! $temBatidas && in_array($status, ['falta', null], true))
            || ($status === 'falta' && $trabalhadas === 0 && $minutosFalta > 0)
            || ($minutosFalta > 0 && $minutosFalta >= max(0, $previstas - $tolerancia))
        );

        return [
            'trabalhadas' => $trabalhadas,
            'previstas' => $previstas,
            'minutos_falta' => $minutosFalta,
            'atraso_bruto' => $atrasoBruto,
            'atraso_descontavel' => $atrasoDescontavel,
            'minutos_extras' => $minutosExtras,
            'entrada_antecipada' => $entradaAntecipada,
            'saida_posterior' => $saidaPosterior,
            'dia_falta_integral' => $diaFaltaIntegral,
            'tem_ocorrencia_falta' => $minutosFalta > 0,
            'total_desconto' => $minutosFalta,
        ];
    }
}
