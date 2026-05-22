<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Services\Rh\MovimentacaoChamadoService;
use App\Services\Rh\MovimentacaoFinalizacaoService;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MovimentacaoChamadoController extends Controller
{
    public function index(Request $request)
    {
        $query = RhMovimentacaoChamado::query()
            ->with(['colaborador:id,nome,matricula,cargo,centro_custo', 'etapaAtual', 'responsavelAtual:id,name'])
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = '%'.trim((string) $request->input('busca')).'%';
                $q->whereHas('colaborador', fn ($c) => $c->where('nome', 'like', $busca)->orWhere('matricula', 'like', $busca));
            })
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->boolean('atrasados'), fn ($q) => $q->where('data_prevista', '<', today())->whereNotIn('status', [
                MovimentacaoChamadoStatus::CONCLUIDO,
                MovimentacaoChamadoStatus::CANCELADO,
            ]));

        $chamados = (clone $query)->orderByDesc('id')->paginate(20)->withQueryString();

        $resumo = [
            'abertos' => RhMovimentacaoChamado::query()->whereNotIn('status', [
                MovimentacaoChamadoStatus::CONCLUIDO,
                MovimentacaoChamadoStatus::CANCELADO,
            ])->count(),
            'atrasados' => RhMovimentacaoChamado::query()
                ->where('data_prevista', '<', today())
                ->whereNotIn('status', [MovimentacaoChamadoStatus::CONCLUIDO, MovimentacaoChamadoStatus::CANCELADO])
                ->count(),
            'aguardando_aprovacao' => RhMovimentacaoChamado::query()->where('status', MovimentacaoChamadoStatus::AGUARDANDO_APROVACAO)->count(),
            'aguardando_dp' => RhMovimentacaoChamado::query()->where('status', MovimentacaoChamadoStatus::AGUARDANDO_DP_FOLHA)->count(),
            'aguardando_exame' => RhMovimentacaoChamado::query()->where('status', MovimentacaoChamadoStatus::AGUARDANDO_EXAME_ASO)->count(),
            'concluidos_mes' => RhMovimentacaoChamado::query()
                ->where('status', MovimentacaoChamadoStatus::CONCLUIDO)
                ->whereMonth('finalizado_em', now()->month)
                ->whereYear('finalizado_em', now()->year)
                ->count(),
        ];

        return view('rh.chamados-movimentacao.index', [
            'chamados' => $chamados,
            'resumo' => $resumo,
            'tipos' => MovimentacaoChamadoTipo::labels(),
            'statuses' => MovimentacaoChamadoStatus::labels(),
            'busca' => $request->input('busca'),
            'tipoFiltro' => $request->input('tipo'),
            'statusFiltro' => $request->input('status'),
        ]);
    }

    public function create(Request $request)
    {
        $colaborador = null;
        if ($request->filled('colaborador')) {
            $colaborador = Colaborador::query()->findOrFail($request->integer('colaborador'));
        }

        return view('rh.chamados-movimentacao.create', [
            'colaborador' => $colaborador,
            'colaboradores' => Colaborador::query()->orderBy('nome')->limit(500)->get(['id', 'nome', 'matricula', 'cargo']),
            'tipo' => $request->query('tipo', MovimentacaoChamadoTipo::DESLIGAMENTO),
            'tipos' => MovimentacaoChamadoTipo::labels(),
            'tiposRescisao' => \App\Support\Rh\ColaboradorMovimentacaoTipos::tiposRescisao(),
        ]);
    }

    public function store(Request $request, MovimentacaoChamadoService $service)
    {
        $data = $request->validate([
            'colaborador_id' => ['required', 'exists:colaboradores,id'],
            'tipo' => ['required', Rule::in(MovimentacaoChamadoTipo::todos())],
            'data_efetiva' => ['nullable', 'date'],
            'data_prevista' => ['nullable', 'date'],
            'tipo_rescisao' => ['nullable', 'string', 'max:80'],
            'motivo_texto' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string'],
            'cargo_novo' => ['nullable', 'string', 'max:255'],
            'centro_custo_novo' => ['nullable', 'string', 'max:80'],
            'salario_novo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $colaborador = Colaborador::query()->findOrFail($data['colaborador_id']);
        unset($data['colaborador_id']);

        $chamado = $service->abrir($colaborador, $data['tipo'], $data, $request->user()?->id);

        return redirect()
            ->route('rh.chamados-movimentacao.show', $chamado)
            ->with('success', "Chamado {$chamado->protocolo} aberto. O cadastro do colaborador não foi alterado.");
    }

    public function show(RhMovimentacaoChamado $chamado)
    {
        $chamado->load([
            'colaborador',
            'etapas.checklistItens',
            'etapaAtual',
            'solicitante:id,name',
            'logs.usuario:id,name',
            'comentarios.usuario:id,name',
        ]);

        $pendencias = app(\App\Services\Rh\MovimentacaoWorkflowService::class)->podeFinalizar($chamado);

        return view('rh.chamados-movimentacao.show', [
            'chamado' => $chamado,
            'pendenciasFinalizacao' => $pendencias,
        ]);
    }

    public function concluirEtapa(Request $request, RhMovimentacaoEtapa $etapa, MovimentacaoChamadoService $service)
    {
        $data = $request->validate([
            'observacao' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($etapa->chamado->isAberto(), 422);
        $service->concluirEtapa($etapa, $data['observacao'] ?? null, $request->user()?->id);

        return redirect()
            ->route('rh.chamados-movimentacao.show', $etapa->chamado_id)
            ->with('success', "Etapa \"{$etapa->nome}\" concluída.");
    }

    public function finalizar(RhMovimentacaoChamado $chamado, MovimentacaoFinalizacaoService $service)
    {
        $service->finalizar($chamado, auth()->id());

        return redirect()
            ->route('rh.chamados-movimentacao.show', $chamado)
            ->with('success', 'Chamado finalizado. Cadastro do colaborador atualizado.');
    }

    public function cancelar(Request $request, RhMovimentacaoChamado $chamado)
    {
        $data = $request->validate(['motivo_cancelamento' => ['required', 'string', 'max:1000']]);
        abort_unless($chamado->isAberto(), 422);

        $chamado->update([
            'status' => MovimentacaoChamadoStatus::CANCELADO,
            'cancelado_em' => now(),
            'cancelado_por_id' => $request->user()?->id,
            'motivo_cancelamento' => $data['motivo_cancelamento'],
        ]);

        return redirect()
            ->route('rh.chamados-movimentacao.index')
            ->with('success', 'Chamado cancelado.');
    }
}
