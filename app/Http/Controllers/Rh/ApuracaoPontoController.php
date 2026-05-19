<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Support\FrequenciaPontoCsvImport;
use App\Support\Rh\CartaoPontoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ApuracaoPontoController extends Controller
{
    public function index(Request $request)
    {
        $dataFim = $request->filled('data_fim')
            ? Carbon::parse($request->input('data_fim'))->startOfDay()
            : today()->startOfDay();

        $dataInicio = $request->filled('data_inicio')
            ? Carbon::parse($request->input('data_inicio'))->startOfDay()
            : $dataFim->copy()->subDays(29);

        if ($dataInicio->gt($dataFim)) {
            [$dataInicio, $dataFim] = [$dataFim->copy(), $dataInicio->copy()];
        }

        $colaboradores = $this->colaboradoresFiltrados($request);
        $colaboradorId = (int) $request->input('colaborador_id');
        if ($colaboradorId <= 0) {
            $colaboradorId = (int) $colaboradores->first()?->id;
        }

        $colaborador = $colaboradores->firstWhere('id', $colaboradorId)
            ?? Colaborador::query()->with(['horarioEscala.dias'])->find($colaboradorId);

        $resumoRegistros = $colaborador
            ? $this->resumoRegistrosPeriodo($colaborador->id, $dataInicio->toDateString(), $dataFim->toDateString())
            : null;

        $cartao = null;
        if ($colaborador) {
            $cartoes = app(CartaoPontoService::class)->montarCartoes(
                collect([$colaborador]),
                $dataInicio->toDateString(),
                $dataFim->toDateString()
            );
            $cartao = $cartoes[0] ?? null;
        }

        $filtrosAtivos = $this->filtrosAtivos($request);

        return view('rh.frequencia.apuracao.index', [
            'dataInicio' => $dataInicio->toDateString(),
            'dataFim' => $dataFim->toDateString(),
            'colaboradores' => $colaboradores,
            'colaborador' => $colaborador,
            'colaboradorId' => $colaborador?->id,
            'cartao' => $cartao,
            'filtrosAtivos' => $filtrosAtivos,
            'departamentos' => $this->valoresDistintos('departamento'),
            'centrosCusto' => $this->valoresDistintos('centro_custo'),
            'cargos' => $this->valoresDistintos('cargo'),
            'horarios' => HorarioEscala::query()->where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']),
            'buscaColaborador' => trim((string) $request->input('busca', '')),
            'resumoRegistros' => $resumoRegistros,
        ]);
    }

    /**
     * @return array{total: int, com_batida: int, csv_ponto: int, origens: array<string, int>}
     */
    private function resumoRegistrosPeriodo(int $colaboradorId, string $dataInicio, string $dataFim): array
    {
        $registros = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaboradorId)
            ->whereDate('data', '>=', $dataInicio)
            ->whereDate('data', '<=', $dataFim)
            ->get(['origem', 'entrada_1', 'saida_1', 'entrada_2', 'saida_2', 'status']);

        $comBatida = 0;
        $origens = [];

        foreach ($registros as $registro) {
            $origens[$registro->origem ?? '—'] = ($origens[$registro->origem ?? '—'] ?? 0) + 1;
            foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
                if (! \App\Support\FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                    $comBatida++;

                    break 2;
                }
            }
        }

        return [
            'total' => $registros->count(),
            'com_batida' => $comBatida,
            'csv_ponto' => $origens[FrequenciaPontoCsvImport::ORIGEM] ?? 0,
            'origens' => $origens,
        ];
    }

    /**
     * @return Collection<int, Colaborador>
     */
    private function colaboradoresFiltrados(Request $request): Collection
    {
        $query = Colaborador::query()
            ->where('status', 'ativo')
            ->with(['horarioEscala'])
            ->orderBy('nome');

        if ($request->filled('departamento')) {
            $query->where('departamento', $request->input('departamento'));
        }

        if ($request->filled('centro_custo')) {
            $query->where('centro_custo', $request->input('centro_custo'));
        }

        if ($request->filled('cargo')) {
            $query->where('cargo', $request->input('cargo'));
        }

        if ($request->filled('horario_escala_id')) {
            $query->where('horario_escala_id', (int) $request->input('horario_escala_id'));
        }

        if ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('matricula', 'like', "%{$busca}%")
                    ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        return $query->get(['id', 'nome', 'matricula', 'cpf', 'rg', 'cargo', 'departamento', 'centro_custo', 'horario_escala_id']);
    }

    /**
     * @return list<array{label: string, valor: string}>
     */
    private function filtrosAtivos(Request $request): array
    {
        $tags = [];

        if ($request->filled('departamento')) {
            $tags[] = ['label' => 'Departamento', 'valor' => (string) $request->input('departamento')];
        } else {
            $tags[] = ['label' => 'Departamentos', 'valor' => 'Todos'];
        }

        if ($request->filled('centro_custo')) {
            $tags[] = ['label' => 'Centro de custo', 'valor' => (string) $request->input('centro_custo')];
        } else {
            $tags[] = ['label' => 'Centros de custo', 'valor' => 'Todos'];
        }

        if ($request->filled('cargo')) {
            $tags[] = ['label' => 'Cargo', 'valor' => (string) $request->input('cargo')];
        } else {
            $tags[] = ['label' => 'Cargos', 'valor' => 'Todos'];
        }

        if ($request->filled('horario_escala_id')) {
            $nome = HorarioEscala::query()->find((int) $request->input('horario_escala_id'))?->nome ?? 'Selecionado';
            $tags[] = ['label' => 'Horário', 'valor' => $nome];
        } else {
            $tags[] = ['label' => 'Horários', 'valor' => 'Todos'];
        }

        $tags[] = ['label' => 'Funcionários', 'valor' => $request->filled('busca') ? 'Filtrado' : 'Todos'];

        return $tags;
    }

    /**
     * @return list<string>
     */
    private function valoresDistintos(string $coluna): array
    {
        return Colaborador::query()
            ->where('status', 'ativo')
            ->whereNotNull($coluna)
            ->where($coluna, '!=', '')
            ->distinct()
            ->orderBy($coluna)
            ->pluck($coluna)
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }
}
