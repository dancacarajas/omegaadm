<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\PguDashboardApiController;
use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Models\ContratoHistogramaRecorte;
use App\Models\ContratoPguAcaoRecomendada;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContratoAcoesRecomendadasController extends Controller
{
    use ContratosHistogramaCatalog;

    public function index(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');

        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        $funcoes = collect();
        if ($contrato !== '') {
            try {
                $sub = Request::create('/api/pgu/dashboard', 'GET', array_filter([
                    'contrato' => $contrato,
                    'competencia' => $competencia,
                    'data_limite_etapa_2' => $dataLimite,
                ], fn ($v) => $v !== null && $v !== ''));
                $payload = app(PguDashboardApiController::class)->assembleDashboard($sub);

                $ranking = collect($payload['ranking_executivo'] ?? [])
                    ->reject(fn ($row) => ($row['tipo_pendencia'] ?? null) === 'agregado')
                    ->filter(fn ($row) => ((int) ($row['pending'] ?? 0)) > 0)
                    ->groupBy(fn ($row) => trim((string) ($row['funcao'] ?? '')))
                    ->map(function ($rows, $funcao) {
                        return [
                            'funcao' => $funcao,
                            'pending' => (int) collect($rows)->sum(fn ($r) => (int) ($r['pending'] ?? 0)),
                        ];
                    })
                    ->sortByDesc('pending')
                    ->take(5)
                    ->values();

                $saved = ContratoPguAcaoRecomendada::query()
                    ->where('contrato', $contrato)
                    ->whereDate('competencia', Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString())
                    ->get()
                    ->keyBy(fn ($r) => trim((string) $r->funcao));

                $funcoes = $ranking->map(function ($row, $idx) use ($saved) {
                    $funcao = trim((string) ($row['funcao'] ?? ''));
                    $pendencias = (int) ($row['pending'] ?? 0);
                    $cad = $saved->get($funcao);

                    return [
                        'ordem' => $idx + 1,
                        'funcao' => $funcao,
                        'pendencias' => $pendencias,
                        'acao_recomendada' => (string) ($cad?->acao_recomendada ?? ''),
                        'responsavel' => (string) ($cad?->responsavel ?? ''),
                    ];
                })->values();
            } catch (\Throwable) {
                $funcoes = collect();
            }
        }

        return view('contratos.acoes-recomendadas', [
            'contratos' => $contratos,
            'contratoSelecionado' => $contrato,
            'competenciaMes' => $competencia,
            'dataLimiteEtapa2' => $dataLimite,
            'funcoes' => $funcoes,
        ]);
    }

    public function salvar(Request $request)
    {
        $data = $request->validate([
            'contrato' => ['required', 'string', 'max:255'],
            'competencia' => ['required', 'date_format:Y-m'],
            'funcoes' => ['nullable', 'array'],
            'funcoes.*.funcao' => ['required', 'string', 'max:255'],
            'funcoes.*.ordem' => ['nullable', 'integer', 'min:1'],
            'funcoes.*.pendencias' => ['nullable', 'integer', 'min:0'],
            'funcoes.*.acao_recomendada' => ['nullable', 'string', 'max:255'],
            'funcoes.*.responsavel' => ['nullable', 'string', 'max:120'],
        ]);

        $competenciaDate = Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth()->toDateString();
        $funcoes = collect($data['funcoes'] ?? [])
            ->map(function ($row, $idx) {
                return [
                    'ordem' => (int) ($row['ordem'] ?? ($idx + 1)),
                    'funcao' => trim((string) ($row['funcao'] ?? '')),
                    'pendencias' => (int) ($row['pendencias'] ?? 0),
                    'acao_recomendada' => trim((string) ($row['acao_recomendada'] ?? '')),
                    'responsavel' => trim((string) ($row['responsavel'] ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['funcao'] !== '')
            ->groupBy('funcao')
            ->map(function ($rows, $funcao) {
                $first = $rows->sortBy('ordem')->first();

                return [
                    'funcao' => $funcao,
                    'ordem' => (int) ($first['ordem'] ?? 0),
                    'pendencias' => (int) $rows->sum('pendencias'),
                    'acao_recomendada' => (string) ($rows->pluck('acao_recomendada')->first(fn ($v) => $v !== '') ?? ''),
                    'responsavel' => (string) ($rows->pluck('responsavel')->first(fn ($v) => $v !== '') ?? ''),
                ];
            })
            ->sortBy('ordem')
            ->values();

        DB::transaction(function () use ($data, $competenciaDate, $funcoes) {
            ContratoPguAcaoRecomendada::query()
                ->where('contrato', $data['contrato'])
                ->whereDate('competencia', $competenciaDate)
                ->delete();

            foreach ($funcoes as $idx => $row) {
                ContratoPguAcaoRecomendada::create([
                    'contrato' => $data['contrato'],
                    'competencia' => $competenciaDate,
                    'funcao' => $row['funcao'],
                    'ordem' => $idx + 1,
                    'pendencias_snapshot' => (int) ($row['pendencias'] ?? 0),
                    'acao_recomendada' => $row['acao_recomendada'] !== '' ? $row['acao_recomendada'] : null,
                    'responsavel' => $row['responsavel'] !== '' ? $row['responsavel'] : null,
                ]);
            }
        });

        return redirect()
            ->route('contratos.acoes-recomendadas.index', [
                'contrato' => $data['contrato'],
                'competencia' => $data['competencia'],
            ])
            ->with('success', 'Ações recomendadas salvas com sucesso.');
    }
}
