<?php

namespace App\Http\Controllers\Almoxarifado;

use App\Http\Controllers\Controller;
use App\Models\Almoxarifado\MobilizacaoMaterial;
use App\Models\Almoxarifado\MobilizacaoMaterialAnexo;
use App\Models\Almoxarifado\MobilizacaoMaterialCategoria;
use App\Models\Almoxarifado\MobilizacaoMaterialRecebimento;
use App\Models\Contrato;
use App\Support\Almoxarifado\AlmoxarifadoAcesso;
use App\Support\Almoxarifado\MobilizacaoCobrancaTextoService;
use App\Support\Almoxarifado\MobilizacaoMaterialAnexoTipo;
use App\Support\Almoxarifado\MobilizacaoMaterialCalculoService;
use App\Support\Almoxarifado\MobilizacaoMaterialHistoricoService;
use App\Support\Almoxarifado\MobilizacaoMaterialPrioridade;
use App\Support\Almoxarifado\MobilizacaoMaterialStatus;
use App\Support\Almoxarifado\MobilizacaoMateriaisExcelExport;
use App\Support\Almoxarifado\MobilizacaoPlanilhaCatalogo;
use App\Support\ContratoAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MobilizacaoMaterialController extends Controller
{
    public function __construct(
        private MobilizacaoMaterialCalculoService $calculo,
        private MobilizacaoMaterialHistoricoService $historico,
        private MobilizacaoCobrancaTextoService $cobrancaTexto,
    ) {}

    public function painel()
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeVisualizar());

        $base = $this->queryBase();
        $indicadores = $this->montarIndicadores($base);

        return view('almoxarifado.mobilizacao.painel', [
            'indicadores' => $indicadores,
            'contratos' => $this->contratosParaSelect(),
            'categorias' => $this->categoriasAtivas(),
            'statusLabels' => MobilizacaoMaterialStatus::labels(),
            'prioridadeLabels' => MobilizacaoMaterialPrioridade::labels(),
        ]);
    }

    public function index()
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeVisualizar());

        $base = $this->queryBase();
        $indicadores = $this->montarIndicadores($base);
        $itens = $this->aplicarFiltros($base)
            ->with(['contrato', 'categoria'])
            ->orderByRaw("CASE prioridade WHEN 'CRITICA' THEN 1 WHEN 'ALTA' THEN 2 WHEN 'MEDIA' THEN 3 WHEN 'BAIXA' THEN 4 ELSE 5 END")
            ->orderBy('descricao_material')
            ->paginate(25)
            ->withQueryString();

        return view('almoxarifado.mobilizacao.index', [
            'itens' => $itens,
            'indicadores' => $indicadores,
            'contratos' => $this->contratosParaSelect(),
            'categorias' => $this->categoriasAtivas(),
            'statusLabels' => MobilizacaoMaterialStatus::labels(),
            'statusBadges' => MobilizacaoMaterialStatus::badgeClasses(),
            'prioridadeLabels' => MobilizacaoMaterialPrioridade::labels(),
            'acesso' => [
                'criar' => AlmoxarifadoAcesso::podeCriarMaterial(),
                'exportar' => AlmoxarifadoAcesso::podeExportar(),
                'cobranca' => AlmoxarifadoAcesso::podeVisualizar(),
                'alterarStatus' => AlmoxarifadoAcesso::podeEditarMaterialBasico(),
                'cancelar' => AlmoxarifadoAcesso::podeCancelarItem(),
            ],
        ]);
    }

    public function exportarExcel(Request $request)
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeExportar());

        $itens = $this->aplicarFiltros($this->queryBase())
            ->with(['contrato', 'categoria'])
            ->orderBy('descricao_material')
            ->get();

        $titulo = 'Controle de Materiais da Mobilização';
        if ($request->filled('relatorio')) {
            $titulo .= ' — '.str_replace('_', ' ', (string) $request->input('relatorio'));
        }

        return MobilizacaoMateriaisExcelExport::download($itens, $titulo);
    }

    public function gerarCobranca(Request $request)
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeVisualizar());

        $data = $request->validate([
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['integer', 'exists:mobilizacao_materiais,id'],
        ]);

        $itens = $this->queryBase()
            ->whereIn('id', $data['itens'])
            ->get();

        abort_unless($itens->count() === count($data['itens']), 403);

        return response()->json([
            'texto' => $this->cobrancaTexto->gerar($itens),
        ]);
    }

    public function create()
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeCriarMaterial());

        $contratoPadrao = $this->contratoReferencia312();

        return view('almoxarifado.mobilizacao.form', $this->dadosFormulario(
            new MobilizacaoMaterial([
                'ativo' => true,
                'prioridade' => MobilizacaoMaterialPrioridade::MEDIA,
                'contrato_id' => $contratoPadrao?->id,
                'situacao_tratativa' => 'SEM TRATATIVA LOCALIZADA',
            ])
        ));
    }

    public function store(Request $request)
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeCriarMaterial());

        $data = $this->validarCadastro($request);
        ContratoAccess::authorizeContratoId((int) $data['contrato_id']);

        $material = new MobilizacaoMaterial($data);
        $material->created_by = auth()->id();
        $material->updated_by = auth()->id();
        $material->origem_cadastro = 'MANUAL';
        $this->calculo->recalcular($material);
        $material->save();

        return redirect()
            ->route('almoxarifado.mobilizacao-materiais.show', $material)
            ->with('success', 'Material cadastrado com sucesso.');
    }

    public function show(MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeVisualizar());

        $mobilizacaoMaterial->load(['contrato', 'categoria', 'recebimentos.autor', 'anexos.uploader', 'historicos.usuario']);

        $calculo = $this->calculo;

        return view('almoxarifado.mobilizacao.show', [
            'material' => $mobilizacaoMaterial,
            'statusLabels' => MobilizacaoMaterialStatus::labels(),
            'statusBadges' => MobilizacaoMaterialStatus::badgeClasses(),
            'prioridadeLabels' => MobilizacaoMaterialPrioridade::labels(),
            'anexoTipos' => MobilizacaoMaterialAnexoTipo::labels(),
            'alertas' => [
                'atrasado' => $calculo->estaAtrasado($mobilizacaoMaterial),
                'sem_previsao' => $calculo->semPrevisaoEmCompras($mobilizacaoMaterial),
                'divergencia' => $calculo->divergenciaQuantidade($mobilizacaoMaterial),
            ],
            'acesso' => $this->acessoView(),
        ]);
    }

    public function edit(MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeEditarMaterialBasico());

        return view('almoxarifado.mobilizacao.form', $this->dadosFormulario($mobilizacaoMaterial));
    }

    public function update(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeEditarMaterialBasico());

        $antes = $mobilizacaoMaterial->only(MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO);
        $data = $this->validarCadastro($request, $mobilizacaoMaterial);
        ContratoAccess::authorizeContratoId((int) $data['contrato_id']);

        if (! AlmoxarifadoAcesso::podeAlterarQuantidadeNecessaria()) {
            unset($data['quantidade_necessaria']);
        }

        if ($mobilizacaoMaterial->status === MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO
            && ($data['status'] ?? '') !== MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO) {
            AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeReabrirItem());
        }

        $mobilizacaoMaterial->fill($data);
        $mobilizacaoMaterial->updated_by = auth()->id();

        $forcarStatus = ($data['status'] ?? $mobilizacaoMaterial->status) !== MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO;
        $this->calculo->recalcular($mobilizacaoMaterial, $forcarStatus);
        $mobilizacaoMaterial->save();

        $this->historico->registrarAlteracoes($mobilizacaoMaterial, $antes);

        return redirect()
            ->route('almoxarifado.mobilizacao-materiais.show', $mobilizacaoMaterial)
            ->with('success', 'Material atualizado com sucesso.');
    }

    public function updateSigo(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeAtualizarSigo());

        $antes = $mobilizacaoMaterial->only(MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO);

        $data = $request->validate([
            'numero_pm' => ['nullable', 'string', 'max:80'],
            'data_pedido_sigo' => ['nullable', 'date'],
            'quantidade_pedida_sigo' => ['nullable', 'numeric', 'min:0'],
            'observacao_pedido' => ['nullable', 'string', 'max:2000'],
        ]);

        $obs = trim((string) ($data['observacao_pedido'] ?? ''));
        unset($data['observacao_pedido']);
        if ($obs !== '') {
            $prefix = '[SIGO] '.$obs;
            $data['observacao_almoxarife'] = filled($mobilizacaoMaterial->observacao_almoxarife)
                ? $mobilizacaoMaterial->observacao_almoxarife."\n".$prefix
                : $prefix;
        }

        $mobilizacaoMaterial->fill($data);
        $mobilizacaoMaterial->updated_by = auth()->id();
        $this->calculo->recalcular($mobilizacaoMaterial);
        $mobilizacaoMaterial->save();
        $this->historico->registrarAlteracoes($mobilizacaoMaterial, $antes, observacao: 'Atualização SIGO');

        return back()->with('success', 'Dados do SIGO salvos.');
    }

    public function updateCompras(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeAtualizarCompras());

        $antes = $mobilizacaoMaterial->only(MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO);

        $data = $request->validate([
            'numero_oc' => ['nullable', 'string', 'max:80'],
            'comprador_responsavel' => ['nullable', 'string', 'max:120'],
            'fornecedor' => ['nullable', 'string', 'max:180'],
            'quantidade_em_compra' => ['required', 'numeric', 'min:0'],
            'data_inicio_compra' => ['nullable', 'date'],
            'previsao_entrega' => ['nullable', 'date'],
            'observacao_compras' => ['nullable', 'string', 'max:2000'],
        ]);

        $obs = trim((string) ($data['observacao_compras'] ?? ''));
        unset($data['observacao_compras']);
        if ($obs !== '') {
            $prefix = '[Compras] '.$obs;
            $data['observacao_gestao'] = filled($mobilizacaoMaterial->observacao_gestao)
                ? $mobilizacaoMaterial->observacao_gestao."\n".$prefix
                : $prefix;
        }

        $mobilizacaoMaterial->fill($data);
        $mobilizacaoMaterial->updated_by = auth()->id();
        $this->calculo->recalcular($mobilizacaoMaterial);
        $mobilizacaoMaterial->save();
        $this->historico->registrarAlteracoes($mobilizacaoMaterial, $antes, observacao: 'Atualização Compras');

        return back()->with('success', 'Dados de Compras salvos.');
    }

    public function storeRecebimento(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeRegistrarRecebimento());

        $data = $request->validate([
            'data_recebimento' => ['required', 'date'],
            'quantidade_recebida' => ['required', 'numeric', 'min:0.01'],
            'responsavel_recebimento' => ['nullable', 'string', 'max:120'],
            'numero_nf' => ['nullable', 'string', 'max:80'],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'anexo' => ['nullable', 'file', 'max:10240'],
        ]);

        $antes = $mobilizacaoMaterial->only(MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO);

        MobilizacaoMaterialRecebimento::create([
            'mobilizacao_material_id' => $mobilizacaoMaterial->id,
            'data_recebimento' => $data['data_recebimento'],
            'quantidade_recebida' => $data['quantidade_recebida'],
            'responsavel_recebimento' => $data['responsavel_recebimento'] ?? auth()->user()?->name,
            'numero_nf' => $data['numero_nf'] ?? null,
            'observacao' => $data['observacao'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $mobilizacaoMaterial->quantidade_recebida = (float) $mobilizacaoMaterial->quantidade_recebida
            + (float) $data['quantidade_recebida'];
        $mobilizacaoMaterial->updated_by = auth()->id();
        $this->calculo->recalcular($mobilizacaoMaterial);
        $mobilizacaoMaterial->save();
        $this->historico->registrarAlteracoes($mobilizacaoMaterial, $antes, observacao: 'Recebimento registrado');

        if ($request->hasFile('anexo')) {
            $this->salvarAnexo($request->file('anexo'), $mobilizacaoMaterial, MobilizacaoMaterialAnexoTipo::COMPROVANTE_RECEBIMENTO);
        }

        $aviso = '';
        if ($this->calculo->divergenciaQuantidade($mobilizacaoMaterial)) {
            $aviso = ' Atenção: quantidade recebida acima da necessária (divergência).';
        }

        return back()->with('success', 'Recebimento registrado.'.$aviso);
    }

    public function storeAnexo(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeAnexar());

        $data = $request->validate([
            'tipo_anexo' => ['required', Rule::in(array_keys(MobilizacaoMaterialAnexoTipo::labels()))],
            'arquivo' => ['required', 'file', 'max:10240'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $this->salvarAnexo($request->file('arquivo'), $mobilizacaoMaterial, $data['tipo_anexo'], $data['observacao'] ?? null);

        return back()->with('success', 'Anexo enviado com sucesso.');
    }

    public function cancelar(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeCancelarItem());

        $data = $request->validate([
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $antes = $mobilizacaoMaterial->only(MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO);
        $mobilizacaoMaterial->status = MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO;
        $mobilizacaoMaterial->acao_do_dia = 'Sem ação.';
        $mobilizacaoMaterial->observacao_gestao = trim(
            ($mobilizacaoMaterial->observacao_gestao ? $mobilizacaoMaterial->observacao_gestao."\n" : '')
            .'[Cancelado] '.$data['justificativa']
        );
        $mobilizacaoMaterial->updated_by = auth()->id();
        $mobilizacaoMaterial->save();
        $this->historico->registrarAlteracoes($mobilizacaoMaterial, $antes, observacao: 'Cancelamento: '.$data['justificativa']);

        return back()->with('success', 'Item marcado como cancelado / não necessário.');
    }

    public function reabrir(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeReabrirItem());

        $data = $request->validate([
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $antes = $mobilizacaoMaterial->only(MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO);
        $mobilizacaoMaterial->observacao_gestao = trim(
            ($mobilizacaoMaterial->observacao_gestao ? $mobilizacaoMaterial->observacao_gestao."\n" : '')
            .'[Reaberto] '.$data['justificativa']
        );
        $mobilizacaoMaterial->updated_by = auth()->id();
        $this->calculo->recalcular($mobilizacaoMaterial, true);
        $mobilizacaoMaterial->save();
        $this->historico->registrarAlteracoes($mobilizacaoMaterial, $antes, observacao: 'Reabertura: '.$data['justificativa']);

        return back()->with('success', 'Item reaberto. Status recalculado automaticamente.');
    }

    public function updateStatus(Request $request, MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeEditarMaterialBasico());

        $data = $request->validate([
            'status' => ['required', Rule::in(MobilizacaoMaterialStatus::all())],
        ]);

        $novoStatus = $data['status'];

        if ($novoStatus === MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO) {
            AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeCancelarItem());
        }

        if ($mobilizacaoMaterial->status === MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO
            && $novoStatus !== MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO) {
            AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeReabrirItem());
        }

        $antes = $mobilizacaoMaterial->only(MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO);
        $mobilizacaoMaterial->status = $novoStatus;
        $mobilizacaoMaterial->updated_by = auth()->id();
        $this->calculo->recalcular($mobilizacaoMaterial, false);
        $mobilizacaoMaterial->save();
        $this->historico->registrarAlteracoes($mobilizacaoMaterial, $antes, observacao: 'Alteração de status na lista');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'status' => $mobilizacaoMaterial->status,
                'label' => MobilizacaoMaterialStatus::labels()[$mobilizacaoMaterial->status] ?? $mobilizacaoMaterial->status,
            ]);
        }

        return back()->with('success', 'Status atualizado.');
    }

    public function destroy(MobilizacaoMaterial $mobilizacaoMaterial)
    {
        $this->authorizeMaterial($mobilizacaoMaterial);
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::isGestao());

        abort_if($mobilizacaoMaterial->temMovimentacao(), 422, 'Não é permitido excluir item com recebimento ou movimentação.');

        $mobilizacaoMaterial->delete();

        return redirect()
            ->route('almoxarifado.mobilizacao-materiais.index')
            ->with('success', 'Material removido.');
    }

    private function queryBase(): Builder
    {
        $query = MobilizacaoMaterial::query()->where('mobilizacao_materiais.ativo', true);

        if (ContratoAccess::shouldRestrict()) {
            $ids = ContratoAccess::contratoIds();
            $query = empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('contrato_id', $ids);
        }

        return $query;
    }

    private function aplicarFiltros(Builder $query): Builder
    {
        if ($contratoId = request('contrato_id')) {
            $query->where('contrato_id', $contratoId);
        }

        if ($categoriaId = request('categoria_id')) {
            $query->where('categoria_id', $categoriaId);
        }

        if ($disciplina = request('disciplina')) {
            $query->where('disciplina', $disciplina);
        }

        if ($categoriaDesc = request('categoria_descricao')) {
            $query->where('categoria_descricao', $categoriaDesc);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($prioridade = request('prioridade')) {
            $query->where('prioridade', $prioridade);
        }

        if ($pm = request('numero_pm')) {
            $query->where('numero_pm', 'like', '%'.$pm.'%');
        }

        if ($oc = request('numero_oc')) {
            $query->where('numero_oc', 'like', '%'.$oc.'%');
        }

        if ($comprador = request('comprador_responsavel')) {
            $query->where('comprador_responsavel', 'like', '%'.$comprador.'%');
        }

        if ($fornecedor = request('fornecedor')) {
            $query->where('fornecedor', 'like', '%'.$fornecedor.'%');
        }

        if ($previsao = request('previsao_entrega')) {
            $query->whereDate('previsao_entrega', $previsao);
        }

        if ($busca = request('busca')) {
            $query->where(function (Builder $q) use ($busca) {
                $q->where('codigo_material', 'like', "%{$busca}%")
                    ->orWhere('descricao_material', 'like', "%{$busca}%")
                    ->orWhere('numero_pm', 'like', "%{$busca}%")
                    ->orWhere('numero_oc', 'like', "%{$busca}%");
            });
        }

        $filtroRapido = request('filtro_rapido');
        if ($filtroRapido) {
            $query = $this->aplicarFiltroRapido($query, $filtroRapido);
        }

        return $query;
    }

    private function aplicarFiltroRapido(Builder $query, string $filtro): Builder
    {
        $hoje = now()->toDateString();

        return match ($filtro) {
            'sem_tratativa' => $query->where('status', MobilizacaoMaterialStatus::SEM_TRATATIVA),
            'pedido_sigo' => $query->where('status', MobilizacaoMaterialStatus::PEDIDO_NO_SIGO),
            'em_compras' => $query->where('status', MobilizacaoMaterialStatus::EM_COMPRAS),
            'compra_parcial' => $query->where('status', MobilizacaoMaterialStatus::COMPRA_PARCIAL),
            'recebido_parcial' => $query->where('status', MobilizacaoMaterialStatus::RECEBIDO_PARCIAL),
            'recebido_total' => $query->where('status', MobilizacaoMaterialStatus::RECEBIDO_TOTAL),
            'cancelado' => $query->where('status', MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO),
            'atrasados' => $query->where('status', '!=', MobilizacaoMaterialStatus::RECEBIDO_TOTAL)
                ->where('status', '!=', MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO)
                ->whereNotNull('previsao_entrega')
                ->whereDate('previsao_entrega', '<', $hoje),
            'sem_previsao' => $query->where('status', MobilizacaoMaterialStatus::EM_COMPRAS)
                ->whereNull('previsao_entrega'),
            'criticos' => $query->where('prioridade', MobilizacaoMaterialPrioridade::CRITICA),
            default => $query,
        };
    }

    /** @return array<string, int> */
    private function montarIndicadores(Builder $base): array
    {
        $hoje = now()->toDateString();
        $clone = fn () => clone $base;

        return [
            'total' => $clone()->count(),
            'sem_tratativa' => $clone()->where('status', MobilizacaoMaterialStatus::SEM_TRATATIVA)->count(),
            'pedido_sigo' => $clone()->where('status', MobilizacaoMaterialStatus::PEDIDO_NO_SIGO)->count(),
            'em_compras' => $clone()->where('status', MobilizacaoMaterialStatus::EM_COMPRAS)->count(),
            'compra_parcial' => $clone()->where('status', MobilizacaoMaterialStatus::COMPRA_PARCIAL)->count(),
            'recebido_parcial' => $clone()->where('status', MobilizacaoMaterialStatus::RECEBIDO_PARCIAL)->count(),
            'recebido_total' => $clone()->where('status', MobilizacaoMaterialStatus::RECEBIDO_TOTAL)->count(),
            'cancelado' => $clone()->where('status', MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO)->count(),
            'atrasados' => $clone()
                ->whereNotIn('status', [MobilizacaoMaterialStatus::RECEBIDO_TOTAL, MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO])
                ->whereNotNull('previsao_entrega')
                ->whereDate('previsao_entrega', '<', $hoje)
                ->count(),
            'sem_previsao' => $clone()
                ->where('status', MobilizacaoMaterialStatus::EM_COMPRAS)
                ->whereNull('previsao_entrega')
                ->count(),
        ];
    }

    private function authorizeMaterial(MobilizacaoMaterial $material): void
    {
        ContratoAccess::authorizeContratoId((int) $material->contrato_id);
    }

    private function validarCadastro(Request $request, ?MobilizacaoMaterial $existente = null): array
    {
        $rules = [
            'contrato_id' => ['required', 'exists:contratos,id'],
            'disciplina' => ['required', Rule::in(MobilizacaoPlanilhaCatalogo::disciplinas())],
            'categoria_descricao' => ['required', Rule::in(MobilizacaoPlanilhaCatalogo::categorias())],
            'situacao_tratativa' => ['required', Rule::in(MobilizacaoPlanilhaCatalogo::situacoesTratativa())],
            'situacao_sigo_descricao' => ['nullable', 'string', 'max:120'],
            'categoria_id' => ['nullable', 'exists:mobilizacao_material_categorias,id'],
            'codigo_material' => ['nullable', 'string', 'max:80'],
            'descricao_material' => ['required', 'string', 'max:5000'],
            'unidade_medida' => ['nullable', 'string', 'max:30'],
            'quantidade_necessaria' => ['required', 'numeric', 'min:0'],
            'quantidade_pedida_sigo' => ['nullable', 'numeric', 'min:0'],
            'quantidade_em_compra' => ['nullable', 'numeric', 'min:0'],
            'quantidade_recebida' => ['nullable', 'numeric', 'min:0'],
            'prioridade' => ['nullable', Rule::in(MobilizacaoMaterialPrioridade::all())],
            'observacao_gestao' => ['nullable', 'string', 'max:5000'],
            'observacao_almoxarife' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(MobilizacaoMaterialStatus::all())],
            'ativo' => ['nullable', 'boolean'],
        ];

        if (! AlmoxarifadoAcesso::podeAlterarQuantidadeNecessaria()) {
            $rules['quantidade_necessaria'] = ['prohibited'];
        }

        if (! AlmoxarifadoAcesso::podeAtualizarSigo()) {
            $rules['quantidade_pedida_sigo'] = ['prohibited'];
        }

        if (! AlmoxarifadoAcesso::podeAtualizarCompras()) {
            $rules['quantidade_em_compra'] = ['prohibited'];
        }

        if (! AlmoxarifadoAcesso::isGestao()) {
            $rules['quantidade_recebida'] = ['prohibited'];
        }

        $data = $request->validate($rules);
        $data['ativo'] = $request->boolean('ativo', true);

        if (! isset($data['prioridade']) || $data['prioridade'] === '') {
            $data['prioridade'] = MobilizacaoMaterialPrioridade::MEDIA;
        }

        if ($existente && ! AlmoxarifadoAcesso::isGestao()) {
            unset($data['status']);
        }

        return $data;
    }

    private function salvarAnexo($file, MobilizacaoMaterial $material, string $tipo, ?string $observacao = null): void
    {
        $path = $file->store('almoxarifado/mobilizacao/'.$material->id, 'public');
        MobilizacaoMaterialAnexo::create([
            'mobilizacao_material_id' => $material->id,
            'tipo_anexo' => $tipo,
            'nome_arquivo' => $file->getClientOriginalName(),
            'caminho_arquivo' => $path,
            'observacao' => $observacao,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, Contrato> */
    private function contratosParaSelect()
    {
        return ContratoAccess::applyContratoModel(Contrato::query())
            ->orderBy('numero')
            ->get(['id', 'numero', 'nome']);
    }

    /** @return array<string, mixed> */
    private function dadosFormulario(MobilizacaoMaterial $material): array
    {
        return [
            'material' => $material,
            'contratos' => $this->contratosParaSelect(),
            'categorias' => $this->categoriasAtivas(),
            'statusLabels' => MobilizacaoMaterialStatus::labels(),
            'prioridadeLabels' => MobilizacaoMaterialPrioridade::labels(),
            'podeAlterarNecessaria' => AlmoxarifadoAcesso::podeAlterarQuantidadeNecessaria(),
            'podeEditarSigo' => AlmoxarifadoAcesso::podeAtualizarSigo(),
            'podeEditarCompras' => AlmoxarifadoAcesso::podeAtualizarCompras(),
            'planilha' => [
                'disciplinas' => MobilizacaoPlanilhaCatalogo::disciplinas(),
                'categorias' => MobilizacaoPlanilhaCatalogo::categorias(),
                'situacoesTratativa' => MobilizacaoPlanilhaCatalogo::situacoesTratativa(),
                'situacoesSigo' => MobilizacaoPlanilhaCatalogo::situacoesSigo(),
                'rotulos' => MobilizacaoPlanilhaCatalogo::rotulos(),
            ],
        ];
    }

    private function contratoReferencia312(): ?Contrato
    {
        return ContratoAccess::applyContratoModel(Contrato::query())
            ->where(function (Builder $q) {
                $q->where(function (Builder $exact) {
                    $exact->where('numero', MobilizacaoPlanilhaCatalogo::CONTRATO_NUMERO_REFERENCIA)
                        ->where('nome', MobilizacaoPlanilhaCatalogo::CONTRATO_NOME_REFERENCIA);
                })
                    ->orWhere('centro_custo', MobilizacaoPlanilhaCatalogo::CONTRATO_CENTRO_CUSTO_REFERENCIA)
                    ->orWhere(function (Builder $legacy) {
                        $legacy->where('numero', 'like', '%312%')
                            ->where('numero', 'like', '%ATHENAS%')
                            ->where('nome', 'like', '%SALOBO%');
                    });
            })
            ->orderByRaw('CASE WHEN numero = ? AND nome = ? THEN 0 WHEN centro_custo = ? THEN 1 ELSE 2 END', [
                MobilizacaoPlanilhaCatalogo::CONTRATO_NUMERO_REFERENCIA,
                MobilizacaoPlanilhaCatalogo::CONTRATO_NOME_REFERENCIA,
                MobilizacaoPlanilhaCatalogo::CONTRATO_CENTRO_CUSTO_REFERENCIA,
            ])
            ->first();
    }

    /** @return \Illuminate\Support\Collection<int, MobilizacaoMaterialCategoria> */
    private function categoriasAtivas()
    {
        return MobilizacaoMaterialCategoria::query()
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get();
    }

    /** @return array<string, bool> */
    private function acessoView(): array
    {
        return [
            'editar' => AlmoxarifadoAcesso::podeEditarMaterialBasico(),
            'sigo' => AlmoxarifadoAcesso::podeAtualizarSigo(),
            'compras' => AlmoxarifadoAcesso::podeAtualizarCompras(),
            'recebimento' => AlmoxarifadoAcesso::podeRegistrarRecebimento(),
            'anexo' => AlmoxarifadoAcesso::podeAnexar(),
            'cancelar' => AlmoxarifadoAcesso::podeCancelarItem(),
            'reabrir' => AlmoxarifadoAcesso::podeReabrirItem(),
            'gestao' => AlmoxarifadoAcesso::isGestao(),
        ];
    }
}
