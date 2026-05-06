@php
    /** @var \App\Models\SsmaPlanoAcao $plano */
    $origemVal = old('origem', $plano->origem);
    $tipoVal = old('tipo', $plano->tipo);
    $statusVal = old('status', $plano->status);
    $prioridadeVal = old('prioridade', $plano->prioridade);
    $nivelRiscoVal = old('nivel_risco', $plano->nivel_risco);
    $prazoVal = old('prazo', $plano->prazo?->format('Y-m-d'));
    $dataConclusaoVal = old('data_conclusao', $plano->data_conclusao?->format('Y-m-d'));
    $validacaoEmVal = old('validacao_ssma_em', $plano->validacao_ssma_em?->format('Y-m-d'));
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Origem da ação *</span>
        <select name="origem" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaPlanoAcao::ORIGENS as $k => $label)
                <option value="{{ $k }}" @selected($origemVal === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('origem')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo *</span>
        <select name="tipo" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaPlanoAcao::TIPOS as $k => $label)
                <option value="{{ $k }}" @selected($tipoVal === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('tipo')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Detalhe da origem (opcional)</span>
    <input type="text" name="origem_detalhe" value="{{ old('origem_detalhe', $plano->origem_detalhe) }}" maxlength="500" placeholder="Ex.: NC-2026-04, auditoria interna, obra X..." class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
    @error('origem_detalhe')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição do desvio *</span>
    <textarea name="descricao_desvio" rows="4" required class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('descricao_desvio', $plano->descricao_desvio) }}</textarea>
    @error('descricao_desvio')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ação necessária *</span>
    <textarea name="acao_necessaria" rows="4" required class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('acao_necessaria', $plano->acao_necessaria) }}</textarea>
    @error('acao_necessaria')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<div class="mt-5 grid gap-5 md:grid-cols-3">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável</span>
        <input type="text" name="responsavel" value="{{ old('responsavel', $plano->responsavel) }}" maxlength="255" placeholder="Nome ou função" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('responsavel')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Prazo *</span>
        <input type="date" name="prazo" value="{{ $prazoVal }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('prazo')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status *</span>
        <select name="status" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaPlanoAcao::STATUS as $k => $label)
                <option value="{{ $k }}" @selected($statusVal === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Prioridade *</span>
        <select name="prioridade" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaPlanoAcao::PRIORIDADES as $k => $label)
                <option value="{{ $k }}" @selected($prioridadeVal === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('prioridade')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Nível de risco *</span>
        <select name="nivel_risco" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @foreach (\App\Models\SsmaPlanoAcao::NIVEIS_RISCO as $k => $label)
                <option value="{{ $k }}" @selected($nivelRiscoVal === $k)>{{ $label }}</option>
            @endforeach
        </select>
        @error('nivel_risco')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

<div class="mt-5 grid gap-5 md:grid-cols-2">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de conclusão</span>
        <input type="date" name="data_conclusao" value="{{ $dataConclusaoVal }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('data_conclusao')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência de conclusão (arquivo)</span>
        <input type="file" name="evidencia_conclusao" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-burgundy">
        <span class="mt-1 block text-xs text-brand-gray">PDF ou imagem, até 10 MB. Envio opcional na criação.</span>
        @error('evidencia_conclusao')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @if (! empty($plano->evidencia_conclusao_path))
            <p class="mt-2 text-sm">
                <span class="font-semibold text-brand-black">Arquivo atual:</span>
                <a href="{{ asset('storage/'.$plano->evidencia_conclusao_path) }}" target="_blank" rel="noopener" class="ml-1 font-bold text-brand-burgundy underline-offset-2 hover:underline">Abrir evidência</a>
            </p>
        @endif
    </label>
</div>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Validação do SSMA</span>
    <textarea name="validacao_ssma" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('validacao_ssma', $plano->validacao_ssma) }}</textarea>
    @error('validacao_ssma')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block max-w-xs">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da validação SSMA</span>
    <input type="date" name="validacao_ssma_em" value="{{ $validacaoEmVal }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
    <span class="mt-1 block text-xs text-brand-gray">Se o status for <strong>Validada</strong> e este campo estiver vazio, a data de hoje será gravada.</span>
    @error('validacao_ssma_em')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Justificativa de atraso</span>
    <textarea name="justificativa_atraso" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('justificativa_atraso', $plano->justificativa_atraso) }}</textarea>
    @error('justificativa_atraso')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>

<label class="mt-5 block">
    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observações</span>
    <textarea name="observacoes" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('observacoes', $plano->observacoes) }}</textarea>
    @error('observacoes')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</label>
