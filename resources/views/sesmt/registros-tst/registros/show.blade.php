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

        if ($fotos->isEmpty() && $registro->arquivo_path) {
            $fotos = collect([[
                'url' => asset('storage/'.ltrim($registro->arquivo_path, '/')),
                'nome' => $registro->arquivo_nome ?? 'Foto',
            ]]);
        }

        $origemLabel = match ($registro->origem) {
            SsmaTstRegistroService::ORIGEM_APP_COLABORADOR => 'App colaborador',
            SsmaTstRegistroService::ORIGEM_SISTEMA => 'Painel SSMA',
            default => $registro->origem ? ucfirst(str_replace('_', ' ', $registro->origem)) : '—',
        };
        $iniciais = mb_strtoupper(mb_substr($registro->colaborador?->nome ?? '?', 0, 1));
    @endphp

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('success') }}</div>
    @endif

    <div class="space-y-6">
        {{-- Hero --}}
        <section class="relative overflow-hidden rounded-2xl border border-brand-burgundy/15 bg-gradient-to-br from-white via-white to-brand-burgundy-soft/50 p-6 shadow-sm sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-brand-burgundy/5 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-10 h-40 w-40 rounded-full bg-brand-burgundy/8 blur-2xl"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 flex-1 items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-lg font-bold text-white shadow-lg shadow-brand-burgundy/25">
                        {{ $iniciais }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-brand-burgundy">Registro #{{ $registro->id }}</p>
                        <h2 class="mt-1 text-2xl font-bold tracking-tight text-brand-black sm:text-3xl">{{ $registro->colaborador?->nome ?? 'Colaborador não informado' }}</h2>
                        <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-brand-gray">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="calendar" class="h-4 w-4 text-brand-burgundy"></i>
                                {{ $registro->data->format('d/m/Y') }}
                            </span>
                            @if ($registro->colaborador?->matricula)
                                <span class="text-zinc-300">|</span>
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="id-card" class="h-4 w-4 text-brand-burgundy"></i>
                                    Mat. {{ $registro->colaborador->matricula }}
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 lg:justify-end">
                    @if ($registro->atividade?->nome)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-burgundy/20 bg-white px-3 py-1.5 text-xs font-bold text-brand-burgundy shadow-sm">
                            <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
                            {{ $registro->atividade->nome }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-bold text-brand-gray shadow-sm">
                            Sem atividade vinculada
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white/90 px-3 py-1.5 text-xs font-bold text-brand-gray shadow-sm">
                        <i data-lucide="{{ $registro->origem === SsmaTstRegistroService::ORIGEM_APP_COLABORADOR ? 'smartphone' : 'monitor' }}" class="h-3.5 w-3.5"></i>
                        {{ $origemLabel }}
                    </span>
                    @if ($fotos->isNotEmpty())
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 shadow-sm">
                            <i data-lucide="camera" class="h-3.5 w-3.5"></i>
                            {{ $fotos->count() }} {{ $fotos->count() === 1 ? 'foto' : 'fotos' }}
                        </span>
                    @endif
                </div>
            </div>
        </section>

        @if ($fotos->isNotEmpty())
            {{-- Galeria --}}
            <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm" data-tst-galeria>
                <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-brand-burgundy">Registro fotográfico</p>
                        <p class="mt-0.5 text-sm text-brand-gray">Clique na imagem para ampliar · use as miniaturas para alternar</p>
                    </div>
                    <a href="{{ $fotos->first()['url'] }}" target="_blank" rel="noopener" class="hidden items-center gap-1.5 text-xs font-bold text-brand-burgundy hover:underline sm:inline-flex" data-tst-abrir-nova>
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                        Abrir original
                    </a>
                </div>

                <div class="@if ($fotos->count() > 1) grid lg:grid-cols-5 @endif">
                    <div class="@if ($fotos->count() > 1) lg:col-span-3 @endif relative bg-zinc-950/5">
                        <button type="button" class="group relative block w-full overflow-hidden text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-burgundy focus-visible:ring-offset-2" data-tst-main-trigger aria-label="Ampliar foto">
                            <img
                                src="{{ $fotos->first()['url'] }}"
                                alt="{{ $fotos->first()['nome'] }}"
                                class="max-h-[min(70vh,520px)] w-full object-contain transition duration-300 group-hover:scale-[1.01]"
                                data-tst-main-img
                            >
                            <span class="absolute bottom-4 right-4 inline-flex items-center gap-1.5 rounded-lg bg-black/55 px-3 py-1.5 text-xs font-semibold text-white opacity-0 backdrop-blur-sm transition group-hover:opacity-100">
                                <i data-lucide="maximize-2" class="h-3.5 w-3.5"></i>
                                Ampliar
                            </span>
                        </button>
                        @if ($fotos->count() > 1)
                            <button type="button" class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-black/40 text-white shadow-lg backdrop-blur-sm transition hover:bg-black/60" data-tst-nav="prev" aria-label="Foto anterior">
                                <i data-lucide="chevron-left" class="h-5 w-5"></i>
                            </button>
                            <button type="button" class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/30 bg-black/40 text-white shadow-lg backdrop-blur-sm transition hover:bg-black/60" data-tst-nav="next" aria-label="Próxima foto">
                                <i data-lucide="chevron-right" class="h-5 w-5"></i>
                            </button>
                            <p class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/50 px-3 py-1 text-xs font-bold text-white backdrop-blur-sm" data-tst-contador>1 / {{ $fotos->count() }}</p>
                        @endif
                    </div>

                    @if ($fotos->count() > 1)
                        <div class="grid grid-cols-2 gap-2 border-t border-zinc-100 p-3 sm:grid-cols-2 lg:col-span-2 lg:border-l lg:border-t-0 lg:p-4">
                            @foreach ($fotos as $i => $foto)
                                <button
                                    type="button"
                                    class="tst-galeria-thumb group relative overflow-hidden rounded-xl border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-burgundy {{ $i === 0 ? 'border-brand-burgundy ring-2 ring-brand-burgundy/20' : 'border-transparent hover:border-zinc-300' }}"
                                    data-tst-thumb
                                    data-index="{{ $i }}"
                                    data-url="{{ $foto['url'] }}"
                                    data-nome="{{ $foto['nome'] }}"
                                    aria-label="Ver foto {{ $i + 1 }}"
                                    aria-current="{{ $i === 0 ? 'true' : 'false' }}"
                                >
                                    <img src="{{ $foto['url'] }}" alt="" class="aspect-square w-full object-cover transition group-hover:scale-105">
                                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-2 py-2 text-left text-[10px] font-bold text-white opacity-0 transition group-hover:opacity-100">
                                        Foto {{ $i + 1 }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- Metadados --}}
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                        <i data-lucide="calendar-days" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data do registro</p>
                        <p class="mt-0.5 text-lg font-bold text-brand-black">{{ $registro->data->format('d/m/Y') }}</p>
                    </div>
                </div>
            </article>
            <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-800">
                        <i data-lucide="user" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Colaborador</p>
                        <p class="mt-0.5 truncate text-sm font-bold text-brand-black">{{ $registro->colaborador?->nome ?? '—' }}</p>
                    </div>
                </div>
            </article>
            <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-900">
                        <i data-lucide="tag" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Atividade</p>
                        <p class="mt-0.5 truncate text-sm font-bold text-brand-black">{{ $registro->atividade?->nome ?? 'Não informada' }}</p>
                    </div>
                </div>
            </article>
            <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">
                        <i data-lucide="clock" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Enviado em</p>
                        <p class="mt-0.5 text-sm font-bold text-brand-black">{{ $registro->created_at->format('d/m/Y H:i') }}</p>
                        @if ($registro->usuario)
                            <p class="mt-0.5 text-xs text-brand-gray">por {{ $registro->usuario->name }}</p>
                        @endif
                    </div>
                </div>
            </article>
        </section>

        {{-- Descrição --}}
        <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-start gap-3 border-b border-zinc-100 pb-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="file-text" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-burgundy">Descrição da atividade</p>
                    <p class="mt-0.5 text-sm text-brand-gray">Relato registrado em campo</p>
                </div>
            </div>
            <div class="mt-5 rounded-xl border border-zinc-100 bg-gradient-to-br from-brand-gray-soft/40 to-white p-5 sm:p-6">
                <p class="whitespace-pre-wrap text-base leading-relaxed text-brand-black">{{ $registro->descricao }}</p>
            </div>
        </section>
    </div>

    @if ($fotos->isNotEmpty())
        <div class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/90 p-4 backdrop-blur-sm" data-tst-lightbox role="dialog" aria-modal="true" aria-label="Visualização ampliada">
            <button type="button" class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" data-tst-lightbox-close aria-label="Fechar">
                <i data-lucide="x" class="h-6 w-6"></i>
            </button>
            @if ($fotos->count() > 1)
                <button type="button" class="absolute left-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" data-tst-lightbox-prev aria-label="Anterior">
                    <i data-lucide="chevron-left" class="h-7 w-7"></i>
                </button>
                <button type="button" class="absolute right-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" data-tst-lightbox-next aria-label="Próxima">
                    <i data-lucide="chevron-right" class="h-7 w-7"></i>
                </button>
            @endif
            <img src="" alt="" class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl" data-tst-lightbox-img>
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
                        mainImg.alt = f.nome;
                    }
                    if (linkNova) linkNova.href = f.url;
                    if (contador) contador.textContent = (atual + 1) + ' / ' + fotos.length;
                    thumbs.forEach((btn, i) => {
                        const ativo = i === atual;
                        btn.setAttribute('aria-current', ativo ? 'true' : 'false');
                        btn.classList.toggle('border-brand-burgundy', ativo);
                        btn.classList.toggle('ring-2', ativo);
                        btn.classList.toggle('ring-brand-burgundy/20', ativo);
                        btn.classList.toggle('border-transparent', !ativo);
                    });
                }

                function atualizarLightbox() {
                    if (!lightboxImg) return;
                    const f = fotos[atual];
                    lightboxImg.src = f.url;
                    lightboxImg.alt = f.nome;
                    if (lightboxCaption) lightboxCaption.textContent = 'Foto ' + (atual + 1) + ' de ' + fotos.length;
                }

                function abrirLightbox() {
                    if (!lightbox) return;
                    atualizarLightbox();
                    lightbox.classList.remove('hidden');
                    lightbox.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }

                function fecharLightbox() {
                    if (!lightbox) return;
                    lightbox.classList.add('hidden');
                    lightbox.classList.remove('flex');
                    document.body.style.overflow = '';
                }

                thumbs.forEach((btn) => {
                    btn.addEventListener('click', () => irPara(parseInt(btn.dataset.index, 10)));
                });

                document.querySelectorAll('[data-tst-nav]').forEach((btn) => {
                    btn.addEventListener('click', () => {
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
                    if (!lightbox || lightbox.classList.contains('hidden')) return;
                    if (e.key === 'Escape') fecharLightbox();
                    if (e.key === 'ArrowLeft') { irPara(atual - 1); atualizarLightbox(); }
                    if (e.key === 'ArrowRight') { irPara(atual + 1); atualizarLightbox(); }
                });

                if (window.lucide?.createIcons) window.lucide.createIcons();
            })();
        </script>
    @endpush
@endif
