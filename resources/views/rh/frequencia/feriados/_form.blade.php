@php
    $feriado = $feriado ?? new \App\Models\FrequenciaFeriado();
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-brand-black">Dados do feriado</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Nome *</span>
                <input type="text" name="nome" value="{{ old('nome', $feriado->nome) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data *</span>
                <input type="date" name="data" value="{{ old('data', $feriado->data?->format('Y-m-d')) }}" required class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <p class="text-[11px] text-brand-gray">Em feriados recorrentes, usa dia e mês (o ano é referência).</p>
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
                <select name="ativo" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="1" @selected(old('ativo', $feriado->ativo ? '1' : '0') == '1')>Ativo</option>
                    <option value="0" @selected(old('ativo', $feriado->ativo ? '1' : '0') == '0')>Inativo</option>
                </select>
            </label>
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 md:col-span-2">
                <input type="checkbox" name="recorrente" value="1" @checked(old('recorrente', $feriado->recorrente)) class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                <span class="text-sm font-semibold text-brand-black">Repetir todo ano (mesmo dia e mês)</span>
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observações</span>
                <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('observacoes', $feriado->observacoes) }}</textarea>
            </label>
        </div>
    </section>

    <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        Ao salvar, o sistema marca o dia como <strong>feriado abonado</strong> no ponto de todos os colaboradores ativos (sem horas falta). Quem já tiver batidas registradas no dia não é alterado.
    </div>
</div>
