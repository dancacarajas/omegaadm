@php
    $dados = $chamado->dados_depois_json ?? [];
    $sigo = $dados['sigo'] ?? [];
    $usuarioLogado = auth()->user()?->name ?? '';
    $nada = $chamado->nadaConsta;
    $editavel = ($podeEditar ?? true) && $chamado->isAberto();
    $tiposUpload = array_diff_key($labelsAnexosDesligamento ?? [], [\App\Support\Rh\MovimentacaoDesligamentoCatalog::ANEXO_CHAMADO_PDF => true]);
    $areasEdit = $areasNadaConstaEditaveis ?? array_keys($labelsAreasNadaConsta ?? []);
@endphp

<section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
    <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5">
        <h3 class="text-lg font-bold text-zinc-900">Desligamento — SIGO, anexos e Nada Consta</h3>
        <p class="text-xs text-zinc-500">SIGO e Nada Consta são obrigatórios antes da finalização. Áreas do Nada Consta respeitam permissões do perfil.</p>
    </div>
    <div class="space-y-8 p-6 sm:p-8">
        @if (($dados['havera_substituicao_vaga'] ?? '') === 'sim' && ($vagaSubstituicao ?? null))
            <div class="flex flex-col gap-3 rounded-2xl border border-sky-200/80 bg-gradient-to-r from-sky-50/90 to-white p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm">
                        <i data-lucide="briefcase" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-sky-950">Vaga de substituição criada automaticamente</p>
                        <p class="mt-0.5 text-xs text-sky-800/90">
                            Contrato (centro de custo): <strong>{{ $vagaSubstituicao->contrato }}</strong>
                            · {{ $vagaSubstituicao->titulo }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('rh.recrutamento.edit', $vagaSubstituicao) }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl bg-sky-700 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-sky-800">
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                    Abrir no recrutamento
                </a>
            </div>
        @endif
        <div id="secao-sigo">
            <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-zinc-800">
                <i data-lucide="database" class="h-4 w-4 text-brand-burgundy"></i>
                Cadastro no SIGO
            </h4>
            @if ($editavel)
                <form method="POST" action="{{ route('rh.chamados-movimentacao.sigo', $chamado) }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <label class="space-y-1 text-xs font-semibold text-zinc-600">Cadastrado no SIGO
                        <select name="cadastrado" required class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                            <option value="1" @selected(($sigo['cadastrado'] ?? false) == true)>Sim</option>
                            <option value="0" @selected(isset($sigo['cadastrado']) && ! $sigo['cadastrado'])>Não</option>
                        </select>
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-zinc-600">Data do cadastro
                        <input type="date" name="data_cadastro" value="{{ $sigo['data_cadastro'] ?? today()->format('Y-m-d') }}" required class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-zinc-600">Responsável pelo cadastro
                        <input type="text" name="responsavel_cadastro" value="{{ $sigo['responsavel_cadastro'] ?? $usuarioLogado }}" required readonly class="mt-1 h-10 w-full cursor-not-allowed rounded-xl border border-zinc-200 bg-zinc-100 px-3 text-sm text-zinc-700">
                        <span class="mt-0.5 block text-[10px] font-normal text-zinc-500">Usuário logado no sistema</span>
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-zinc-600">Protocolo SIGO
                        <input type="text" name="protocolo_sigo" value="{{ $sigo['protocolo_sigo'] ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-zinc-600 sm:col-span-2">Observação
                        <textarea name="observacao" rows="2" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">{{ $sigo['observacao'] ?? '' }}</textarea>
                    </label>
                    <div class="sm:col-span-2"><button type="submit" class="inline-flex h-10 items-center rounded-xl bg-brand-burgundy px-4 text-xs font-bold text-white">Salvar SIGO</button></div>
                </form>
            @else
                <p class="text-sm text-zinc-600">SIGO: {{ ($sigo['cadastrado'] ?? false) ? 'Cadastrado' : '—' }} · {{ $sigo['data_cadastro'] ?? '—' }} · {{ $sigo['responsavel_cadastro'] ?? '—' }}</p>
            @endif
        </div>

        <div id="secao-sigo-anexos" class="scroll-mt-6">
            <h4 class="mb-3 text-sm font-bold text-zinc-800">Anexos obrigatórios</h4>
            <ul class="mb-4 space-y-1 text-xs">
                @foreach ($anexosObrigatoriosDesligamento ?? [] as $tipo)
                    @php $ok = $chamado->anexos->contains(fn ($a) => $a->tipo_documento === $tipo); @endphp
                    <li class="flex items-center gap-2 {{ $ok ? 'text-emerald-700' : 'text-amber-800' }}">
                        <i data-lucide="{{ $ok ? 'check-circle' : 'circle' }}" class="h-3.5 w-3.5"></i>
                        {{ $labelsAnexosDesligamento[$tipo] ?? $tipo }}
                    </li>
                @endforeach
            </ul>
            @if ($chamado->anexos->where('tipo_documento', '!=', \App\Support\Rh\MovimentacaoDesligamentoCatalog::ANEXO_CHAMADO_PDF)->isNotEmpty())
                <ul class="mb-4 divide-y rounded-xl border border-zinc-200 text-xs">
                    @foreach ($chamado->anexos->where('tipo_documento', '!=', \App\Support\Rh\MovimentacaoDesligamentoCatalog::ANEXO_CHAMADO_PDF) as $anexo)
                        <li class="flex justify-between gap-2 px-3 py-2">
                            <span>{{ $labelsAnexosDesligamento[$anexo->tipo_documento] ?? $anexo->tipo_documento }}</span>
                            <a href="{{ route('rh.chamados-movimentacao.anexos.download', $anexo) }}" class="font-bold text-brand-burgundy hover:underline">Baixar</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($editavel)
                <form method="POST" action="{{ route('rh.chamados-movimentacao.anexos', $chamado) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <label class="text-xs font-semibold text-zinc-600">Tipo
                        <select name="tipo_documento" required class="mt-1 h-10 rounded-xl border border-zinc-200 px-3 text-sm">
                            @foreach ($tiposUpload as $k => $l)
                                <option value="{{ $k }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-zinc-600">Arquivo
                        <input type="file" name="arquivo" required accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block text-sm">
                    </label>
                    <button type="submit" class="inline-flex h-10 items-center rounded-xl border border-zinc-200 bg-white px-4 text-xs font-bold text-brand-burgundy">Enviar</button>
                </form>
            @endif
        </div>

        @if ($nada)
            <div id="secao-nada-consta" class="scroll-mt-6">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-sm font-bold text-zinc-800">Nada Consta Demissional</h4>
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-[10px] font-bold uppercase">{{ $nada->statusLabel() }}</span>
                </div>
                @if ($editavel)
                    <form method="POST" action="{{ route('rh.chamados-movimentacao.nada-consta', $chamado) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="text-xs font-semibold text-zinc-600">Data emissão
                                <input type="date" name="data_emissao" value="{{ $nada->data_emissao?->format('Y-m-d') ?? today()->format('Y-m-d') }}" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                            </label>
                            <label class="text-xs font-semibold text-zinc-600">Gestor contrato
                                <input type="text" name="gestor_contrato" value="{{ $nada->gestor_contrato ?? $dados['gestor_responsavel'] ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                            </label>
                            <label class="text-xs font-semibold text-zinc-600">Responsável RH
                                <input type="text" name="responsavel_rh" value="{{ $nada->responsavel_rh ?? $usuarioLogado }}" readonly class="mt-1 h-10 w-full cursor-not-allowed rounded-xl border border-zinc-200 bg-zinc-100 px-3 text-sm text-zinc-700">
                            </label>
                            <label class="text-xs font-semibold text-zinc-600">Assinatura colaborador
                                <input type="text" name="assinatura_colaborador" value="{{ $nada->assinatura_colaborador ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                            </label>
                            <label class="text-xs font-semibold text-zinc-600 sm:col-span-2">Assinatura gestor
                                <input type="text" name="assinatura_gestor" value="{{ $nada->assinatura_gestor ?? '' }}" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                            </label>
                            <label class="text-xs font-semibold text-zinc-600 sm:col-span-2">Justificativa / observação
                                <textarea name="observacao" rows="2" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">{{ $nada->observacao ?? '' }}</textarea>
                            </label>
                        </div>
                        @foreach ($areasNadaConsta ?? [] as $area => $defItens)
                            @php $podeArea = in_array($area, $areasEdit, true); @endphp
                            <div class="rounded-2xl border p-4 {{ $podeArea ? 'border-zinc-200/80 bg-zinc-50/50' : 'border-zinc-100 bg-zinc-100/50 opacity-80' }}">
                                <p class="mb-3 text-xs font-bold uppercase text-brand-burgundy">
                                    {{ $labelsAreasNadaConsta[$area] ?? $area }}
                                    @unless ($podeArea)<span class="ml-2 text-zinc-500 normal-case">(somente leitura)</span>@endunless
                                </p>
                                <div class="space-y-4">
                                    @foreach ($nada->itens->where('area', $area) as $item)
                                        @php $nomeItem = collect($defItens)->firstWhere('slug', $item->item)['nome'] ?? $item->item; @endphp
                                        <div class="rounded-xl border border-white bg-white p-3 shadow-sm">
                                            <p class="mb-2 text-xs font-semibold text-zinc-800">{{ $nomeItem }}</p>
                                            @if ($podeArea)
                                                <input type="hidden" name="itens[{{ $item->id }}][id]" value="{{ $item->id }}">
                                                <div class="grid gap-2 sm:grid-cols-3">
                                                    <label class="text-[10px] font-bold text-zinc-500">Tem débito?
                                                        <select name="itens[{{ $item->id }}][tem_debito]" class="mt-0.5 h-9 w-full rounded-lg border border-zinc-200 text-xs">
                                                            <option value="">—</option>
                                                            <option value="0" @selected($item->tem_debito === false)>Não</option>
                                                            <option value="1" @selected($item->tem_debito === true)>Sim</option>
                                                        </select>
                                                    </label>
                                                    <label class="text-[10px] font-bold text-zinc-500 sm:col-span-2">Tratativa
                                                        <select name="itens[{{ $item->id }}][status_tratativa]" class="mt-0.5 h-9 w-full rounded-lg border border-zinc-200 text-xs">
                                                            @foreach ($statusTratativa ?? [] as $k => $l)
                                                                <option value="{{ $k }}" @selected($item->status_tratativa === $k)>{{ $l }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="text-[10px] font-bold text-zinc-500 sm:col-span-3">Descrição pendência
                                                        <input type="text" name="itens[{{ $item->id }}][descricao_pendencia]" value="{{ $item->descricao_pendencia }}" class="mt-0.5 h-9 w-full rounded-lg border border-zinc-200 px-2 text-xs">
                                                    </label>
                                                    <label class="text-[10px] font-bold text-zinc-500">Valor
                                                        <input type="number" step="0.01" name="itens[{{ $item->id }}][valor_pendencia]" value="{{ $item->valor_pendencia }}" class="mt-0.5 h-9 w-full rounded-lg border border-zinc-200 px-2 text-xs">
                                                    </label>
                                                    <label class="text-[10px] font-bold text-zinc-500 sm:col-span-2">Responsável pela validação
                                                        <input type="text" name="itens[{{ $item->id }}][responsavel_nome]" value="{{ $item->responsavel_nome ?? $usuarioLogado }}" class="mt-0.5 h-9 w-full rounded-lg border border-zinc-200 px-2 text-xs">
                                                    </label>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-2 border-t border-zinc-100 pt-3">
                                                    @foreach (['evidencia' => 'Evidência', 'termo_baixa' => 'Termo de baixa', 'autorizacao_desconto' => 'Autorização desconto'] as $tipoAnexo => $labelAnexo)
                                                        <form method="POST" action="{{ route('rh.chamados-movimentacao.nada-consta-item.anexo', $item) }}" enctype="multipart/form-data" class="inline-flex flex-wrap items-center gap-1 rounded-lg border border-zinc-200 bg-zinc-50 px-2 py-1">
                                                            @csrf
                                                            <input type="hidden" name="tipo" value="{{ $tipoAnexo }}">
                                                            <span class="text-[10px] font-bold text-zinc-600">{{ $labelAnexo }}</span>
                                                            <input type="file" name="arquivo" accept=".pdf,.jpg,.jpeg,.png,.webp" class="max-w-[140px] text-[10px]" required>
                                                            <button type="submit" class="text-[10px] font-bold text-brand-burgundy">OK</button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-[10px] text-zinc-600">Débito: {{ $item->tem_debito === null ? '—' : ($item->tem_debito ? 'Sim' : 'Não') }} · {{ $item->statusTratativaLabel() }}</p>
                                            @endif
                                            <div class="mt-2 flex flex-wrap gap-2 text-[10px]">
                                                @if ($item->anexoEvidencia)<a href="{{ route('rh.chamados-movimentacao.anexos.download', $item->anexoEvidencia) }}" class="text-brand-burgundy font-bold">Evidência</a>@endif
                                                @if ($item->anexoTermoBaixa)<a href="{{ route('rh.chamados-movimentacao.anexos.download', $item->anexoTermoBaixa) }}" class="text-brand-burgundy font-bold">Termo baixa</a>@endif
                                                @if ($item->anexoAutorizacaoDesconto)<a href="{{ route('rh.chamados-movimentacao.anexos.download', $item->anexoAutorizacaoDesconto) }}" class="text-brand-burgundy font-bold">Aut. desconto</a>@endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-zinc-800 px-4 text-xs font-bold text-white">Salvar Nada Consta</button>
                    </form>
                    @if (($podeValidarRh ?? false) && ! $nada->validado_rh)
                        <form method="POST" action="{{ route('rh.chamados-movimentacao.nada-consta.validar-rh', $chamado) }}" class="mt-3" onsubmit="return confirm('Validar Nada Consta pelo RH?')">
                            @csrf
                            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white">
                                <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                                Validar Nada Consta (RH)
                            </button>
                        </form>
                    @elseif ($nada->validado_rh)
                        <p class="mt-2 text-xs font-semibold text-emerald-700">Validado pelo RH em {{ $nada->validado_rh_em?->format('d/m/Y H:i') }}</p>
                    @endif
                @else
                    <p class="text-sm text-zinc-600">Status: {{ $nada->statusLabel() }} · Validado RH: {{ $nada->validado_rh ? 'Sim' : 'Não' }}</p>
                @endif
            </div>
        @endif
    </div>
</section>
