<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Extrato detalhado de ausências no período (horas e classificação gerencial).
 */
class ExtratoAusenciasPeriodo
{
    public const NATUREZA_TODAS = 'todas';

    public const NATUREZA_JUSTIFICADA = 'justificada';

    public const NATUREZA_INJUSTIFICADA = 'injustificada';

    /**
     * @return array<string, mixed>
     */
    public function montar(
        string $inicio,
        string $fim,
        ?int $colaboradorId = null,
        string $naturezaFiltro = self::NATUREZA_TODAS
    ): array {
        $inicioCarbon = Carbon::parse($inicio)->startOfDay();
        $fimCarbon = Carbon::parse($fim)->startOfDay();
        if ($fimCarbon->lt($inicioCarbon)) {
            [$inicioCarbon, $fimCarbon] = [$fimCarbon, $inicioCarbon];
        }

        $inicioStr = $inicioCarbon->toDateString();
        $fimStr = $fimCarbon->toDateString();
        $naturezaFiltro = $this->normalizarNatureza($naturezaFiltro);

        $query = AbsenteismoPeriodoProcessador::queryRegistros($inicioStr, $fimStr, $colaboradorId, null);
        $processado = AbsenteismoPeriodoProcessador::processar($query->get());

        $absenteismoPeriodo = AbsenteismoPeriodoProcessador::totaisParaResumoAbsenteismo(
            $processado['totais'],
            $inicioStr,
            $fimStr,
            max(1, $inicioCarbon->diffInDays($fimCarbon, false) + 1),
            $colaboradorId,
            $colaboradorId !== null ? 'colaborador' : 'efetivo'
        );

        $linhasFiltradas = array_values(array_filter(
            $processado['linhas'],
            function (array $linha) use ($naturezaFiltro) {
                if ($naturezaFiltro === self::NATUREZA_TODAS) {
                    return true;
                }
                if ($linha['natureza'] !== $naturezaFiltro) {
                    return false;
                }

                return $naturezaFiltro !== self::NATUREZA_INJUSTIFICADA
                    || ($linha['horas_injustificada'] ?? 0) > 0;
            }
        ));

        $resumoFiltro = AbsenteismoPeriodoProcessador::resumoDeLinhas(
            $linhasFiltradas,
            (float) $absenteismoPeriodo['horas_previstas']
        );

        /** @var Collection<int, Collection<int, array<string, mixed>>> $porColaborador */
        $porColaborador = collect($linhasFiltradas)->groupBy('colaborador_id');

        $colaboradores = [];
        foreach ($porColaborador as $grupo) {
            $primeiro = $grupo->first();
            /** @var Colaborador $colab */
            $colab = $primeiro['colaborador'];

            $colaboradores[] = [
                'colaborador' => $colab,
                'qtd_ocorrencias' => $grupo->count(),
                'horas_previstas' => round($grupo->sum('horas_previstas'), 1),
                'horas_ausencia' => round($grupo->sum('horas_ausencia'), 1),
                'horas_justificada' => round($grupo->sum('horas_justificada'), 1),
                'horas_injustificada' => round($grupo->sum('horas_injustificada'), 1),
                'ocorrencias' => $grupo->values()->all(),
            ];
        }

        usort($colaboradores, fn ($a, $b) => $b['horas_ausencia'] <=> $a['horas_ausencia']
            ?: strcasecmp($a['colaborador']->nome, $b['colaborador']->nome));

        $exibirResumo = $naturezaFiltro === self::NATUREZA_TODAS
            ? $absenteismoPeriodo
            : array_merge($absenteismoPeriodo, $resumoFiltro);

        return [
            'inicio' => $inicioStr,
            'fim' => $fimStr,
            'dias' => max(1, $inicioCarbon->diffInDays($fimCarbon, false) + 1),
            'colaborador_id' => $colaboradorId,
            'natureza_filtro' => $naturezaFiltro,
            'absenteismo' => $absenteismoPeriodo,
            'resumo_exibicao' => $exibirResumo,
            'resumo_filtro' => $resumoFiltro,
            'total_ocorrencias' => count($linhasFiltradas),
            'total_horas_ausencia' => $resumoFiltro['horas_ausencia_geral'],
            'total_horas_justificada' => $resumoFiltro['horas_ausencia_justificada'],
            'total_horas_injustificada' => $resumoFiltro['horas_ausencia_injustificada'],
            'colaboradores' => $colaboradores,
            'linhas' => $linhasFiltradas,
        ];
    }

    private function normalizarNatureza(string $natureza): string
    {
        return match ($natureza) {
            self::NATUREZA_JUSTIFICADA, self::NATUREZA_INJUSTIFICADA => $natureza,
            default => self::NATUREZA_TODAS,
        };
    }

    public static function rotuloTipoOcorrencia(\App\Models\FrequenciaRegistro $registro, array $horas): string
    {
        $status = (string) ($registro->status ?? '');

        if ($status === 'falta') {
            return 'Falta injustificada';
        }

        if ($status === 'justificado') {
            if ($registro->relationLoaded('justificativaTipoCatalogo') && $registro->justificativaTipoCatalogo) {
                $cat = $registro->justificativaTipoCatalogo->categoria;
                if ($cat === 'atestado') {
                    return 'Atestado médico';
                }
                if ($cat === 'abono') {
                    return 'Abono / mobilização';
                }

                return $registro->justificativaTipoCatalogo->nome;
            }

            return match ($registro->justificativa_tipo) {
                'atestado' => 'Atestado médico',
                'abono' => 'Abono / mobilização',
                default => 'Ausência justificada',
            };
        }

        if (in_array($status, ['presente', 'incompleto'], true)) {
            $atrasoMin = $horas['ausencia_injustificada_minutos'];
            $previstasMin = $horas['previstas_minutos'];
            if ($atrasoMin > 0 && $atrasoMin < $previstasMin) {
                return 'Atraso / falta parcial';
            }

            return $status === 'incompleto' ? 'Registro incompleto' : 'Falta parcial';
        }

        return ucfirst($status ?: 'Ausência');
    }

    public static function rotuloOrigem(?string $origem): string
    {
        return match ($origem) {
            'csv_ponto' => 'Importação CSV',
            'afd' => 'Importação AFD',
            'grade' => 'Grade automática',
            'manual' => 'Manual',
            'app_colaborador' => 'App colaborador',
            default => $origem ? ucfirst(str_replace('_', ' ', $origem)) : '—',
        };
    }
}
