@extends('layouts.app')

@section('title', 'Movimentações de efetivo - Omega286')
@section('eyebrow', 'RH / Efetivo')
@section('page-title', 'Movimentações de efetivo')

@section('actions')
    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar ao efetivo
    </a>
@endsection

@section('content')
    @php
        $filtrosAtivos = filled($busca) || filled($tipoFiltro);
        $totalPagina = $movimentacoes->count();
    @endphp

    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-900 shadow-sm">
            <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Hero --}}
    <section class="relative mb-6 overflow-hidden rounded-3xl border border-brand-burgundy/20 bg-brand-burgundy-dark shadow-lg shadow-brand-burgundy/15">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36]"></div>
        <div class="pointer-events-none absolute -right-10 top-0 h-56 w-56 rounded-full bg-white/[0.07] blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-72 rounded-full bg-black/10 blur-2xl"></div>
        <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between sm:p-8">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-brand-burgundy-soft backdrop-blur-sm">
                    <i data-lucide="git-branch" class="h-3.5 w-3.5 text-white/90"></i>
                    Histórico do efetivo
                </span>
                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">Movimentações</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-brand-burgundy-soft/90">
                    Desligamentos, transferências, afastamentos INSS, alterações de cargo e salário — consulte e abra a ficha do colaborador.
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3 sm:justify-end">
                <div class="min-w-[5.5rem] rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-center backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy-soft/80">No histórico</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">{{ $resumo['total_geral'] }}</p>
                </div>
                <div class="min-w-[5.5rem] rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-center backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy-soft/80">Em aberto</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">{{ $resumo['em_aberto'] }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- KPIs --}}
    <section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <article class="rounded-2xl border border-brand-burgundy/15 bg-white p-5 shadow-sm ring-1 ring-brand-burgundy/5 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="history" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Total registrado</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['total_geral'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-600">
                <i data-lucide="filter" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Resultado do filtro</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['total_filtrado'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                <i data-lucide="calendar-clock" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Períodos em aberto</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['em_aberto'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="heart-pulse" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">INSS em aberto</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['afastamento_inss'] }}</p>
        </article>
    </section>

    {{-- Listagem --}}
    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/25">
                        <i data-lucide="list" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold tracking-tight text-zinc-900">Registros de movimentação</h3>
                        <p class="text-xs text-zinc-500">
                            {{ $resumo['total_filtrado'] }} registro(s)
                            @if ($totalPagina > 0)
                                · {{ $totalPagina }} nesta página
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('rh.efetivo.movimentacoes.index') }}" class="mt-5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-2 sm:col-span-2 lg:col-span-5">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                            <i data-lucide="search" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                            Colaborador
                        </span>
                        <input
                            type="search"
                            name="busca"
                            id="busca"
                            value="{{ $busca }}"
                            placeholder="Nome ou matrícula…"
                            class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10"
                        >
                    </label>
                    <label class="space-y-2 lg:col-span-4">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                            <i data-lucide="git-branch" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                            Tipo
                        </span>
                        <select name="tipo" id="tipo" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="">Todos os tipos</option>
                            @foreach ($tipos as $key => $label)
                                <option value="{{ $key }}" @selected($tipoFiltro === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-3">
                        <button type="submit" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                            Filtrar
                        </button>
                        @if ($filtrosAtivos)
                            <a href="{{ route('rh.efetivo.movimentacoes.index') }}" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900" title="Limpar filtros">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </a>
                        @endif
                    </div>
                </div>

                @if ($filtrosAtivos)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Filtros:</span>
                        @if (filled($busca))
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-semibold text-brand-burgundy">{{ $busca }}</span>
                        @endif
                        @if (filled($tipoFiltro))
                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600">{{ $tipos[$tipoFiltro] ?? $tipoFiltro }}</span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] border-collapse text-left text-sm">
                <thead class="border-b border-zinc-200/80 bg-zinc-50/80 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-5 py-4">Período</th>
                        <th class="px-5 py-4">Colaborador</th>
                        <th class="px-5 py-4">Tipo</th>
                        <th class="px-5 py-4">Resumo</th>
                        <th class="px-5 py-4">Registrado por</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100/90">
                    @forelse ($movimentacoes as $mov)
                        <tr class="transition hover:bg-brand-burgundy/[0.02]">
                            <td class="whitespace-nowrap px-5 py-4">
                                <time class="text-sm font-bold text-zinc-900">{{ $mov->data_inicio->format('d/m/Y') }}</time>
                                @if ($mov->data_fim)
                                    <span class="block text-xs font-medium text-zinc-400">até {{ $mov->data_fim->format('d/m/Y') }}</span>
                                @else
                                    <span class="mt-1 inline-flex rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 ring-1 ring-amber-200/80">Em aberto</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if (filled($mov->colaborador->foto_path))
                                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full ring-2 ring-zinc-100">
                                            <img src="{{ $mov->colaborador->urlFotoPerfil() }}" alt="" class="h-full w-full object-cover">
                                        </div>
                                    @else
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-xs font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">
                                            {{ mb_strtoupper(mb_substr($mov->colaborador->nome, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('rh.efetivo.show', $mov->colaborador) }}" class="truncate font-semibold text-brand-burgundy hover:underline">{{ $mov->colaborador->nome }}</a>
                                        <p class="text-xs text-zinc-500">
                                            {{ $mov->colaborador->matricula ? 'Mat. '.$mov->colaborador->matricula : '—' }}
                                            @if ($mov->colaborador->cargo)
                                                · {{ $mov->colaborador->cargo }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">{{ $mov->tipoLabel() }}</span>
                            </td>
                            <td class="max-w-sm px-5 py-4 text-sm leading-relaxed text-zinc-600">{{ $mov->resumoAlteracao() }}</td>
                            <td class="px-5 py-4">
                                @if ($mov->registradoPor)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-zinc-500">
                                        <i data-lucide="user-check" class="h-3.5 w-3.5 text-brand-burgundy/60"></i>
                                        {{ $mov->registradoPor->name }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('rh.efetivo.movimentacoes.edit', [$mov->colaborador, $mov]) }}" class="inline-flex h-9 items-center gap-1 rounded-xl border border-zinc-200/80 bg-white px-3 text-xs font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy/30 hover:bg-brand-burgundy-soft">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Alterar
                                    </a>
                                    <a href="{{ route('rh.efetivo.show', $mov->colaborador) }}" class="inline-flex h-9 items-center gap-1 rounded-xl bg-brand-burgundy px-3 text-xs font-bold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        Ficha
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16">
                                <div class="mx-auto max-w-md text-center">
                                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="history" class="h-8 w-8"></i>
                                    </span>
                                    <p class="mt-4 text-base font-semibold text-zinc-700">Nenhuma movimentação encontrada</p>
                                    <p class="mt-2 text-sm text-zinc-500">Registre eventos pela ficha do colaborador ou ajuste os filtros acima.</p>
                                    <a href="{{ route('rh.efetivo.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-burgundy px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                        <i data-lucide="users" class="h-4 w-4"></i>
                                        Ir para o efetivo
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movimentacoes->hasPages())
            <div class="border-t border-zinc-100 bg-zinc-50/50 px-5 py-4">
                {{ $movimentacoes->links() }}
            </div>
        @endif
    </section>
@endsection
