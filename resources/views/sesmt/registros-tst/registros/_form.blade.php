@php
    $dataVal = old('data', $registro->data?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $atividadeId = old('ssma_tst_atividade_id', $registro->ssma_tst_atividade_id);
    $colaboradorId = old('colaborador_id', $registro->colaborador_id);
    $descricaoVal = old('descricao', $registro->descricao);
    $fotosExistentes = $registro->exists ? $registro->fotos->count() : 0;
    $fotosExistentesUrls = $registro->exists
        ? $registro->fotos->map(fn ($f) => $f->urlPublica())->filter()->values()->all()
        : [];
@endphp

<div class="mx-auto max-w-lg space-y-6">
    <label class="block">
        <span class="text-sm font-semibold text-brand-black">Atividade</span>
        <span class="ml-1 text-xs font-normal text-brand-gray">(opcional)</span>
        <select name="ssma_tst_atividade_id" class="mt-2 h-12 w-full rounded-xl border border-zinc-200 bg-white px-4 text-base outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            <option value="">Escolher</option>
            @foreach ($atividades as $atv)
                <option value="{{ $atv->id }}" @selected((string) $atividadeId === (string) $atv->id)>{{ $atv->nome }}</option>
            @endforeach
        </select>
        @error('ssma_tst_atividade_id')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-brand-black">Data <span class="text-red-600">*</span></span>
        <input type="date" name="data" value="{{ $dataVal }}" required class="mt-2 h-12 w-full rounded-xl border border-zinc-200 bg-white px-4 text-base outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('data')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-brand-black">Colaborador <span class="text-red-600">*</span></span>
        <select name="colaborador_id" required class="mt-2 h-12 w-full rounded-xl border border-zinc-200 bg-white px-4 text-base outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            <option value="">Escolher</option>
            @foreach ($colaboradores as $colab)
                <option value="{{ $colab->id }}" @selected((string) $colaboradorId === (string) $colab->id)>
                    {{ $colab->nome }}@if ($colab->matricula) ({{ $colab->matricula }})@endif
                </option>
            @endforeach
        </select>
        @error('colaborador_id')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-brand-black">Descrição da atividade <span class="text-red-600">*</span></span>
        <textarea name="descricao" rows="4" required placeholder="Descreva o que foi realizado..." class="mt-2 w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-base outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $descricaoVal }}</textarea>
        @error('descricao')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <x-tst-fotos-upload
        variant="admin"
        :obrigatorio="! $registro->exists"
        :fotosExistentes="$fotosExistentes"
        :fotosExistentesUrls="$fotosExistentesUrls"
    />
</div>
