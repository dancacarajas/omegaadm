@php
    /** @var \App\Models\SsmaAmbientalRegistro $registro */
    $compVal = old('competencia', $registro->competencia?->format('Y-m'));
@endphp

<label class="block max-w-xs">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência *</span>
    <input type="month" name="competencia" value="{{ $compVal }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
    <span class="mt-1 block text-xs text-brand-gray">Um registro por mês (consolidado).</span>
    @error('competencia')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <label class="block">
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Resíduos gerados</span>
        <textarea name="residuos_gerados" rows="4" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('residuos_gerados', $registro->residuos_gerados) }}</textarea>
        @error('residuos_gerados')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label class="block">
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Resíduos destinados</span>
        <textarea name="residuos_destinados" rows="4" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('residuos_destinados', $registro->residuos_destinados) }}</textarea>
        @error('residuos_destinados')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Quantidade destinada corretamente (painel diretoria)</span>
        <input type="number" name="quantidade_residuos_destinados_corretamente" value="{{ old('quantidade_residuos_destinados_corretamente', $registro->quantidade_residuos_destinados_corretamente) }}" step="0.001" min="0" placeholder="Ex.: toneladas ou m³ — defina a unidade nas observações" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('quantidade_residuos_destinados_corretamente')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label class="block">
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência de destinação (arquivo)</span>
        <input type="file" name="evidencia_destinacao" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-burgundy">
        @error('evidencia_destinacao')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @if (! empty($registro->evidencia_destinacao_path))
            <p class="mt-2 text-sm">
                <a href="{{ asset('storage/'.$registro->evidencia_destinacao_path) }}" target="_blank" rel="noopener" class="font-bold text-brand-burgundy underline-offset-2 hover:underline">Arquivo atual</a>
            </p>
        @endif
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Coleta seletiva</span>
    <textarea name="coleta_seletiva" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('coleta_seletiva', $registro->coleta_seletiva) }}</textarea>
    @error('coleta_seletiva')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Vazamentos / derramamentos (qtd.)</span>
        <input type="number" name="vazamentos_derramamentos" value="{{ old('vazamentos_derramamentos', $registro->vazamentos_derramamentos ?? 0) }}" min="0" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('vazamentos_derramamentos')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ocorrências ambientais (qtd.)</span>
        <input type="number" name="ocorrencias_ambientais" value="{{ old('ocorrencias_ambientais', $registro->ocorrencias_ambientais ?? 0) }}" min="0" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('ocorrencias_ambientais')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ações ambientais concluídas (qtd.)</span>
        <input type="number" name="acoes_ambientais_concluidas" value="{{ old('acoes_ambientais_concluidas', $registro->acoes_ambientais_concluidas ?? 0) }}" min="0" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('acoes_ambientais_concluidas')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Não conformidades ambientais (qtd.)</span>
        <input type="number" name="nao_conformidades_ambientais" value="{{ old('nao_conformidades_ambientais', $registro->nao_conformidades_ambientais ?? 0) }}" min="0" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('nao_conformidades_ambientais')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Produtos químicos</span>
    <textarea name="produtos_quimicos" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('produtos_quimicos', $registro->produtos_quimicos) }}</textarea>
    @error('produtos_quimicos')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Armazenamento de resíduos</span>
    <textarea name="armazenamento_residuos" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('armazenamento_residuos', $registro->armazenamento_residuos) }}</textarea>
    @error('armazenamento_residuos')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Consumo de água (m³)</span>
        <input type="number" name="consumo_agua_m3" value="{{ old('consumo_agua_m3', $registro->consumo_agua_m3) }}" step="0.001" min="0" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('consumo_agua_m3')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Consumo de energia (kWh)</span>
        <input type="number" name="consumo_energia_kwh" value="{{ old('consumo_energia_kwh', $registro->consumo_energia_kwh) }}" step="0.001" min="0" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('consumo_energia_kwh')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Licenças / condicionantes (se aplicável)</span>
    <textarea name="licencas_condicionantes" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('licencas_condicionantes', $registro->licencas_condicionantes) }}</textarea>
    @error('licencas_condicionantes')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ações ambientais realizadas (descrição)</span>
    <textarea name="acoes_ambientais_realizadas" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('acoes_ambientais_realizadas', $registro->acoes_ambientais_realizadas) }}</textarea>
    @error('acoes_ambientais_realizadas')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Campanhas ambientais</span>
    <textarea name="campanhas_ambientais" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('campanhas_ambientais', $registro->campanhas_ambientais) }}</textarea>
    @error('campanhas_ambientais')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observações</span>
    <textarea name="observacoes" rows="2" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('observacoes', $registro->observacoes) }}</textarea>
    @error('observacoes')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>
