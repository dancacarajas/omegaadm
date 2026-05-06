@php
    /** @var \App\Models\SsmaEpcRegistro $epc */
    $cond = old('condicao', $epc->condicao);
    $nec = old('necessita_correcao', $epc->necessita_correcao ? '1' : '0');
    if ($nec === true || $nec === 1) {
        $nec = '1';
    } else {
        $nec = (string) $nec;
    }
@endphp

<label class="block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Local *</span>
    <input type="text" name="local" value="{{ old('local', $epc->local) }}" required maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
    @error('local')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo de EPC *</span>
        <input type="text" name="tipo_epc" value="{{ old('tipo_epc', $epc->tipo_epc) }}" required maxlength="255" placeholder="Ex.: tela antitorre, guarda-corpo..." class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('tipo_epc')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Condição *</span>
        <select name="condicao" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaEpcRegistro::CONDICOES as $k => $label)
                <option value="{{ $k }}" @selected($cond === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('condicao')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-3">
    <input type="hidden" name="necessita_correcao" value="0">
    <input type="checkbox" name="necessita_correcao" value="1" @checked($nec === '1' || $nec === true || $nec === 1) class="mt-1 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
    <span>
        <span class="text-sm font-semibold text-brand-black">Necessita correção?</span>
        <span class="mt-0.5 block text-xs text-brand-gray">Marque se há plano de adequação ou reparo pendente.</span>
    </span>
</label>
@error('necessita_correcao')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
@enderror

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Risco associado</span>
    <textarea name="risco_associado" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('risco_associado', $epc->risco_associado) }}</textarea>
    @error('risco_associado')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável</span>
        <input type="text" name="responsavel" value="{{ old('responsavel', $epc->responsavel) }}" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('responsavel')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Prazo</span>
        <input type="date" name="prazo" value="{{ old('prazo', $epc->prazo?->format('Y-m-d')) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('prazo')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência fotográfica</span>
    <input type="file" name="evidencia_foto" accept=".jpg,.jpeg,.png,.gif,.webp" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-burgundy">
    <span class="mt-1 block text-xs text-brand-gray">Imagem até 10 MB.</span>
    @error('evidencia_foto')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @if (! empty($epc->evidencia_foto_path))
        <p class="mt-2 text-sm">
            <a href="{{ asset('storage/'.$epc->evidencia_foto_path) }}" target="_blank" rel="noopener" class="font-bold text-brand-burgundy underline-offset-2 hover:underline">Foto atual</a>
        </p>
    @endif
</label>
