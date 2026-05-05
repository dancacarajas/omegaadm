@php
    $value = fn (string $field, $default = null) => old($field, data_get($item, $field, $default));
@endphp

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-brand-black">Planilha financeira da medição</h2>
    <p class="mt-1 text-sm text-brand-gray">Previsto x medido com justificativas, evidências e decomposição dos desvios financeiros.</p>

    <div class="mt-5 grid gap-4 md:grid-cols-4">
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência *</span>
            <input type="date" name="competencia" value="{{ $value('competencia', now()->startOfMonth()->toDateString()) }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
            <input type="text" name="contrato" value="{{ $value('contrato') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Item contratual *</span>
            <input type="text" name="item_contratual" value="{{ $value('item_contratual') }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição</span>
            <input type="text" name="descricao" value="{{ $value('descricao') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>

        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Valor unitário previsto</span>
            <input type="number" step="0.01" min="0" name="valor_unitario_previsto" value="{{ $value('valor_unitario_previsto', 0) }}" data-medicao-unitario class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Quantidade prevista</span>
            <input type="number" step="0.01" min="0" name="quantidade_prevista" value="{{ $value('quantidade_prevista', 0) }}" data-medicao-qprev class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Valor previsto</span>
            <input type="number" step="0.01" name="valor_previsto" value="{{ $value('valor_previsto', 0) }}" data-medicao-vprev class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Quantidade medida</span>
            <input type="number" step="0.01" min="0" name="quantidade_medida" value="{{ $value('quantidade_medida', 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Valor medido</span>
            <input type="number" step="0.01" min="0" name="valor_medido" value="{{ $value('valor_medido', 0) }}" data-medicao-vmed class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Diferença</span>
            <input type="number" step="0.01" name="diferenca" value="{{ $value('diferenca', 0) }}" data-medicao-dif class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Desvio %</span>
            <input type="number" step="0.01" name="desvio_percentual" value="{{ $value('desvio_percentual', 0) }}" data-medicao-desvio class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>

        <label class="md:col-span-4">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Justificativa</span>
            <textarea name="justificativa" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('justificativa') }}</textarea>
        </label>
    </div>
</section>

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-brand-black">Composição financeira dos desvios</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-4">
        @foreach ([
            'valor_glosado' => 'Itens glosados',
            'valor_nao_executado' => 'Itens não executados',
            'valor_executado_nao_medido' => 'Executado não medido',
            'valor_hora_extra' => 'Horas extras',
            'valor_adicional' => 'Valores adicionais',
            'valor_mobilizacao' => 'Mobilizações',
            'valor_nao_programado' => 'Não programados',
        ] as $field => $label)
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">{{ $label }}</span>
                <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ $value($field, 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
        @endforeach
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência</span>
            <input type="file" name="evidencia" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
            @if (! empty($item->evidencia_path))
                <a href="{{ asset('storage/'.$item->evidencia_path) }}" target="_blank" class="mt-2 inline-flex text-xs font-bold text-brand-burgundy">Ver evidência atual</a>
            @endif
        </label>
    </div>
</section>

@push('scripts')
<script>
(() => {
    const vu = document.querySelector('[data-medicao-unitario]');
    const qp = document.querySelector('[data-medicao-qprev]');
    const vp = document.querySelector('[data-medicao-vprev]');
    const vm = document.querySelector('[data-medicao-vmed]');
    const df = document.querySelector('[data-medicao-dif]');
    const dz = document.querySelector('[data-medicao-desvio]');
    if (!vu || !qp || !vp || !vm || !df || !dz) return;

    const n = (el) => parseFloat(el.value || '0');
    const recalcPrev = () => {
        if (document.activeElement === vp && vp.value !== '') return;
        vp.value = (n(vu) * n(qp)).toFixed(2);
    };
    const recalcDiff = () => {
        const d = n(vm) - n(vp);
        df.value = d.toFixed(2);
        dz.value = n(vp) > 0 ? ((d / n(vp)) * 100).toFixed(2) : '0.00';
    };
    [vu, qp].forEach(el => el.addEventListener('input', () => { recalcPrev(); recalcDiff(); }));
    [vp, vm].forEach(el => el.addEventListener('input', recalcDiff));
})();
</script>
@endpush
