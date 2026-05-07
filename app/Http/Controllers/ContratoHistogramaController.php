<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Models\ContratoHistogramaLinha;
use App\Models\ContratoHistogramaRecorte;
use App\Models\RecrutamentoVaga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $linhaRecrutamentoStatus = [];
        if ($contratoSelecionado !== '') {
            $linhas = ContratoHistogramaLinha::query()
                ->where('contrato', $contratoSelecionado)
                ->whereDate('competencia', $competencia)
                ->orderBy('ordem')
                ->get();

            if ($linhas->isNotEmpty()) {
                $linhasArray = $linhas->map(function (ContratoHistogramaLinha $linha) {
                    return [
                        'tipo_linha' => $linha->tipo_linha,
                        'item_codigo' => $linha->item_codigo,
                        'descricao' => $linha->descricao,
                        'unidade' => $linha->unidade,
                        'mobilizacao' => (float) $linha->mobilizacao,
                        'pre_pgu' => (float) $linha->pre_pgu,
                        'pgu' => (float) $linha->pgu,
                        'pos_pgu' => (float) $linha->pos_pgu,
                        'desmobilizacao' => (float) $linha->desmobilizacao,
                    ];
                });

                // Garante sincronização de histogramas já cadastrados sem depender de novo salvamento manual.
                $this->syncRecrutamentoFromHistograma($contratoSelecionado, $competencia, $linhasArray);
            }

            $linhaRecrutamentoStatus = $this->buildLinhaRecrutamentoStatus($contratoSelecionado, $competenciaMes, $linhas);

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

        $linhasItem = $linhas->filter(fn (ContratoHistogramaLinha $linha) => ($linha->tipo_linha ?? 'item') !== 'grupo');
        $totais = [
            'mobilizacao' => (float) $linhasItem->sum('mobilizacao'),
            'pre_pgu' => (float) $linhasItem->sum('pre_pgu'),
            'pgu' => (float) $linhasItem->sum('pgu'),
            'pos_pgu' => (float) $linhasItem->sum('pos_pgu'),
            'desmobilizacao' => (float) $linhasItem->sum('desmobilizacao'),
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
            'linhaRecrutamentoStatus' => $linhaRecrutamentoStatus,
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

            $this->syncRecrutamentoFromHistograma($data['contrato'], $competencia, $linhas);
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

    /**
     * Gera/atualiza vagas de recrutamento a partir dos itens do histograma.
     * O histograma passa a ser previsão de quantitativo por função (coluna Pré-PGU).
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $linhas
     */
    private function syncRecrutamentoFromHistograma(string $contrato, string $competencia, $linhas): void
    {
        $competenciaYm = Carbon::parse($competencia)->format('Y-m');
        $funcoes = collect($linhas)
            ->filter(fn ($linha) => ($linha['tipo_linha'] ?? 'item') !== 'grupo')
            ->map(function ($linha) {
                $descricao = trim((string) ($linha['descricao'] ?? ''));
                $codigo = trim((string) ($linha['item_codigo'] ?? ''));
                if ($descricao === '') {
                    $descricao = $codigo !== '' ? "Função {$codigo}" : 'Função sem descrição';
                }

                return [
                    'descricao' => $descricao,
                    'pre_pgu' => (float) ($linha['pre_pgu'] ?? 0),
                    'item_codigo' => $codigo,
                ];
            })
            ->groupBy('descricao')
            ->map(function ($rows, $descricao) {
                return [
                    'descricao' => (string) $descricao,
                    'pre_total' => (float) collect($rows)->sum('pre_pgu'),
                    'item_codigo' => (string) (collect($rows)->pluck('item_codigo')->filter()->first() ?? ''),
                ];
            })
            ->values();

        $existentes = RecrutamentoVaga::query()
            ->where('contrato', $contrato)
            ->where('form_state->origem_histograma', true)
            ->where('form_state->origem_histograma_competencia', $competenciaYm)
            ->get()
            ->groupBy(fn (RecrutamentoVaga $vaga) => mb_strtolower(trim((string) $vaga->titulo)));

        $keepIds = [];

        foreach ($funcoes as $funcao) {
            $quantidadePlanejada = (int) ceil((float) $funcao['pre_total']);
            if ($quantidadePlanejada <= 0) {
                continue;
            }

            $descricao = (string) $funcao['descricao'];
            $itemCodigo = (string) $funcao['item_codigo'];
            $origemKey = implode('|', ['histograma', $contrato, $competenciaYm, $descricao]);
            $tituloKey = mb_strtolower(trim($descricao));

            $vagaExistente = optional($existentes->get($tituloKey))->first();

            if ($vagaExistente) {
                $state = $vagaExistente->form_state ?? [];
                $state['vaga_quantidade'] = (string) $quantidadePlanejada;
                $state['vaga_contrato'] = $contrato;
                $state['origem_histograma_pre_total'] = (float) $funcao['pre_total'];

                $vagaExistente->update([
                    'titulo' => $descricao,
                    'quantidade' => $quantidadePlanejada,
                    'contrato' => $contrato,
                    'tipo' => $vagaExistente->tipo ?: 'Nova vaga',
                    'status' => $vagaExistente->status ?: 'Em abertura',
                    'form_state' => $state,
                ]);
                $keepIds[] = $vagaExistente->id;
                continue;
            }

            $created = RecrutamentoVaga::query()->create([
                'titulo' => $descricao,
                'quantidade' => $quantidadePlanejada,
                'prioridade' => null,
                'tipo' => 'Nova vaga',
                'contrato' => $contrato,
                'gestor' => null,
                'local' => null,
                'data_solicitacao' => now()->toDateString(),
                'previsao_inicio' => null,
                'salario' => null,
                'status' => 'Em abertura',
                'descricao' => 'Gerada automaticamente a partir do histograma.',
                'requisitos' => null,
                'form_state' => [
                    'vaga_titulo' => $descricao,
                    'vaga_quantidade' => (string) $quantidadePlanejada,
                    'vaga_tipo' => 'Nova vaga',
                    'vaga_status' => 'Em abertura',
                    'vaga_contrato' => $contrato,
                    'vaga_data_solicitacao' => now()->toDateString(),
                    'origem_histograma' => true,
                    'origem_histograma_key' => $origemKey,
                    'origem_histograma_competencia' => $competenciaYm,
                    'origem_histograma_item_codigo' => $itemCodigo,
                    'origem_histograma_pre_total' => (float) $funcao['pre_total'],
                ],
            ]);
            $keepIds[] = $created->id;
        }

        RecrutamentoVaga::query()
            ->where('contrato', $contrato)
            ->where('form_state->origem_histograma', true)
            ->where('form_state->origem_histograma_competencia', $competenciaYm)
            ->when(! empty($keepIds), fn ($query) => $query->whereNotIn('id', $keepIds))
            ->delete();
    }

    /**
     * @param  Collection<int, ContratoHistogramaLinha>  $linhas
     * @return array<int, array{percent:int,completed:bool}>
     */
    private function buildLinhaRecrutamentoStatus(string $contrato, string $competenciaMes, Collection $linhas): array
    {
        $status = [];
        $linhasItem = $linhas
            ->filter(fn (ContratoHistogramaLinha $linha) => ($linha->tipo_linha ?? '') !== 'grupo');

        if ($linhasItem->isEmpty()) {
            return $status;
        }

        $titulos = $linhasItem
            ->map(fn (ContratoHistogramaLinha $linha) => trim((string) $linha->descricao))
            ->filter()
            ->unique()
            ->values();

        $vagasPorTitulo = RecrutamentoVaga::query()
            ->where('contrato', $contrato)
            ->where('form_state->origem_histograma_competencia', $competenciaMes)
            ->whereIn('titulo', $titulos->all())
            ->get()
            ->groupBy(fn (RecrutamentoVaga $vaga) => trim((string) $vaga->titulo));

        foreach ($linhas as $linha) {
            if (($linha->tipo_linha ?? '') === 'grupo') {
                $status[$linha->id] = ['percent' => 0, 'completed' => false, 'mobilizacao' => 0];
                continue;
            }

            $titulo = trim((string) $linha->descricao);
            $vagas = $vagasPorTitulo->get($titulo, collect());
            if ($vagas->isEmpty()) {
                $status[$linha->id] = ['percent' => 0, 'completed' => false, 'mobilizacao' => 0];
                continue;
            }

            $percents = $vagas
                ->map(fn (RecrutamentoVaga $vaga) => $this->computeRhFlowProgressPercent($vaga));
            $mobilizacao = (int) $vagas
                ->sum(fn (RecrutamentoVaga $vaga) => $this->countCompletedCandidates($vaga));

            $avg = (int) round($percents->avg() ?? 0);
            $allDone = $percents->every(fn (int $percent) => $percent >= 100);

            $status[$linha->id] = [
                'percent' => max(0, min(100, $avg)),
                'completed' => $allDone,
                'mobilizacao' => $mobilizacao,
            ];
        }

        return $status;
    }

    private function computeRhFlowProgressPercent(RecrutamentoVaga $vaga): int
    {
        $state = $vaga->form_state ?? [];
        $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
        $approved = collect(range(1, $quantity))
            ->map(fn ($position) => [
                'position' => $position,
                'name' => $state["candidato_{$position}_nome_completo"] ?? '',
                'status' => $state["candidato_{$position}_status"] ?? 'pendente',
            ])
            ->filter(fn ($candidate) => $candidate['status'] === 'aprovado' && filled($candidate['name']))
            ->values();

        $approvedCount = $approved->count();
        $candidateSteps = ['treinamentos', 'assinatura', 'sgc', 'liberacao'];

        $checks = collect($state)
            ->filter(fn ($value, $key) => str_starts_with((string) $key, 'rh-check-'))
            ->values()
            ->slice(0, 3);
        $done = $checks->count() > 0 && $checks->every(fn ($value) => (bool) $value) ? 1.0 : 0.0;

        foreach ($candidateSteps as $step) {
            if ($approvedCount === 0) {
                continue;
            }

            $doneInStep = $approved
                ->filter(fn ($candidate) => $this->candidateStepDone($state, (int) $candidate['position'], $step))
                ->count();
            $done += $doneInStep / $approvedCount;
        }

        return (int) round(($done / (1 + count($candidateSteps))) * 100);
    }

    private function countCompletedCandidates(RecrutamentoVaga $vaga): int
    {
        $state = $vaga->form_state ?? [];
        $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));

        return collect(range(1, $quantity))
            ->filter(function (int $position) use ($state) {
                $status = $state["candidato_{$position}_status"] ?? 'pendente';
                $name = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));
                if ($status !== 'aprovado' || $name === '') {
                    return false;
                }

                return $this->candidateStepDone($state, $position, 'liberacao');
            })
            ->count();
    }

    private function candidateStepDone(array $state, int $position, string $step): bool
    {
        if ($step === 'treinamentos') {
            return filled($state["candidato_{$position}_treinamentos_data_inicio"] ?? null)
                && filled($state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null);
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
}
