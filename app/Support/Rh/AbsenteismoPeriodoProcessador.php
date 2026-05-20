<?php

namespace App\Support\Rh;

use App\Models\FrequenciaRegistro;
use App\Support\FrequenciaCalculo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agregação única de absenteísmo e linhas de ocorrência (fonte de verdade para taxas e extrato).
 */
final class AbsenteismoPeriodoProcessador
{
    /** Mesma regra da apuração: só conta injustificada acima da tolerância de falta (RH_FREQUENCIA_TOLERANCIA_FALTA_MINUTOS). */

    /**
     * @return Builder<FrequenciaRegistro>
     */
    public static function queryRegistros(
        string $inicioStr,
        string $fimStr,
        ?int $colaboradorId,
        ?array $identificadoresContrato
    ): Builder {
        return FrequenciaRegistro::query()
            ->whereDate('data', '>=', $inicioStr)
            ->whereDate('data', '<=', $fimStr)
            ->whereHas('colaborador', function ($q) use ($colaboradorId, $identificadoresContrato) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
                if ($colaboradorId !== null) {
                    $q->where('id', $colaboradorId);
                } elseif ($identificadoresContrato !== null && $identificadoresContrato !== []) {
                    ColaboradorQueryPorContratoPainel::aplicar($q, $identificadoresContrato);
                }
            })
            ->with(['colaborador', 'justificativaTipoCatalogo'])
            ->orderBy('data')
            ->orderBy('colaborador_id');
    }

    /**
     * @return array{
     *     totais: array{
     *         previstas_minutos: int,
     *         ausencia_geral_minutos: int,
     *         ausencia_justificada_minutos: int,
     *         ausencia_injustificada_minutos: int,
     *         dias_jornada: int,
     *         dias_injustificados: int,
     *         dias_justificados: int,
     *         presentes: int
     *     },
     *     linhas: list<array<string, mixed>>
     * }
     */
    public static function processar(iterable $registros): array
    {
        $previstasMin = 0;
        $ausenciaGeralMin = 0;
        $ausenciaJustMin = 0;
        $ausenciaInjustMin = 0;
        $diasJornada = 0;
        $diasInjustificados = 0;
        $diasJustificados = 0;
        $presentes = 0;
        $linhas = [];

        foreach ($registros as $registro) {
            if (! $registro instanceof FrequenciaRegistro) {
                continue;
            }

            $h = AbsenteismoHorasRegistro::calcular($registro);
            if ($h['previstas_minutos'] <= 0) {
                continue;
            }

            $injustMin = FrequenciaCalculo::faltaEfetivaMinutos(
                $h['ausencia_injustificada_minutos'] > 0 ? $h['ausencia_injustificada_minutos'] : null
            );
            $justMin = $h['ausencia_justificada_minutos'];
            $geralMin = $justMin + $injustMin;

            $diasJornada++;
            $previstasMin += $h['previstas_minutos'];
            $ausenciaGeralMin += $geralMin;
            $ausenciaJustMin += $justMin;
            $ausenciaInjustMin += $injustMin;

            if ($injustMin > 0) {
                $diasInjustificados++;
            }
            if ($justMin > 0) {
                $diasJustificados++;
            }
            if ($geralMin === 0 && (string) ($registro->status ?? '') === 'presente') {
                $presentes++;
            }

            if ($geralMin <= 0) {
                continue;
            }

            $data = $registro->data instanceof Carbon ? $registro->data : Carbon::parse($registro->data);
            $colab = $registro->colaborador;
            if ($colab === null) {
                continue;
            }

            $linhas[] = [
                'colaborador_id' => $colab->id,
                'colaborador' => $colab,
                'data' => $data->toDateString(),
                'data_fmt' => $data->format('d/m/Y'),
                'dia_semana' => self::diaSemanaCurto($data),
                'natureza' => $injustMin > 0
                    ? ExtratoAusenciasPeriodo::NATUREZA_INJUSTIFICADA
                    : ExtratoAusenciasPeriodo::NATUREZA_JUSTIFICADA,
                'tipo_label' => ExtratoAusenciasPeriodo::rotuloTipoOcorrencia($registro, $h),
                'status' => (string) ($registro->status ?? ''),
                'horas_previstas' => round($h['previstas_minutos'] / 60, 1),
                'horas_ausencia' => round($geralMin / 60, 1),
                'horas_justificada' => round($justMin / 60, 1),
                'horas_injustificada' => round($injustMin / 60, 1),
                'origem' => ExtratoAusenciasPeriodo::rotuloOrigem($registro->origem),
                'registro_id' => $registro->id,
            ];
        }

        return [
            'totais' => [
                'previstas_minutos' => $previstasMin,
                'ausencia_geral_minutos' => $ausenciaGeralMin,
                'ausencia_justificada_minutos' => $ausenciaJustMin,
                'ausencia_injustificada_minutos' => $ausenciaInjustMin,
                'dias_jornada' => $diasJornada,
                'dias_injustificados' => $diasInjustificados,
                'dias_justificados' => $diasJustificados,
                'presentes' => $presentes,
            ],
            'linhas' => $linhas,
        ];
    }

    /**
     * @param  array<string, int>  $totais
     * @return array<string, mixed>
     */
    public static function totaisParaResumoAbsenteismo(
        array $totais,
        string $inicioStr,
        string $fimStr,
        int $diasPeriodo,
        ?int $colaboradorId,
        string $escopo
    ): array {
        $previstasMin = (int) $totais['previstas_minutos'];
        $ausenciaGeralMin = (int) $totais['ausencia_geral_minutos'];
        $ausenciaJustMin = (int) $totais['ausencia_justificada_minutos'];
        $ausenciaInjustMin = (int) $totais['ausencia_injustificada_minutos'];

        $taxaGeral = $previstasMin > 0 ? round(($ausenciaGeralMin / $previstasMin) * 100, 1) : 0.0;
        $taxaJustificada = $previstasMin > 0 ? round(($ausenciaJustMin / $previstasMin) * 100, 1) : 0.0;
        $taxaInjustificada = $previstasMin > 0 ? round(($ausenciaInjustMin / $previstasMin) * 100, 1) : 0.0;

        return [
            'inicio' => $inicioStr,
            'fim' => $fimStr,
            'dias' => $diasPeriodo,
            'ausencias' => (int) $totais['dias_injustificados'],
            'ausencias_justificadas_dias' => (int) $totais['dias_justificados'],
            'base' => (int) $totais['dias_jornada'],
            'presentes' => (int) $totais['presentes'],
            'taxa' => $taxaGeral,
            'taxa_geral' => $taxaGeral,
            'taxa_justificada' => $taxaJustificada,
            'taxa_injustificada' => $taxaInjustificada,
            'horas_previstas' => round($previstasMin / 60, 1),
            'horas_ausencia_geral' => round($ausenciaGeralMin / 60, 1),
            'horas_ausencia_justificada' => round($ausenciaJustMin / 60, 1),
            'horas_ausencia_injustificada' => round($ausenciaInjustMin / 60, 1),
            'colaborador_id' => $colaboradorId,
            'escopo' => $escopo,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $linhas
     * @return array<string, mixed>
     */
    public static function resumoDeLinhas(array $linhas, float $horasPrevistasPeriodo): array
    {
        $colecao = collect($linhas);
        $hGeral = round($colecao->sum('horas_ausencia'), 1);
        $hJust = round($colecao->sum('horas_justificada'), 1);
        $hInjust = round($colecao->sum('horas_injustificada'), 1);

        $taxaGeral = $horasPrevistasPeriodo > 0 ? round(100 * $hGeral / $horasPrevistasPeriodo, 1) : 0.0;
        $taxaJustificada = $horasPrevistasPeriodo > 0 ? round(100 * $hJust / $horasPrevistasPeriodo, 1) : 0.0;
        $taxaInjustificada = $horasPrevistasPeriodo > 0 ? round(100 * $hInjust / $horasPrevistasPeriodo, 1) : 0.0;

        return [
            'horas_previstas' => $horasPrevistasPeriodo,
            'horas_ausencia_geral' => $hGeral,
            'horas_ausencia_justificada' => $hJust,
            'horas_ausencia_injustificada' => $hInjust,
            'taxa_geral' => $taxaGeral,
            'taxa_justificada' => $taxaJustificada,
            'taxa_injustificada' => $taxaInjustificada,
            'total_ocorrencias' => $colecao->count(),
        ];
    }

    private static function diaSemanaCurto(Carbon $data): string
    {
        return match ((int) $data->isoWeekday()) {
            1 => 'SEG',
            2 => 'TER',
            3 => 'QUA',
            4 => 'QUI',
            5 => 'SEX',
            6 => 'SAB',
            default => 'DOM',
        };
    }
}
