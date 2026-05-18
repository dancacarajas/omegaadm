@extends('layouts.app')

@section('title', 'Registro TST #' . $registro->id)
@section('eyebrow', 'SSMA / Registros TST')
@section('page-title', 'Registro de campo')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.registros-tst.registros.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Lista
        </a>
        @if ($podeEditar)
            <a href="{{ route('sesmt.registros-tst.registros.edit', $registro) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="pencil" class="h-4 w-4"></i>
                Editar
            </a>
            <form method="POST" action="{{ route('sesmt.registros-tst.registros.destroy', $registro) }}" onsubmit="return confirm('Excluir este registro permanentemente?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 transition hover:bg-red-100">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Excluir
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    @php
        use App\Support\SsmaTstRegistroService;

        $fotos = $registro->fotos->map(fn ($f) => [
            'url' => $f->urlPublica(),
            'nome' => $f->arquivo_nome ?? 'Foto',
        ])->filter(fn ($f) => $f['url'])->values();

        $origemLabel = match ($registro->origem) {
            SsmaTstRegistroService::ORIGEM_APP_COLABORADOR => 'App colaborador',
            SsmaTstRegistroService::ORIGEM_SISTEMA => 'Painel SSMA',
            default => $registro->origem ? ucfirst(str_replace('_', ' ', $registro->origem)) : '—',
        };
        $iniciais = mb_strtoupper(mb_substr($registro->colaborador?->nome ?? '?', 0, 1));
        $multiFotos = $fotos->count() > 1;
    @endphp

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('success') }}</div>
    @endif

    <div class="tst-show-page">
        <section class="tst-show-hero">
            <div class="tst-show-hero-glow-a"></div>
            <div class="tst-show-hero-glow-b"></div>
            <div class="tst-show-hero-inner">
                <div class="tst-show-hero-left">
                    <div class="tst-show-avatar">{{ $iniciais }}</div>
                    <div class="min-w-0">
                        <p class="tst-show-eyebrow">Registro #{{ $registro->id }}</p>
                        <h2 class="tst-show-title">{{ $registro->colaborador?->nome ?? 'Colaborador não informado' }}</h2>
                        <p class="tst-show-meta">
                            <i data-lucide="calendar" class="inline h-4 w-4 text-brand-burgundy"></i>
                            {{ $registro->data->format('d/m/Y') }}
                            @if ($registro->colaborador?->matricula)
                                <span class="text-zinc-300">·</span>
                                <i data-lucide="id-card" class="inline h-4 w-4 text-brand-burgundy"></i>
                                Mat. {{ $registro->colaborador->matricula }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="tst-show-badges">
                    @if ($registro->atividade?->nome)
                        <span class="tst-show-badge tst-show-badge--primary">
                            <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
                            {{ $registro->atividade->nome }}
                        </span>
                    @endif
                    <span class="tst-show-badge">
                        <i data-lucide="{{ $registro->origem === SsmaTstRegistroService::ORIGEM_APP_COLABORADOR ? 'smartphone' : 'monitor' }}" class="h-3.5 w-3.5"></i>
                        {{ $origemLabel }}
                    </span>
                    @if ($fotos->isNotEmpty())
                        <span class="tst-show-badge tst-show-badge--photos">
                            <i data-lucide="camera" class="h-3.5 w-3.5"></i>
                            {{ $fotos->count() }} {{ $fotos->count() === 1 ? 'foto' : 'fotos' }}
                        </span>
                    @endif
                </div>
            </div>
        </section>

        @if ($fotos->isNotEmpty())
            <section class="tst-show-galeria" data-tst-galeria>
                <div class="tst-show-galeria-head">
                    <div>
                        <p class="tst-show-galeria-title">Registro fotográfico</p>
                        <p class="tst-show-galeria-hint">Clique na imagem para ampliar{{ $multiFotos ? ' · use as miniaturas para alternar' : '' }}</p>
                    </div>
                    <a href="{{ $fotos->first()['url'] }}" target="_blank" rel="noopener" class="hidden text-xs font-bold text-brand-burgundy hover:underline sm:inline" data-tst-abrir-nova>
                        Abrir original ↗
                    </a>
                </div>

                <div class="tst-show-galeria-body {{ $multiFotos ? 'tst-show-galeria-body--multi' : '' }}">
                    <div class="tst-show-galeria-main">
                        <button type="button" class="tst-show-galeria-main-btn" data-tst-main-trigger aria-label="Ampliar foto">
                            <img
                                src="{{ $fotos->first()['url'] }}"
                                alt="Foto 1"
                                class="tst-show-galeria-main-img"
                                data-tst-main-img
                                loading="eager"
                            >
                            <span class="tst-show-galeria-zoom-hint">
                                <i data-lucide="maximize-2" class="h-3.5 w-3.5"></i>
                                Ampliar
                            </span>
                        </button>
                        @if ($multiFotos)
                            <button type="button" class="tst-show-galeria-nav tst-show-galeria-nav--prev" data-tst-nav="prev" aria-label="Foto anterior">
                                <i data-lucide="chevron-left" class="h-5 w-5"></i>
                            </button>
                            <button type="button" class="tst-show-galeria-nav tst-show-galeria-nav--next" data-tst-nav="next" aria-label="Próxima foto">
                                <i data-lucide="chevron-right" class="h-5 w-5"></i>
                            </button>
                            <p class="tst-show-galeria-counter" data-tst-contador>1 / {{ $fotos->count() }}</p>
                        @endif
                    </div>

                    @if ($multiFotos)
                        <div class="tst-show-thumbs">
                            @foreach ($fotos as $i => $foto)
                                <button
                                    type="button"
                                    class="tst-show-thumb {{ $i === 0 ? 'tst-show-thumb--active' : '' }}"
                                    data-tst-thumb
                                    data-index="{{ $i }}"
                                    data-url="{{ $foto['url'] }}"
                                    aria-label="Ver foto {{ $i + 1 }}"
                                    aria-current="{{ $i === 0 ? 'true' : 'false' }}"
                                >
                                    <img src="{{ $foto['url'] }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="tst-show-cards">
            <article class="tst-show-card">
                <div class="tst-show-card-row">
                    <span class="tst-show-card-icon bg-brand-burgundy-soft text-brand-burgundy">
                        <i data-lucide="calendar-days" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <p class="tst-show-card-label">Data do registro</p>
                        <p class="tst-show-card-value">{{ $registro->data->format('d/m/Y') }}</p>
                    </div>
                </div>
            </article>
            <article class="tst-show-card">
                <div class="tst-show-card-row">
                    <span class="tst-show-card-icon bg-blue-50 text-blue-800">
                        <i data-lucide="user" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="tst-show-card-label">Colaborador</p>
                        <p class="tst-show-card-value truncate">{{ $registro->colaborador?->nome ?? '—' }}</p>
                    </div>
                </div>
            </article>
            <article class="tst-show-card">
                <div class="tst-show-card-row">
                    <span class="tst-show-card-icon bg-amber-50 text-amber-900">
                        <i data-lucide="tag" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="tst-show-card-label">Atividade</p>
                        <p class="tst-show-card-value truncate">{{ $registro->atividade?->nome ?? 'Não informada' }}</p>
                    </div>
                </div>
            </article>
            <article class="tst-show-card">
                <div class="tst-show-card-row">
                    <span class="tst-show-card-icon bg-emerald-50 text-emerald-800">
                        <i data-lucide="clock" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <p class="tst-show-card-label">Enviado em</p>
                        <p class="tst-show-card-value">{{ $registro->created_at->format('d/m/Y H:i') }}</p>
                        @if ($registro->usuario)
                            <p class="mt-0.5 text-xs text-brand-gray">por {{ $registro->usuario->name }}</p>
                        @endif
                    </div>
                </div>
            </article>
        </section>

        <section class="tst-show-desc">
            <div class="flex items-start gap-3 border-b border-zinc-100 pb-4">
                <span class="tst-show-card-icon bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="file-text" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="tst-show-galeria-title">Descrição da atividade</p>
                    <p class="tst-show-galeria-hint">Relato registrado em campo</p>
                </div>
            </div>
            <div class="tst-show-desc-inner">
                <p class="whitespace-pre-wrap text-base leading-relaxed text-brand-black">{{ $registro->descricao }}</p>
            </div>
        </section>
    </div>

    @if ($fotos->isNotEmpty())
        <div class="tst-show-lightbox" data-tst-lightbox role="dialog" aria-modal="true" aria-label="Visualização ampliada">
            <button type="button" class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white" data-tst-lightbox-close aria-label="Fechar">
                <i data-lucide="x" class="h-6 w-6"></i>
            </button>
            @if ($multiFotos)
                <button type="button" class="absolute left-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white" data-tst-lightbox-prev aria-label="Anterior">
                    <i data-lucide="chevron-left" class="h-7 w-7"></i>
                </button>
                <button type="button" class="absolute right-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white" data-tst-lightbox-next aria-label="Próxima">
                    <i data-lucide="chevron-right" class="h-7 w-7"></i>
                </button>
            @endif
            <img src="" alt="" data-tst-lightbox-img>
            <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-sm font-semibold text-white/90" data-tst-lightbox-caption></p>
        </div>
    @endif
@endsection

@if ($fotos->isNotEmpty())
    @push('scripts')
        <script>
            (function () {
                const fotos = @json($fotos->values());
                let atual = 0;

                const mainImg = document.querySelector('[data-tst-main-img]');
                const mainTrigger = document.querySelector('[data-tst-main-trigger]');
                const contador = document.querySelector('[data-tst-contador]');
                const linkNova = document.querySelector('[data-tst-abrir-nova]');
                const thumbs = document.querySelectorAll('[data-tst-thumb]');
                const lightbox = document.querySelector('[data-tst-lightbox]');
                const lightboxImg = document.querySelector('[data-tst-lightbox-img]');
                const lightboxCaption = document.querySelector('[data-tst-lightbox-caption]');

                function irPara(index) {
                    if (!fotos.length) return;
                    atual = (index + fotos.length) % fotos.length;
                    const f = fotos[atual];
                    if (mainImg) {
                        mainImg.src = f.url;
                        mainImg.alt = 'Foto ' + (atual + 1);
                    }
                    if (linkNova) linkNova.href = f.url;
                    if (contador) contador.textContent = (atual + 1) + ' / ' + fotos.length;
                    thumbs.forEach((btn, i) => {
                        const ativo = i === atual;
                        btn.setAttribute('aria-current', ativo ? 'true' : 'false');
                        btn.classList.toggle('tst-show-thumb--active', ativo);
                    });
                }

                function atualizarLightbox() {
                    if (!lightboxImg) return;
                    const f = fotos[atual];
                    lightboxImg.src = f.url;
                    lightboxImg.alt = 'Foto ' + (atual + 1);
                    if (lightboxCaption) lightboxCaption.textContent = 'Foto ' + (atual + 1) + ' de ' + fotos.length;
                }

                function abrirLightbox() {
                    if (!lightbox) return;
                    atualizarLightbox();
                    lightbox.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                }

                function fecharLightbox() {
                    if (!lightbox) return;
                    lightbox.classList.remove('is-open');
                    document.body.style.overflow = '';
                }

                thumbs.forEach((btn) => {
                    btn.addEventListener('click', () => irPara(parseInt(btn.dataset.index, 10)));
                });

                document.querySelectorAll('[data-tst-nav]').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        irPara(atual + (btn.dataset.tstNav === 'next' ? 1 : -1));
                    });
                });

                mainTrigger?.addEventListener('click', abrirLightbox);
                document.querySelector('[data-tst-lightbox-close]')?.addEventListener('click', fecharLightbox);
                lightbox?.addEventListener('click', (e) => { if (e.target === lightbox) fecharLightbox(); });
                document.querySelector('[data-tst-lightbox-prev]')?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    irPara(atual - 1);
                    atualizarLightbox();
                });
                document.querySelector('[data-tst-lightbox-next]')?.addEventListener('click', (e) => {
                    e.stopPropagation();
                    irPara(atual + 1);
                    atualizarLightbox();
                });

                document.addEventListener('keydown', (e) => {
                    if (!lightbox?.classList.contains('is-open')) return;
                    if (e.key === 'Escape') fecharLightbox();
                    if (e.key === 'ArrowLeft') { irPara(atual - 1); atualizarLightbox(); }
                    if (e.key === 'ArrowRight') { irPara(atual + 1); atualizarLightbox(); }
                });

                if (window.lucide?.createIcons) window.lucide.createIcons();
            })();
        </script>
    @endpush
@endif
