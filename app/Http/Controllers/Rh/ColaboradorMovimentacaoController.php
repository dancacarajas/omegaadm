<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Models\Contrato;
use App\Services\Rh\ColaboradorMovimentacaoService;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ColaboradorMovimentacaoController extends Controller
{
    public function index(Request $request)
    {
        $movimentacoes = ColaboradorMovimentacao::query()
            ->with(['colaborador:id,nome,matricula,cargo', 'registradoPor:id,name'])
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = '%'.trim((string) $request->input('busca')).'%';
                $q->whereHas('colaborador', fn ($c) => $c->where('nome', 'like', $busca)
                    ->orWhere('matricula', 'like', $busca));
            })
            ->orderByDesc('data_inicio')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('rh.colaboradores.movimentacoes.index', [
            'movimentacoes' => $movimentacoes,
            'tipos' => ColaboradorMovimentacaoTipos::labels(),
            'tipoFiltro' => $request->input('tipo'),
            'busca' => $request->input('busca'),
        ]);
    }

    public function create(Colaborador $colaborador, Request $request)
    {
        $tipo = $request->query('tipo', ColaboradorMovimentacaoTipos::DESLIGAMENTO);
        if (! in_array($tipo, ColaboradorMovimentacaoTipos::todos(), true)) {
            $tipo = ColaboradorMovimentacaoTipos::DESLIGAMENTO;
        }

        return view('rh.colaboradores.movimentacoes.create', [
            'colaborador' => $colaborador,
            'tipo' => $tipo,
            'tipos' => ColaboradorMovimentacaoTipos::labels(),
            'tiposRescisao' => ColaboradorMovimentacaoTipos::tiposRescisao(),
            'especiesInss' => ColaboradorMovimentacaoTipos::especiesInss(),
            'centrosCusto' => $this->centrosCustoSugestoes(),
            'contratos' => Contrato::query()->orderBy('numero')->get(['id', 'numero', 'nome', 'centro_custo']),
        ]);
    }

    public function store(Request $request, Colaborador $colaborador, ColaboradorMovimentacaoService $service)
    {
        $data = $this->validateMovimentacao($request);
        $service->registrar($colaborador, $data, $request->user()?->id);

        return redirect()
            ->route('rh.efetivo.show', $colaborador)
            ->with('success', ColaboradorMovimentacaoTipos::label($data['tipo']).' registrado com sucesso.');
    }

    public function destroy(Colaborador $colaborador, ColaboradorMovimentacao $movimentacao)
    {
        abort_unless($movimentacao->colaborador_id === $colaborador->id, 404);
        $movimentacao->delete();

        return redirect()
            ->route('rh.efetivo.show', $colaborador)
            ->with('success', 'Registro de movimentação removido. O cadastro do colaborador não foi revertido automaticamente — ajuste manualmente se necessário.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMovimentacao(Request $request): array
    {
        $tipo = $request->input('tipo');
        $rules = [
            'tipo' => ['required', Rule::in(ColaboradorMovimentacaoTipos::todos())],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'motivo_texto' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string'],
        ];

        $rules += match ($tipo) {
            ColaboradorMovimentacaoTipos::DESLIGAMENTO => [
                'tipo_rescisao' => ['required', Rule::in(array_keys(ColaboradorMovimentacaoTipos::tiposRescisao()))],
                'motivo_codigo' => ['nullable', 'string', 'max:40'],
            ],
            ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO => [
                'centro_custo_novo' => ['required', 'string', 'max:80'],
                'tipo_contrato_novo' => ['nullable', 'string', 'max:80'],
                'local_trabalho_novo' => ['nullable', 'string', 'max:255'],
                'departamento_novo' => ['nullable', 'string', 'max:255'],
            ],
            ColaboradorMovimentacaoTipos::PROMOCAO => [
                'cargo_novo' => ['required', 'string', 'max:255'],
                'salario_novo' => ['nullable', 'numeric', 'min:0'],
                'departamento_novo' => ['nullable', 'string', 'max:255'],
            ],
            ColaboradorMovimentacaoTipos::MUDANCA_FUNCAO => [
                'cargo_novo' => ['required', 'string', 'max:255'],
                'departamento_novo' => ['nullable', 'string', 'max:255'],
            ],
            ColaboradorMovimentacaoTipos::FERIAS => [
                'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
                'dias_ferias' => ['nullable', 'integer', 'min:1', 'max:60'],
                'abono_pecuniario' => ['nullable', 'boolean'],
            ],
            ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS => [
                'especie_beneficio_inss' => ['required', Rule::in(array_keys(ColaboradorMovimentacaoTipos::especiesInss()))],
                'cid' => ['nullable', 'string', 'max:20'],
            ],
            default => [],
        };

        return $request->validate($rules);
    }

    /**
     * @return list<string>
     */
    private function centrosCustoSugestoes(): array
    {
        $deColaboradores = Colaborador::query()
            ->whereNotNull('centro_custo')
            ->where('centro_custo', '!=', '')
            ->distinct()
            ->orderBy('centro_custo')
            ->pluck('centro_custo');

        $deContratos = Contrato::query()
            ->whereNotNull('centro_custo')
            ->where('centro_custo', '!=', '')
            ->distinct()
            ->orderBy('centro_custo')
            ->pluck('centro_custo');

        return $deColaboradores->merge($deContratos)->unique()->values()->all();
    }
}
