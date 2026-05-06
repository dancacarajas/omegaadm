@php
    /** @var \App\Models\SsmaRisco $risco */
    $catVal = old('categoria', $risco->categoria);
    $stVal = old('status', $risco->status);
    $pVal = (int) old('probabilidade', $risco->probabilidade ?? 3);
    $sVal = (int) old('severidade', $risco->severidade ?? 3);
    $prazoVal = old('prazo', $risco->prazo?->format('Y-m-d'));
    $previewClass = \App\Models\SsmaRisco::classificacaoFromScores($pVal, $sVal);
    $previewLabel = \App\Models\SsmaRisco::CLASSIFICACOES[$previewClass] ?? $previewClass;
@endphp

<label class="block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Risco identificado *</span>
    <textarea name="risco_identificado" rows="3" required class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('risco_identificado', $risco->risco_identificado) }}</textarea>
    @error('risco_identificado')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Área / local</span>
        <input type="text" name="area_local" value="{{ old('area_local', $risco->area_local) }}" maxlength="255" placeholder="Obra, setor, pátio..." class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('area_local')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Categoria *</span>
        <select name="categoria" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaRisco::CATEGORIAS as $k => $label)
                <option value="{{ $k }}" @selected($catVal === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('categoria')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Atividade *</span>
    <textarea name="atividade" rows="3" required class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('atividade', $risco->atividade) }}</textarea>
    @error('atividade')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Probabilidade * (1–5)</span>
        <select name="probabilidade" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaRisco::ESCALA_NIVEIS as $n => $label)
                <option value="{{ $n }}" @selected($pVal === $n)>{{ $label }}</option>
            @endforeach
        </select>
        @error('probabilidade')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Severidade * (1–5)</span>
        <select name="severidade" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaRisco::ESCALA_NIVEIS as $n => $label)
                <option value="{{ $n }}" @selected($sVal === $n)>{{ $label }}</option>
            @endforeach
        </select>
        @error('severidade')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<p class="mt-3 rounded-lg border border-zinc-200 bg-brand-gray-soft/50 px-4 py-3 text-sm">
    <span class="font-semibold text-brand-gray">Classificação final (automática):</span>
    <span class="ml-2 font-bold text-brand-black">{{ $previewLabel }}</span>
    <span class="text-brand-gray">· ao salvar, o sistema grava com base em P×S.</span>
</p>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Medida de controle existente</span>
    <textarea name="medida_controle_existente" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('medida_controle_existente', $risco->medida_controle_existente) }}</textarea>
    @error('medida_controle_existente')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Medida adicional necessária</span>
    <textarea name="medida_adicional_necessaria" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('medida_adicional_necessaria', $risco->medida_adicional_necessaria) }}</textarea>
    @error('medida_adicional_necessaria')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-3">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável</span>
        <input type="text" name="responsavel" value="{{ old('responsavel', $risco->responsavel) }}" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('responsavel')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Prazo</span>
        <input type="date" name="prazo" value="{{ $prazoVal }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('prazo')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status *</span>
        <select name="status" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaRisco::STATUS as $k => $label)
                <option value="{{ $k }}" @selected($stVal === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

@if ($risco->tratado_em)
    <p class="mt-3 text-xs text-brand-gray">Data de tratamento registrada: <strong class="text-brand-black">{{ $risco->tratado_em->format('d/m/Y') }}</strong> (preenchida ao marcar status Tratado).</p>
@endif

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência (arquivo)</span>
    <input type="file" name="evidencia" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-burgundy">
    <span class="mt-1 block text-xs text-brand-gray">PDF ou imagem, até 10 MB.</span>
    @error('evidencia')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @if (! empty($risco->evidencia_path))
        <p class="mt-2 text-sm">
            <span class="font-semibold text-brand-black">Arquivo atual:</span>
            <a href="{{ asset('storage/'.$risco->evidencia_path) }}" target="_blank" rel="noopener" class="ml-1 font-bold text-brand-burgundy underline-offset-2 hover:underline">Abrir evidência</a>
        </p>
    @endif
</label>
