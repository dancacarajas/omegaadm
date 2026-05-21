@extends('layouts.app')

@section('title', 'Novo colaborador - Omega286')
@section('eyebrow', 'RH / Efetivo')
@section('page-title', 'Novo colaborador')

@section('actions')
    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $navEtapas = [
            ['id' => 'dados-pessoais', 'n' => '01', 't' => 'Dados pessoais'],
            ['id' => 'documentos', 'n' => '02', 't' => 'Documentos'],
            ['id' => 'contrato', 'n' => '03', 't' => 'Contrato'],
            ['id' => 'admissao', 'n' => '04', 't' => 'Admissão'],
            ['id' => 'mobilizacao-sgc', 'n' => '05', 't' => 'SGC Vale'],
        ];
    @endphp

    <form method="POST" action="{{ route('rh.efetivo.store') }}" enctype="multipart/form-data">
        @csrf

        <header class="relative mb-8 overflow-hidden rounded-3xl border border-brand-burgundy/15 bg-white shadow-xl shadow-brand-burgundy/10 ring-1 ring-brand-burgundy/10">
            <div class="relative overflow-hidden bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36] px-6 py-8 sm:px-8">
                <div class="pointer-events-none absolute -right-16 -top-10 h-64 w-64 rounded-full bg-white/[0.08] blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-40 w-80 rounded-full bg-black/15 blur-2xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-bold text-brand-burgundy-soft backdrop-blur-sm">
                            <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                            Admissão de colaborador
                        </span>
                        <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">Nova ficha do efetivo</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-brand-burgundy-soft/95">
                            Preencha identificação, documentos, contrato e mobilização SGC na ordem das etapas.
                        </p>
                    </div>
                    <div class="hidden h-20 w-20 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 backdrop-blur-sm sm:flex">
                        <i data-lucide="clipboard-list" class="h-10 w-10 text-white/90"></i>
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

        <div class="sticky bottom-0 z-20 -mx-4 border-t border-zinc-200/90 bg-white/95 px-4 py-4 shadow-[0_-8px_30px_rgba(67,16,32,0.08)] backdrop-blur-md sm:-mx-6 sm:px-6">
            <div class="mx-auto flex max-w-5xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-zinc-500">
                    Os dados podem ser ajustados depois na ficha do colaborador.
                </p>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-5 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/25 transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Salvar colaborador
                    </button>
                </div>
            </div>
        </div>
    </form>

    <nav class="form-etapa-nav-mobile fixed bottom-4 left-3 right-3 z-30 flex gap-1.5 overflow-x-auto rounded-2xl border border-zinc-200/90 bg-white/95 p-1.5 shadow-xl backdrop-blur-md xl:hidden" aria-label="Etapas">
        @foreach ($navEtapas as $link)
            <a href="#{{ $link['id'] }}" data-form-etapa-nav-mobile class="form-etapa-nav-mobile-link shrink-0 rounded-xl px-3 py-2 text-xs font-bold text-zinc-600">{{ $link['n'] }}</a>
        @endforeach
    </nav>

    <style>
        html { scroll-behavior: smooth; }
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
                const setActive = (id) => navLinks.forEach((a) => a.classList.toggle('is-active', a.getAttribute('href') === '#' + id));
                const obs = new IntersectionObserver((entries) => {
                    const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (visible?.target?.id) setActive(visible.target.id);
                }, { rootMargin: '-25% 0px -55% 0px', threshold: [0, 0.15, 0.4] });
                sections.forEach((el) => obs.observe(el));
            })();
        </script>
    @endpush
@endsection
