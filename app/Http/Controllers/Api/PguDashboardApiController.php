<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Http\Controllers\Controller;
use App\Models\ContratoHistogramaLinha;
use App\Models\ContratoHistogramaRecorte;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PguDashboardApiController extends Controller
{
    use ContratosHistogramaCatalog;

    public function index(Request $request): JsonResponse
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
        $deadline = isset($data['data_limite_etapa_2']) && $data['data_limite_etapa_2']
            ? Carbon::parse($data['data_limite_etapa_2'])->startOfDay()
            : null;
        if ($deadline === null) {
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $data['contrato'])
                ->whereDate('competencia', $competenciaMes->toDateString())
                ->first();
            if ($recorte?->data_limite_etapa_2) {
                $deadline = $recorte->data_limite_etapa_2->copy()->startOfDay();
            }
        }

        $linhas = ContratoHistogramaLinha::query()
            ->where('contrato', $data['contrato'])
            ->whereDate('competencia', $competenciaMes->toDateString())
            ->itensParaMetricasPgu()
            ->orderBy('ordem')
            ->get();

        $totaisMaoDeObra = ContratoHistogramaLinha::query()
            ->where('contrato', $data['contrato'])
            ->whereDate('competencia', $competenciaMes->toDateString())
            ->selectRaw('COALESCE(SUM(mobilizacao), 0) as sum_mobilizacao, COALESCE(SUM(pre_pgu), 0) as sum_pre_pgu, COALESCE(SUM(pgu), 0) as sum_pgu, COALESCE(SUM(pos_pgu), 0) as sum_pos_pgu')
            ->first();

        $ranking = $this->buildRankingFromLinhas($linhas);
        $rankingExecutivo = $this->buildRankingExecutivo($ranking, 5);
        $paretoExecutivo = $this->buildParetoExecutivo($rankingExecutivo);
        $trend = $this->buildTrend($data['contrato'], $competenciaMes, 6);
        $heatmap = $this->buildHeatmapExecutivo($rankingExecutivo);
        $treemap = $this->buildTreemapPendencias($rankingExecutivo);
        $funcoesPgu100 = $this->buildFuncoesPgu100($ranking);

        $overallProgress = $ranking === [] ? 0.0 : round(collect($ranking)->avg('progress'), 1);
        $totalPending = (int) round(collect($ranking)->sum('pending'));
        $totalFunctions = count($ranking);
        $completedFunctions = collect($ranking)->filter(fn ($r) => ($r['progress'] ?? 0) >= 100)->count();
        $criticalFunctions = collect($ranking)->where('status', 'critical')->count();

        $deadlineRisk = $this->deadlineRisk($deadline, $overallProgress);
        $progressDelta = $this->progressDeltaFromTrend($trend);
        $itensAtrasadosFase2 = 0;
        if ($deadline && Carbon::today()->gt($deadline->copy()->startOfDay())) {
            $itensAtrasadosFase2 = $linhas->filter(function (ContratoHistogramaLinha $l) {
                $pre = (float) $l->pre_pgu;
                $pgu = (float) $l->pgu;

                return $pre > 0 && $pgu < $pre;
            })->count();
        }

        return response()->json([
            'summary' => [
                'overall_progress' => $overallProgress,
                'overall_progress_delta' => $progressDelta,
                'total_pending' => $totalPending,
                'total_functions' => $totalFunctions,
                'completed_functions' => $completedFunctions,
                'critical_functions' => $criticalFunctions,
                'deadline_risk' => $deadlineRisk,
                'deadline_risk_label' => $this->deadlineRiskLabel($deadlineRisk),
                'deadline_date' => $deadline?->toDateString(),
                'itens_atrasados_fase2' => $itensAtrasadosFase2,
            ],
            'donut_avanco' => [
                'overall' => $overallProgress,
                'avanco' => $overallProgress,
                'pendente' => round(max(0, 100 - $overallProgress), 1),
            ],
            'ranking' => $ranking,
            'ranking_executivo' => $rankingExecutivo,
            'pareto_executivo' => $paretoExecutivo,
            'trend' => $trend,
            'trend_notas' => 'Série por competência mensal (histograma salvo). Evolução diária exige registro futuro por dia.',
            'heatmap' => $heatmap,
            'treemap_pendencias' => $treemap,
            'funcoes_pgu_100' => $funcoesPgu100,
            'mao_de_obra' => [
                'mobilizacao' => round((float) ($totaisMaoDeObra?->sum_mobilizacao ?? 0), 2),
                'pre_pgu' => round((float) ($totaisMaoDeObra?->sum_pre_pgu ?? 0), 2),
                'pgu' => round((float) ($totaisMaoDeObra?->sum_pgu ?? 0), 2),
                'pos_pgu' => round((float) ($totaisMaoDeObra?->sum_pos_pgu ?? 0), 2),
            ],
        ]);
    }

    /**
     * Funções com avanço PGU em 100% no recorte (pré-PGU totalmente coberto pela PGU).
     *
     * @param  array<int, array<string, mixed>>  $ranking
     * @return array<int, array<string, mixed>>
     */
    private function buildFuncoesPgu100(array $ranking): array
    {
        return collect($ranking)
            ->filter(fn ($r) => (float) ($r['progress'] ?? 0) >= 100)
            ->values()
            ->map(fn ($r) => [
                'codigo' => $r['codigo'] ?? null,
                'funcao' => $r['funcao'] ?? $r['function'],
                'completed' => (int) ($r['completed'] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, ContratoHistogramaLinha>  $linhas
     * @return array<int, array<string, mixed>>
     */
    private function buildRankingFromLinhas($linhas): array
    {
        $out = [];

        foreach ($linhas as $linha) {
            $etapa1 = (float) ($linha->pre_pgu ?? 0);
            $etapa2 = (float) ($linha->pgu ?? 0);
            $pending = (float) max($etapa1 - $etapa2, 0);
            $progress = $etapa1 > 0 ? min(($etapa2 / $etapa1) * 100, 100) : 0.0;
            $pendingInt = (int) ceil($pending);
            $status = $this->classifyStatus($pendingInt, (float) $progress);

            $codigo = $linha->item_codigo ? trim((string) $linha->item_codigo) : null;
            $funcao = trim((string) $linha->descricao);
            $label = $codigo !== null && $codigo !== '' ? $codigo.' - '.$funcao : $funcao;

            $out[] = [
                'linha_id' => $linha->id,
                'codigo' => $codigo,
                'funcao' => $funcao,
                'function' => $label,
                'pre_pgu' => round($etapa1, 2),
                'pgu' => round($etapa2, 2),
                'pending' => $pendingInt,
                'completed' => (int) round($etapa2),
                'progress' => round((float) $progress, 1),
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
    private function buildTrend(string $contrato, Carbon $ateMes, int $meses): array
    {
        $out = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $m = $ateMes->copy()->subMonths($i)->startOfMonth();
            $linhas = ContratoHistogramaLinha::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $m->toDateString())
                ->itensParaMetricasPgu()
                ->get();

            if ($linhas->isEmpty()) {
                $out[] = [
                    'date' => $m->format('m/Y'),
                    'completed' => 0,
                    'pending' => 0,
                    'progress' => 0.0,
                ];

                continue;
            }

            $ranking = $this->buildRankingFromLinhas($linhas);
            $sumCompleted = (int) collect($ranking)->sum('completed');
            $sumPending = (int) collect($ranking)->sum('pending');
            $avgProgress = $ranking === [] ? 0.0 : round(collect($ranking)->avg('progress'), 1);

            $out[] = [
                'date' => $m->format('m/Y'),
                'completed' => $sumCompleted,
                'pending' => $sumPending,
                'progress' => $avgProgress,
            ];
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
}
