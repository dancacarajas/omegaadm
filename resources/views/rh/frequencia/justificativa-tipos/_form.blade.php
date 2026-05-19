@php
    $tipo = $tipo ?? new \App\Models\FrequenciaJustificativaTipo();
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-brand-black">Tipo de justificativa</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label class="space-y-2 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Nome *</span>
                <input type="text" name="nome" value="{{ old('nome', $tipo->nome) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Categoria *</span>
                <select name="categoria" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    @foreach (\App\Models\FrequenciaJustificativaTipo::CATEGORIAS as $valor => $label)
                        <option value="{{ $valor }}" @selected(old('categoria', $tipo->categoria) === $valor)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ordem</span>
                <input type="number" name="ordem" value="{{ old('ordem', $tipo->ordem ?? 0) }}" min="0" max="9999" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
                <select name="ativo" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="1" @selected(old('ativo', $tipo->ativo ? '1' : '0') == '1')>Ativo</option>
                    <option value="0" @selected(old('ativo', $tipo->ativo ? '1' : '0') == '0')>Inativo</option>
                </select>
            </label>
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 md:col-span-2">
                <input type="checkbox" name="limpa_batidas" value="1" @checked(old('limpa_batidas', $tipo->limpa_batidas ?? true)) class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                <span class="text-sm font-semibold text-brand-black">Ao aplicar, limpar batidas do dia (ex.: atestado cobre o dia inteiro)</span>
            </label>
        </div>
    </section>
</div>
