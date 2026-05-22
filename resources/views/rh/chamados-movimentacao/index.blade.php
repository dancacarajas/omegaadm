@extends('layouts.app')

@section('title', 'Chamados de Movimentação - Omega286')
@section('eyebrow', 'RH / Movimentações')
@section('page-title', 'Chamados de movimentação')

@section('actions')
    <a href="{{ route('rh.chamados-movimentacao.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo chamado
    </a>
@endsection

@section('content')
    @php
        $filtrosAtivos = filled($busca) || filled($tipoFiltro) || filled($statusFiltro) || request()->boolean('atrasados');
        $totalFiltrado = $chamados->total();
        $totalPagina = $chamados->count();
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
                    <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-white/90"></i>
                    Fluxo com etapas e aprovações
                </span>
                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">Chamados de movimentação</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-brand-burgundy-soft/90">
                    Abra processos de desligamento, alteração contratual, promoção ou afastamento. O cadastro do colaborador só é atualizado na finalização do chamado.
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3 sm:justify-end">
                <div class="min-w-[5.5rem] rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-center backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy-soft/80">Em aberto</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">{{ $resumo['abertos'] }}</p>
                </div>
                <div class="min-w-[5.5rem] rounded-2xl border border-amber-300/30 bg-amber-500/15 px-5 py-3 text-center backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-100/90">Atrasados</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">{{ $resumo['atrasados'] }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- KPIs --}}
    <section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <article class="rounded-2xl border border-brand-burgundy/15 bg-white p-5 shadow-sm ring-1 ring-brand-burgundy/5 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="folder-open" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Abertos</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['abertos'] }}</p>
        </article>
        <article class="rounded-2xl border border-amber-200/80 bg-white p-5 shadow-sm ring-1 ring-amber-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                <i data-lucide="alarm-clock" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-amber-800">Atrasados</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-amber-900">{{ $resumo['atrasados'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-700">
                <i data-lucide="shield-check" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Aguard. aprovação</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['aguardando_aprovacao'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                <i data-lucide="calculator" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Aguard. DP</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['aguardando_dp'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                <i data-lucide="stethoscope" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Aguard. exame</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['aguardando_exame'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                <i data-lucide="circle-check" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Concluídos (mês)</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-zinc-900">{{ $resumo['concluidos_mes'] }}</p>
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
                        <h3 class="text-lg font-bold tracking-tight text-zinc-900">Chamados em andamento</h3>
                        <p class="text-xs text-zinc-500">
                            {{ $totalFiltrado }} chamado(s)
                            @if ($totalPagina > 0)
                                · {{ $totalPagina }} nesta página
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('rh.chamados-movimentacao.index') }}" class="mt-5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-2 sm:col-span-2 lg:col-span-4">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                            <i data-lucide="search" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                            Colaborador
                        </span>
                        <input
                            type="search"
                            name="busca"
                            value="{{ $busca }}"
                            placeholder="Nome ou matrícula…"
                            class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10"
                        >
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                            <i data-lucide="git-branch" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                            Tipo
                        </span>
                        <select name="tipo" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="">Todos os tipos</option>
                            @foreach ($tipos as $k => $l)
                                <option value="{{ $k }}" @selected($tipoFiltro === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                            <i data-lucide="activity" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                            Status
                        </span>
                        <select name="status" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="">Todos os status</option>
                            @foreach ($statuses as $k => $l)
                                <option value="{{ $k }}" @selected($statusFiltro === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex flex-col gap-3 sm:col-span-2 lg:col-span-2">
                        <label class="flex h-12 cursor-pointer items-center gap-2 rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-700 transition hover:bg-white">
                            <input type="checkbox" name="atrasados" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20" @checked(request()->boolean('atrasados'))>
                            Só atrasados
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                <i data-lucide="filter" class="h-4 w-4"></i>
                                Filtrar
                            </button>
                            @if ($filtrosAtivos)
                                <a href="{{ route('rh.chamados-movimentacao.index') }}" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900" title="Limpar filtros">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </a>
                            @endif
                        </div>
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
                        @if (filled($statusFiltro))
                            <span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-800">{{ $statuses[$statusFiltro] ?? $statusFiltro }}</span>
                        @endif
                        @if (request()->boolean('atrasados'))
                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Atrasados</span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] border-collapse text-left text-sm">
                <thead class="border-b border-zinc-200/80 bg-zinc-50/80 text-[11px] font-bold uppercase tracking-wider text-zinc-500">
                    <tr>
                        <th class="px-5 py-4">Chamado</th>
                        <th class="px-5 py-4">Colaborador</th>
                        <th class="px-5 py-4">Tipo</th>
                        <th class="px-5 py-4">Etapa atual</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Previsto</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100/90">
                    @forelse ($chamados as $c)
                        <tr class="transition hover:bg-brand-burgundy/[0.02]">
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="font-mono text-xs font-bold text-zinc-900">{{ $c->protocolo }}</span>
                                <span class="mt-1 block text-[10px] font-medium uppercase tracking-wider text-zinc-400">Protocolo</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-xs font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">
                                        {{ mb_strtoupper(mb_substr($c->colaborador->nome, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('rh.efetivo.show', $c->colaborador) }}" class="truncate font-semibold text-brand-burgundy hover:underline">{{ $c->colaborador->nome }}</a>
                                        <p class="text-xs text-zinc-500">
                                            {{ $c->colaborador->matricula ? 'Mat. '.$c->colaborador->matricula : '—' }}
                                            @if ($c->colaborador->cargo)
                                                · {{ $c->colaborador->cargo }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">{{ $c->tipoLabel() }}</span>
                            </td>
                            <td class="px-5 py-4 text-sm text-zinc-600">{{ $c->etapaAtual?->nome ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold text-zinc-700">{{ $c->statusLabel() }}</span>
                                @if ($c->isAtrasado())
                                    <span class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-amber-700">
                                        <i data-lucide="alert-triangle" class="h-3 w-3"></i>
                                        Atrasado
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <time class="text-sm font-bold text-zinc-900">{{ $c->data_prevista?->format('d/m/Y') ?? '—' }}</time>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('rh.chamados-movimentacao.show', $c) }}" class="inline-flex h-9 items-center gap-1 rounded-xl bg-brand-burgundy px-3 text-xs font-bold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    Ver chamado
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16">
                                <div class="mx-auto max-w-md text-center">
                                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="clipboard-list" class="h-8 w-8"></i>
                                    </span>
                                    <p class="mt-4 text-base font-semibold text-zinc-700">Nenhum chamado encontrado</p>
                                    <p class="mt-2 text-sm text-zinc-500">Abra um novo chamado ou ajuste os filtros acima.</p>
                                    <a href="{{ route('rh.chamados-movimentacao.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-burgundy px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                        Novo chamado
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($chamados->hasPages())
            <div class="border-t border-zinc-100 bg-zinc-50/50 px-5 py-4">
                {{ $chamados->links() }}
            </div>
        @endif
    </section>
@endsection
