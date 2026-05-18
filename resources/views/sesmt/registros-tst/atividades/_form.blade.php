@php
    $nomeVal = old('nome', $atividade->nome);
    $ativoVal = old('ativo', $atividade->ativo ?? true);
    $exibirAppVal = old('exibir_no_app', $atividade->exibir_no_app ?? true);
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
        <p class="mt-1 text-xs text-brand-gray">Menor número aparece primeiro nas listas.</p>
        @error('ordem')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 bg-brand-gray-soft/30 px-4 py-3">
        <input type="hidden" name="ativo" value="0">
        <input type="checkbox" name="ativo" value="1" class="mt-0.5 h-5 w-5 shrink-0 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($ativoVal) id="atividade-ativo">
        <span class="text-sm">
            <span class="font-semibold text-brand-black">Atividade ativa</span>
            <span class="mt-0.5 block text-xs font-normal text-brand-gray">Disponível no painel SSMA (registros criados pela equipe). Inativas não aparecem em lugar nenhum.</span>
        </span>
    </label>

    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft/25 px-4 py-3">
        <input type="hidden" name="exibir_no_app" value="0">
        <input type="checkbox" name="exibir_no_app" value="1" class="mt-0.5 h-5 w-5 shrink-0 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($exibirAppVal && $ativoVal) id="atividade-exibir-app" @disabled(! $ativoVal)>
        <span class="text-sm">
            <span class="font-semibold text-brand-black">Exibir no app do colaborador</span>
            <span class="mt-0.5 block text-xs font-normal text-brand-gray">Lista em <strong class="font-semibold text-brand-black">/registro-tst</strong> para colaboradores registrarem em campo. No painel SSMA continuam visíveis todas as atividades ativas.</span>
        </span>
    </label>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const ativo = document.getElementById('atividade-ativo');
                const app = document.getElementById('atividade-exibir-app');
                if (!ativo || !app) return;

                const sync = () => {
                    if (!ativo.checked) {
                        app.checked = false;
                        app.disabled = true;
                    } else {
                        app.disabled = false;
                    }
                };

                ativo.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endpush
@endonce
