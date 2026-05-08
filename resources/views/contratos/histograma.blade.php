@extends($layout ?? 'layouts.app')

@section('title', 'Histograma de contrato - Omega286')
@section('eyebrow', $histogramaEyebrow ?? 'Contrato')
@section('page-title', 'Histograma')

@section('content')
    @php
        $fmtQtd = static fn ($valor) => rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
        $fmtData = static fn (?string $ymd) => $ymd ? \Carbon\Carbon::parse($ymd)->format('d/m/Y') : '';
    @endphp
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif
    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Histograma de mão de obra por contrato</h2>
            <p class="mt-1 text-sm text-brand-gray">Competência = mês-base dos dados (snapshot) · Pré-PGU = mobilização disponível · PGU = necessidade prevista · A data limite da Fase 2 é um prazo calendário e pode estar em mês diferente da competência.</p>
        </div>
        <form method="GET" class="grid gap-3 p-5 md:grid-cols-[1fr_170px_auto] md:items-end">
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                <select name="contrato" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Selecionar...</option>
                    @foreach ($contratos as $contrato)
                        <option value="{{ $contrato }}" @selected($contratoSelecionado === $contrato)>{{ $contrato }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência</span>
                <input type="month" name="competencia" value="{{ $competenciaMes }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <button class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                Carregar
            </button>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <form
            method="POST"
            action="{{ route($salvarRoute ?? 'contratos.histograma.salvar') }}"
            class="space-y-0"
            data-histograma-form
            data-hoje="{{ $histogramaHoje }}"
            data-limite="{{ $dataLimiteEtapa2 ?? '' }}"
            data-inicio-monitoramento="{{ $inicioMonitoramento ?? '' }}"
        >
            @csrf
            <input type="hidden" name="contrato" value="{{ $contratoSelecionado }}">
            <input type="hidden" name="competencia" value="{{ $competenciaMes }}">

            <div class="flex flex-col gap-4 border-b border-zinc-200 p-5 lg:flex-row lg:flex-wrap lg:items-end lg:justify-between">
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-bold text-brand-black">
                        {{ $contratoSelecionado ?: 'Selecione um contrato' }} · {{ \Carbon\Carbon::createFromFormat('Y-m', $competenciaMes)->format('m/Y') }}
                    </h3>
                    <p class="text-sm text-brand-gray">Use “Grupo” para linhas de título e “Item” para linhas detalhadas.</p>
                    @if ($contratoSelecionado)
                        <p class="mt-2 text-xs text-brand-gray">
                            Ação recomendada e responsável do <strong class="font-semibold text-brand-black">Slide 5</strong> (plano executivo PGU) são editados no módulo
                            <a href="{{ route('contratos.acoes-recomendadas.index', ['contrato' => $contratoSelecionado, 'competencia' => $competenciaMes]) }}" class="font-semibold text-brand-burgundy underline-offset-2 hover:underline">Ações recomendadas</a>.
                        </p>
                    @endif
                </div>
                <label class="w-full min-w-[220px] max-w-xs lg:w-auto">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Início do monitoramento</span>
                    <input
                        type="date"
                        name="inicio_monitoramento"
                        value="{{ $inicioMonitoramento ?? '' }}"
                        class="mt-2 h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"
                    >
                    <p class="mt-1.5 text-xs leading-snug text-brand-gray">Define a data inicial oficial do período para cálculo dos indicadores do ciclo (avanço, variações e marcos).</p>
                </label>
                <label class="w-full min-w-[220px] max-w-xs lg:w-auto">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data limite da transição Fase 1 → Fase 2</span>
                    <input
                        type="date"
                        name="data_limite_etapa_2"
                        value="{{ $dataLimiteEtapa2 ?? '' }}"
                        data-limite-input
                        class="mt-2 h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"
                    >
                    <p class="mt-1.5 text-xs leading-snug text-brand-gray">Após esta data, cada <strong class="font-semibold text-brand-black">item</strong> com <strong>PGU maior que Pré-PGU</strong> (necessidade acima da mobilização) é sinalizado como atraso na transição.</p>
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-add-grupo class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-sm font-semibold text-brand-black transition hover:border-brand-burgundy hover:text-brand-burgundy">
                        + Grupo
                    </button>
                    <button type="button" data-add-item class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-sm font-semibold text-brand-black transition hover:border-brand-burgundy hover:text-brand-burgundy">
                        + Item
                    </button>
                    <span data-autosave-status class="inline-flex h-10 items-center rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs font-semibold text-brand-gray">
                        Auto-save ativo
                    </span>
                    <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                        Salvar histograma
                    </button>
                </div>
            </div>

            @if ($situacaoPrazo === 'vencido_atraso' && $dataLimiteEtapa2)
                <div class="border-b border-red-200 bg-red-50 px-5 py-3 text-sm text-red-950">
                    <p class="font-bold">Prazo vencido — transição Fase 1 → Fase 2</p>
                    <p class="mt-1">Data limite era <strong>{{ $fmtData($dataLimiteEtapa2) }}</strong>. Existem <strong>{{ $contagemAtrasadas }}</strong> item(ns) com necessidade de PGU acima da mobilização Pré-PGU (destaque em vermelho na tabela).</p>
                </div>
            @elseif ($situacaoPrazo === 'vencido_ok' && $dataLimiteEtapa2)
                <div class="border-b border-emerald-200 bg-emerald-50 px-5 py-3 text-sm text-emerald-950">
                    <p class="font-bold">Prazo vencido — situação em dia</p>
                    <p class="mt-1">Data limite era <strong>{{ $fmtData($dataLimiteEtapa2) }}</strong>. Nenhum item ficou com folga de PGU em relação ao Pré-PGU.</p>
                </div>
            @elseif ($situacaoPrazo === 'futuro' && $dataLimiteEtapa2)
                <div class="border-b border-sky-200 bg-sky-50 px-5 py-3 text-sm text-sky-950">
                    <p class="font-bold">Prazo da transição em aberto</p>
                    <p class="mt-1">
                        Data limite: <strong>{{ $fmtData($dataLimiteEtapa2) }}</strong>.
                        @if (($diasAteLimite ?? 0) === 0)
                            <span>Hoje é o último dia do prazo.</span>
                        @else
                            Faltam <strong>{{ $diasAteLimite }}</strong> dia(s).
                        @endif
                    </p>
                </div>
            @endif

            <div class="border-b border-zinc-200 bg-zinc-50/80 px-5 py-3 text-xs text-brand-gray">
                <p><strong class="text-brand-black">Legenda da tabela:</strong> linhas <span class="rounded bg-emerald-100 px-1.5 py-0.5 font-semibold text-emerald-900">Item</span> ficam verdes quando <strong>todas as vagas da função concluírem os steps do recrutamento</strong>. Enquanto não concluir, a linha mostra barra de progresso. Linhas amarelas = <strong>Grupo</strong> (título).</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1280px] text-left text-sm">
                    <thead class="sticky top-0 z-20 border-b-2 border-zinc-300 bg-zinc-50 text-xs uppercase tracking-wide text-brand-gray shadow-sm">
                        <tr>
                            <th class="px-3 py-3.5 font-semibold">Tipo</th>
                            <th class="px-3 py-3.5 font-semibold">Item</th>
                            <th class="px-3 py-3.5 font-semibold">Descrição</th>
                            <th class="px-3 py-3.5 font-semibold">Unid.</th>
                            <th class="px-3 py-3.5 font-semibold tabular-nums">Mobilização</th>
                            <th class="px-3 py-3.5 font-semibold tabular-nums">Pré-PGU</th>
                            <th class="px-3 py-3.5 font-semibold tabular-nums">PGU</th>
                            <th class="px-3 py-3.5 font-semibold tabular-nums">Pós-PGU</th>
                            <th class="px-3 py-3.5 font-semibold tabular-nums">Desmobilização</th>
                            <th class="px-3 py-3.5 text-right font-semibold">Ação</th>
                        </tr>
                    </thead>
                    <tbody data-histograma-rows class="divide-y divide-zinc-200/80 bg-white">
                        @forelse ($linhas as $i => $linha)
                            @php
                                $ehGrupo = ($linha->tipo_linha ?? '') === 'grupo';
                                $rhStatus = $linhaRecrutamentoStatus[$linha->id] ?? ['percent' => 0, 'completed' => false];
                                $linhaConcluida = ! $ehGrupo && (bool) ($rhStatus['completed'] ?? false);
                                $linhaProgress = (int) ($rhStatus['percent'] ?? 0);
                                $linhaMobilizacao = (float) ($rhStatus['mobilizacao'] ?? $linha->mobilizacao);
                                $histogramaTrEstado = 'histograma-tr-item';
                                if ($ehGrupo) {
                                    $histogramaTrEstado = 'histograma-tr-grupo';
                                } elseif ($linhaConcluida) {
                                    $histogramaTrEstado = 'histograma-tr-concluida';
                                }
                            @endphp
                            <tr
                                data-row
                                data-rh-progress="{{ $linhaProgress }}"
                                data-rh-completed="{{ $linhaConcluida ? '1' : '0' }}"
                                id="hist-linha-{{ $linha->id }}"
                                class="scroll-mt-24 transition-colors {{ $histogramaTrEstado }}"
                            >
                                <td class="px-3 py-2.5 align-middle">
                                    <select name="linhas[{{ $i }}][tipo_linha]" data-field="tipo" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs font-semibold shadow-sm">
                                        <option value="item" @selected($linha->tipo_linha === 'item')>Item</option>
                                        <option value="grupo" @selected($linha->tipo_linha === 'grupo')>Grupo</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2.5 align-middle"><input name="linhas[{{ $i }}][item_codigo]" value="{{ $linha->item_codigo }}" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs shadow-sm"></td>
                                <td class="px-3 py-2.5 align-middle" data-desc-cell>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input name="linhas[{{ $i }}][descricao]" value="{{ $linha->descricao }}" required class="min-w-0 flex-1 rounded border border-zinc-200 bg-white px-2 text-xs h-9 shadow-sm">
                                        <span data-atraso-badge class="hidden">Atrasado F2</span>
                                        <span
                                            data-concluida-badge
                                            class="{{ $linhaConcluida ? 'inline-flex shrink-0 items-center rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white' : 'hidden' }}"
                                        >Concluída</span>
                                    </div>
                                    @if (! $ehGrupo && ! $linhaConcluida)
                                        <div class="mt-2">
                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200">
                                                <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ $linhaProgress }}%"></div>
                                            </div>
                                            <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-brand-gray">Avanço recrutamento: {{ $linhaProgress }}%</p>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 align-middle"><input name="linhas[{{ $i }}][unidade]" value="{{ $linha->unidade }}" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs shadow-sm"></td>
                                <td class="px-3 py-2.5 align-middle">
                                    <input type="number" step="0.01" min="0" name="linhas[{{ $i }}][mobilizacao]" value="{{ $linhaMobilizacao }}" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="mobilizacao" readonly>
                                </td>
                                <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][pre_pgu]" value="{{ $linha->pre_pgu }}" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="pre_pgu"></td>
                                <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][pgu]" value="{{ $linha->pgu }}" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="pgu"></td>
                                <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][pos_pgu]" value="{{ $linha->pos_pgu }}" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="pos_pgu"></td>
                                <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][desmobilizacao]" value="{{ $linha->desmobilizacao }}" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="desmobilizacao"></td>
                                <td class="px-3 py-2.5 align-middle text-right">
                                    <button type="button" data-remove-row class="inline-flex h-8 w-8 items-center justify-center rounded border border-red-200 bg-white text-red-700 shadow-sm hover:bg-red-50">×</button>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row>
                                <td colspan="10" class="px-5 py-8 text-center text-sm text-brand-gray">Nenhuma linha ainda. Clique em “+ Grupo” ou “+ Item”.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-zinc-300 bg-zinc-100/90 text-sm font-bold text-brand-black">
                        <tr>
                            <td colspan="4" class="px-3 py-3.5 text-right text-xs uppercase tracking-wide text-brand-gray">Totais (itens)</td>
                            <td class="px-3 py-3.5 tabular-nums" data-total="mobilizacao">{{ $fmtQtd($totais['mobilizacao'] ?? 0) }}</td>
                            <td class="px-3 py-3.5 tabular-nums" data-total="pre_pgu">{{ $fmtQtd($totais['pre_pgu'] ?? 0) }}</td>
                            <td class="px-3 py-3.5 tabular-nums" data-total="pgu">{{ $fmtQtd($totais['pgu'] ?? 0) }}</td>
                            <td class="px-3 py-3.5 tabular-nums" data-total="pos_pgu">{{ $fmtQtd($totais['pos_pgu'] ?? 0) }}</td>
                            <td class="px-3 py-3.5 tabular-nums" data-total="desmobilizacao">{{ $fmtQtd($totais['desmobilizacao'] ?? 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>
    </section>

    @push('scripts')
        <script>
            (() => {
                const form = document.querySelector('[data-histograma-form]');
                if (!form) return;
                const tbody = form.querySelector('[data-histograma-rows]');
                const autosaveStatus = form.querySelector('[data-autosave-status]');
                const emptyRow = () => tbody.querySelector('[data-empty-row]');
                let autosaveTimer = null;
                let autosaveInFlight = false;
                let autosaveQueued = false;

                const setAutosaveStatus = (text, tone = 'muted') => {
                    if (!autosaveStatus) return;
                    autosaveStatus.textContent = text;
                    autosaveStatus.className = 'inline-flex h-10 items-center rounded-lg border px-3 text-xs font-semibold';
                    if (tone === 'ok') {
                        autosaveStatus.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                    } else if (tone === 'saving') {
                        autosaveStatus.classList.add('border-sky-200', 'bg-sky-50', 'text-sky-800');
                    } else if (tone === 'error') {
                        autosaveStatus.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
                    } else {
                        autosaveStatus.classList.add('border-zinc-200', 'bg-zinc-50', 'text-brand-gray');
                    }
                };

                const runAutosave = async () => {
                    if (autosaveInFlight) {
                        autosaveQueued = true;
                        return;
                    }
                    autosaveInFlight = true;
                    setAutosaveStatus('Salvando...', 'saving');

                    try {
                        const payload = new FormData(form);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: payload,
                            credentials: 'same-origin',
                        });
                        if (!response.ok) throw new Error('Falha ao salvar');
                        setAutosaveStatus('Salvo automaticamente', 'ok');
                    } catch (_e) {
                        setAutosaveStatus('Erro no auto-save', 'error');
                    } finally {
                        autosaveInFlight = false;
                        if (autosaveQueued) {
                            autosaveQueued = false;
                            scheduleAutosave(200);
                        }
                    }
                };

                const scheduleAutosave = (delay = 700) => {
                    clearTimeout(autosaveTimer);
                    autosaveTimer = setTimeout(runAutosave, delay);
                };

                const rowBaseCls = (tipo) =>
                    tipo === 'grupo' ? 'histograma-tr-grupo' : 'histograma-tr-item';

                const rowHtml = (idx, tipo = 'item') => `
                    <tr data-row class="scroll-mt-24 transition-colors ${rowBaseCls(tipo)}">
                        <td class="px-3 py-2.5 align-middle">
                            <select name="linhas[${idx}][tipo_linha]" data-field="tipo" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs font-semibold shadow-sm">
                                <option value="item" ${tipo === 'item' ? 'selected' : ''}>Item</option>
                                <option value="grupo" ${tipo === 'grupo' ? 'selected' : ''}>Grupo</option>
                            </select>
                        </td>
                        <td class="px-3 py-2.5 align-middle"><input name="linhas[${idx}][item_codigo]" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs shadow-sm"></td>
                        <td class="px-3 py-2.5 align-middle" data-desc-cell>
                            <div class="flex flex-wrap items-center gap-2">
                                <input name="linhas[${idx}][descricao]" required class="min-w-0 flex-1 h-9 rounded border border-zinc-200 bg-white px-2 text-xs shadow-sm">
                                <span data-atraso-badge class="hidden">Atrasado F2</span>
                                <span data-concluida-badge class="hidden">Concluída</span>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 align-middle"><input name="linhas[${idx}][unidade]" value="Unid." class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs shadow-sm"></td>
                        <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[${idx}][mobilizacao]" value="0" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="mobilizacao"></td>
                        <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[${idx}][pre_pgu]" value="0" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="pre_pgu"></td>
                        <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[${idx}][pgu]" value="0" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="pgu"></td>
                        <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[${idx}][pos_pgu]" value="0" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="pos_pgu"></td>
                        <td class="px-3 py-2.5 align-middle"><input type="number" step="0.01" min="0" name="linhas[${idx}][desmobilizacao]" value="0" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs tabular-nums shadow-sm" data-num="desmobilizacao"></td>
                        <td class="px-3 py-2.5 align-middle text-right"><button type="button" data-remove-row class="inline-flex h-8 w-8 items-center justify-center rounded border border-red-200 bg-white text-red-700 shadow-sm hover:bg-red-50">×</button></td>
                    </tr>`;

                const reindex = () => {
                    tbody.querySelectorAll('[data-row]').forEach((tr, i) => {
                        tr.querySelectorAll('input,select').forEach((el) => {
                            el.name = el.name.replace(/linhas\[\d+\]/, `linhas[${i}]`);
                        });
                    });
                };

                const format = (n) => (n || 0).toLocaleString('pt-BR', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                });
                const recalcTotals = () => {
                    const totals = { mobilizacao: 0, pre_pgu: 0, pgu: 0, pos_pgu: 0, desmobilizacao: 0 };
                    tbody.querySelectorAll('[data-row]').forEach((tr) => {
                        const tipo = tr.querySelector('[data-field="tipo"]')?.value || 'item';
                        if (tipo === 'grupo') {
                            return;
                        }
                        Object.keys(totals).forEach((k) => {
                            const v = parseFloat(tr.querySelector(`[data-num="${k}"]`)?.value || '0');
                            totals[k] += Number.isNaN(v) ? 0 : v;
                        });
                    });
                    Object.keys(totals).forEach((k) => {
                        const el = form.querySelector(`[data-total="${k}"]`);
                        if (el) el.textContent = format(totals[k]);
                    });
                };

                const limiteInput = form.querySelector('[data-limite-input]');
                const BADGE_CONCLUIDA_ON =
                    'inline-flex shrink-0 items-center rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white';

                const HIST_TR = {
                    grupo: 'histograma-tr-grupo',
                    atrasada: 'histograma-tr-atrasada',
                    concluida: 'histograma-tr-concluida',
                    item: 'histograma-tr-item',
                };
                const HIST_TR_ALL = Object.values(HIST_TR);

                const stripRowStateClasses = (tr) => {
                    HIST_TR_ALL.forEach((c) => tr.classList.remove(c));
                };

                const rowIsItem = (tr) => (tr.querySelector('[data-field="tipo"]')?.value || 'item') === 'item';

                const refreshRowStates = () => {
                    tbody.querySelectorAll('[data-row]').forEach((tr) => {
                        const badgeA = tr.querySelector('[data-atraso-badge]');
                        const badgeC = tr.querySelector('[data-concluida-badge]');
                        if (!badgeA || !badgeC) return;
                            const rhCompleted = tr.dataset.rhCompleted === '1';

                        stripRowStateClasses(tr);
                        badgeA.classList.add('hidden');
                        badgeC.classList.add('hidden');

                        if (!rowIsItem(tr)) {
                            tr.classList.add(HIST_TR.grupo);
                            return;
                        }

                            if (rhCompleted) {
                            tr.classList.add(HIST_TR.concluida);
                            badgeC.className = BADGE_CONCLUIDA_ON;
                        } else {
                            tr.classList.add(HIST_TR.item);
                        }
                    });
                };

                const addRow = (tipo) => {
                    emptyRow()?.remove();
                    tbody.insertAdjacentHTML('beforeend', rowHtml(tbody.querySelectorAll('[data-row]').length, tipo));
                    reindex();
                    recalcTotals();
                    refreshRowStates();
                    scheduleAutosave();
                };

                form.querySelector('[data-add-grupo]')?.addEventListener('click', () => addRow('grupo'));
                form.querySelector('[data-add-item]')?.addEventListener('click', () => addRow('item'));

                tbody.addEventListener('click', (e) => {
                    if (e.target.closest('[data-remove-row]')) {
                        e.target.closest('[data-row]')?.remove();
                        reindex();
                        if (!tbody.querySelector('[data-row]')) {
                            tbody.insertAdjacentHTML('beforeend', '<tr data-empty-row><td colspan="10" class="px-5 py-8 text-center text-sm text-brand-gray">Nenhuma linha ainda. Clique em “+ Grupo” ou “+ Item”.</td></tr>');
                        }
                        recalcTotals();
                        refreshRowStates();
                        scheduleAutosave();
                    }
                });

                limiteInput?.addEventListener('change', () => {
                    form.dataset.limite = limiteInput.value || '';
                    refreshRowStates();
                    scheduleAutosave();
                });

                tbody.addEventListener('change', (e) => {
                    if (e.target.matches('[data-field="tipo"]')) {
                        refreshRowStates();
                    }
                    if (e.target.matches('[data-num]')) recalcTotals();
                    if (e.target.matches('[data-num="pre_pgu"], [data-num="pgu"]')) refreshRowStates();
                    if (e.target.matches('input, select')) scheduleAutosave();
                });

                tbody.addEventListener('input', (e) => {
                    if (e.target.matches('[data-num]')) recalcTotals();
                    if (e.target.matches('[data-num="pre_pgu"], [data-num="pgu"]')) refreshRowStates();
                    if (e.target.matches('input, select')) scheduleAutosave();
                });

                recalcTotals();
                refreshRowStates();
            })();
        </script>
    @endpush
@endsection
