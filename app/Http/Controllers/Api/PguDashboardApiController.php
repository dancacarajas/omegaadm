<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Http\Controllers\Controller;
use App\Models\ContratoHistogramaLinha;
use App\Models\ContratoHistogramaRecorte;
use App\Models\RecrutamentoVaga;
use App\Support\RecrutamentoCandidatoFase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PguDashboardApiController extends Controller
{
    use ContratosHistogramaCatalog;

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->assembleDashboard($request));
    }

    /**
     * Monta o payload do dashboard PGU (reutilizado pela API JSON e pela apresentação Blade).
     *
     * @return array<string, mixed>
     */
    public function assembleDashboard(Request $request): array
    {
        $data = $request->validate([
            'contrato' => ['required', 'string', 'max:255'],
            'competencia' => ['required', 'date_format:Y-m'],
            'data_limite_etapa_2' => ['nullable', 'date'],
        ]);

        $permitidos = $this->contratosDisponiveis();
        if ($permitidos !== [] && ! in_array($data['contrato'], $permitidos, true)) {
            abort(403, 'Contrato não permitido para o seu perfil.');
        }

        $competenciaMes = Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth();
        $recorte = ContratoHistogramaRecorte::query()
            ->where('contrato', $data['contrato'])
            ->whereDate('competencia', $competenciaMes->toDateString())
            ->first();
        $deadline = isset($data['data_limite_etapa_2']) && $data['data_limite_etapa_2']
            ? Carbon::parse($data['data_limite_etapa_2'])->startOfDay()
            : null;
        if ($deadline === null) {
            if ($recorte?->data_limite_etapa_2) {
                $deadline = $recorte->data_limite_etapa_2->copy()->startOfDay();
            }
        }

        $vagas = RecrutamentoVaga::query()
            ->where('contrato', $data['contrato'])
            ->where(function ($query) use ($competenciaMes) {
                $query->whereMonth('data_solicitacao', $competenciaMes->month)
                    ->whereYear('data_solicitacao', $competenciaMes->year)
                    ->orWhere(function ($q) use ($competenciaMes) {
                        $q->whereNull('data_solicitacao')
                            ->whereMonth('created_at', $competenciaMes->month)
                            ->whereYear('created_at', $competenciaMes->year);
                    });
            })
            ->latest('id')
            ->get();

        $cycleStartAt = $recorte?->inicio_monitoramento
            ? $recorte->inicio_monitoramento->copy()->startOfDay()
            : $competenciaMes->copy()->startOfMonth();
        $monthsInCycle = max(
            1,
            $cycleStartAt->copy()->startOfMonth()->diffInMonths($competenciaMes->copy()->startOfMonth()) + 1
        );

        $kpisItens = $this->buildKpisRecrutamento($vagas);
        $ranking = $this->buildRankingFromVagas($vagas);
        $rankingExecutivo = $this->buildRankingExecutivo($ranking, 5);
        $paretoExecutivo = $this->buildParetoExecutivo($rankingExecutivo);
        $rankingIndiretas = $this->buildRankingExecutivo(
            array_values(array_filter($ranking, fn ($r) => str_starts_with((string) ($r['codigo'] ?? ''), '1.1'))),
            5
        );
        $rankingDiretas = $this->buildRankingExecutivo(
            array_values(array_filter($ranking, fn ($r) => str_starts_with((string) ($r['codigo'] ?? ''), '1.2'))),
            5
        );
        $paretoIndiretas = $this->buildParetoExecutivo($rankingIndiretas);
        $paretoDiretas = $this->buildParetoExecutivo($rankingDiretas);
        $trendPayload = $this->buildTrend(
            $data['contrato'],
            $competenciaMes,
            (float) $kpisItens['vagas_concluidas_no_pgu'],
            (float) $kpisItens['vagas_pendentes_por_funcao'],
            $ranking === [] ? 0.0 : round(collect($ranking)->avg('progress'), 1),
            $monthsInCycle
        );
        $trend = $trendPayload['points'];
        $faseAtual = $this->buildCurrentPhaseProgress($vagas);
        $faseTrend = $this->buildPhaseTrend($data['contrato'], $competenciaMes, $monthsInCycle);
        $cycleMovements = $this->buildCycleMovementsFromHistorico(
            $data['contrato'],
            $competenciaMes,
            $cycleStartAt,
            $deadline
        );
        $heatmap = $this->buildHeatmapExecutivo($rankingExecutivo);
        $treemap = $this->buildTreemapPendencias($rankingExecutivo);
        $funcoesPgu100 = $this->buildFuncoesPgu100($ranking);

        $overallProgress = $ranking === [] ? 0.0 : round(collect($ranking)->avg('progress'), 1);
        $totalPending = (int) round($kpisItens['vagas_pendentes_por_funcao']);
        $totalFunctions = (int) round(collect($ranking)->sum(fn ($r) => (float) ($r['pgu'] ?? 0)));
        /** Vagas consolidadas só em linhas 100% (legado / slides que usam “integral” por linha). */
        $completedFunctions = (int) round(collect($ranking)->filter(function ($r) {
            $pre = (float) ($r['pre_pgu'] ?? 0);
            $pgu = (float) ($r['pgu'] ?? 0);
            if ($pre <= 0 && $pgu <= 0) {
                return false;
            }
            if (! empty($r['sem_pgu_informado'])) {
                return false;
            }

            return (float) ($r['progress'] ?? 0) >= 100;
        })->sum(fn ($r) => (float) ($r['completed'] ?? 0)));
        /** Total de vagas com liberação / consolidadas no PGU (soma de `completed` em todo o ranking) — base do % consolidado vs total_functions. */
        $vagasConcluidas = (int) round(collect($ranking)->sum(fn ($r) => (float) ($r['completed'] ?? 0)));
        $criticalFunctions = collect($ranking)->where('status', 'critical')->count();

        $deadlineRisk = $this->deadlineRisk($deadline, $overallProgress);
        $progressDelta = $this->progressDeltaFromTrend($trend);
        $itensAtrasadosFase2 = (int) collect($ranking)->filter(fn ($row) => ((float) ($row['progress'] ?? 0)) < 100)->count();
        $cycleStartDate = $cycleStartAt->toDateString();

        $kpiVagasPrevistas = (int) round((float) ($kpisItens['vagas_pgu_previstas'] ?? 0));
        $maturidadeTotalVagas = $kpiVagasPrevistas > 0 ? $kpiVagasPrevistas : $totalFunctions;
        $aceiteToSgcProgressPct = $this->buildAceiteToSgcProgressPercent($vagas);

        return [
            'summary' => [
                'overall_progress' => $overallProgress,
                'overall_progress_delta' => $progressDelta,
                'total_pending' => $totalPending,
                'total_functions' => $totalFunctions,
                'completed_functions' => $completedFunctions,
                'vagas_concluidas' => $vagasConcluidas,
                'critical_functions' => $criticalFunctions,
                'deadline_risk' => $deadlineRisk,
                'deadline_risk_label' => $this->deadlineRiskLabel($deadlineRisk),
                'deadline_date' => $deadline?->toDateString(),
                'cycle_start_date' => $cycleStartDate,
                'itens_atrasados_fase2' => $itensAtrasadosFase2,
                'kpis_mao_de_obra_itens' => $kpisItens,
                /** Avanço médio (ponderado por vagas) do fluxo RH até Postagem SGC — mesma fórmula do passo a passo, sem Liberação (5 pesos). */
                'aceite_to_sgc_progress_pct' => $aceiteToSgcProgressPct,
            ],
            'donut_avanco' => [
                'overall' => $overallProgress,
                'avanco' => $overallProgress,
                'pendente' => round(max(0, 100 - $overallProgress), 1),
            ],
            'ranking' => $ranking,
            'ranking_executivo' => $rankingExecutivo,
            'pareto_executivo' => $paretoExecutivo,
            'ranking_executivo_indiretas' => $rankingIndiretas,
            'ranking_executivo_diretas' => $rankingDiretas,
            'pareto_executivo_indiretas' => $paretoIndiretas,
            'pareto_executivo_diretas' => $paretoDiretas,
            'trend' => $trend,
            'trend_notas' => $trendPayload['note'],
            'cycle_movements' => $cycleMovements,
            'fase_atual' => $faseAtual,
            'fase_trend' => $faseTrend,
            'heatmap' => $heatmap,
            'treemap_pendencias' => $treemap,
            'funcoes_pgu_100' => $funcoesPgu100,
            // Total de vagas do recortamento (KPI) ou, se vazio, soma PGU no histograma — base do funil de maturidade.
            'maturidade_total_vagas' => $maturidadeTotalVagas,
            'mao_de_obra' => [
                'mobilizacao' => round((float) ($kpisItens['vagas_pgu_previstas'] ?? 0), 2),
                'pre_pgu' => round((float) ($kpisItens['vagas_concluidas_no_pgu'] ?? 0), 2),
                'pgu' => round((float) ($kpisItens['vagas_em_andamento'] ?? 0), 2),
                'pos_pgu' => round((float) ($kpisItens['vagas_liberadas'] ?? 0), 2),
            ],
            /** Contagens cumulativas por etapa (mesma base do funil de Maturidade) — painel «Avanço de Contratações». */
            'contratacoes_funil' => $this->buildContratacoesFunil($vagas),
        ];
    }

    /**
     * @param  Collection<int, RecrutamentoVaga>  $vagas
     * @return array<string, mixed>
     */
    private function buildKpisRecrutamento(Collection $vagas): array
    {
        $previstas = (int) $vagas->sum(fn (RecrutamentoVaga $vaga) => max(1, (int) $vaga->quantidade));
        $concluidas = 0;
        $emAndamento = 0;
        $liberadas = 0;
        $accInd = ['pgu' => 0, 'concluidas' => 0, 'pendentes' => 0];
        $accDir = ['pgu' => 0, 'concluidas' => 0, 'pendentes' => 0];

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            $qty = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
            $approved = $this->approvedCandidates($vaga);
            $approvedCount = $approved->count();
            $completed = $approved
                ->filter(fn ($c) => $this->candidateStepDone($state, (int) $c['position'], 'liberacao'))
                ->count();

            $concluidas += $completed;
            $emAndamento += max(0, $approvedCount - $completed);
            $liberadas += $completed;

            $pend = max(0, $qty - $completed);
            $codigo = trim((string) ($state['origem_histograma_item_codigo'] ?? ''));
            if (preg_match('/^1\.1(\.|$)/', $codigo) === 1) {
                $accInd['pgu'] += $qty;
                $accInd['concluidas'] += $completed;
                $accInd['pendentes'] += $pend;
            } elseif (preg_match('/^1\.2(\.|$)/', $codigo) === 1) {
                $accDir['pgu'] += $qty;
                $accDir['concluidas'] += $completed;
                $accDir['pendentes'] += $pend;
            }
        }

        $pendentes = max(0, $previstas - $concluidas);

        return [
            'vagas_pgu_previstas' => $previstas,
            'vagas_concluidas_no_pgu' => $concluidas,
            'vagas_pendentes_por_funcao' => $pendentes,
            'vagas_pre_sem_pgu_informado' => 0,
            'vagas_em_andamento' => $emAndamento,
            'vagas_liberadas' => $liberadas,
            'por_grupo' => [
                ['grupo' => 'Equipe Indireta', 'pgu' => $accInd['pgu'], 'concluidas' => $accInd['concluidas'], 'pendentes' => $accInd['pendentes']],
                ['grupo' => 'Equipe Direta', 'pgu' => $accDir['pgu'], 'concluidas' => $accDir['concluidas'], 'pendentes' => $accDir['pendentes']],
                ['grupo' => 'Total', 'pgu' => $previstas, 'concluidas' => $concluidas, 'pendentes' => $pendentes],
            ],
        ];
    }

    /**
     * @param  Collection<int, RecrutamentoVaga>  $vagas
     * @return array<int, array<string, mixed>>
     */
    private function buildRankingFromVagas(Collection $vagas): array
    {
        $out = [];

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            $qty = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
            $approved = $this->approvedCandidates($vaga);
            $completed = $approved
                ->filter(fn ($c) => $this->candidateStepDone($state, (int) $c['position'], 'liberacao'))
                ->count();
            $pending = max(0, $qty - $completed);
            $progress = min(100, round(($completed / max(1, $qty)) * 100, 1));
            $status = $this->classifyStatus((int) $pending, (float) $progress);

            $out[] = [
                'linha_id' => $vaga->id,
                'codigo' => trim((string) ($state['origem_histograma_item_codigo'] ?? '')) ?: null,
                'funcao' => (string) ($vaga->titulo ?: 'Vaga sem título'),
                'function' => (string) ($vaga->titulo ?: 'Vaga sem título'),
                'pre_pgu' => (float) $completed,
                'pgu' => (float) $qty,
                'pending' => (int) $pending,
                'completed' => (float) $completed,
                'progress' => (float) $progress,
                'sem_pgu_informado' => false,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
            ];
        }

        usort($out, fn ($a, $b) => $b['pending'] <=> $a['pending']);

        return $out;
    }

    /**
     * Passo 01 do fluxo RH: três primeiros checkboxes persistidos em `rh-check-*` (alinhado à listagem de recrutamento).
     */
    private function recrutamentoStepOneDone(array $state): bool
    {
        $checks = collect($state)
            ->filter(fn ($value, $key) => str_starts_with((string) $key, 'rh-check-'))
            ->values();
        $chunk = $checks->slice(0, 3);

        return $chunk->count() > 0 && $chunk->every(fn ($value) => (bool) $value);
    }

    /**
     * Percentual 0–100: mesma regra do "Progresso do fluxo RH" no formulário (1/5 passo recrutamento + 4/5 média dos aprovados por etapa),
     * porém só até Postagem SGC — Liberação não entra. Vagas sem aprovados só pontuam o passo 01; demais etapas somam 0 até haver aprovado.
     * Média ponderada pela quantidade de vagas de cada ficha.
     *
     * @param  Collection<int, RecrutamentoVaga>  $vagas
     */
    private function buildAceiteToSgcProgressPercent(Collection $vagas): float
    {
        if ($vagas->isEmpty()) {
            return 0.0;
        }

        $pesoTotal = 0.0;
        $acumulado = 0.0;

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            $qty = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
            $approved = $this->approvedCandidates($vaga);
            $approvedCount = $approved->count();

            $done = $this->recrutamentoStepOneDone($state) ? 1.0 : 0.0;
            if ($approvedCount > 0) {
                foreach (['exame_medico', 'treinamentos', 'assinatura', 'sgc'] as $step) {
                    $doneInStep = $approved
                        ->filter(fn ($c) => $this->candidateStepDone($state, (int) $c['position'], $step))
                        ->count();
                    $done += $doneInStep / $approvedCount;
                }
            }

            $pct = min(100.0, round(($done / 5.0) * 100, 1));
            $acumulado += $pct * $qty;
            $pesoTotal += $qty;
        }

        if ($pesoTotal <= 0) {
            return 0.0;
        }

        return round($acumulado / $pesoTotal, 1);
    }

    private function approvedCandidates(RecrutamentoVaga $vaga): Collection
    {
        $state = $vaga->form_state ?? [];
        $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));

        return collect(range(1, $quantity))
            ->map(fn ($position) => [
                'position' => $position,
                'name' => $state["candidato_{$position}_nome_completo"] ?? '',
                'status' => $state["candidato_{$position}_status"] ?? 'pendente',
            ])
            ->filter(fn ($candidate) => $candidate['status'] === 'aprovado' && filled($candidate['name']))
            ->values();
    }

    private function candidateStepDone(array $state, int $position, string $step): bool
    {
        if ($step === 'exame_medico') {
            $trainingStart = $state["candidato_{$position}_exameMedico_data_inicio"]
                ?? $state["candidato_{$position}_treinamentos_data_inicio"]
                ?? null;
            $trainingEnd = $state["candidato_{$position}_exameMedico_data_fim"]
                ?? $state["candidato_{$position}_treinamentos_data_fim"]
                ?? null;
            $trainingConfirmedAt = $state["candidato_{$position}_exameMedico_data_confirmacao"]
                ?? $state["candidato_{$position}_treinamentos_data_confirmacao"]
                ?? null;
            $scheduledAt = $state["candidato_{$position}_exameMedico_data_agendamento"]
                ?? $state["candidato_{$position}_treinamentos_data_agendamento"]
                ?? null;

            if (blank($trainingEnd) && filled($trainingStart)) {
                try {
                    $trainingEnd = Carbon::parse($trainingStart)->addDays(5)->toDateString();
                } catch (\Throwable) {
                    $trainingEnd = null;
                }
            }

            return filled($trainingStart) && filled($trainingConfirmedAt)
                && (filled($scheduledAt) || filled($trainingEnd));
        }

        if ($step === 'treinamentos') {
            if (! empty($state["candidato_{$position}_treinamentos_capacitacao"])) {
                if (! $this->hasLegacyMirroredTrainingData($state, $position)) {
                    return true;
                }
            }
            $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
            $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;

            return filled($trainingStart) && filled($trainingConfirmedAt)
                && ! $this->hasLegacyMirroredTrainingData($state, $position);
        }

        if ($step === 'assinatura') {
            return filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
        }

        if ($step === 'sgc') {
            $hasPendency = filled($state["candidato_{$position}_sgc_pendencia_descricao"] ?? null);
            $pendencyDone = $hasPendency
                ? filled($state["candidato_{$position}_sgc_data_nova_postagem"] ?? null)
                : filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);

            return filled($state["candidato_{$position}_sgc_data_postagem"] ?? null)
                && filled($state["candidato_{$position}_sgc_numero_postagem"] ?? null)
                && $pendencyDone
                && filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);
        }

        if ($step === 'liberacao') {
            return filled($state["candidato_{$position}_liberacao_orientado_data"] ?? null)
                && filled($state["candidato_{$position}_liberacao_epi_data"] ?? null)
                && filled($state["candidato_{$position}_liberacao_rota_endereco"] ?? null);
        }

        return false;
    }

    /**
     * Protege indicadores contra legado onde Treinamentos recebeu cópia automática de Exame Médico.
     */
    private function hasLegacyMirroredTrainingData(array $state, int $position): bool
    {
        $trainingStart = trim((string) ($state["candidato_{$position}_treinamentos_data_inicio"] ?? ''));
        $trainingConfirmed = trim((string) ($state["candidato_{$position}_treinamentos_data_confirmacao"] ?? ''));
        if ($trainingStart === '' || $trainingConfirmed === '') {
            return false;
        }

        $exameStart = trim((string) ($state["candidato_{$position}_exameMedico_data_inicio"] ?? ''));
        $exameConfirmed = trim((string) ($state["candidato_{$position}_exameMedico_data_confirmacao"] ?? ''));
        if ($exameStart === '' || $exameConfirmed === '') {
            return false;
        }

        $sgcPosted = filled($state["candidato_{$position}_sgc_data_postagem"] ?? null);
        $signed = filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
        if ($sgcPosted || $signed) {
            return false;
        }

        return $trainingStart === $exameStart && $trainingConfirmed === $exameConfirmed;
    }

    /**
     * Indicadores PGU (fase Treinamentos): inclui candidatos com etapa concluída
     * ou com data de início dos treinamentos já informada — sem exigir confirmação.
     * Ignora legado em que Treinamentos era cópia espelhada do Exame Médico (mesma regra da listagem RH).
     */
    private function candidateAlcancouFaseTreinamentosParaIndicadores(array $state, int $position): bool
    {
        if ($this->candidateStepDone($state, $position, 'treinamentos')) {
            return true;
        }

        $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
        if (! filled($trainingStart)) {
            return false;
        }

        if ($this->hasLegacyMirroredTrainingData($state, $position)) {
            return false;
        }

        return true;
    }

    /**
     * Funções “100%” no recorte: mobilização (Pré-PGU) cobre a necessidade (PGU), linha a linha.
     * Ignora linhas com PGU = 0 e Pré > 0 (PGU não informado).
     *
     * @param  array<int, array<string, mixed>>  $ranking
     * @return array<int, array<string, mixed>>
     */
    private function buildFuncoesPgu100(array $ranking): array
    {
        return collect($ranking)
            ->filter(function ($r) {
                $pre = (float) ($r['pre_pgu'] ?? 0);
                $pgu = (float) ($r['pgu'] ?? 0);
                if ($pre <= 0 && $pgu <= 0) {
                    return false;
                }
                if ($pgu <= 0 && $pre > 0) {
                    return false;
                }

                return (float) ($r['progress'] ?? 0) >= 100;
            })
            ->values()
            ->map(fn ($r) => [
                'codigo' => $r['codigo'] ?? null,
                'funcao' => $r['funcao'] ?? $r['function'],
                'completed' => round((float) ($r['completed'] ?? 0), 2),
            ])
            ->all();
    }

    /**
     * KPIs agregados só sobre linhas-item (sem grupos), alinhados ao histograma oficial:
     * pendência real = soma max(0, PGU − Pré); coberto = soma min(Pré, PGU).
     *
     * @param  Collection<int, ContratoHistogramaLinha>  $linhas
     * @return array<string, mixed>
     */
    private function buildKpisMaoDeObraItens(Collection $linhas): array
    {
        $accInd = ['pgu' => 0.0, 'concluidas' => 0.0, 'pendentes' => 0.0];
        $accDir = ['pgu' => 0.0, 'concluidas' => 0.0, 'pendentes' => 0.0];
        $preSemPgu = 0.0;

        foreach ($linhas as $linha) {
            $pre = (float) ($linha->pre_pgu ?? 0);
            $pgu = (float) ($linha->pgu ?? 0);
            $coberto = min($pre, $pgu);
            $pend = max($pgu - $pre, 0);
            if ($pre > 0 && $pgu <= 0) {
                $preSemPgu += $pre;
            }
            $cod = trim((string) ($linha->item_codigo ?? ''));
            $bucket = null;
            if (preg_match('/^1\.1(\.|$)/', $cod) === 1) {
                $bucket = 'ind';
            } elseif (preg_match('/^1\.2(\.|$)/', $cod) === 1) {
                $bucket = 'dir';
            }
            if ($bucket === 'ind') {
                $accInd['pgu'] += $pgu;
                $accInd['concluidas'] += $coberto;
                $accInd['pendentes'] += $pend;
            } elseif ($bucket === 'dir') {
                $accDir['pgu'] += $pgu;
                $accDir['concluidas'] += $coberto;
                $accDir['pendentes'] += $pend;
            }
        }

        $tPgu = $accInd['pgu'] + $accDir['pgu'];
        $tConc = $accInd['concluidas'] + $accDir['concluidas'];
        $tPend = $accInd['pendentes'] + $accDir['pendentes'];

        return [
            'vagas_pgu_previstas' => round($tPgu, 1),
            'vagas_concluidas_no_pgu' => round($tConc, 1),
            'vagas_pendentes_por_funcao' => round($tPend, 1),
            'vagas_pre_sem_pgu_informado' => round($preSemPgu, 1),
            'por_grupo' => [
                [
                    'grupo' => 'Equipe Indireta',
                    'pgu' => round($accInd['pgu'], 1),
                    'concluidas' => round($accInd['concluidas'], 1),
                    'pendentes' => round($accInd['pendentes'], 1),
                ],
                [
                    'grupo' => 'Equipe Direta',
                    'pgu' => round($accDir['pgu'], 1),
                    'concluidas' => round($accDir['concluidas'], 1),
                    'pendentes' => round($accDir['pendentes'], 1),
                ],
                [
                    'grupo' => 'Total',
                    'pgu' => round($tPgu, 1),
                    'concluidas' => round($tConc, 1),
                    'pendentes' => round($tPend, 1),
                ],
            ],
        ];
    }

    /**
     * Pré-PGU = mobilizado; PGU = necessidade; pendência = PGU − Pré (≥ 0); avanço = Pré/PGU (% da necessidade coberta).
     *
     * @param  Collection<int, ContratoHistogramaLinha>  $linhas
     * @return array<int, array<string, mixed>>
     */
    private function buildRankingFromLinhas($linhas): array
    {
        $out = [];

        foreach ($linhas as $linha) {
            $pre = (float) ($linha->pre_pgu ?? 0);
            $pgu = (float) ($linha->pgu ?? 0);
            $pending = (float) max($pgu - $pre, 0);
            if ($pgu > 0) {
                $progress = min(($pre / $pgu) * 100, 100);
            } elseif ($pre <= 0) {
                $progress = 100.0;
            } else {
                $progress = 0.0;
            }
            $pendingInt = (int) max(round($pending), 0);
            $status = $this->classifyStatus($pendingInt, (float) $progress);

            $codigo = $linha->item_codigo ? trim((string) $linha->item_codigo) : null;
            $funcao = trim((string) $linha->descricao);
            $label = $codigo !== null && $codigo !== '' ? $codigo.' - '.$funcao : $funcao;
            $coberto = min($pre, $pgu);

            $out[] = [
                'linha_id' => $linha->id,
                'codigo' => $codigo,
                'funcao' => $funcao,
                'function' => $label,
                'pre_pgu' => round($pre, 2),
                'pgu' => round($pgu, 2),
                'pending' => $pendingInt,
                'completed' => round($coberto, 2),
                'progress' => round((float) $progress, 1),
                'sem_pgu_informado' => $pre > 0 && $pgu <= 0,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
            ];
        }

        usort($out, fn ($a, $b) => $b['pending'] <=> $a['pending']);

        return $out;
    }

    private function classifyStatus(int $pending, float $progress): string
    {
        if ($pending >= 50 || $progress < 15) {
            return 'critical';
        }
        if ($pending >= 25 || $progress < 35) {
            return 'high';
        }
        if ($pending >= 10 || $progress < 70) {
            return 'warning';
        }

        return 'success';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'critical' => 'Crítico',
            'high' => 'Alto',
            'warning' => 'Atenção',
            'success' => 'Controlado',
            'neutral' => 'Concentrado',
            default => '—',
        };
    }

    /**
     * Top N funções com pendência + linha "Outras funções" (apresentação executiva).
     *
     * @param  array<int, array<string, mixed>>  $ranking
     * @return array<int, array<string, mixed>>
     */
    private function buildRankingExecutivo(array $ranking, int $topN): array
    {
        $items = collect($ranking)
            ->map(function (array $r) {
                $pendPguMenosPre = (int) ($r['pending'] ?? 0);
                $semPgu = ! empty($r['sem_pgu_informado']);
                $pendVisual = $pendPguMenosPre;
                $tipo = 'falta_mobilizar';
                if ($pendVisual === 0 && $semPgu) {
                    $pendVisual = (int) max(round((float) ($r['pre_pgu'] ?? 0)), 1);
                    $tipo = 'pgu_nao_informado';
                }

                return array_merge($r, [
                    'pending' => $pendVisual,
                    'pending_pgu_menos_pre' => $pendPguMenosPre,
                    'tipo_pendencia' => $tipo,
                ]);
            })
            ->filter(fn ($r) => (int) ($r['pending'] ?? 0) > 0)
            ->sortByDesc('pending')
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $top = $items->take($topN)->values();
        $out = $top->map(fn ($r) => [
            'codigo' => $r['codigo'] ?? null,
            'funcao' => $r['funcao'] ?? $r['function'],
            'pending' => (int) $r['pending'],
            'pending_pgu_menos_pre' => (int) ($r['pending_pgu_menos_pre'] ?? 0),
            'tipo_pendencia' => (string) ($r['tipo_pendencia'] ?? 'falta_mobilizar'),
            'progress' => round((float) ($r['progress'] ?? 0), 1),
            'status' => $r['status'],
            'status_label' => $r['status_label'],
        ])->all();

        $rest = $items->skip($topN);
        if ($rest->isEmpty()) {
            return $out;
        }

        $sumOutras = (int) $rest->sum('pending');
        $out[] = [
            'codigo' => null,
            'funcao' => 'Outras funções',
            'pending' => $sumOutras,
            'pending_pgu_menos_pre' => (int) $rest->sum('pending_pgu_menos_pre'),
            'tipo_pendencia' => 'agregado',
            'progress' => round((float) $rest->avg('progress'), 1),
            'status' => 'neutral',
            'status_label' => $this->statusLabel('neutral'),
        ];

        return $out;
    }

    /**
     * Pareto apenas sobre o conjunto executivo (até 6 barras no slide).
     *
     * @param  array<int, array<string, mixed>>  $executivo
     * @return array<int, array<string, mixed>>
     */
    private function buildParetoExecutivo(array $executivo): array
    {
        $total = (int) collect($executivo)->sum('pending');
        if ($total === 0) {
            return [];
        }

        $accumulated = 0;

        return collect($executivo)->map(function ($item) use ($total, &$accumulated) {
            $accumulated += (int) $item['pending'];

            return [
                'funcao' => $item['funcao'],
                'pending' => (int) $item['pending'],
                'accumulated' => round(($accumulated / $total) * 100, 1),
            ];
        })->values()->all();
    }

    private function statusRiskScore(string $status): int
    {
        return match ($status) {
            'critical' => 100,
            'high' => 75,
            'warning' => 50,
            'success' => 15,
            'neutral' => 35,
            default => 40,
        };
    }

    private function deadlineRisk(?Carbon $deadline, float $progress): string
    {
        if ($deadline === null) {
            return 'undefined';
        }

        $daysLeft = (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        if ($daysLeft < 0) {
            return 'expired';
        }
        if ($daysLeft <= 3 && $progress < 80) {
            return 'critical';
        }
        if ($daysLeft <= 7 && $progress < 70) {
            return 'high';
        }
        if ($progress >= 90) {
            return 'low';
        }

        return 'medium';
    }

    private function deadlineRiskLabel(string $risk): string
    {
        return match ($risk) {
            'undefined' => 'Indefinido',
            'expired' => 'Atrasado',
            'critical' => 'Alto',
            'high' => 'Elevado',
            'medium' => 'Moderado',
            'low' => 'Baixo',
            default => '—',
        };
    }

    /**
     * Série mensal a partir do histograma salvo por competência.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTrend(
        string $contrato,
        Carbon $competenciaMes,
        float $currentCompleted,
        float $currentPending,
        float $currentProgress,
        int $meses
    ): array
    {
        $out = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $m = $competenciaMes->copy()->subMonths($i)->startOfMonth();
            $vagas = RecrutamentoVaga::query()
                ->where('contrato', $contrato)
                ->where(function ($query) use ($m) {
                    $query->whereMonth('data_solicitacao', $m->month)
                        ->whereYear('data_solicitacao', $m->year)
                        ->orWhere(function ($q) use ($m) {
                            $q->whereNull('data_solicitacao')
                                ->whereMonth('created_at', $m->month)
                                ->whereYear('created_at', $m->year);
                        });
                })
                ->get();

            if ($vagas->isEmpty()) {
                $out[] = [
                    'date' => $m->format('m/Y'),
                    'completed' => 0,
                    'pending' => 0,
                    'progress' => 0.0,
                ];

                continue;
            }

            $ranking = $this->buildRankingFromVagas($vagas);
            $kpMes = $this->buildKpisRecrutamento($vagas);
            $sumCompleted = $kpMes['vagas_concluidas_no_pgu'];
            $sumPending = $kpMes['vagas_pendentes_por_funcao'];
            $avgProgress = $ranking === [] ? 0.0 : round(collect($ranking)->avg('progress'), 1);

            $out[] = [
                'date' => $m->format('m/Y'),
                'completed' => $sumCompleted,
                'pending' => $sumPending,
                'progress' => $avgProgress,
            ];
        }

        return [
            'points' => $out,
            'note' => 'Série mensal por competência com base no avanço das vagas do recrutamento.',
        ];
    }

    /**
     * Funil «Avanço de Contratações»: mesma lógica cumulativa de {@see buildCurrentPhaseProgress()}
     * (cartão «Maturidade do Fluxo PGU»). Cada indicador conta candidatos aprovados que já atingiram a etapa.
     * «Triagem» = base aprovada (como «Vagas preenchidas» na maturidade). «Teste prático» sem campo na ficha → 0.
     *
     * @return array{total: int, etapas_monitoradas: int, itens: list<array{key: string, label: string, valor: int, icon: string}>}
     */
    private function buildContratacoesFunil(Collection $vagas): array
    {
        $order = [
            'triagem' => ['label' => 'TRIAGEM / AVALIAÇÃO RECRUTAMENTO', 'icon' => 'user-search'],
            'exame' => ['label' => 'EXAME MÉDICO', 'icon' => 'stethoscope'],
            'teste_pratico' => ['label' => 'TESTE PRÁTICO', 'icon' => 'clipboard-check'],
            'treinamento' => ['label' => 'TREINAMENTO INTRODUTÓRIO', 'icon' => 'graduation-cap'],
            'assinatura' => ['label' => 'ASSINATURA DOCUMENTAL', 'icon' => 'file-signature'],
            'sgc_e_liberacao' => ['label' => 'SGC E LIBERAÇÃO', 'icon' => 'shield-check'],
        ];

        $triagem = 0;
        $exame = 0;
        $treinamento = 0;
        $assinatura = 0;
        $sgcLiberacao = 0;
        $totalAprovados = 0;

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            foreach ($this->approvedCandidates($vaga) as $c) {
                $position = (int) $c['position'];
                $totalAprovados++;
                $triagem++;
                if ($this->candidateStepDone($state, $position, 'exame_medico')) {
                    $exame++;
                }
                if ($this->candidateAlcancouFaseTreinamentosParaIndicadores($state, $position)) {
                    $treinamento++;
                }
                if ($this->candidateStepDone($state, $position, 'assinatura')) {
                    $assinatura++;
                }
                if ($this->candidateStepDone($state, $position, 'sgc')
                    || $this->candidateStepDone($state, $position, 'liberacao')) {
                    $sgcLiberacao++;
                }
            }
        }

        $counts = [
            'triagem' => $triagem,
            'exame' => $exame,
            'teste_pratico' => 0,
            'treinamento' => $treinamento,
            'assinatura' => $assinatura,
            'sgc_e_liberacao' => $sgcLiberacao,
        ];

        $itens = [];
        foreach ($order as $key => $meta) {
            $itens[] = [
                'key' => $key,
                'label' => $meta['label'],
                'valor' => $counts[$key],
                'icon' => $meta['icon'],
            ];
        }

        return [
            'total' => $totalAprovados,
            'etapas_monitoradas' => count($order),
            'itens' => $itens,
        ];
    }

    /**
     * @param  Collection<int, RecrutamentoVaga>  $vagas
     * @return array<int, array{fase:string,valor:int}>
     */
    private function buildCurrentPhaseProgress(Collection $vagas): array
    {
        $counts = [
            'recrutamento' => 0,
            'exame_medico' => 0,
            'treinamentos' => 0,
            'assinatura_documental' => 0,
            'sgc' => 0,
            'liberacao' => 0,
        ];

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            $approved = $this->approvedCandidates($vaga);

            foreach ($approved as $candidate) {
                $position = (int) $candidate['position'];
                $counts['recrutamento']++;
                if ($this->candidateStepDone($state, $position, 'exame_medico')) {
                    $counts['exame_medico']++;
                }
                if ($this->candidateAlcancouFaseTreinamentosParaIndicadores($state, $position)) {
                    $counts['treinamentos']++;
                }
                if ($this->candidateStepDone($state, $position, 'assinatura')) {
                    $counts['assinatura_documental']++;
                }
                if ($this->candidateStepDone($state, $position, 'sgc')) {
                    $counts['sgc']++;
                }
                if ($this->candidateStepDone($state, $position, 'liberacao')) {
                    $counts['liberacao']++;
                }
            }
        }

        return [
            ['fase' => 'Recrutamento', 'valor' => $counts['recrutamento']],
            ['fase' => 'Exame Médico', 'valor' => $counts['exame_medico']],
            ['fase' => 'Treinamentos', 'valor' => $counts['treinamentos']],
            ['fase' => 'Assinatura documental', 'valor' => $counts['assinatura_documental']],
            ['fase' => 'Postagem SGC', 'valor' => $counts['sgc']],
            ['fase' => 'Liberação', 'valor' => $counts['liberacao']],
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function buildPhaseTrend(string $contrato, Carbon $competenciaMes, int $meses): array
    {
        $out = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $m = $competenciaMes->copy()->subMonths($i)->startOfMonth();
            $vagas = RecrutamentoVaga::query()
                ->where('contrato', $contrato)
                ->where(function ($query) use ($m) {
                    $query->whereMonth('data_solicitacao', $m->month)
                        ->whereYear('data_solicitacao', $m->year)
                        ->orWhere(function ($q) use ($m) {
                            $q->whereNull('data_solicitacao')
                                ->whereMonth('created_at', $m->month)
                                ->whereYear('created_at', $m->year);
                        });
                })
                ->get();

            $counts = $this->buildCurrentPhaseProgress($vagas);
            $row = ['date' => $m->format('m/Y')];
            foreach ($counts as $count) {
                $fase = (string) ($count['fase'] ?? '');
                $valor = (int) ($count['valor'] ?? 0);
                if ($fase === 'Recrutamento') {
                    $row['recrutamento'] = $valor;
                } elseif ($fase === 'Exame Médico') {
                    $row['exame_medico'] = $valor;
                } elseif ($fase === 'Treinamentos') {
                    $row['treinamentos'] = $valor;
                } elseif ($fase === 'Assinatura documental') {
                    $row['assinatura_documental'] = $valor;
                } elseif ($fase === 'Postagem SGC') {
                    $row['sgc'] = $valor;
                } elseif ($fase === 'Liberação') {
                    $row['liberacao'] = $valor;
                }
            }

            $row += [
                'recrutamento' => 0,
                'exame_medico' => 0,
                'treinamentos' => 0,
                'assinatura_documental' => 0,
                'sgc' => 0,
                'liberacao' => 0,
            ];
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Heatmap executivo: funções do ranking executivo x indicadores (máx. 6 linhas).
     *
     * @param  array<int, array<string, mixed>>  $executivo
     * @return array<int, array<string, mixed>>
     */
    private function buildHeatmapExecutivo(array $executivo): array
    {
        $heatmap = [];

        foreach ($executivo as $row) {
            $nome = (string) $row['funcao'];
            $pendingNorm = min((int) $row['pending'], 100);
            $status = (string) $row['status'];
            $progressVal = (int) round(min(max((float) ($row['progress'] ?? 0), 0), 100));

            $heatmap[] = ['funcao' => $nome, 'axis' => 'Pendências', 'value' => $pendingNorm];
            $heatmap[] = ['funcao' => $nome, 'axis' => 'Avanço', 'value' => $progressVal];
            $heatmap[] = ['funcao' => $nome, 'axis' => 'Risco', 'value' => $this->statusRiskScore($status)];
        }

        return $heatmap;
    }

    /**
     * @param  array<int, array<string, mixed>>  $executivo
     * @return array<int, array{name: string, value: int}>
     */
    private function buildTreemapPendencias(array $executivo): array
    {
        return collect($executivo)
            ->map(fn ($row) => [
                'name' => (string) $row['funcao'],
                'value' => (int) $row['pending'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $trend
     */
    private function progressDeltaFromTrend(array $trend): ?float
    {
        if (count($trend) < 2) {
            return null;
        }

        $last = (float) ($trend[count($trend) - 1]['progress'] ?? 0);
        $prev = (float) ($trend[count($trend) - 2]['progress'] ?? 0);

        return round($last - $prev, 1);
    }

    /**
     * Movimentações reais do ciclo com base no histórico intraday do histograma.
     *
     * @return array<int, array{date:string,mov:string,qtd:string,impactoPos:bool,impacto:string}>
     */
    private function buildCycleMovementsFromHistorico(
        string $contrato,
        Carbon $competenciaMes,
        Carbon $cycleStartAt,
        ?Carbon $deadline
    ): array {
        $endAt = Carbon::today()->endOfDay();
        if ($deadline && $deadline->lt($endAt)) {
            $endAt = $deadline->copy()->endOfDay();
        }

        $rows = DB::table('contrato_histograma_historicos')
            ->where('contrato', $contrato)
            ->whereDate('competencia', $competenciaMes->toDateString())
            ->where('snapshot_at', '>=', $cycleStartAt->copy()->startOfDay())
            ->where('snapshot_at', '<=', $endAt)
            ->orderBy('snapshot_at')
            ->get([
                'snapshot_at',
                'completed',
                'pending',
                'progress',
            ]);

        if ($rows->isEmpty()) {
            return [];
        }

        $out = [];
        $prev = null;
        foreach ($rows as $r) {
            $completed = (float) ($r->completed ?? 0);
            $progress = (float) ($r->progress ?? 0);
            $dt = Carbon::parse((string) $r->snapshot_at);

            if ($prev === null) {
                $out[] = [
                    'date' => $dt->format('d/m/Y'),
                    'mov' => 'Início do período',
                    'qtd' => '+' . (string) ((int) round($completed)),
                    'impactoPos' => $progress >= 0,
                    'impacto' => '+' . number_format($progress, 1, ',', '') . ' p.p.',
                ];
                $prev = ['completed' => $completed, 'progress' => $progress];
                continue;
            }

            $dCompleted = (float) $completed - (float) $prev['completed'];
            $dProgress = (float) $progress - (float) $prev['progress'];
            if (abs($dCompleted) < 0.00001 && abs($dProgress) < 0.00001) {
                continue;
            }

            $out[] = [
                'date' => $dt->format('d/m/Y'),
                'mov' => $dCompleted >= 0 ? 'Atualização de consolidação' : 'Reclassificação de consolidação',
                'qtd' => ($dCompleted >= 0 ? '+' : '−') . (string) ((int) round(abs($dCompleted))),
                'impactoPos' => $dProgress >= 0,
                'impacto' => ($dProgress >= 0 ? '+' : '') . number_format($dProgress, 1, ',', '') . ' p.p.',
            ];
            $prev = ['completed' => $completed, 'progress' => $progress];
        }

        return array_slice($out, -6);
    }
}
