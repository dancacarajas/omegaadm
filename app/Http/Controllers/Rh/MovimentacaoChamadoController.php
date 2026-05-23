<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\RecrutamentoVaga;
use App\Models\Rh\RhMovimentacaoAnexo;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoChecklistItem;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Models\Rh\RhMovimentacaoNadaConstaItem;
use App\Services\Rh\MovimentacaoChamadoService;
use App\Services\Rh\MovimentacaoDesligamentoAutoProgressaoService;
use App\Services\Rh\MovimentacaoDesligamentoRules;
use App\Services\Rh\MovimentacaoFinalizacaoService;
use App\Services\Rh\MovimentacaoLogService;
use App\Services\Rh\MovimentacaoChamadoPdfService;
use App\Services\Rh\MovimentacaoNadaConstaPdfService;
use App\Services\Rh\MovimentacaoNadaConstaService;
use App\Services\Rh\MovimentacaoSubstituicaoVagaService;
use App\Services\Rh\MovimentacaoWorkflowService;
use App\Support\Rh\MovimentacaoChamadoAcesso;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use App\Support\Rh\MovimentacaoAfastamentoInssCatalog;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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
            'afastamento_inss_abertos' => RhMovimentacaoChamado::query()
                ->where('tipo', MovimentacaoChamadoTipo::AFASTAMENTO_INSS)
                ->whereNotIn('status', [MovimentacaoChamadoStatus::CONCLUIDO, MovimentacaoChamadoStatus::CANCELADO])
                ->count(),
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

        $chamadoOrigem = null;
        if ($request->filled('chamado_origem')) {
            $chamadoOrigem = RhMovimentacaoChamado::query()
                ->where('tipo', MovimentacaoChamadoTipo::AFASTAMENTO_INSS)
                ->find($request->integer('chamado_origem'));
        }

        $colaboradores = Colaborador::query()->orderBy('nome')->limit(500)->get(['id', 'nome', 'matricula', 'cargo', 'centro_custo', 'tipo_contrato']);
        $substituicaoVaga = app(MovimentacaoSubstituicaoVagaService::class);
        $gestoresPorColaborador = $substituicaoVaga->mapGestoresPorColaborador($colaboradores);
        $gestorContratoInicial = $colaborador
            ? ($gestoresPorColaborador[$colaborador->id] ?? $substituicaoVaga->gestorDoContrato($colaborador))
            : '';

        return view('rh.chamados-movimentacao.create', [
            'colaborador' => $colaborador,
            'chamadoOrigem' => $chamadoOrigem,
            'colaboradores' => $colaboradores,
            'gestoresPorColaborador' => $gestoresPorColaborador,
            'gestorContratoInicial' => $gestorContratoInicial,
            'tipo' => $request->query('tipo', MovimentacaoChamadoTipo::DESLIGAMENTO),
            'tipos' => MovimentacaoChamadoTipo::labels(),
            'tiposRescisao' => ColaboradorMovimentacaoTipos::tiposRescisao(),
            'tiposAfastamento' => MovimentacaoAfastamentoInssCatalog::tiposAfastamentoInicial(),
            'classificacoesAfastamento' => MovimentacaoAfastamentoInssCatalog::classificacoes(),
            'especiesInss' => ColaboradorMovimentacaoTipos::especiesInss(),
        ]);
    }

    public function store(Request $request, MovimentacaoChamadoService $service)
    {
        $rules = [
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
            'chamado_origem_id' => ['nullable', 'exists:rh_movimentacao_chamados,id'],
        ];

        if ($request->input('tipo') === MovimentacaoChamadoTipo::DESLIGAMENTO) {
            $rules = array_merge($rules, [
                'data_prevista' => ['required', 'date'],
                'ultimo_dia_trabalhado' => ['required', 'date'],
                'tipo_rescisao' => ['required', 'string', Rule::in(array_keys(ColaboradorMovimentacaoTipos::tiposRescisao()))],
                'motivo_texto' => ['required', 'string', 'max:500'],
                'gestor_responsavel' => ['nullable', 'string', 'max:120'],
                'havera_substituicao_vaga' => ['required', Rule::in(['sim', 'nao'])],
                'observacoes' => ['nullable', 'string'],
            ]);
        }

        if ($request->input('tipo') === MovimentacaoChamadoTipo::AFASTAMENTO_INSS) {
            $rules = array_merge($rules, [
                'data_recebimento_atestado' => ['required', 'date'],
                'data_inicio_afastamento' => ['required', 'date'],
                'data_final_atestado' => ['nullable', 'date', 'after_or_equal:data_inicio_afastamento'],
                'quantidade_dias' => ['nullable', 'integer', 'min:1', 'max:730'],
                'tipo_afastamento' => ['required', 'string', 'max:80'],
                'doenca_comum' => ['nullable', 'boolean'],
                'acidente_trabalho' => ['nullable', 'boolean'],
                'acidente_trajeto' => ['nullable', 'boolean'],
                'doenca_ocupacional' => ['nullable', 'boolean'],
                'recorrencia_atestados' => ['nullable', 'boolean'],
                'responsavel_recebimento' => ['nullable', 'string', 'max:120'],
                'especie_beneficio_inss' => ['nullable', 'string', 'max:80'],
                'cid' => ['nullable', 'string', 'max:20'],
                'atestado_medico' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
                'relatorio_medico' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
                'declaracao_comparecimento' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            ]);
        }

        $data = $request->validate($rules);

        $colaborador = Colaborador::query()->findOrFail($data['colaborador_id']);
        unset($data['colaborador_id']);

        if ($data['tipo'] === MovimentacaoChamadoTipo::DESLIGAMENTO) {
            if (trim((string) ($data['gestor_responsavel'] ?? '')) === '') {
                $data['gestor_responsavel'] = app(MovimentacaoSubstituicaoVagaService::class)->gestorDoContrato($colaborador);
            }
            abort_if(
                trim((string) ($data['gestor_responsavel'] ?? '')) === '',
                422,
                'Gestor responsável não encontrado. Cadastre o gestor no contrato do colaborador ou informe manualmente.'
            );
        }

        if ($data['tipo'] === MovimentacaoChamadoTipo::AFASTAMENTO_INSS) {
            $data = array_merge($data, $this->montarDadosAfastamentoInss($data, $colaborador));
        }

        if ($data['tipo'] === MovimentacaoChamadoTipo::DESLIGAMENTO) {
            $data = array_merge($data, $this->montarDadosDesligamento($data, $colaborador, $request->user()?->name));
        }

        $chamado = $service->abrir($colaborador, $data['tipo'], $data, $request->user()?->id);

        if ($data['tipo'] === MovimentacaoChamadoTipo::AFASTAMENTO_INSS) {
            $this->salvarAnexosAfastamento($request, $chamado, $request->user()?->id);
        }

        $chamado = $chamado->fresh();
        $mensagem = "Chamado {$chamado->protocolo} aberto. O cadastro do colaborador não foi alterado.";
        $redirect = redirect()->route('rh.chamados-movimentacao.show', $chamado)->with('success', $mensagem);

        $vagaId = $chamado->dados_depois_json['recrutamento_vaga_id'] ?? null;
        if ($vagaId) {
            $redirect->with('recrutamento_vaga_id', $vagaId);
        }

        return $redirect;
    }

    public function show(RhMovimentacaoChamado $chamado, MovimentacaoWorkflowService $workflow)
    {
        $chamado->load([
            'colaborador',
            'etapas.checklistItens',
            'etapaAtual',
            'anexos',
            'nadaConsta.itens',
            'chamadoOrigem:id,protocolo',
            'solicitante:id,name',
            'logs.usuario:id,name',
            'comentarios.usuario:id,name',
        ]);

        if ($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO) {
            if ($chamado->nadaConsta !== null) {
                app(MovimentacaoNadaConstaService::class)->sincronizarItensComCatalogo($chamado->nadaConsta);
                $chamado->load('nadaConsta.itens');
            }

            app(MovimentacaoDesligamentoAutoProgressaoService::class)
                ->sincronizar($chamado, auth()->id());
        }

        $workflow->sincronizarStatusChamado($chamado);
        $chamado->refresh();
        $chamado->load([
            'colaborador',
            'etapas.checklistItens',
            'etapaAtual',
            'anexos',
            'nadaConsta.itens.anexoEvidencia',
            'nadaConsta.itens.anexoTermoBaixa',
            'nadaConsta.itens.anexoAutorizacaoDesconto',
            'logs.usuario',
        ]);

        $pendencias = $workflow->podeFinalizar($chamado);
        $desligamentoRules = app(MovimentacaoDesligamentoRules::class);
        $acesso = app(MovimentacaoChamadoAcesso::class);
        $user = auth()->user();
        $bloqueiosPorEtapa = [];
        if ($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO) {
            foreach ($chamado->etapas as $etapa) {
                if (! $etapa->isConcluida()) {
                    $bloqueiosPorEtapa[$etapa->id] = $desligamentoRules->pendenciasConcluirEtapa($etapa, $chamado);
                }
            }
        }

        $pdfAnexo = $chamado->anexos->firstWhere('tipo_documento', MovimentacaoDesligamentoCatalog::ANEXO_CHAMADO_PDF);
        $nadaConstaPdfAnexo = $chamado->anexos->firstWhere('tipo_documento', MovimentacaoDesligamentoCatalog::ANEXO_NADA_CONSTA_PDF);

        $vagaSubstituicao = null;
        if ($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO) {
            $vagaId = $chamado->dados_depois_json['recrutamento_vaga_id'] ?? null;
            if ($vagaId) {
                $vagaSubstituicao = RecrutamentoVaga::query()->find($vagaId);
            }
        }

        return view('rh.chamados-movimentacao.show', [
            'chamado' => $chamado,
            'pendenciasFinalizacao' => $pendencias,
            'bloqueiosPorEtapa' => $bloqueiosPorEtapa,
            'podeEditar' => $chamado->isAberto() && $acesso->podeEditarChamado($user),
            'podeValidarRh' => $chamado->isAberto() && $acesso->podeValidarNadaConstaRh($user),
            'areasNadaConstaEditaveis' => $acesso->areasEditaveis($user),
            'pdfAnexo' => $pdfAnexo,
            'nadaConstaPdfAnexo' => $nadaConstaPdfAnexo,
            'labelsAnexosDesligamento' => MovimentacaoDesligamentoCatalog::labelsAnexos(),
            'anexosObrigatoriosDesligamento' => MovimentacaoDesligamentoCatalog::anexosObrigatoriosPorTipoRescisao(
                (string) ($chamado->dados_depois_json['tipo_rescisao'] ?? '')
            ),
            'conteudoPacoteDocumentos' => MovimentacaoDesligamentoCatalog::conteudoEsperadoPacoteDocumentos(
                (string) ($chamado->dados_depois_json['tipo_rescisao'] ?? '')
            ),
            'areasNadaConsta' => MovimentacaoDesligamentoCatalog::areasNadaConsta(),
            'labelsAreasNadaConsta' => MovimentacaoDesligamentoCatalog::labelsAreas(),
            'statusTratativa' => MovimentacaoDesligamentoCatalog::statusTratativa(),
            'classificacoesAfastamento' => MovimentacaoAfastamentoInssCatalog::classificacoes(),
            'resultadosFinais' => MovimentacaoAfastamentoInssCatalog::resultadosFinais(),
            'podeVerDadosSensiveis' => $this->podeVerDadosMedicosSensiveis(),
            'vagaSubstituicao' => $vagaSubstituicao,
        ]);
    }

    public function atualizarDadosAfastamento(Request $request, RhMovimentacaoChamado $chamado, MovimentacaoLogService $logService)
    {
        abort_unless($chamado->tipo === MovimentacaoChamadoTipo::AFASTAMENTO_INSS, 404);
        abort_unless($chamado->isAberto(), 422);

        $data = $request->validate([
            'classificacao' => ['nullable', 'string', Rule::in(array_keys(MovimentacaoAfastamentoInssCatalog::classificacoes()))],
            'resultado_final' => ['nullable', 'string', Rule::in(array_keys(MovimentacaoAfastamentoInssCatalog::resultadosFinais()))],
            'data_retorno_prevista' => ['nullable', 'date'],
            'data_retorno_real' => ['nullable', 'date'],
            'numero_beneficio_inss' => ['nullable', 'string', 'max:40'],
            'protocolo_esocial' => ['nullable', 'string', 'max:80'],
            'cid' => ['nullable', 'string', 'max:20'],
        ]);

        if (isset($data['cid']) && ! $this->podeVerDadosMedicosSensiveis()) {
            unset($data['cid']);
        }

        $depois = array_merge($chamado->dados_depois_json ?? [], array_filter($data, fn ($v) => $v !== null && $v !== ''));

        if (! empty($depois['quantidade_dias']) && (int) $depois['quantidade_dias'] > 15
            && empty($depois['classificacao'])) {
            $depois['classificacao'] = MovimentacaoAfastamentoInssCatalog::CLASS_SUPERIOR_15_INSS;
        }

        $chamado->update(['dados_depois_json' => $depois]);
        $logService->registrar($chamado, 'dados_afastamento_atualizados', null, null, array_keys($data), $request->user()?->id);

        return redirect()
            ->route('rh.chamados-movimentacao.show', $chamado)
            ->with('success', 'Dados do afastamento atualizados.');
    }

    public function pdf(RhMovimentacaoChamado $chamado, MovimentacaoChamadoPdfService $pdfService): \Symfony\Component\HttpFoundation\Response
    {
        $chamado->load(['colaborador', 'etapas.checklistItens', 'anexos', 'nadaConsta.itens', 'logs.usuario']);

        $conteudo = $pdfService->renderPdf($chamado);

        return response($conteudo, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="chamado-'.$chamado->protocolo.'.pdf"',
        ]);
    }

    public function nadaConstaPdf(RhMovimentacaoChamado $chamado, MovimentacaoNadaConstaPdfService $pdfService): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($chamado->tipo === 'desligamento', 404);

        $chamado->load(['colaborador', 'nadaConsta.itens']);
        $matricula = $chamado->colaborador->matricula ?? $chamado->colaborador_id;
        $conteudo = $pdfService->renderPdf($chamado);

        return response($conteudo, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="nada-consta-'.$matricula.'.pdf"',
        ]);
    }

    public function nadaConstaPdfArquivar(
        RhMovimentacaoChamado $chamado,
        MovimentacaoNadaConstaPdfService $pdfService,
        MovimentacaoLogService $logService,
    ): RedirectResponse {
        abort_unless($chamado->tipo === 'desligamento', 404);
        abort_unless($chamado->isAberto(), 422);

        $anexo = $pdfService->gerarEArmazenar($chamado, auth()->id());
        $logService->registrar($chamado, 'nada_consta_pdf_gerado', null, null, $anexo->nome_arquivo, auth()->id());

        return redirect()
            ->route('rh.chamados-movimentacao.show', $chamado)
            ->with('success', 'PDF do Nada Consta gerado e anexado ao chamado.');
    }

    public function downloadAnexo(RhMovimentacaoAnexo $anexo): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($anexo->caminho), 404);

        return Storage::disk('public')->download($anexo->caminho, $anexo->nome_arquivo);
    }

    public function anexoItemNadaConsta(
        Request $request,
        RhMovimentacaoNadaConstaItem $item,
        MovimentacaoNadaConstaService $nadaService,
        MovimentacaoLogService $logService
    ) {
        $item->load('nadaConsta.chamado');
        $chamado = $item->nadaConsta?->chamado;
        abort_if($chamado === null, 404);
        $this->abortSeChamadoSomenteLeitura($chamado);
        abort_unless(app(MovimentacaoChamadoAcesso::class)->podeEditarAreaNadaConsta($request->user(), $item->area), 403);

        $data = $request->validate([
            'tipo' => ['required', Rule::in(['evidencia', 'termo_baixa', 'autorizacao_desconto'])],
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $nadaService->anexarNoItem($item, $data['tipo'], $request->file('arquivo'), $request->user()?->id);
        $logService->registrar($chamado, 'nada_consta_item_anexo', 'item', (string) $item->id, $data['tipo'], $request->user()?->id);

        return redirect()->route('rh.chamados-movimentacao.show', $chamado)->with('success', 'Anexo do item registrado.');
    }

    public function toggleChecklist(RhMovimentacaoChecklistItem $item, MovimentacaoChamadoService $service)
    {
        $item->load('etapa.chamado');
        $chamado = $item->etapa?->chamado;
        abort_if($chamado === null, 404);
        $this->abortSeChamadoSomenteLeitura($chamado);

        $service->alternarChecklist($item, auth()->id());
        $item->refresh();

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'item_id' => $item->id,
                'status' => $item->status,
                'nome' => $item->nome,
            ]);
        }

        return redirect()
            ->route('rh.chamados-movimentacao.show', $chamado)
            ->with('success', 'Checklist atualizado.');
    }

    public function atualizarSigo(Request $request, RhMovimentacaoChamado $chamado, MovimentacaoLogService $logService)
    {
        abort_unless($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO, 404);
        $this->abortSeChamadoSomenteLeitura($chamado);

        $data = $request->validate([
            'cadastrado' => ['required', Rule::in(['1', '0', 1, 0, true, false])],
            'data_cadastro' => ['required', 'date'],
            'responsavel_cadastro' => ['required', 'string', 'max:120'],
            'protocolo_sigo' => ['nullable', 'string', 'max:80'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ]);

        $depois = $chamado->dados_depois_json ?? [];
        $depois['sigo'] = [
            'cadastrado' => filter_var($data['cadastrado'], FILTER_VALIDATE_BOOLEAN),
            'data_cadastro' => $data['data_cadastro'],
            'responsavel_cadastro' => $data['responsavel_cadastro'] ?: $this->nomeUsuarioLogado(),
            'protocolo_sigo' => $data['protocolo_sigo'] ?? null,
            'observacao' => $data['observacao'] ?? null,
        ];
        $chamado->update(['dados_depois_json' => $depois]);
        $logService->registrar($chamado, 'sigo_atualizado', null, null, null, $request->user()?->id);

        $chamado = $chamado->fresh(['etapas', 'anexos', 'nadaConsta.itens']);
        app(MovimentacaoDesligamentoAutoProgressaoService::class)->sincronizar($chamado, $request->user()?->id);

        return redirect()->route('rh.chamados-movimentacao.show', $chamado)->with('success', 'Dados do SIGO salvos.');
    }

    public function salvarAnexos(Request $request, RhMovimentacaoChamado $chamado, MovimentacaoLogService $logService)
    {
        abort_unless($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO, 404);
        $this->abortSeChamadoSomenteLeitura($chamado);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,zip', 'max:25600'],
            'etapa_slug' => ['nullable', 'string', 'max:60'],
        ]);

        $tipoPacote = MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS;
        $anexosAntigos = $chamado->anexos()->where('tipo_documento', $tipoPacote)->get();
        foreach ($anexosAntigos as $antigo) {
            Storage::disk('public')->delete($antigo->caminho);
            $antigo->delete();
        }

        $etapa = $chamado->etapas()->where('slug', $request->input('etapa_slug', 'cadastro_sigo'))->first();
        $file = $request->file('arquivo');
        $path = $file->store('rh/chamados-movimentacao/'.$chamado->id, 'public');

        RhMovimentacaoAnexo::query()->create([
            'chamado_id' => $chamado->id,
            'etapa_id' => $etapa?->id,
            'nome_arquivo' => $file->getClientOriginalName(),
            'caminho' => $path,
            'tipo_documento' => $tipoPacote,
            'obrigatorio' => true,
            'uploaded_by' => $request->user()?->id,
        ]);

        $logService->registrar($chamado, 'anexo_incluido', 'tipo_documento', null, $tipoPacote, $request->user()?->id);

        $chamado = $chamado->fresh(['etapas', 'anexos', 'nadaConsta.itens']);
        app(MovimentacaoDesligamentoAutoProgressaoService::class)->sincronizar($chamado, $request->user()?->id);

        return redirect()->route('rh.chamados-movimentacao.show', $chamado)->with('success', 'Pacote de documentos enviado.');
    }

    public function atualizarNadaConsta(Request $request, RhMovimentacaoChamado $chamado, MovimentacaoNadaConstaService $nadaService, MovimentacaoLogService $logService)
    {
        abort_unless($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO, 404);
        $this->abortSeChamadoSomenteLeitura($chamado);

        $data = $request->validate([
            'data_emissao' => ['nullable', 'date'],
            'gestor_contrato' => ['nullable', 'string', 'max:120'],
            'responsavel_rh' => ['nullable', 'string', 'max:120'],
            'observacao' => ['nullable', 'string'],
            'itens' => ['nullable', 'array'],
            'itens.*.id' => ['required_with:itens', 'integer'],
            'itens.*.tem_debito' => ['nullable', Rule::in(['0', '1', 0, 1, ''])],
            'itens.*.descricao_pendencia' => ['nullable', 'string', 'max:500'],
            'itens.*.valor_pendencia' => ['nullable', 'numeric', 'min:0'],
            'itens.*.status_tratativa' => ['nullable', 'string', Rule::in(array_keys(MovimentacaoDesligamentoCatalog::statusTratativa()))],
            'itens.*.responsavel_nome' => ['nullable', 'string', 'max:120'],
            'itens.*.observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $nada = $chamado->nadaConsta ?? $nadaService->inicializar($chamado);
        $nada->update(array_filter([
            'data_emissao' => $data['data_emissao'] ?? $nada->data_emissao,
            'gestor_contrato' => $data['gestor_contrato'] ?? null,
            'responsavel_rh' => $data['responsavel_rh'] ?? $this->nomeUsuarioLogado(),
            'observacao' => $data['observacao'] ?? null,
        ], fn ($v) => $v !== null));

        $acesso = app(MovimentacaoChamadoAcesso::class);
        if (! empty($data['itens'])) {
            $payload = [];
            foreach ($data['itens'] as $row) {
                $itemModel = RhMovimentacaoNadaConstaItem::query()->find($row['id'] ?? 0);
                if ($itemModel === null || ! $acesso->podeEditarAreaNadaConsta($request->user(), $itemModel->area)) {
                    continue;
                }
                $tem = $row['tem_debito'] ?? null;
                $temDebito = $tem === '' || $tem === null ? null : (bool) (int) $tem;
                $payload[] = [
                    'id' => (int) $row['id'],
                    'tem_debito' => $temDebito,
                    'descricao_pendencia' => $temDebito === true ? ($row['descricao_pendencia'] ?? null) : null,
                    'valor_pendencia' => $temDebito === true ? ($row['valor_pendencia'] ?? null) : null,
                    'status_tratativa' => $temDebito === true ? ($row['status_tratativa'] ?? null) : null,
                    'responsavel_nome' => $temDebito === true
                        ? (filled($row['responsavel_nome'] ?? null) ? $row['responsavel_nome'] : $this->nomeUsuarioLogado())
                        : null,
                    'observacao' => $row['observacao'] ?? null,
                ];
            }
            $nadaService->salvarItens($nada, $payload, $request->user()?->id);
        }

        $logService->registrar($chamado, 'nada_consta_atualizado', null, null, null, $request->user()?->id);

        return redirect()->route('rh.chamados-movimentacao.show', $chamado)->with('success', 'Nada Consta atualizado.');
    }

    public function validarNadaConstaRh(RhMovimentacaoChamado $chamado, MovimentacaoNadaConstaService $nadaService, MovimentacaoDesligamentoRules $rules)
    {
        abort_unless($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO, 404);
        $this->abortSeChamadoSomenteLeitura($chamado);
        abort_unless(app(MovimentacaoChamadoAcesso::class)->podeValidarNadaConstaRh(auth()->user()), 403);

        $chamado->loadMissing(['anexos', 'nadaConsta.itens']);
        $pendencias = $rules->pendenciasNadaConsta($chamado, paraAcaoValidarRh: true);
        if ($pendencias !== []) {
            return redirect()
                ->route('rh.chamados-movimentacao.show', $chamado)
                ->with('error', implode(' ', $pendencias));
        }

        $nada = $chamado->nadaConsta;
        abort_if($nada === null, 422);

        $nadaService->validarRh($nada, auth()->id());

        $chamado = $chamado->fresh(['etapas', 'anexos', 'nadaConsta.itens']);
        app(MovimentacaoDesligamentoAutoProgressaoService::class)->sincronizar($chamado, auth()->id());

        return redirect()->route('rh.chamados-movimentacao.show', $chamado)->with('success', 'Nada Consta validado pelo RH.');
    }

    public function concluirChecklistEtapa(
        RhMovimentacaoEtapa $etapa,
        MovimentacaoChamadoService $service,
    ) {
        $etapa->load('chamado');
        $chamado = $etapa->chamado;
        abort_if($chamado === null, 404);
        $this->abortSeChamadoSomenteLeitura($chamado);

        $itens = $service->concluirTodosChecklistDaEtapa($etapa, auth()->id());

        return response()->json([
            'ok' => true,
            'etapa_id' => $etapa->id,
            'itens' => collect($itens)->map(fn ($item) => [
                'item_id' => $item->id,
                'status' => $item->status,
                'nome' => $item->nome,
            ])->values()->all(),
        ]);
    }

    public function concluirEtapa(Request $request, RhMovimentacaoEtapa $etapa, MovimentacaoChamadoService $service)
    {
        $data = $request->validate([
            'observacao' => ['nullable', 'string', 'max:2000'],
        ]);

        $etapa->load('chamado');
        $this->abortSeChamadoSomenteLeitura($etapa->chamado);

        if ($request->expectsJson()) {
            $chamado = $etapa->chamado;
            if ($chamado->tipo === \App\Support\Rh\MovimentacaoChamadoTipo::DESLIGAMENTO) {
                $bloqueios = app(\App\Services\Rh\MovimentacaoDesligamentoRules::class)
                    ->pendenciasConcluirEtapa($etapa, $chamado);
                if ($bloqueios !== []) {
                    return response()->json([
                        'message' => implode(' ', $bloqueios),
                        'problemas' => $bloqueios,
                    ], 422);
                }
            }
        }

        try {
            $service->concluirEtapa($etapa, $data['observacao'] ?? null, $request->user()?->id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
            }

            throw $e;
        }

        $etapa->refresh();
        $chamado = $etapa->chamado?->fresh();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'etapa_id' => $etapa->id,
                'etapa_status' => $etapa->status,
                'chamado_status' => $chamado?->status,
            ]);
        }

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

    /** @param  array<string, mixed>  $data */
    private function montarDadosAfastamentoInss(array $data, Colaborador $colaborador): array
    {
        $inicio = Carbon::parse($data['data_inicio_afastamento']);
        $fim = ! empty($data['data_final_atestado']) ? Carbon::parse($data['data_final_atestado']) : null;
        $dias = $data['quantidade_dias'] ?? ($fim ? $inicio->diffInDays($fim) + 1 : null);

        $classificacao = null;
        if ($dias !== null && (int) $dias > 15) {
            $classificacao = MovimentacaoAfastamentoInssCatalog::CLASS_SUPERIOR_15_INSS;
        } elseif (! empty($data['acidente_trabalho']) || ($data['tipo_afastamento'] ?? '') === 'acidente_trabalho') {
            $classificacao = MovimentacaoAfastamentoInssCatalog::CLASS_ACIDENTE_TRABALHO;
        } elseif (! empty($data['acidente_trajeto'])) {
            $classificacao = MovimentacaoAfastamentoInssCatalog::CLASS_ACIDENTE_TRAJETO;
        } elseif (! empty($data['doenca_ocupacional'])) {
            $classificacao = MovimentacaoAfastamentoInssCatalog::CLASS_DOENCA_OCUPACIONAL;
        } elseif ($dias !== null && (int) $dias <= 15) {
            $classificacao = MovimentacaoAfastamentoInssCatalog::CLASS_ATESTATO_15;
        }

        return [
            'data_efetiva' => $data['data_inicio_afastamento'],
            'data_prevista' => $data['data_final_atestado'] ?? $data['data_prevista'] ?? null,
            'data_recebimento_atestado' => $data['data_recebimento_atestado'],
            'data_inicio_afastamento' => $data['data_inicio_afastamento'],
            'data_final_atestado' => $data['data_final_atestado'] ?? null,
            'quantidade_dias' => $dias,
            'tipo_afastamento' => $data['tipo_afastamento'],
            'doenca_comum' => (bool) ($data['doenca_comum'] ?? false),
            'acidente_trabalho' => (bool) ($data['acidente_trabalho'] ?? false),
            'acidente_trajeto' => (bool) ($data['acidente_trajeto'] ?? false),
            'doenca_ocupacional' => (bool) ($data['doenca_ocupacional'] ?? false),
            'recorrencia_atestados' => (bool) ($data['recorrencia_atestados'] ?? false),
            'responsavel_recebimento' => $data['responsavel_recebimento'] ?? null,
            'especie_beneficio_inss' => $data['especie_beneficio_inss'] ?? null,
            'cid' => $this->podeVerDadosMedicosSensiveis() ? ($data['cid'] ?? null) : null,
            'classificacao' => $classificacao,
            'colaborador_matricula' => $colaborador->matricula,
            'colaborador_contrato' => $colaborador->tipo_contrato,
            'colaborador_funcao' => $colaborador->cargo,
            'chamado_origem_id' => $data['chamado_origem_id'] ?? null,
            'motivo_texto' => $data['motivo_texto'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
        ];
    }

    private function salvarAnexosAfastamento(Request $request, RhMovimentacaoChamado $chamado, ?int $userId): void
    {
        $etapa = $chamado->etapas()->where('slug', 'registro_atestado')->first();

        $mapa = [
            'atestado_medico' => MovimentacaoAfastamentoInssCatalog::ANEXO_ATESTADO,
            'relatorio_medico' => MovimentacaoAfastamentoInssCatalog::ANEXO_RELATORIO,
            'declaracao_comparecimento' => MovimentacaoAfastamentoInssCatalog::ANEXO_COMPARECIMENTO,
        ];

        foreach ($mapa as $campo => $tipoDoc) {
            if (! $request->hasFile($campo)) {
                continue;
            }
            $file = $request->file($campo);
            $path = $file->store('rh/chamados-movimentacao/'.$chamado->id, 'public');
            RhMovimentacaoAnexo::query()->create([
                'chamado_id' => $chamado->id,
                'etapa_id' => $etapa?->id,
                'nome_arquivo' => $file->getClientOriginalName(),
                'caminho' => $path,
                'tipo_documento' => $tipoDoc,
                'obrigatorio' => $tipoDoc === MovimentacaoAfastamentoInssCatalog::ANEXO_ATESTADO,
                'uploaded_by' => $userId,
            ]);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function montarDadosDesligamento(array $data, Colaborador $colaborador, ?string $solicitanteNome): array
    {
        $gestor = trim((string) ($data['gestor_responsavel'] ?? ''));
        if ($gestor === '') {
            $gestor = app(MovimentacaoSubstituicaoVagaService::class)->gestorDoContrato($colaborador);
        }

        return [
            'data_efetiva' => $data['data_prevista'] ?? $data['data_efetiva'] ?? null,
            'data_prevista' => $data['data_prevista'] ?? null,
            'ultimo_dia_trabalhado' => $data['ultimo_dia_trabalhado'],
            'tipo_rescisao' => $data['tipo_rescisao'],
            'motivo_texto' => $data['motivo_texto'],
            'gestor_responsavel' => $gestor,
            'havera_substituicao_vaga' => $data['havera_substituicao_vaga'],
            'observacoes' => $data['observacoes'] ?? null,
            'colaborador_matricula' => $colaborador->matricula,
            'colaborador_contrato' => $colaborador->tipo_contrato ?? $colaborador->centro_custo,
            'colaborador_funcao' => $colaborador->cargo,
            'colaborador_setor' => $colaborador->departamento,
            'solicitante_nome' => $solicitanteNome,
            'responsavel_rh' => $solicitanteNome,
        ];
    }

    private function nomeUsuarioLogado(): string
    {
        return trim((string) (auth()->user()?->name ?? ''));
    }

    private function abortSeChamadoSomenteLeitura(RhMovimentacaoChamado $chamado): void
    {
        abort_unless($chamado->isAberto(), 422, 'Chamado encerrado — somente leitura.');
        abort_unless(app(MovimentacaoChamadoAcesso::class)->podeEditarChamado(auth()->user()), 403);
    }

    private function podeVerDadosMedicosSensiveis(): bool
    {
        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        // Fase 1: perfis finos (gestor limitado) na fase 2; usuários autenticados no módulo RH.
        return true;
    }
}
