@php
    $nomeVal = old('nome', $atividade->nome);
    $ativoVal = old('ativo', $atividade->ativo ?? true);
    $ordemVal = old('ordem', $atividade->ordem ?? 0);
@endphp

<div class="grid max-w-xl gap-5">
    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Nome da atividade <span class="text-red-600">*</span></span>
        <input type="text" name="nome" value="{{ $nomeVal }}" required maxlength="255" placeholder="Ex.: Inspeção de andaime" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        @error('nome')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label>
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ordem no formulário</span>
        <input type="number" name="ordem" value="{{ $ordemVal }}" min="0" max="9999" class="mt-2 h-11 w-full max-w-[8rem] rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        <p class="mt-1 text-xs text-brand-gray">Menor número aparece primeiro na lista do colaborador.</p>
        @error('ordem')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 bg-brand-gray-soft/30 px-4 py-3">
        <input type="hidden" name="ativo" value="0">
        <input type="checkbox" name="ativo" value="1" class="h-5 w-5 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($ativoVal)>
        <span class="text-sm font-semibold text-brand-black">Atividade disponível no formulário de campo</span>
    </label>
</div>
