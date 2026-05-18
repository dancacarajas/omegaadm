@php
    $dataVal = old('data', $registro->data?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $atividadeId = old('ssma_tst_atividade_id', $registro->ssma_tst_atividade_id);
    $colaboradorId = old('colaborador_id', $registro->colaborador_id);
    $descricaoVal = old('descricao', $registro->descricao);
    $arquivoObrigatorio = ! $registro->exists;
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

    <label class="block">
        <span class="text-sm font-semibold text-brand-black">Registro fotográfico @if ($arquivoObrigatorio)<span class="text-red-600">*</span>@endif</span>
        <p class="mt-1 text-xs text-brand-gray">Somente imagem (JPG, PNG, GIF ou WebP). Tamanho máximo: 10 MB.</p>
        @if ($registro->arquivo_path)
            <p class="mt-2 text-xs font-medium text-brand-gray">Arquivo atual: {{ $registro->arquivo_nome ?? 'anexo' }}</p>
        @endif
        <div class="mt-3 sm:max-w-md" data-tst-admin-foto>
            <input type="file" id="arquivo-admin-camera" class="sr-only" accept="image/jpeg,image/png,image/gif,image/webp" capture="environment" tabindex="-1">
            <input type="file" id="arquivo-admin-galeria" class="sr-only" accept="image/jpeg,image/png,image/gif,image/webp" tabindex="-1">
            <input type="file" name="arquivo" id="arquivo-admin" class="sr-only" accept="image/jpeg,image/png,image/gif,image/webp" @if ($arquivoObrigatorio) required @endif tabindex="-1">
            <div class="grid grid-cols-2 gap-2">
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-brand-burgundy/30 bg-brand-burgundy-soft/40 px-3 py-3 text-sm font-semibold text-brand-burgundy" data-tst-btn-camera>
                    <i data-lucide="camera" class="h-4 w-4"></i>
                    Tirar foto
                </button>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-3 text-sm font-semibold text-brand-black" data-tst-btn-galeria>
                    <i data-lucide="image" class="h-4 w-4"></i>
                    Anexar imagem
                </button>
            </div>
            <p class="mt-2 hidden text-xs font-medium text-brand-gray" data-tst-foto-nome></p>
        </div>
        @error('arquivo')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </label>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                document.querySelectorAll('[data-tst-admin-foto]').forEach(function (wrap) {
                    const envio = wrap.querySelector('#arquivo-admin');
                    const cam = wrap.querySelector('#arquivo-admin-camera');
                    const gal = wrap.querySelector('#arquivo-admin-galeria');
                    const nome = wrap.querySelector('[data-tst-foto-nome]');
                    if (!envio || !cam || !gal) return;

                    const aplicar = (file) => {
                        if (!file || !file.type.startsWith('image/')) return;
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        envio.files = dt.files;
                        if (nome) {
                            nome.textContent = file.name;
                            nome.classList.remove('hidden');
                        }
                    };

                    wrap.querySelector('[data-tst-btn-camera]')?.addEventListener('click', () => cam.click());
                    wrap.querySelector('[data-tst-btn-galeria]')?.addEventListener('click', () => gal.click());
                    cam.addEventListener('change', () => aplicar(cam.files?.[0]));
                    gal.addEventListener('change', () => aplicar(gal.files?.[0]));
                });
            })();
        </script>
    @endpush
@endonce
