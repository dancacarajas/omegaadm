@extends($layout ?? 'layouts.app')

@section('title', 'Histograma de contrato - Omega286')
@section('eyebrow', 'Contrato')
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
            action="{{ route('contratos.histograma.salvar') }}"
            class="space-y-0"
            data-histograma-form
            data-hoje="{{ $histogramaHoje }}"
            data-limite="{{ $dataLimiteEtapa2 ?? '' }}"
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
                </div>
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

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1620px] text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                        <tr>
                            <th class="px-3 py-3">Tipo</th>
                            <th class="px-3 py-3">Item</th>
                            <th class="px-3 py-3">Descrição</th>
                            <th class="px-3 py-3">Unid.</th>
                            <th class="px-3 py-3">Mobilização</th>
                            <th class="px-3 py-3">Pré-PGU</th>
                            <th class="px-3 py-3">PGU</th>
                            <th class="px-3 py-3">Pós-PGU</th>
                            <th class="px-3 py-3">Desmobilização</th>
                            <th class="px-3 py-3">Ação recomendada (Slide 5)</th>
                            <th class="px-3 py-3">Responsável (Slide 5)</th>
                            <th class="px-3 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody data-histograma-rows class="divide-y divide-zinc-100">
                        @forelse ($linhas as $i => $linha)
                            @php
                                $ehGrupo = ($linha->tipo_linha ?? '') === 'grupo';
                                $linhaAtrasada = $dataLimiteEtapa2
                                    && ! $ehGrupo
                                    && \Carbon\Carbon::today()->gt(\Carbon\Carbon::parse($dataLimiteEtapa2)->startOfDay())
                                    && (float) $linha->pgu > (float) $linha->pre_pgu + 0.00001;
                            @endphp
                            <tr data-row id="hist-linha-{{ $linha->id }}" class="scroll-mt-24 {{ $linha->tipo_linha === 'grupo' ? 'bg-[#f8f4d9]/70 font-bold text-brand-black' : '' }} {{ $linhaAtrasada ? 'bg-red-50/90 ring-1 ring-red-300/80' : '' }}">
                                <td class="px-3 py-2">
                                    <select name="linhas[{{ $i }}][tipo_linha]" data-field="tipo" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs font-semibold">
                                        <option value="item" @selected($linha->tipo_linha === 'item')>Item</option>
                                        <option value="grupo" @selected($linha->tipo_linha === 'grupo')>Grupo</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input name="linhas[{{ $i }}][item_codigo]" value="{{ $linha->item_codigo }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs"></td>
                                <td class="px-3 py-2" data-desc-cell>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input name="linhas[{{ $i }}][descricao]" value="{{ $linha->descricao }}" required class="min-w-0 flex-1 rounded border border-zinc-200 px-2 text-xs h-9">
                                        <span
                                            data-atraso-badge
                                            class="{{ $linhaAtrasada ? 'inline-flex shrink-0 items-center rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white' : 'hidden' }}"
                                        >Atrasado F2</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2"><input name="linhas[{{ $i }}][unidade]" value="{{ $linha->unidade }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][mobilizacao]" value="{{ $linha->mobilizacao }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="mobilizacao"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][pre_pgu]" value="{{ $linha->pre_pgu }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="pre_pgu"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][pgu]" value="{{ $linha->pgu }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="pgu"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][pos_pgu]" value="{{ $linha->pos_pgu }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="pos_pgu"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[{{ $i }}][desmobilizacao]" value="{{ $linha->desmobilizacao }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="desmobilizacao"></td>
                                <td class="px-3 py-2"><input name="linhas[{{ $i }}][acao_recomendada]" value="{{ $linha->acao_recomendada }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" placeholder="Ex.: Força-tarefa documental"></td>
                                <td class="px-3 py-2"><input name="linhas[{{ $i }}][responsavel]" value="{{ $linha->responsavel }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" placeholder="Ex.: Gestão PGU"></td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" data-remove-row class="inline-flex h-8 w-8 items-center justify-center rounded border border-red-200 text-red-700 hover:bg-red-50">×</button>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row>
                                <td colspan="12" class="px-5 py-8 text-center text-sm text-brand-gray">Nenhuma linha ainda. Clique em “+ Grupo” ou “+ Item”.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-zinc-200 bg-brand-gray-soft/40 text-sm font-bold text-brand-black">
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-right">Totais</td>
                            <td class="px-3 py-3" data-total="mobilizacao">{{ $fmtQtd($totais['mobilizacao'] ?? 0) }}</td>
                            <td class="px-3 py-3" data-total="pre_pgu">{{ $fmtQtd($totais['pre_pgu'] ?? 0) }}</td>
                            <td class="px-3 py-3" data-total="pgu">{{ $fmtQtd($totais['pgu'] ?? 0) }}</td>
                            <td class="px-3 py-3" data-total="pos_pgu">{{ $fmtQtd($totais['pos_pgu'] ?? 0) }}</td>
                            <td class="px-3 py-3" data-total="desmobilizacao">{{ $fmtQtd($totais['desmobilizacao'] ?? 0) }}</td>
                            <td></td>
                            <td></td>
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
                const emptyRow = () => tbody.querySelector('[data-empty-row]');

                const rowHtml = (idx, tipo = 'item') => `
                    <tr data-row class="${tipo === 'grupo' ? 'bg-[#f8f4d9]/70 font-bold text-brand-black' : ''}">
                        <td class="px-3 py-2">
                            <select name="linhas[${idx}][tipo_linha]" data-field="tipo" class="h-9 w-full rounded border border-zinc-200 bg-white px-2 text-xs font-semibold">
                                <option value="item" ${tipo === 'item' ? 'selected' : ''}>Item</option>
                                <option value="grupo" ${tipo === 'grupo' ? 'selected' : ''}>Grupo</option>
                            </select>
                        </td>
                        <td class="px-3 py-2"><input name="linhas[${idx}][item_codigo]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs"></td>
                        <td class="px-3 py-2" data-desc-cell>
                            <div class="flex flex-wrap items-center gap-2">
                                <input name="linhas[${idx}][descricao]" required class="min-w-0 flex-1 h-9 rounded border border-zinc-200 px-2 text-xs">
                                <span data-atraso-badge class="hidden">Atrasado F2</span>
                            </div>
                        </td>
                        <td class="px-3 py-2"><input name="linhas[${idx}][unidade]" value="Unid." class="h-9 w-full rounded border border-zinc-200 px-2 text-xs"></td>
                        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[${idx}][mobilizacao]" value="0" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="mobilizacao"></td>
                        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[${idx}][pre_pgu]" value="0" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="pre_pgu"></td>
                        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[${idx}][pgu]" value="0" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="pgu"></td>
                        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[${idx}][pos_pgu]" value="0" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="pos_pgu"></td>
                        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="linhas[${idx}][desmobilizacao]" value="0" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" data-num="desmobilizacao"></td>
                        <td class="px-3 py-2"><input name="linhas[${idx}][acao_recomendada]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" placeholder="Ex.: Força-tarefa documental"></td>
                        <td class="px-3 py-2"><input name="linhas[${idx}][responsavel]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs" placeholder="Ex.: Gestão PGU"></td>
                        <td class="px-3 py-2 text-right"><button type="button" data-remove-row class="inline-flex h-8 w-8 items-center justify-center rounded border border-red-200 text-red-700 hover:bg-red-50">×</button></td>
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
                const BADGE_ON =
                    'inline-flex shrink-0 items-center rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white';

                const limitePassou = () => {
                    const L = (form.dataset.limite || '').trim();
                    const H = (form.dataset.hoje || '').trim();
                    if (!L || !H) return false;
                    const dL = new Date(`${L}T12:00:00`);
                    const dH = new Date(`${H}T12:00:00`);
                    if (Number.isNaN(dL.getTime()) || Number.isNaN(dH.getTime())) return false;
                    return dH > dL;
                };
                const rowIsItem = (tr) => (tr.querySelector('[data-field="tipo"]')?.value || 'item') === 'item';

                const refreshAtrasoHighlight = () => {
                    const late = limitePassou();
                    tbody.querySelectorAll('[data-row]').forEach((tr) => {
                        const badge = tr.querySelector('[data-atraso-badge]');
                        if (!badge) return;
                        if (!rowIsItem(tr) || !late) {
                            tr.classList.remove('bg-red-50/90', 'ring-1', 'ring-red-300/80');
                            badge.className = 'hidden';
                            return;
                        }
                        const pre = parseFloat(tr.querySelector('[data-num="pre_pgu"]')?.value || '0');
                        const pgu = parseFloat(tr.querySelector('[data-num="pgu"]')?.value || '0');
                        const show = pgu > pre + 1e-9;
                        if (show) {
                            tr.classList.add('bg-red-50/90', 'ring-1', 'ring-red-300/80');
                            badge.className = BADGE_ON;
                        } else {
                            tr.classList.remove('bg-red-50/90', 'ring-1', 'ring-red-300/80');
                            badge.className = 'hidden';
                        }
                    });
                };

                const addRow = (tipo) => {
                    emptyRow()?.remove();
                    tbody.insertAdjacentHTML('beforeend', rowHtml(tbody.querySelectorAll('[data-row]').length, tipo));
                    reindex();
                    recalcTotals();
                    refreshAtrasoHighlight();
                };

                form.querySelector('[data-add-grupo]')?.addEventListener('click', () => addRow('grupo'));
                form.querySelector('[data-add-item]')?.addEventListener('click', () => addRow('item'));

                tbody.addEventListener('click', (e) => {
                    if (e.target.closest('[data-remove-row]')) {
                        e.target.closest('[data-row]')?.remove();
                        reindex();
                        if (!tbody.querySelector('[data-row]')) {
                            tbody.insertAdjacentHTML('beforeend', '<tr data-empty-row><td colspan="12" class="px-5 py-8 text-center text-sm text-brand-gray">Nenhuma linha ainda. Clique em “+ Grupo” ou “+ Item”.</td></tr>');
                        }
                        recalcTotals();
                        refreshAtrasoHighlight();
                    }
                });

                limiteInput?.addEventListener('change', () => {
                    form.dataset.limite = limiteInput.value || '';
                    refreshAtrasoHighlight();
                });

                tbody.addEventListener('change', (e) => {
                    if (e.target.matches('[data-field="tipo"]')) {
                        const tr = e.target.closest('[data-row]');
                        tr?.classList.toggle('bg-[#f8f4d9]/70', e.target.value === 'grupo');
                        tr?.classList.toggle('font-bold', e.target.value === 'grupo');
                        tr?.classList.toggle('text-brand-black', e.target.value === 'grupo');
                        refreshAtrasoHighlight();
                    }
                    if (e.target.matches('[data-num]')) recalcTotals();
                    if (e.target.matches('[data-num="pre_pgu"], [data-num="pgu"]')) refreshAtrasoHighlight();
                });

                tbody.addEventListener('input', (e) => {
                    if (e.target.matches('[data-num]')) recalcTotals();
                    if (e.target.matches('[data-num="pre_pgu"], [data-num="pgu"]')) refreshAtrasoHighlight();
                });

                recalcTotals();
                refreshAtrasoHighlight();
            })();
        </script>
    @endpush
@endsection
