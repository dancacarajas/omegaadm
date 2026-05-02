<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\SesmtTarefa;
use Illuminate\Http\Request;

class SesmtController extends Controller
{
    public function index()
    {
        $this->sincronizarTarefas();

        $colaboradores = Colaborador::query()
            ->with('sesmtTarefas')
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('matricula', 'like', "%{$busca}%")
                        ->orWhere('cargo', 'like', "%{$busca}%");
                });
            })
            ->orderBy('nome')
            ->paginate(12)
            ->withQueryString();

        $tarefas = SesmtTarefa::query();
        $totalTarefas = (clone $tarefas)->count();
        $pendentes = (clone $tarefas)->where('status', 'pendente')->count();
        $emAndamento = (clone $tarefas)->where('status', 'em_andamento')->count();
        $concluidas = (clone $tarefas)->where('status', 'concluido')->count();

        return view('sesmt.index', compact('colaboradores', 'totalTarefas', 'pendentes', 'emAndamento', 'concluidas'));
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
            ->route('sesmt.index')
            ->with('success', 'Tarefa SESMT atualizada.');
    }

    public function sync()
    {
        $this->sincronizarTarefas();

        return redirect()
            ->route('sesmt.index')
            ->with('success', 'Demandas SESMT sincronizadas com o efetivo.');
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
