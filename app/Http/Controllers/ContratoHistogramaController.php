<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Models\ContratoHistogramaLinha;
use App\Models\ContratoHistogramaRecorte;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContratoHistogramaController extends Controller
{
    use ContratosHistogramaCatalog;

    private function isPublicRoute(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'publico.');
    }

    public function index(Request $request)
    {
        $competenciaMes = $request->input('competencia', now()->format('Y-m'));
        $competencia = Carbon::createFromFormat('Y-m', $competenciaMes)->startOfMonth()->toDateString();

        $contratos = $this->contratosDisponiveis();
        $contratoSelecionado = $request->input('contrato', $contratos[0] ?? '');

        $linhas = collect();
        $recorte = null;
        if ($contratoSelecionado !== '') {
            $linhas = ContratoHistogramaLinha::query()
                ->where('contrato', $contratoSelecionado)
                ->whereDate('competencia', $competencia)
                ->orderBy('ordem')
                ->get();

            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contratoSelecionado)
                ->whereDate('competencia', $competencia)
                ->first();
        }

        $dataLimiteEtapa2 = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        $hoje = Carbon::today();
        $limiteCarbon = $recorte?->data_limite_etapa_2?->copy()->startOfDay();
        $contagemAtrasadas = ($limiteCarbon && $hoje->gt($limiteCarbon))
            ? $linhas->filter(fn (ContratoHistogramaLinha $l) => $this->itemAtrasadoTransicaoFase2($l, $limiteCarbon))->count()
            : 0;
        $situacaoPrazo = null;
        $diasAteLimite = null;
        if ($limiteCarbon) {
            if ($hoje->gt($limiteCarbon)) {
                $situacaoPrazo = $contagemAtrasadas > 0 ? 'vencido_atraso' : 'vencido_ok';
            } else {
                $situacaoPrazo = 'futuro';
                $diasAteLimite = (int) $hoje->diffInDays($limiteCarbon, false);
            }
        }

        $totais = [
            'mobilizacao' => (float) $linhas->sum('mobilizacao'),
            'pre_pgu' => (float) $linhas->sum('pre_pgu'),
            'pgu' => (float) $linhas->sum('pgu'),
            'pos_pgu' => (float) $linhas->sum('pos_pgu'),
            'desmobilizacao' => (float) $linhas->sum('desmobilizacao'),
        ];

        return view('contratos.histograma', [
            'contratos' => $contratos,
            'contratoSelecionado' => $contratoSelecionado,
            'competenciaMes' => $competenciaMes,
            'linhas' => $linhas,
            'totais' => $totais,
            'dataLimiteEtapa2' => $dataLimiteEtapa2,
            'histogramaHoje' => $hoje->toDateString(),
            'contagemAtrasadas' => $contagemAtrasadas,
            'situacaoPrazo' => $situacaoPrazo,
            'diasAteLimite' => $diasAteLimite,
            'layout' => $this->isPublicRoute($request) ? 'layouts.public-contratos' : 'layouts.app',
        ]);
    }

    public function salvar(Request $request)
    {
        $data = $request->validate([
            'contrato' => ['required', 'string', 'max:255'],
            'competencia' => ['required', 'date_format:Y-m'],
            'data_limite_etapa_2' => ['nullable', 'date'],
            'linhas' => ['nullable', 'array'],
            'linhas.*.tipo_linha' => ['nullable', 'in:grupo,item'],
            'linhas.*.item_codigo' => ['nullable', 'string', 'max:30'],
            'linhas.*.descricao' => ['required', 'string', 'max:255'],
            'linhas.*.unidade' => ['nullable', 'string', 'max:20'],
            'linhas.*.mobilizacao' => ['nullable', 'numeric', 'min:0'],
            'linhas.*.pre_pgu' => ['nullable', 'numeric', 'min:0'],
            'linhas.*.pgu' => ['nullable', 'numeric', 'min:0'],
            'linhas.*.pos_pgu' => ['nullable', 'numeric', 'min:0'],
            'linhas.*.desmobilizacao' => ['nullable', 'numeric', 'min:0'],
        ]);

        $competencia = Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth()->toDateString();
        $linhas = collect($data['linhas'] ?? [])
            ->filter(fn ($l) => filled($l['descricao'] ?? null))
            ->values();

        DB::transaction(function () use ($data, $competencia, $linhas) {
            ContratoHistogramaLinha::query()
                ->where('contrato', $data['contrato'])
                ->whereDate('competencia', $competencia)
                ->delete();

            foreach ($linhas as $index => $linha) {
                ContratoHistogramaLinha::create([
                    'contrato' => $data['contrato'],
                    'competencia' => $competencia,
                    'tipo_linha' => $linha['tipo_linha'] ?? 'item',
                    'ordem' => $index + 1,
                    'item_codigo' => $linha['item_codigo'] ?? null,
                    'descricao' => $linha['descricao'],
                    'acao_recomendada' => null,
                    'responsavel' => null,
                    'unidade' => $linha['unidade'] ?? 'Unid.',
                    'mobilizacao' => (float) ($linha['mobilizacao'] ?? 0),
                    'pre_pgu' => (float) ($linha['pre_pgu'] ?? 0),
                    'pgu' => (float) ($linha['pgu'] ?? 0),
                    'pos_pgu' => (float) ($linha['pos_pgu'] ?? 0),
                    'desmobilizacao' => (float) ($linha['desmobilizacao'] ?? 0),
                ]);
            }

            $recorte = ContratoHistogramaRecorte::query()->updateOrCreate(
                [
                    'contrato' => $data['contrato'],
                    'competencia' => $competencia,
                ],
                [
                    'data_limite_etapa_2' => ! empty($data['data_limite_etapa_2'])
                        ? Carbon::parse($data['data_limite_etapa_2'])->toDateString()
                        : null,
                ]
            );

            $metrics = $this->buildSnapshotMetrics($linhas);

            $hasHistory = DB::table('contrato_histograma_historicos')
                ->where('contrato', $data['contrato'])
                ->whereDate('competencia', $competencia)
                ->exists();

            if (! $hasHistory) {
                $baselineAt = $recorte->created_at ?? now();
                DB::table('contrato_histograma_historicos')->insert([
                    'contrato' => $data['contrato'],
                    'competencia' => $competencia,
                    'snapshot_date' => Carbon::parse($baselineAt)->toDateString(),
                    'snapshot_at' => Carbon::parse($baselineAt),
                    'total_functions' => $metrics['total_functions'],
                    'completed' => $metrics['completed'],
                    'pending' => $metrics['pending'],
                    'progress' => $metrics['progress'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }

            DB::table('contrato_histograma_historicos')->insert([
                'contrato' => $data['contrato'],
                'competencia' => $competencia,
                'snapshot_date' => Carbon::today()->toDateString(),
                'snapshot_at' => now(),
                'total_functions' => $metrics['total_functions'],
                'completed' => $metrics['completed'],
                'pending' => $metrics['pending'],
                'progress' => $metrics['progress'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        });

        return redirect()
            ->route('contratos.histograma.index', [
                'contrato' => $data['contrato'],
                'competencia' => $data['competencia'],
            ])
            ->with('success', 'Histograma salvo com sucesso.');
    }

    /**
     * Após a data limite: item ainda com pendência real (necessidade PGU maior que mobilização Pré-PGU).
     */
    private function itemAtrasadoTransicaoFase2(ContratoHistogramaLinha $linha, Carbon $limiteDia): bool
    {
        if (($linha->tipo_linha ?? '') === 'grupo') {
            return false;
        }
        if (Carbon::today()->lte($limiteDia)) {
            return false;
        }
        $pre = (float) $linha->pre_pgu;
        $pgu = (float) $linha->pgu;

        return $pgu > $pre + 0.00001;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $linhas
     * @return array{total_functions:int,completed:float,pending:float,progress:float}
     */
    private function buildSnapshotMetrics($linhas): array
    {
        $itens = $linhas->filter(fn ($linha) => ($linha['tipo_linha'] ?? 'item') !== 'grupo');

        $totalFunctions = $itens->count();
        $completed = 0.0;
        $pending = 0.0;
        $sumProgress = 0.0;

        foreach ($itens as $linha) {
            $pre = (float) ($linha['pre_pgu'] ?? 0);
            $pgu = (float) ($linha['pgu'] ?? 0);
            $completed += min($pre, $pgu);
            $pending += max($pgu - $pre, 0);

            if ($pgu > 0) {
                $progress = min(($pre / $pgu) * 100, 100);
            } elseif ($pre <= 0) {
                $progress = 100.0;
            } else {
                $progress = 0.0;
            }
            $sumProgress += $progress;
        }

        return [
            'total_functions' => $totalFunctions,
            'completed' => round($completed, 2),
            'pending' => round($pending, 2),
            'progress' => $totalFunctions > 0 ? round($sumProgress / $totalFunctions, 2) : 0.0,
        ];
    }
}
