@php
    $competenciaVal = old('competencia', $prazo->competencia?->format('Y-m') ?? now()->format('Y-m'));
    $limite = old('data_limite');
    if ($limite === null && isset($prazo->data_limite)) {
        $limite = $prazo->data_limite->format('Y-m-d\TH:i');
    }
    $exige = old('exige_finalizado', $prazo->exige_finalizado ?? true);
    if (is_string($exige)) {
        $exige = $exige === '1' || $exige === 'on';
    }
    $recorrenteVal = old('recorrente', $prazo->recorrente ?? false);
    if (is_string($recorrenteVal)) {
        $recorrenteVal = $recorrenteVal === '1';
    }
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência *</span>
        <input type="month" name="competencia" value="{{ $competenciaVal }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        <span class="mt-1 block text-xs text-brand-gray">Mês do registro mensal. Em <strong>recorrente</strong>, é o mês inicial em que a regra passa a valer.</span>
        @error('competencia')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo de prazo *</span>
        <select name="recorrente" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            <option value="0" @selected(! $recorrenteVal)>Único — só para a competência acima</option>
            <option value="1" @selected($recorrenteVal)>Recorrente — mesmo dia e hora em todo mês (a partir da competência)</option>
        </select>
        @error('recorrente')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data e hora limite *</span>
    <input type="datetime-local" name="data_limite" value="{{ $limite }}" required class="mt-2 h-11 w-full max-w-md rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
    <span class="mt-1 block text-xs text-brand-gray">No modo <strong>recorrente</strong>, apenas o <strong>dia do mês</strong> e a <strong>hora</strong> são repetidos; em meses com menos dias (ex.: 31 em fevereiro), usa-se o último dia do mês.</span>
    @error('data_limite')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-3">
    <input type="hidden" name="exige_finalizado" value="0">
    <input type="checkbox" name="exige_finalizado" value="1" @checked($exige) class="mt-1 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
    <span>
        <span class="text-sm font-semibold text-brand-black">Considerar SLA cumprido apenas com registro finalizado</span>
        <span class="mt-0.5 block text-xs text-brand-gray">Se desmarcado, basta existir qualquer registro (rascunho ou finalizado) para a competência.</span>
    </span>
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observação (opcional)</span>
    <textarea name="observacao" rows="3" maxlength="500" placeholder="Ex.: enviar e-mail à gerência após o prazo" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('observacao', $prazo->observacao ?? '') }}</textarea>
    @error('observacao')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>
