<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\SesmtTarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SesmtController extends Controller
{
    public function index(Request $request)
    {
        $this->sincronizarTarefas();

        $baseQuery = $this->colaboradoresFiltradosQuery($request);
        $colaboradorIds = (clone $baseQuery)->pluck('id');
        $indicadores = $this->indicadoresConformidade($colaboradorIds);

        $colaboradores = (clone $baseQuery)
            ->with('sesmtTarefas')
            ->orderBy('nome')
            ->paginate(12)
            ->withQueryString();

        $opcoesCargo = Colaborador::query()
            ->where('status', 'ativo')
            ->whereNotNull('cargo')
            ->where('cargo', '!=', '')
            ->distinct()
            ->orderBy('cargo')
            ->pluck('cargo');

        $opcoesColaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $filtrosAtivos = $request->filled('busca')
            || $request->filled('cargo')
            || $request->filled('status_tarefa')
            || $request->filled('tipo_pendencia')
            || $request->filled('colaborador_id')
            || $request->filled('responsavel_tratativa')
            || $request->boolean('somente_vencidas');

        return view('sesmt.index', [
            'colaboradores' => $colaboradores,
            'indicadores' => $indicadores,
            'opcoesCargo' => $opcoesCargo,
            'opcoesColaboradores' => $opcoesColaboradores,
            'filtrosAtivos' => $filtrosAtivos,
        ]);
    }

    public function update(Request $request, SesmtTarefa $tarefa)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pendente,em_andamento,concluido'],
            'data_prevista' => ['nullable', 'date'],
            'data_conclusao' => ['nullable', 'date'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
        ]);

        if ($data['status'] === 'concluido' && empty($data['data_conclusao'])) {
            $data['data_conclusao'] = now()->toDateString();
        }

        if ($data['status'] !== 'concluido') {
            $data['data_conclusao'] = null;
        }

        $tarefa->update($data);

        return redirect()
            ->back()
            ->with('success', 'Tarefa SSMA atualizada.');
    }

    public function sync()
    {
        $this->sincronizarTarefas();

        return redirect()
            ->back()
            ->with('success', 'Demandas SSMA sincronizadas com o efetivo.');
    }

    private function colaboradoresFiltradosQuery(Request $request)
    {
        $q = Colaborador::query()->where('status', 'ativo');

        if ($request->filled('busca')) {
            $busca = (string) $request->input('busca');
            $q->where(function ($q2) use ($busca) {
                $q2->where('nome', 'like', "%{$busca}%")
                    ->orWhere('matricula', 'like', "%{$busca}%")
                    ->orWhere('cargo', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('cargo')) {
            $q->where('cargo', $request->input('cargo'));
        }

        if ($request->filled('colaborador_id')) {
            $q->where('id', $request->integer('colaborador_id'));
        }

        if ($request->filled('status_tarefa')) {
            $status = (string) $request->input('status_tarefa');
            $q->whereHas('sesmtTarefas', fn ($t) => $t->where('status', $status));
        }

        if ($request->filled('tipo_pendencia')) {
            $tipo = (string) $request->input('tipo_pendencia');
            $q->whereHas('sesmtTarefas', function ($t) use ($tipo) {
                $t->where('tipo', $tipo)
                    ->whereNotIn('status', ['concluido']);
            });
        }

        if ($request->boolean('somente_vencidas')) {
            $q->whereHas('sesmtTarefas', function ($t) {
                $t->whereNotNull('data_prevista')
                    ->whereDate('data_prevista', '<', now()->toDateString())
                    ->whereNotIn('status', ['concluido']);
            });
        }

        if ($request->filled('responsavel_tratativa')) {
            $r = trim((string) $request->input('responsavel_tratativa'));
            if ($r !== '') {
                $q->whereHas('sesmtTarefas', fn ($t) => $t->where('responsavel', 'like', "%{$r}%"));
            }
        }

        return $q;
    }

    /**
     * @param  Collection<int, int>  $colaboradorIds
     * @return array{
     *     total_resultado: int,
     *     total_ativos: int,
     *     conformes_100: int,
     *     com_pendencia: int,
     *     pendencias_criticas: int,
     *     pendencias_vencidas: int,
     *     percentual_geral: int,
     * }
     */
    private function indicadoresConformidade(Collection $colaboradorIds): array
    {
        $totalAtivos = Colaborador::query()->where('status', 'ativo')->count();

        if ($colaboradorIds->isEmpty()) {
            return [
                'total_resultado' => 0,
                'total_ativos' => $totalAtivos,
                'conformes_100' => 0,
                'com_pendencia' => 0,
                'pendencias_criticas' => 0,
                'pendencias_vencidas' => 0,
                'percentual_geral' => 0,
            ];
        }

        $tarefas = SesmtTarefa::query()->whereIn('colaborador_id', $colaboradorIds)->get();
        $totalTarefas = $tarefas->count();
        $concluidas = $tarefas->where('status', 'concluido')->count();
        $percentualGeral = $totalTarefas > 0 ? (int) round(($concluidas / $totalTarefas) * 100) : 0;

        $pendenciasVencidas = $tarefas->filter(fn (SesmtTarefa $t) => $t->estaVencida())->count();
        $pendenciasCriticas = $tarefas->filter(fn (SesmtTarefa $t) => $t->pendenciaEhCritica())->count();

        $conformes100 = 0;
        $comPendencia = 0;
        foreach ($colaboradorIds as $cid) {
            $ts = $tarefas->where('colaborador_id', $cid);
            if ($ts->isEmpty()) {
                continue;
            }
            if ($ts->every(fn (SesmtTarefa $t) => $t->status === 'concluido')) {
                $conformes100++;
            } else {
                $comPendencia++;
            }
        }

        return [
            'total_resultado' => $colaboradorIds->count(),
            'total_ativos' => $totalAtivos,
            'conformes_100' => $conformes100,
            'com_pendencia' => $comPendencia,
            'pendencias_criticas' => $pendenciasCriticas,
            'pendencias_vencidas' => $pendenciasVencidas,
            'percentual_geral' => $percentualGeral,
        ];
    }

    private function sincronizarTarefas(): void
    {
        Colaborador::query()
            ->where('status', 'ativo')
            ->select('id')
            ->chunkById(100, function ($colaboradores) {
                foreach ($colaboradores as $colaborador) {
                    foreach (SesmtTarefa::TIPOS_PADRAO as $tipo) {
                        SesmtTarefa::firstOrCreate([
                            'colaborador_id' => $colaborador->id,
                            'tipo' => $tipo,
                        ]);
                    }
                }
            });
    }
}
