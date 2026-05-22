@php
    $contratosAtivos = $contratosAtivos ?? collect();
    $cartaoPeriodo = $cartaoPeriodo ?? ['inicio' => now()->startOfMonth()->toDateString(), 'fim' => now()->endOfMonth()->toDateString()];
    $contratoCartaoPadrao = $contratosAtivos->first();
    $contratoCartaoValor = $contratoCartaoPadrao
        ? trim((string) ($contratoCartaoPadrao->centro_custo ?: $contratoCartaoPadrao->numero ?: $contratoCartaoPadrao->nome))
        : '';
@endphp

<section id="relatorio-cartao-ponto" class="mb-5 scroll-mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Relatório</p>
        <h2 class="mt-1 text-xl font-bold text-brand-black">Cartão de ponto (PDF)</h2>
        <p class="mt-1 text-sm text-brand-gray">Gera o cartão no padrão Control iD: um PDF por colaborador, com marcações, totais e horário de trabalho da escala.</p>
    </div>
    <form id="form-cartao-ponto" method="GET" action="{{ route('rh.frequencia.cartao-ponto.pdf') }}" target="_blank" class="space-y-4 p-5">
        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
            <label class="space-y-2 xl:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                <select name="contrato" id="cartao-contrato" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    @forelse ($contratosAtivos as $ct)
                        @php
                            $ctVal = trim((string) ($ct->centro_custo ?: $ct->numero ?: $ct->nome));
                            $ctLabel = trim(($ct->numero ? $ct->numero.' — ' : '').($ct->nome ?: $ctVal));
                        @endphp
                        <option value="{{ $ctVal }}" @selected($ctVal === $contratoCartaoValor)>{{ $ctLabel }}</option>
                    @empty
                        <option value="">Nenhum contrato disponível</option>
                    @endforelse
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data início</span>
                <input type="date" name="data_inicio" value="{{ $cartaoPeriodo['inicio'] }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data fim</span>
                <input type="date" name="data_fim" value="{{ $cartaoPeriodo['fim'] }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
        </div>

        <fieldset class="space-y-3">
            <legend class="text-xs font-bold uppercase tracking-wide text-brand-gray">Colaboradores</legend>
            <div class="flex flex-wrap gap-4 text-sm font-semibold text-brand-black">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" name="escopo" value="contrato" checked class="text-brand-burgundy focus:ring-brand-burgundy/20" data-cartao-escopo>
                    Todos do contrato
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" name="escopo" value="colaborador" class="text-brand-burgundy focus:ring-brand-burgundy/20" data-cartao-escopo>
                    Um colaborador
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="radio" name="escopo" value="selecionados" class="text-brand-burgundy focus:ring-brand-burgundy/20" data-cartao-escopo>
                    Selecionados
                </label>
            </div>

            <div id="cartao-colaborador-unico" class="hidden max-w-xl">
                <select name="colaborador_id" id="cartao-colaborador-id" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Carregando…</option>
                </select>
            </div>

            <div id="cartao-colaboradores-selecionados" class="hidden">
                <p class="mb-2 text-xs text-brand-gray">Marque os colaboradores que entrarão no PDF (Ctrl+clique para vários).</p>
                <select name="colaborador_ids[]" id="cartao-colaborador-ids" multiple size="8" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"></select>
            </div>
        </fieldset>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                <i data-lucide="file-text" class="h-4 w-4"></i>
                Gerar cartão de ponto (PDF)
            </button>
        </div>
    </form>
</section>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('form-cartao-ponto');
    if (!form) return;
    const selContrato = document.getElementById('cartao-contrato');
    const selUnico = document.getElementById('cartao-colaborador-id');
    const selMulti = document.getElementById('cartao-colaborador-ids');
    const boxUnico = document.getElementById('cartao-colaborador-unico');
    const boxMulti = document.getElementById('cartao-colaboradores-selecionados');
    const urlLista = @json(route('rh.frequencia.cartao-ponto.colaboradores'));
    let lista = [];

    function escopoAtual() {
        return form.querySelector('[name=escopo]:checked')?.value || 'contrato';
    }

    function atualizarEscopoUi() {
        const escopo = escopoAtual();
        boxUnico.classList.toggle('hidden', escopo !== 'colaborador');
        boxMulti.classList.toggle('hidden', escopo !== 'selecionados');
        selUnico.required = escopo === 'colaborador';
        selUnico.disabled = escopo !== 'colaborador';
        selMulti.disabled = escopo !== 'selecionados';
    }

    function preencherSelects() {
        const opts = lista.map(c => `<option value="${c.id}">${c.label}</option>`).join('');
        selUnico.innerHTML = opts || '<option value="">Nenhum colaborador</option>';
        selMulti.innerHTML = opts;
    }

    async function carregarColaboradores() {
        const contrato = selContrato?.value;
        if (!contrato) return;
        selUnico.innerHTML = '<option value="">Carregando…</option>';
        selMulti.innerHTML = '';
        const res = await fetch(`${urlLista}?contrato=${encodeURIComponent(contrato)}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        lista = data.colaboradores || [];
        preencherSelects();
    }

    form.querySelectorAll('[data-cartao-escopo]').forEach(el => {
        el.addEventListener('change', atualizarEscopoUi);
    });

    selContrato?.addEventListener('change', carregarColaboradores);

    form.addEventListener('submit', function (e) {
        const escopo = escopoAtual();
        if (escopo === 'colaborador' && !selUnico.value) {
            e.preventDefault();
            alert('Selecione um colaborador.');
        }
        if (escopo === 'selecionados' && selMulti.selectedOptions.length === 0) {
            e.preventDefault();
            alert('Selecione ao menos um colaborador.');
        }
    });

    atualizarEscopoUi();
    carregarColaboradores();
})();
</script>
@endpush
