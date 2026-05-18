@extends('layouts.ponto-mobile')

@section('title', 'Novo registro TST')

@section('content')
    <header class="ponto-header">
        <div class="ponto-header-top">
            @include('ponto._brand', ['class' => 'ponto-logo'])
            <form method="POST" action="{{ route('tst-campo.sair') }}">
                @csrf
                <button type="submit" class="ponto-btn-sair" title="Sair" aria-label="Sair">
                    <i data-lucide="log-out" class="h-5 w-5"></i>
                </button>
            </form>
        </div>

        <div class="ponto-user">
            <div class="ponto-user-avatar">{{ mb_substr($colaborador->nome, 0, 1) }}</div>
            <div class="ponto-user-meta">
                <p class="ponto-user-label">Colaborador</p>
                <h1 class="ponto-user-name">{{ $colaborador->nome }}</h1>
                <p class="ponto-user-matricula">Matrícula {{ $colaborador->matricula ?: '—' }}</p>
            </div>
        </div>

        <div class="ponto-clock-card">
            <p class="ponto-clock-label">Registro de campo</p>
            <p class="ponto-clock-date">{{ now()->format('d/m/Y') }}</p>
            <p class="ponto-clock-tz">Segurança do trabalho · CT-286</p>
        </div>
    </header>

    <main class="ponto-main">
        @if (session('success'))
            <div class="ponto-card" role="status">
                <span class="ponto-card-icon ponto-card-icon--success">
                    <i data-lucide="circle-check" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-text text-emerald-950">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="ponto-card" role="alert">
                <span class="ponto-card-icon ponto-card-icon--error">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-title ponto-card-title--warn">Verifique os campos</p>
                    <ul class="mt-2 list-inside list-disc text-sm text-red-950">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="ponto-section">
            <div class="ponto-section-head">
                <h2 class="ponto-section-title">Novo registro</h2>
            </div>
            <form method="POST" action="{{ route('tst-campo.store') }}" enctype="multipart/form-data" id="form-tst-campo" class="flex flex-col gap-3">
                @csrf

                <div class="ponto-field-card">
                    <label for="ssma_tst_atividade_id" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">
                        Atividade <span class="font-normal normal-case text-brand-gray">(opcional)</span>
                    </label>
                    <select name="ssma_tst_atividade_id" id="ssma_tst_atividade_id" class="ponto-input">
                        <option value="">Escolher</option>
                        @foreach ($atividades as $atv)
                            <option value="{{ $atv->id }}" @selected(old('ssma_tst_atividade_id') == $atv->id)>{{ $atv->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ponto-field-card">
                    <label for="data" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Data <span class="text-red-600">*</span></label>
                    <input type="date" name="data" id="data" value="{{ old('data', $dataHoje) }}" required class="ponto-input">
                </div>

                <div class="ponto-field-card">
                    <label for="descricao" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição da atividade <span class="text-red-600">*</span></label>
                    <textarea name="descricao" id="descricao" rows="4" required placeholder="Descreva o que foi realizado..." class="ponto-input min-h-[6rem] resize-y py-3">{{ old('descricao') }}</textarea>
                </div>

                <div class="ponto-field-card" id="tst-foto-card">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-brand-gray">Registro fotográfico <span class="text-red-600">*</span></p>
                    <p class="mb-3 text-xs text-brand-gray">Somente imagem (JPG, PNG, GIF ou WebP — até 10 MB).</p>

                    <input type="file" id="arquivo-camera" class="tst-sr-only" accept="image/jpeg,image/png,image/gif,image/webp" capture="environment" tabindex="-1">
                    <input type="file" id="arquivo-galeria" class="tst-sr-only" accept="image/jpeg,image/png,image/gif,image/webp" tabindex="-1">
                    <input type="file" name="arquivo" id="arquivo" class="tst-sr-only" accept="image/jpeg,image/png,image/gif,image/webp" required tabindex="-1">

                    <div class="tst-photo-actions" id="tst-photo-actions">
                        <button type="button" class="tst-photo-btn tst-photo-btn--primary" id="btn-tirar-foto" aria-label="Abrir câmera e tirar foto">
                            <span class="tst-photo-btn-icon">
                                <i data-lucide="camera" class="h-5 w-5"></i>
                            </span>
                            Tirar foto
                        </button>
                        <button type="button" class="tst-photo-btn" id="btn-escolher-galeria" aria-label="Escolher imagem da galeria">
                            <span class="tst-photo-btn-icon">
                                <i data-lucide="image" class="h-5 w-5"></i>
                            </span>
                            Anexar imagem
                        </button>
                    </div>

                    <p id="tst-photo-filename" class="tst-photo-filename hidden" aria-live="polite"></p>

                    <div id="tst-preview" class="mt-3 hidden overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50">
                        <img src="" alt="Pré-visualização da foto" class="max-h-52 w-full object-contain">
                        <button type="button" id="btn-remover-foto" class="tst-photo-clear">Remover foto e escolher outra</button>
                    </div>
                    <p id="tst-photo-erro" class="mt-2 hidden text-xs font-semibold text-red-600" role="alert"></p>
                </div>

                <button type="submit" class="ponto-btn-primary">
                    <span class="ponto-btn-primary-row">
                        <i data-lucide="send" class="h-5 w-5"></i>
                        Enviar registro
                    </span>
                    <span class="ponto-btn-primary-hint">Os dados serão enviados ao SSMA</span>
                </button>
            </form>
        </section>

        @if ($recentes->isNotEmpty())
            <section class="ponto-section">
                <div class="ponto-section-head">
                    <h2 class="ponto-section-title">Seus registros recentes</h2>
                    <span class="ponto-badge">{{ $recentes->count() }}</span>
                </div>
                <ul class="ponto-batida-list">
                    @foreach ($recentes as $item)
                        <li class="ponto-batida ponto-batida--done">
                            <div class="ponto-batida-left min-w-0">
                                <span class="ponto-batida-icon ponto-batida-icon--done">
                                    <i data-lucide="camera" class="h-4 w-4"></i>
                                </span>
                                <span class="ponto-batida-label min-w-0">
                                    <span class="block truncate font-semibold">{{ $item->atividade?->nome ?? 'Sem atividade' }}</span>
                                    <span class="ponto-batida-sublabel line-clamp-2">{{ Str::limit($item->descricao, 80) }}</span>
                                </span>
                            </div>
                            <span class="ponto-batida-hora ponto-batida-hora--done shrink-0 text-right">
                                {{ $item->data->format('d/m/Y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </main>
@endsection

@push('scripts')
    <script>
        (function () {
            const MAX_BYTES = 10 * 1024 * 1024;
            const TIPOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            const inputEnvio = document.getElementById('arquivo');
            const inputCamera = document.getElementById('arquivo-camera');
            const inputGaleria = document.getElementById('arquivo-galeria');
            const btnCamera = document.getElementById('btn-tirar-foto');
            const btnGaleria = document.getElementById('btn-escolher-galeria');
            const btnRemover = document.getElementById('btn-remover-foto');
            const preview = document.getElementById('tst-preview');
            const img = preview?.querySelector('img');
            const filenameEl = document.getElementById('tst-photo-filename');
            const erroEl = document.getElementById('tst-photo-erro');
            const form = document.getElementById('form-tst-campo');

            let previewUrl = null;

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

            function limparPreviewUrl() {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    previewUrl = null;
                }
            }

            function aplicarArquivo(file) {
                mostrarErro('');
                if (!file) {
                    return false;
                }
                if (!TIPOS.includes(file.type)) {
                    mostrarErro('Selecione apenas imagem (JPG, PNG, GIF ou WebP).');
                    return false;
                }
                if (file.size > MAX_BYTES) {
                    mostrarErro('A imagem deve ter no máximo 10 MB.');
                    return false;
                }

                const dt = new DataTransfer();
                dt.items.add(file);
                inputEnvio.files = dt.files;

                limparPreviewUrl();
                if (img && preview) {
                    previewUrl = URL.createObjectURL(file);
                    img.src = previewUrl;
                    preview.classList.remove('hidden');
                }
                if (filenameEl) {
                    filenameEl.textContent = file.name;
                    filenameEl.classList.remove('hidden');
                }
                return true;
            }

            function limparFoto() {
                limparPreviewUrl();
                inputEnvio.value = '';
                inputCamera.value = '';
                inputGaleria.value = '';
                preview?.classList.add('hidden');
                filenameEl?.classList.add('hidden');
                mostrarErro('');
            }

            btnCamera?.addEventListener('click', () => inputCamera?.click());
            btnGaleria?.addEventListener('click', () => inputGaleria?.click());

            inputCamera?.addEventListener('change', () => {
                const file = inputCamera.files?.[0];
                if (file) aplicarArquivo(file);
            });

            inputGaleria?.addEventListener('change', () => {
                const file = inputGaleria.files?.[0];
                if (file) aplicarArquivo(file);
            });

            btnRemover?.addEventListener('click', limparFoto);

            form?.addEventListener('submit', function (e) {
                if (!inputEnvio?.files?.length) {
                    e.preventDefault();
                    mostrarErro('Adicione uma foto: tire uma foto ou anexe uma imagem.');
                    return;
                }
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                }
            });

            if (window.lucide?.createIcons) {
                window.lucide.createIcons();
            }
        })();
    </script>
@endpush
