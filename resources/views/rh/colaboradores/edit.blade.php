@extends('layouts.app')

@section('title', 'Editar colaborador - Omega286')
@section('eyebrow', 'RH / Efetivo')
@section('page-title', 'Editar colaborador')

@section('actions')
    <a href="{{ route('rh.efetivo.show', $colaborador) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar à ficha
    </a>
    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy/40">
        <i data-lucide="users" class="h-4 w-4"></i>
        Efetivo
    </a>
@endsection

@section('content')
    @php
        $st = $colaborador->status ?? 'ativo';
        $statusLabel = ['ativo' => 'Ativo', 'afastado' => 'Afastado', 'desligado' => 'Desligado'];
        $statusUiHero = [
            'ativo' => 'bg-emerald-400/25 text-emerald-50 ring-1 ring-inset ring-emerald-300/40',
            'afastado' => 'bg-amber-400/25 text-amber-50 ring-1 ring-inset ring-amber-300/40',
            'desligado' => 'bg-white/15 text-white/80 ring-1 ring-inset ring-white/25',
        ];
        $iniciais = collect(preg_split('/\s+/u', trim($colaborador->nome), -1, PREG_SPLIT_NO_EMPTY))
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->join('');
        $navEtapas = [
            ['id' => 'dados-pessoais', 'n' => '01', 't' => 'Dados pessoais'],
            ['id' => 'documentos', 'n' => '02', 't' => 'Documentos'],
            ['id' => 'contrato', 'n' => '03', 't' => 'Contrato'],
            ['id' => 'admissao', 'n' => '04', 't' => 'Admissão'],
            ['id' => 'mobilizacao-sgc', 'n' => '05', 't' => 'SGC Vale'],
        ];
    @endphp

    <form method="POST" action="{{ route('rh.efetivo.update', $colaborador) }}" enctype="multipart/form-data" id="form-editar-colaborador">
        @csrf
        @method('PUT')

        <header class="relative mb-8 overflow-hidden rounded-3xl border border-brand-burgundy/15 bg-white shadow-xl shadow-brand-burgundy/10 ring-1 ring-brand-burgundy/10">
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36] px-6 py-8 sm:px-8">
                <div class="pointer-events-none absolute -right-16 -top-10 h-64 w-64 rounded-full bg-white/[0.08] blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-40 w-80 rounded-full bg-black/15 blur-2xl"></div>

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                    <a href="#dados-pessoais" class="relative shrink-0" title="Ir para foto de perfil">
                        @if (filled($colaborador->foto_path))
                            <img src="{{ $colaborador->urlFotoPerfil() }}" alt="" class="h-24 w-24 rounded-full object-cover shadow-2xl shadow-black/30 ring-4 ring-white/90 sm:h-28 sm:w-28">
                        @else
                            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-white/25 to-white/5 text-3xl font-bold text-white shadow-2xl ring-4 ring-white/90 sm:h-28 sm:w-28 sm:text-4xl">
                                {{ $iniciais ?: mb_strtoupper(mb_substr($colaborador->nome, 0, 1)) }}
                            </div>
                        @endif
                    </a>
                    <div class="min-w-0 flex-1">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-bold text-brand-burgundy-soft backdrop-blur-sm">
                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                            Atualização cadastral
                        </span>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold {{ $statusUiHero[$st] ?? $statusUiHero['ativo'] }}">
                                <span class="h-2 w-2 rounded-full bg-current opacity-80"></span>
                                {{ $statusLabel[$st] ?? $st }}
                            </span>
                            @if ($colaborador->matricula)
                                <span class="rounded-full border border-white/25 bg-white/10 px-3 py-1 font-mono text-xs font-bold text-white">Mat. {{ $colaborador->matricula }}</span>
                            @endif
                        </div>
                        <h2 class="mt-3 text-2xl font-bold leading-tight tracking-tight text-white sm:text-3xl">{{ $colaborador->nome }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-brand-burgundy-soft/95">
                            Revise e salve os dados da ficha. Use o menu ao lado para ir direto a cada etapa.
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <div class="grid gap-8 xl:grid-cols-[252px_minmax(0,1fr)]">
            <aside class="hidden xl:block">
                <nav class="form-etapa-nav sticky top-24 overflow-hidden rounded-3xl border border-zinc-200/90 bg-white p-2 shadow-md shadow-zinc-200/40 ring-1 ring-zinc-100" aria-label="Etapas do formulário">
                    <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.05] to-transparent px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-brand-burgundy/80">Etapas</p>
                    </div>
                    <ul class="space-y-0.5 p-2">
                        @foreach ($navEtapas as $link)
                            <li>
                                <a href="#{{ $link['id'] }}" data-form-etapa-nav class="form-etapa-nav-link flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-zinc-600 transition hover:bg-brand-burgundy-soft hover:text-brand-burgundy">
                                    <span class="form-etapa-nav-num flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-xs font-bold text-zinc-500 transition">{{ $link['n'] }}</span>
                                    <span class="leading-tight">{{ $link['t'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <div class="min-w-0 pb-28 xl:pb-8">
                @include('rh.colaboradores._form')
            </div>
        </div>

        <div class="sticky bottom-0 z-20 -mx-4 border-t border-zinc-200/90 bg-white/95 px-4 py-4 shadow-[0_-8px_30px_rgba(67,16,32,0.08)] backdrop-blur-md sm:-mx-6 sm:px-6 xl:bottom-0">
            <div class="mx-auto flex max-w-5xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="hidden text-sm font-medium text-zinc-500 sm:block">
                    <i data-lucide="info" class="mr-1 inline h-4 w-4 text-brand-burgundy"></i>
                    Alterações entram em vigor após salvar o cadastro.
                </p>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('rh.efetivo.show', $colaborador) }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-5 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/25 transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Atualizar cadastro
                    </button>
                </div>
            </div>
        </div>
    </form>

    <nav class="form-etapa-nav-mobile fixed bottom-4 left-3 right-3 z-30 flex gap-1.5 overflow-x-auto rounded-2xl border border-zinc-200/90 bg-white/95 p-1.5 shadow-xl shadow-zinc-300/30 backdrop-blur-md xl:hidden" aria-label="Etapas">
        @foreach ($navEtapas as $link)
            <a href="#{{ $link['id'] }}" data-form-etapa-nav-mobile class="form-etapa-nav-mobile-link shrink-0 rounded-xl px-3 py-2 text-xs font-bold text-zinc-600 transition">{{ $link['n'] }}</a>
        @endforeach
    </nav>

    <style>
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
        .form-etapa-nav-link.is-active { background-color: rgb(247 233 238); color: #6f1731; }
        .form-etapa-nav-link.is-active .form-etapa-nav-num { background-color: #6f1731; color: #fff; }
        .form-etapa-nav-mobile-link.is-active { background-color: #6f1731; color: #fff; }
    </style>

    @push('scripts')
        <script>
            (function () {
                const navLinks = document.querySelectorAll('[data-form-etapa-nav], [data-form-etapa-nav-mobile]');
                const sections = [...document.querySelectorAll('.form-secao')];
                if (!sections.length || !navLinks.length) return;
                const setActive = (id) => {
                    navLinks.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === '#' + id));
                };
                const obs = new IntersectionObserver((entries) => {
                    const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (visible?.target?.id) setActive(visible.target.id);
                }, { rootMargin: '-25% 0px -55% 0px', threshold: [0, 0.15, 0.4] });
                sections.forEach((el) => obs.observe(el));
            })();
        </script>
    @endpush
@endsection
