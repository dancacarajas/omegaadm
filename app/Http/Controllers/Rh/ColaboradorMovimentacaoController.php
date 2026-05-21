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
        $query = ColaboradorMovimentacao::query()
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = '%'.trim((string) $request->input('busca')).'%';
                $q->whereHas('colaborador', fn ($c) => $c->where('nome', 'like', $busca)
                    ->orWhere('matricula', 'like', $busca));
            });

        $movimentacoes = (clone $query)
            ->with(['colaborador:id,nome,matricula,cargo,foto_path', 'registradoPor:id,name'])
            ->orderByDesc('data_inicio')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $resumo = [
            'total_geral' => ColaboradorMovimentacao::count(),
            'total_filtrado' => $movimentacoes->total(),
            'em_aberto' => ColaboradorMovimentacao::query()->whereNull('data_fim')->count(),
            'afastamento_inss' => ColaboradorMovimentacao::query()
                ->where('tipo', ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS)
                ->whereNull('data_fim')
                ->count(),
        ];

        return view('rh.colaboradores.movimentacoes.index', [
            'movimentacoes' => $movimentacoes,
            'tipos' => ColaboradorMovimentacaoTipos::labels(),
            'tipoFiltro' => $request->input('tipo'),
            'busca' => $request->input('busca'),
            'resumo' => $resumo,
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

    /** URL curta: /rh/movimentacoes/{id}/editar (produção Hostinger). */
    public function editByMovimentacao(ColaboradorMovimentacao $movimentacao)
    {
        $colaborador = $movimentacao->colaborador()->firstOrFail();

        return $this->edit($colaborador, $movimentacao);
    }

    public function edit(Colaborador $colaborador, ColaboradorMovimentacao $movimentacao)
    {
        $this->garantirMovimentacaoDoColaborador($colaborador, $movimentacao);

        return view('rh.colaboradores.movimentacoes.create', [
            'colaborador' => $colaborador,
            'movimentacao' => $movimentacao,
            'tipo' => $movimentacao->tipo,
            'tipos' => ColaboradorMovimentacaoTipos::labels(),
            'tiposRescisao' => ColaboradorMovimentacaoTipos::tiposRescisao(),
            'especiesInss' => ColaboradorMovimentacaoTipos::especiesInss(),
            'centrosCusto' => $this->centrosCustoSugestoes(),
            'contratos' => Contrato::query()->orderBy('numero')->get(['id', 'numero', 'nome', 'centro_custo']),
        ]);
    }

    public function updateByMovimentacao(
        Request $request,
        ColaboradorMovimentacao $movimentacao,
        ColaboradorMovimentacaoService $service
    ) {
        $colaborador = $movimentacao->colaborador()->firstOrFail();

        return $this->update($request, $colaborador, $movimentacao, $service);
    }

    public function update(
        Request $request,
        Colaborador $colaborador,
        ColaboradorMovimentacao $movimentacao,
        ColaboradorMovimentacaoService $service
    ) {
        $this->garantirMovimentacaoDoColaborador($colaborador, $movimentacao);

        $data = $this->validateMovimentacao($request, (string) $movimentacao->tipo);
        $service->atualizar($movimentacao, $data);

        return redirect()
            ->route('rh.efetivo.show', $colaborador)
            ->with('success', ColaboradorMovimentacaoTipos::label((string) $movimentacao->tipo).' atualizado com sucesso.');
    }

    public function destroy(Colaborador $colaborador, ColaboradorMovimentacao $movimentacao, ColaboradorMovimentacaoService $service)
    {
        $this->garantirMovimentacaoDoColaborador($colaborador, $movimentacao);
        $movimentacao->delete();
        $service->sincronizarCadastroColaborador($colaborador->fresh());

        return redirect()
            ->route('rh.efetivo.show', $colaborador)
            ->with('success', 'Registro de movimentação removido. O cadastro do colaborador foi reajustado conforme as demais movimentações.');
    }

    private function garantirMovimentacaoDoColaborador(Colaborador $colaborador, ColaboradorMovimentacao $movimentacao): void
    {
        abort_unless($movimentacao->colaborador_id === $colaborador->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMovimentacao(Request $request, ?string $tipoFixo = null): array
    {
        $tipo = $tipoFixo ?? $request->input('tipo');
        $rules = [
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

        if ($tipoFixo === null) {
            $rules = array_merge([
                'tipo' => ['required', Rule::in(ColaboradorMovimentacaoTipos::todos())],
            ], $rules);
        }

        $validated = $request->validate($rules);
        $validated['tipo'] = $tipo;

        return $validated;
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
