@extends('layouts.app')

@section('title', 'Busca no sistema')
@section('page-title', 'Busca no sistema')
@section('eyebrow', 'Pesquisa')

@section('content')
    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5">
            <form method="GET" action="{{ route('busca.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="relative min-w-0 flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-burgundy"></i>
                    <input
                        type="search"
                        name="q"
                        value="{{ $termo }}"
                        autofocus
                        placeholder="Nome, matrícula, benefício, chamado…"
                        class="h-12 w-full rounded-2xl border border-zinc-200 bg-white pl-11 pr-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10"
                    >
                </label>
                <button type="submit" class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
            <p class="mt-3 text-xs text-brand-gray">Digite ao menos 2 caracteres. A busca respeita os módulos que você pode acessar.</p>
        </div>

        @if ($termo === '')
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="search" class="h-7 w-7"></i>
                </div>
                <p class="mt-4 text-lg font-bold text-brand-black">O que você procura?</p>
                <p class="mt-2 text-sm text-brand-gray">Colaboradores, benefícios, chamados de movimentação e mais.</p>
            </div>
        @elseif (mb_strlen($termo) < 2)
            <div class="px-6 py-12 text-center text-sm text-brand-gray">
                Informe pelo menos <strong class="text-brand-black">2 caracteres</strong> para pesquisar.
            </div>
        @elseif ($grupos === [])
            <div class="px-6 py-12 text-center">
                <p class="text-lg font-bold text-brand-black">Nenhum resultado para «{{ $termo }}»</p>
                <p class="mt-2 text-sm text-brand-gray">Tente outro termo ou verifique se você tem acesso ao módulo correspondente.</p>
            </div>
        @else
            <div class="divide-y divide-zinc-100 p-6">
                @foreach ($grupos as $grupo)
                    <section class="py-5 first:pt-0 last:pb-0">
                        <h2 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="{{ $grupo['icone'] }}" class="h-4 w-4 text-brand-burgundy"></i>
                            {{ $grupo['titulo'] }}
                        </h2>
                        <ul class="space-y-2">
                            @foreach ($grupo['itens'] as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200/80 bg-zinc-50/50 px-4 py-3 transition hover:border-brand-burgundy/30 hover:bg-brand-burgundy-soft/30">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-brand-black">{{ $item['titulo'] }}</p>
                                            @if ($item['subtitulo'] !== '')
                                                <p class="mt-0.5 truncate text-xs text-brand-gray">{{ $item['subtitulo'] }}</p>
                                            @endif
                                        </div>
                                        @if ($item['badge'])
                                            <span class="shrink-0 rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-gray ring-1 ring-zinc-200">{{ $item['badge'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </section>
@endsection
