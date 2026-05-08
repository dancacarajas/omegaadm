<?php

namespace App\Http\Controllers;

use App\Models\PatrimonialHistogramaLinha;
use App\Models\PatrimonialHistogramaRecorte;
use App\Models\Patrimonio;
use App\Support\ContratoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatrimonialHistogramaController extends Controller
{
    public function index(Request $request)
    {
        $competenciaMes = $request->input('competencia', now()->format('Y-m'));
        $competencia = Carbon::createFromFormat('Y-m', $competenciaMes)->startOfMonth()->toDateString();

        $contratos = ContratoAccess::applyContratoString(Patrimonio::query())
            ->whereNotNull('contrato')
            ->where('contrato', '!=', '')
            ->distinct()
            ->orderBy('contrato')
            ->pluck('contrato')
            ->values()
            ->all();

        $contratoSelecionado = $request->input('contrato', $contratos[0] ?? '');

        $linhas = collect();
        $recorte = null;
        if ($contratoSelecionado !== '') {
            $linhas = PatrimonialHistogramaLinha::query()
                ->where('contrato', $contratoSelecionado)
                ->whereDate('competencia', $competencia)
                ->orderBy('ordem')
                ->get();

            if ($linhas->isEmpty()) {
                $linhas = $this->linhasBaseEquipamentos($contratoSelecionado);
            }

            $recorte = PatrimonialHistogramaRecorte::query()
                ->where('contrato', $contratoSelecionado)
                ->whereDate('competencia', $competencia)
                ->first();
        }

        $inicioMonitoramento = $recorte?->inicio_monitoramento?->format('Y-m-d')
            ?? Carbon::createFromFormat('Y-m', $competenciaMes)->startOfMonth()->toDateString();
        $dataLimiteEtapa2 = $recorte?->data_limite_etapa_2?->format('Y-m-d');

        $linhasItem = $linhas->filter(fn (PatrimonialHistogramaLinha $linha) => ($linha->tipo_linha ?? 'item') !== 'grupo');
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
            'inicioMonitoramento' => $inicioMonitoramento,
            'dataLimiteEtapa2' => $dataLimiteEtapa2,
            'histogramaHoje' => Carbon::today()->toDateString(),
            'contagemAtrasadas' => 0,
            'situacaoPrazo' => null,
            'diasAteLimite' => null,
            'linhaRecrutamentoStatus' => [],
            'layout' => 'layouts.app',
            'salvarRoute' => 'patrimonial.histograma.salvar',
            'histogramaEyebrow' => 'Patrimonial',
            'histogramaTitulo' => 'Histograma de equipamentos por contrato',
            'histogramaDescricao' => 'Estrutura isolada do módulo Patrimonial. Use as linhas para planejar quantitativos e movimentação de equipamentos.',
            'mostrarAcoesRecomendadas' => false,
            'usarStatusRh' => false,
            'mobilizacaoReadonly' => false,
        ]);
    }

    public function salvar(Request $request)
    {
        $data = $request->validate([
            'contrato' => ['required', 'string', 'max:255'],
            'competencia' => ['required', 'date_format:Y-m'],
            'inicio_monitoramento' => ['nullable', 'date'],
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
            PatrimonialHistogramaLinha::query()
                ->where('contrato', $data['contrato'])
                ->whereDate('competencia', $competencia)
                ->delete();

            foreach ($linhas as $index => $linha) {
                PatrimonialHistogramaLinha::create([
                    'contrato' => $data['contrato'],
                    'competencia' => $competencia,
                    'tipo_linha' => $linha['tipo_linha'] ?? 'item',
                    'ordem' => $index + 1,
                    'item_codigo' => $linha['item_codigo'] ?? null,
                    'descricao' => $linha['descricao'],
                    'unidade' => $linha['unidade'] ?? 'Unid.',
                    'mobilizacao' => (float) ($linha['mobilizacao'] ?? 0),
                    'pre_pgu' => (float) ($linha['pre_pgu'] ?? 0),
                    'pgu' => (float) ($linha['pgu'] ?? 0),
                    'pos_pgu' => (float) ($linha['pos_pgu'] ?? 0),
                    'desmobilizacao' => (float) ($linha['desmobilizacao'] ?? 0),
                ]);
            }

            PatrimonialHistogramaRecorte::query()->updateOrCreate(
                [
                    'contrato' => $data['contrato'],
                    'competencia' => $competencia,
                ],
                [
                    'inicio_monitoramento' => ! empty($data['inicio_monitoramento'])
                        ? Carbon::parse($data['inicio_monitoramento'])->toDateString()
                        : Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth()->toDateString(),
                    'data_limite_etapa_2' => ! empty($data['data_limite_etapa_2'])
                        ? Carbon::parse($data['data_limite_etapa_2'])->toDateString()
                        : null,
                ]
            );
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Histograma patrimonial salvo com sucesso.',
            ]);
        }

        return redirect()
            ->route('patrimonial.histograma.index', [
                'contrato' => $data['contrato'],
                'competencia' => $data['competencia'],
            ])
            ->with('success', 'Histograma patrimonial salvo com sucesso.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, PatrimonialHistogramaLinha>
     */
    private function linhasBaseEquipamentos(string $contrato)
    {
        $grupos = ContratoAccess::applyContratoString(Patrimonio::query())
            ->where('contrato', $contrato)
            ->selectRaw('COALESCE(NULLIF(categoria, ""), "EQUIPAMENTOS") as categoria_grupo, nome, COUNT(*) as quantidade')
            ->groupBy('categoria_grupo', 'nome')
            ->orderBy('categoria_grupo')
            ->orderBy('nome')
            ->get();

        $rows = collect();
        $ordem = 1;
        $grupoAtual = null;

        foreach ($grupos as $item) {
            $categoria = trim((string) $item->categoria_grupo);
            if ($categoria === '') {
                $categoria = 'EQUIPAMENTOS';
            }

            if ($grupoAtual !== $categoria) {
                $rows->push(new PatrimonialHistogramaLinha([
                    'contrato' => $contrato,
                    'competencia' => now()->startOfMonth()->toDateString(),
                    'tipo_linha' => 'grupo',
                    'ordem' => $ordem++,
                    'item_codigo' => null,
                    'descricao' => mb_strtoupper($categoria),
                    'unidade' => 'Unid.',
                    'mobilizacao' => 0,
                    'pre_pgu' => 0,
                    'pgu' => 0,
                    'pos_pgu' => 0,
                    'desmobilizacao' => 0,
                ]));
                $grupoAtual = $categoria;
            }

            $quantidade = (float) ($item->quantidade ?? 0);
            $rows->push(new PatrimonialHistogramaLinha([
                'contrato' => $contrato,
                'competencia' => now()->startOfMonth()->toDateString(),
                'tipo_linha' => 'item',
                'ordem' => $ordem++,
                'item_codigo' => null,
                'descricao' => (string) $item->nome,
                'unidade' => 'Unid.',
                'mobilizacao' => $quantidade,
                'pre_pgu' => $quantidade,
                'pgu' => $quantidade,
                'pos_pgu' => 0,
                'desmobilizacao' => 0,
            ]));
        }

        return $rows;
    }
}

