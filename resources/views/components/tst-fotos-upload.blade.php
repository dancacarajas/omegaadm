@props([
    'minFotos' => 1,
    'maxFotos' => 4,
    'obrigatorio' => true,
    'fotosExistentes' => 0,
    'fotosExistentesUrls' => [],
    'variant' => 'mobile',
])

@php
    $restantes = max(0, $maxFotos - $fotosExistentes);
    $uid = 'tst-fotos-' . uniqid();
@endphp

<div
    class="@if ($variant === 'mobile') ponto-field-card @endif"
    data-tst-fotos-upload
    data-min="{{ $minFotos }}"
    data-max="{{ $maxFotos }}"
    data-existentes="{{ $fotosExistentes }}"
    id="{{ $uid }}"
>
    @if ($variant === 'mobile')
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-brand-gray">
            Registro fotográfico @if ($obrigatorio)<span class="text-red-600">*</span>@endif
        </p>
        <p class="mb-3 text-xs text-brand-gray">
            De {{ $minFotos }} a {{ $maxFotos }} imagens (JPG, PNG, GIF ou WebP — até 10 MB cada).
            Pode misturar câmera e galeria.
        </p>
    @else
        <span class="text-sm font-semibold text-brand-black">
            Registro fotográfico @if ($obrigatorio && $fotosExistentes === 0)<span class="text-red-600">*</span>@endif
        </span>
        <p class="mt-1 text-xs text-brand-gray">
            De {{ $minFotos }} a {{ $maxFotos }} imagens no total (JPG, PNG, GIF ou WebP — até 10 MB cada).
            @if ($fotosExistentes > 0)
                Já há {{ $fotosExistentes }} foto(s); você pode adicionar até {{ $restantes }}.
            @endif
        </p>
    @endif

    @if (count($fotosExistentesUrls) > 0)
        <div class="tst-fotos-grid mt-3" data-tst-existentes>
            @foreach ($fotosExistentesUrls as $url)
                <div class="tst-foto-thumb tst-foto-thumb--locked">
                    <img src="{{ $url }}" alt="Foto já enviada">
                </div>
            @endforeach
        </div>
    @endif

    <input type="file" class="tst-sr-only" data-tst-camera accept="image/jpeg,image/png,image/gif,image/webp" capture="environment" tabindex="-1">
    <input type="file" class="tst-sr-only" data-tst-galeria accept="image/jpeg,image/png,image/gif,image/webp" @if ($restantes > 1) multiple @endif tabindex="-1">

    <div class="@if ($variant === 'mobile') tst-photo-actions @else grid grid-cols-2 gap-2 mt-3 @endif" data-tst-actions>
        <button type="button" class="@if ($variant === 'mobile') tst-photo-btn tst-photo-btn--primary @else inline-flex items-center justify-center gap-2 rounded-xl border-2 border-brand-burgundy/30 bg-brand-burgundy-soft/40 px-3 py-3 text-sm font-semibold text-brand-burgundy @endif" data-tst-btn-camera @disabled($restantes === 0)>
            @if ($variant === 'mobile')
                <span class="tst-photo-btn-icon"><i data-lucide="camera" class="h-5 w-5"></i></span>
            @else
                <i data-lucide="camera" class="h-4 w-4"></i>
            @endif
            Tirar foto
        </button>
        <button type="button" class="@if ($variant === 'mobile') tst-photo-btn @else inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-3 text-sm font-semibold text-brand-black @endif" data-tst-btn-galeria @disabled($restantes === 0)>
            @if ($variant === 'mobile')
                <span class="tst-photo-btn-icon"><i data-lucide="image" class="h-5 w-5"></i></span>
            @else
                <i data-lucide="image" class="h-4 w-4"></i>
            @endif
            Anexar imagem
        </button>
    </div>

    <p class="tst-photo-contador @if ($variant !== 'mobile') mt-2 text-xs font-semibold text-brand-gray @endif" data-tst-contador aria-live="polite">
        @if ($fotosExistentes > 0)
            {{ $fotosExistentes }} de {{ $maxFotos }} foto(s) no registro.
        @else
            Nenhuma foto selecionada (mín. {{ $minFotos }}, máx. {{ $maxFotos }}).
        @endif
    </p>

    <div class="tst-fotos-grid hidden" data-tst-preview-grid></div>

    <p class="mt-2 hidden text-xs font-semibold text-red-600" data-tst-erro role="alert"></p>

    @error('arquivos')
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
    @error('arquivos.*')
        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const MAX_BYTES = 10 * 1024 * 1024;
                const TIPOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                function initTstFotosUpload(root) {
                    const minFotos = parseInt(root.dataset.min || '1', 10);
                    const maxFotos = parseInt(root.dataset.max || '4', 10);
                    const existentes = parseInt(root.dataset.existentes || '0', 10);
                    const maxNovas = Math.max(0, maxFotos - existentes);

                    const inputCamera = root.querySelector('[data-tst-camera]');
                    const inputGaleria = root.querySelector('[data-tst-galeria]');
                    const btnCamera = root.querySelector('[data-tst-btn-camera]');
                    const btnGaleria = root.querySelector('[data-tst-btn-galeria]');
                    const contador = root.querySelector('[data-tst-contador]');
                    const grid = root.querySelector('[data-tst-preview-grid]');
                    const erroEl = root.querySelector('[data-tst-erro]');
                    const form = root.closest('form');

                    const selecionadas = [];

                    function mostrarErro(msg) {
                        if (!erroEl) return;
                        if (msg) {
                            erroEl.textContent = msg;
                            erroEl.classList.remove('hidden');
                        } else {
                            erroEl.textContent = '';
                            erroEl.classList.add('hidden');
                        }
                    }

                    function totalFotos() {
                        return existentes + selecionadas.length;
                    }

                    function atualizarContador() {
                        const total = totalFotos();
                        if (contador) {
                            if (selecionadas.length === 0 && existentes > 0) {
                                contador.textContent = existentes + ' de ' + maxFotos + ' foto(s) no registro. Adicione até ' + maxNovas + ' nova(s).';
                            } else if (selecionadas.length === 0) {
                                contador.textContent = 'Nenhuma foto selecionada (mín. ' + minFotos + ', máx. ' + maxFotos + ').';
                            } else {
                                contador.textContent = total + ' de ' + maxFotos + ' foto(s)' + (existentes > 0 ? ' no total' : '') + '.';
                            }
                        }
                        const desabilitar = totalFotos() >= maxFotos;
                        btnCamera?.toggleAttribute('disabled', desabilitar);
                        btnGaleria?.toggleAttribute('disabled', desabilitar);
                        if (inputGaleria) {
                            inputGaleria.multiple = !desabilitar && maxNovas - selecionadas.length > 1;
                        }
                    }

                    function sincronizarInputs() {
                        const dt = new DataTransfer();
                        selecionadas.forEach((item) => dt.items.add(item.file));
                        let holder = form?.querySelector('[data-tst-arquivos-holder]');
                        if (!holder && form) {
                            holder = document.createElement('div');
                            holder.setAttribute('data-tst-arquivos-holder', '');
                            holder.className = 'hidden';
                            form.appendChild(holder);
                        }
                        if (holder) {
                            holder.innerHTML = '';
                            for (let i = 0; i < dt.files.length; i++) {
                                const input = document.createElement('input');
                                input.type = 'file';
                                input.name = 'arquivos[]';
                                input.className = 'hidden';
                                input.files = (() => {
                                    const d = new DataTransfer();
                                    d.items.add(dt.files[i]);
                                    return d.files;
                                })();
                                holder.appendChild(input);
                            }
                        }
                    }

                    function renderGrid() {
                        if (!grid) return;
                        grid.innerHTML = '';
                        if (selecionadas.length === 0) {
                            grid.classList.add('hidden');
                            return;
                        }
                        grid.classList.remove('hidden');
                        selecionadas.forEach((item, index) => {
                            const wrap = document.createElement('div');
                            wrap.className = 'tst-foto-thumb';
                            const img = document.createElement('img');
                            img.src = item.url;
                            img.alt = 'Foto ' + (index + 1);
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'tst-foto-remover';
                            btn.textContent = 'Remover';
                            btn.addEventListener('click', () => remover(index));
                            wrap.appendChild(img);
                            wrap.appendChild(btn);
                            grid.appendChild(wrap);
                        });
                    }

                    function validarArquivo(file) {
                        if (!TIPOS.includes(file.type)) {
                            return 'Use apenas JPG, PNG, GIF ou WebP.';
                        }
                        if (file.size > MAX_BYTES) {
                            return 'Cada imagem deve ter no máximo 10 MB.';
                        }
                        return null;
                    }

                    function adicionarArquivos(files) {
                        mostrarErro('');
                        const lista = Array.from(files || []);
                        if (!lista.length) return;

                        const vagas = maxNovas - selecionadas.length;
                        if (vagas <= 0) {
                            mostrarErro('Máximo de ' + maxFotos + ' fotos por registro.');
                            return;
                        }

                        const paraAdicionar = lista.slice(0, vagas);
                        if (lista.length > vagas) {
                            mostrarErro('Só é possível adicionar mais ' + vagas + ' foto(s).');
                        }

                        for (const file of paraAdicionar) {
                            const err = validarArquivo(file);
                            if (err) {
                                mostrarErro(err);
                                continue;
                            }
                            const duplicada = selecionadas.some(
                                (s) => s.file.name === file.name && s.file.size === file.size && s.file.lastModified === file.lastModified
                            );
                            if (duplicada) continue;

                            selecionadas.push({
                                file,
                                url: URL.createObjectURL(file),
                            });
                        }

                        inputCamera.value = '';
                        inputGaleria.value = '';
                        renderGrid();
                        sincronizarInputs();
                        atualizarContador();
                    }

                    function remover(index) {
                        const item = selecionadas[index];
                        if (item?.url) URL.revokeObjectURL(item.url);
                        selecionadas.splice(index, 1);
                        renderGrid();
                        sincronizarInputs();
                        atualizarContador();
                        mostrarErro('');
                    }

                    btnCamera?.addEventListener('click', () => {
                        if (totalFotos() < maxFotos) inputCamera?.click();
                    });
                    btnGaleria?.addEventListener('click', () => {
                        if (totalFotos() < maxFotos) inputGaleria?.click();
                    });
                    inputCamera?.addEventListener('change', () => adicionarArquivos(inputCamera.files));
                    inputGaleria?.addEventListener('change', () => adicionarArquivos(inputGaleria.files));

                    form?.addEventListener('submit', function (e) {
                        const total = totalFotos();
                        if (existentes === 0 && total < minFotos) {
                            e.preventDefault();
                            mostrarErro('Adicione pelo menos ' + minFotos + ' foto (máximo ' + maxFotos + ').');
                            return;
                        }
                        if (total > maxFotos) {
                            e.preventDefault();
                            mostrarErro('Máximo de ' + maxFotos + ' fotos por registro.');
                        }
                    });

                    atualizarContador();
                }

                document.querySelectorAll('[data-tst-fotos-upload]').forEach(initTstFotosUpload);

                if (window.lucide?.createIcons) {
                    window.lucide.createIcons();
                }
            })();
        </script>
    @endpush
@endonce
