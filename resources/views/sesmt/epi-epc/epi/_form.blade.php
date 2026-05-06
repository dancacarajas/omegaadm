@php
    /** @var \App\Models\SsmaEpiEntrega $epi */
    $st = old('status', $epi->status);
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Colaborador *</span>
        <input type="text" name="colaborador" value="{{ old('colaborador', $epi->colaborador) }}" required maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('colaborador')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Cargo</span>
        <input type="text" name="cargo" value="{{ old('cargo', $epi->cargo) }}" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('cargo')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">EPI obrigatório *</span>
    <input type="text" name="epi_obrigatorio" value="{{ old('epi_obrigatorio', $epi->epi_obrigatorio) }}" required maxlength="500" placeholder="Ex.: capacete, luvas, cinto..." class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
    @error('epi_obrigatorio')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">CA (número)</span>
        <input type="text" name="ca_numero" value="{{ old('ca_numero', $epi->ca_numero) }}" maxlength="120" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('ca_numero')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Validade do CA</span>
        <input type="date" name="validade_ca" value="{{ old('validade_ca', $epi->validade_ca?->format('Y-m-d')) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('validade_ca')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-3">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de entrega</span>
        <input type="date" name="data_entrega" value="{{ old('data_entrega', $epi->data_entrega?->format('Y-m-d')) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('data_entrega')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de substituição</span>
        <input type="date" name="data_substituicao" value="{{ old('data_substituicao', $epi->data_substituicao?->format('Y-m-d')) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('data_substituicao')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status *</span>
        <select name="status" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaEpiEntrega::STATUS as $k => $label)
                <option value="{{ $k }}" @selected($st === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência / assinatura (arquivo)</span>
    <input type="file" name="evidencia_epi" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-burgundy">
    <span class="mt-1 block text-xs text-brand-gray">Recibo, termo ou imagem da assinatura. Até 10 MB.</span>
    @error('evidencia_epi')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @if (! empty($epi->evidencia_path))
        <p class="mt-2 text-sm">
            <a href="{{ asset('storage/'.$epi->evidencia_path) }}" target="_blank" rel="noopener" class="font-bold text-brand-burgundy underline-offset-2 hover:underline">Arquivo atual</a>
        </p>
    @endif
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observação</span>
    <textarea name="observacao" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('observacao', $epi->observacao) }}</textarea>
    @error('observacao')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>
