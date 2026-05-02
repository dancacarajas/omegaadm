<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Veiculo;
use App\Models\VeiculoMobilizacao;
use App\Models\VeiculoSolicitacao;
use App\Support\ContratoAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VeiculoController extends Controller
{
    public function index()
    {
        $solicitacoes = ContratoAccess::applyContratoString(VeiculoSolicitacao::query())
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('placa', 'like', "%{$busca}%")
                        ->orWhere('modelo', 'like', "%{$busca}%")
                        ->orWhere('marca', 'like', "%{$busca}%")
                        ->orWhere('contrato', 'like', "%{$busca}%")
                        ->orWhere('linha_contratual', 'like', "%{$busca}%")
                        ->orWhere('finalidade', 'like', "%{$busca}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('veiculos.index', compact('solicitacoes'));
    }

    public function create()
    {
        return view('veiculos.create', ['solicitacao' => null]);
    }

    public function editSolicitacao(VeiculoSolicitacao $solicitacao)
    {
        $this->authorizeContratoString($solicitacao->contrato);

        return view('veiculos.create', compact('solicitacao'));
    }

    public function storeSolicitacao(Request $request)
    {
        $solicitacao = VeiculoSolicitacao::create($this->solicitacaoData($request));
        $step = $this->normalizeStep($request->input('current_step'));

        return redirect()
            ->route('veiculos.solicitacoes.edit', ['solicitacao' => $solicitacao, 'step' => $step])
            ->with('success', 'Solicitacao salva. Voce pode atualizar a pagina sem perder os dados gravados.');
    }

    public function updateSolicitacao(Request $request, VeiculoSolicitacao $solicitacao)
    {
        $this->authorizeContratoString($solicitacao->contrato);

        $solicitacao->update($this->solicitacaoData($request, $solicitacao));
        $step = $this->normalizeStep($request->input('current_step'));

        return redirect()
            ->route('veiculos.solicitacoes.edit', ['solicitacao' => $solicitacao, 'step' => $step])
            ->with('success', 'Solicitacao atualizada. Os dados continuam salvos nesta ficha.');
    }

    public function destroySolicitacao(VeiculoSolicitacao $solicitacao)
    {
        $this->authorizeContratoString($solicitacao->contrato);

        $paths = [
            $solicitacao->crlv_path,
            $solicitacao->tag_evidencia_path,
            $solicitacao->subcontratacao_evidencia_path,
            $solicitacao->subcontratacao_cartao_cnpj_path,
            $solicitacao->subcontratacao_minuta_path,
            $solicitacao->subcontratacao_contrato_social_path,
            $solicitacao->subcontratacao_documento_veiculo_path,
            $solicitacao->svg_evidencia_path,
        ];

        $documentosAdicionais = collect($solicitacao->documentos_adicionais ?? [])
            ->pluck('path')
            ->filter()
            ->all();

        Storage::disk('public')->delete(array_filter(array_merge($paths, $documentosAdicionais)));
        $solicitacao->delete();

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Mobilizacao excluida com sucesso.');
    }

    private function authorizeContratoString(?string $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }

        abort_unless($contrato && in_array($contrato, ContratoAccess::contratoValores(), true), 404);
    }

    private function solicitacaoData(Request $request, ?VeiculoSolicitacao $solicitacao = null): array
    {
        $data = $request->validate([
            'status' => ['required', 'in:em_andamento,concluido,bloqueado'],
            'current_step' => ['nullable', 'string'],
            'data_inicio_atividade' => ['nullable', 'date'],
            'data_fim_atividade' => ['nullable', 'date'],
            'data_liberacao_inspecao' => ['nullable', 'date'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'linha_contratual' => ['nullable', 'string', 'max:255'],
            'criterio_tecnico' => ['nullable', 'string', 'max:255'],
            'finalidade' => ['nullable', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'checklist_data' => ['nullable', 'array'],
            'placa' => ['nullable', 'string', 'max:20'],
            'renavam' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'ano_fabricacao' => ['nullable', 'string', 'max:4'],
            'ano_modelo' => ['nullable', 'string', 'max:4'],
            'cor' => ['nullable', 'string', 'max:255'],
            'proprietario' => ['nullable', 'string', 'max:255'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'crlv' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'documentos_adicionais' => ['nullable', 'array'],
            'documentos_adicionais.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
            'tag_checklist_data' => ['nullable', 'array'],
            'tag_numero_protocolo' => ['nullable', 'string', 'max:255'],
            'tag_data_solicitacao' => ['nullable', 'date'],
            'tag_evidencia' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'tag_observacoes' => ['nullable', 'string'],
            'subcontratacao_checklist_data' => ['nullable', 'array'],
            'subcontratacao_data_analise' => ['nullable', 'date'],
            'subcontratacao_data_autorizacao' => ['nullable', 'date'],
            'subcontratacao_protocolo' => ['nullable', 'string', 'max:255'],
            'subcontratacao_cartao_cnpj' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'subcontratacao_minuta' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'subcontratacao_contrato_social' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'subcontratacao_documento_veiculo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'subcontratacao_evidencia' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'subcontratacao_observacoes' => ['nullable', 'string'],
            'svg_checklist_data' => ['nullable', 'array'],
            'svg_data_postagem' => ['nullable', 'date'],
            'svg_protocolo' => ['nullable', 'string', 'max:255'],
            'svg_evidencia' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'svg_observacoes' => ['nullable', 'string'],
            'vistoria_checklist_data' => ['nullable', 'array'],
            'vistoria_previsao_inicio' => ['nullable', 'date'],
            'vistoria_previsao_fim' => ['nullable', 'date'],
            'vistoria_data_agendada' => ['nullable', 'date'],
            'vistoria_resultado' => ['nullable', 'in:pendente,aprovado,reprovado,com_pendencia'],
            'vistoria_observacoes' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $data['checklist_data'] = [
            'veiculo_solicitado' => filled($data['finalidade'] ?? null),
            'periodo_definido' => filled($data['data_inicio_atividade'] ?? null) && filled($data['data_fim_atividade'] ?? null),
            'inspecao_prevista' => filled($data['data_liberacao_inspecao'] ?? null),
            'linha_confirmada' => filled($data['contrato'] ?? null) && filled($data['linha_contratual'] ?? null),
            'criterios_conferidos' => filled($data['criterio_tecnico'] ?? null),
            'anexo_validado' => true,
        ];

        if ($request->hasFile('crlv')) {
            $data['crlv_path'] = $request->file('crlv')->store('veiculos/crlv', 'public');
        }

        if ($request->hasFile('documentos_adicionais')) {
            $data['documentos_adicionais'] = collect($request->file('documentos_adicionais'))
                ->map(fn ($file) => [
                    'nome' => $file->getClientOriginalName(),
                    'path' => $file->store('veiculos/documentos-adicionais', 'public'),
                ])
                ->values()
                ->all();
        }

        if ($request->hasFile('tag_evidencia')) {
            $data['tag_evidencia_path'] = $request->file('tag_evidencia')->store('veiculos/tag-evidencias', 'public');
        }

        if ($request->hasFile('subcontratacao_evidencia')) {
            $data['subcontratacao_evidencia_path'] = $request->file('subcontratacao_evidencia')->store('veiculos/subcontratacao-evidencias', 'public');
        }

        foreach ([
            'subcontratacao_cartao_cnpj' => 'subcontratacao_cartao_cnpj_path',
            'subcontratacao_minuta' => 'subcontratacao_minuta_path',
            'subcontratacao_contrato_social' => 'subcontratacao_contrato_social_path',
            'subcontratacao_documento_veiculo' => 'subcontratacao_documento_veiculo_path',
        ] as $input => $column) {
            if ($request->hasFile($input)) {
                $data[$column] = $request->file($input)->store('veiculos/subcontratacao-documentos', 'public');
            }
        }

        if ($request->hasFile('svg_evidencia')) {
            $data['svg_evidencia_path'] = $request->file('svg_evidencia')->store('veiculos/svg-evidencias', 'public');
        }

        $hasStoredFile = fn (string $column): bool => filled($data[$column] ?? null) || filled($solicitacao?->{$column});

        $data['tag_checklist_data'] = [
            'crlv_conferido' => $hasStoredFile('crlv_path'),
            'dados_completos' => collect(['placa', 'renavam', 'marca', 'modelo', 'ano_fabricacao', 'proprietario'])
                ->every(fn ($field) => filled($data[$field] ?? null)),
            'evidencia_salva' => filled($data['tag_data_solicitacao'] ?? null)
                && filled($data['tag_numero_protocolo'] ?? null)
                && $hasStoredFile('tag_evidencia_path'),
        ];

        $data['subcontratacao_checklist_data'] = [
            'analise_inicial' => filled($data['subcontratacao_data_analise'] ?? null),
            'autorizacao_aprovada' => filled($data['subcontratacao_data_autorizacao'] ?? null),
        ];

        $data['svg_checklist_data'] = [
            'documentacao_reunida' => $hasStoredFile('crlv_path')
                && $hasStoredFile('subcontratacao_cartao_cnpj_path')
                && $hasStoredFile('subcontratacao_minuta_path')
                && $hasStoredFile('subcontratacao_contrato_social_path')
                && $hasStoredFile('subcontratacao_documento_veiculo_path'),
            'mobilizacao_postada' => filled($data['svg_data_postagem'] ?? null)
                && filled($data['svg_protocolo'] ?? null)
                && $hasStoredFile('svg_evidencia_path'),
            'fluxo_acompanhado' => filled($data['svg_data_postagem'] ?? null) && filled($data['svg_protocolo'] ?? null),
            'pendencias_corrigidas' => filled($data['svg_data_postagem'] ?? null) && filled($data['svg_protocolo'] ?? null),
        ];

        $data['vistoria_checklist_data'] = [
            'data_prevista' => filled($data['vistoria_previsao_inicio'] ?? null) && filled($data['vistoria_previsao_fim'] ?? null),
            'veiculo_disponivel' => filled($data['vistoria_data_agendada'] ?? null),
            'checklist_revisado' => filled($data['vistoria_data_agendada'] ?? null),
            'resultado_registrado' => filled($data['vistoria_resultado'] ?? null),
        ];

        unset(
            $data['crlv'],
            $data['tag_evidencia'],
            $data['subcontratacao_evidencia'],
            $data['subcontratacao_cartao_cnpj'],
            $data['subcontratacao_minuta'],
            $data['subcontratacao_contrato_social'],
            $data['subcontratacao_documento_veiculo'],
            $data['svg_evidencia'],
        );

        return $data;
    }

    private function normalizeStep(?string $step): string
    {
        $allowedSteps = [
            'step-inicial',
            'step-veiculo',
            'step-tag',
            'step-subcontratacao',
            'step-svg',
            'step-vistoria',
            'step-finalizacao',
        ];

        return in_array($step, $allowedSteps, true) ? $step : 'step-inicial';
    }

    public function store(Request $request)
    {
        $veiculo = Veiculo::create($this->validatedData($request));
        $this->sincronizarMobilizacao($veiculo);
        $this->atualizarEtapaInicial($request, $veiculo);
        $this->atualizarStatusMobilizacao($veiculo);

        return redirect()
            ->route('veiculos.show', $veiculo)
            ->with('success', 'Veiculo cadastrado e Step 01 iniciado.');
    }

    public function show(Veiculo $veiculo)
    {
        $this->sincronizarMobilizacao($veiculo);
        $veiculo->load('mobilizacoes');

        return view('veiculos.show', compact('veiculo'));
    }

    public function edit(Veiculo $veiculo)
    {
        return view('veiculos.edit', compact('veiculo'));
    }

    public function update(Request $request, Veiculo $veiculo)
    {
        $veiculo->update($this->validatedData($request, $veiculo));
        $this->sincronizarMobilizacao($veiculo);
        $this->atualizarStatusMobilizacao($veiculo);

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veiculo atualizado.');
    }

    public function destroy(Veiculo $veiculo)
    {
        $veiculo->delete();

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veiculo removido.');
    }

    public function updateMobilizacao(Request $request, VeiculoMobilizacao $mobilizacao)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pendente,em_andamento,concluido,bloqueado'],
            'data_prevista' => ['nullable', 'date'],
            'data_realizada' => ['nullable', 'date'],
            'numero_solicitacao' => ['nullable', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'link_evidencia' => ['nullable', 'string', 'max:255'],
            'checklist_data' => ['nullable', 'array'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $data['checklist_data'] = collect($request->input('checklist_data', []))
            ->map(fn ($value) => (bool) $value)
            ->all();

        if ($data['status'] === 'concluido' && empty($data['data_realizada'])) {
            $data['data_realizada'] = now()->toDateString();
        }

        if ($data['status'] !== 'concluido') {
            $data['data_realizada'] = null;
        }

        $mobilizacao->update($data);
        $this->atualizarStatusMobilizacao($mobilizacao->veiculo);

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Etapa de mobilizacao atualizada.');
    }

    private function validatedData(Request $request, ?Veiculo $veiculo = null): array
    {
        $id = $veiculo?->id;

        return $request->validate([
            'placa' => ['required', 'string', 'max:20', 'unique:veiculos,placa,'.$id],
            'renavam' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'ano_fabricacao' => ['nullable', 'string', 'max:4'],
            'ano_modelo' => ['nullable', 'string', 'max:4'],
            'cor' => ['nullable', 'string', 'max:255'],
            'proprietario' => ['nullable', 'string', 'max:255'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'linha_contratual' => ['nullable', 'string', 'max:255'],
            'criterio_tecnico' => ['nullable', 'string', 'max:255'],
            'data_inicio_atividade' => ['nullable', 'date'],
            'data_fim_atividade' => ['nullable', 'date'],
            'data_liberacao_inspecao' => ['nullable', 'date'],
            'status' => ['required', 'in:ativo,inativo,manutencao'],
            'observacoes' => ['nullable', 'string'],
        ]);
    }

    private function sincronizarMobilizacao(Veiculo $veiculo): void
    {
        foreach (VeiculoMobilizacao::ETAPAS_PADRAO as $etapa) {
            VeiculoMobilizacao::firstOrCreate([
                'veiculo_id' => $veiculo->id,
                'etapa' => $etapa,
            ]);
        }
    }

    private function atualizarStatusMobilizacao(Veiculo $veiculo): void
    {
        $mobilizacoes = $veiculo->mobilizacoes()->get();

        $status = match (true) {
            $mobilizacoes->firstWhere('etapa', 'FINALIZACAO')?->status === 'concluido' => 'mobilizacao_concluida',
            $mobilizacoes->contains('status', 'bloqueado') => 'bloqueado',
            $mobilizacoes->contains(fn ($item) => in_array($item->status, ['em_andamento', 'concluido'], true)) => 'em_mobilizacao',
            default => 'pendente',
        };

        $veiculo->updateQuietly(['mobilizacao_status' => $status]);
    }

    private function atualizarEtapaInicial(Request $request, Veiculo $veiculo): void
    {
        $data = $request->validate([
            'mobilizacao.status' => ['nullable', 'in:pendente,em_andamento,concluido,bloqueado'],
            'mobilizacao.data_prevista' => ['nullable', 'date'],
            'mobilizacao.data_realizada' => ['nullable', 'date'],
            'mobilizacao.numero_solicitacao' => ['nullable', 'string', 'max:255'],
            'mobilizacao.responsavel' => ['nullable', 'string', 'max:255'],
            'mobilizacao.link_evidencia' => ['nullable', 'string', 'max:255'],
            'mobilizacao.checklist_data' => ['nullable', 'array'],
            'mobilizacao.observacoes' => ['nullable', 'string'],
        ])['mobilizacao'] ?? [];

        if ($data === []) {
            return;
        }

        $data['status'] ??= 'em_andamento';
        $data['checklist_data'] = collect($data['checklist_data'] ?? [])
            ->map(fn ($value) => (bool) $value)
            ->all();

        if ($data['status'] === 'concluido' && empty($data['data_realizada'])) {
            $data['data_realizada'] = now()->toDateString();
        }

        if ($data['status'] !== 'concluido') {
            $data['data_realizada'] = null;
        }

        $veiculo->mobilizacoes()
            ->where('etapa', 'ETAPA_INICIAL')
            ->first()
            ?->update($data);
    }
}
