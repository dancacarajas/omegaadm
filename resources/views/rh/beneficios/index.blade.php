@extends('layouts.app')

@section('title', 'Benefícios - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Benefícios')

@section('actions')
    <a href="{{ route('rh.beneficios.adesoes.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="clipboard-list" class="h-4 w-4 text-brand-burgundy"></i>
        Solicitações à Matriz
    </a>
    <a href="{{ route('rh.beneficios.extrato.config') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="file-text" class="h-4 w-4 text-brand-burgundy"></i>
        Extrato
    </a>
    <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
        <i data-lucide="calculator" class="h-4 w-4"></i>
        Gerar extrato
    </a>
    <a href="{{ route('rh.beneficios.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo benefício
    </a>
@endsection

@section('content')
    @include('rh.beneficios.partials._alerts')

    @include('rh.beneficios.partials._hero', [
        'badgeIcon' => 'hand-heart',
        'badgeText' => 'Módulo de benefícios',
        'title' => 'Gestão de benefícios',
        'description' => 'Cadastro, vínculos, extrato e adesão à Matriz (pedido → aviso de coleta → entrega ao colaborador).',
        'stats' => [
            ['label' => 'Cadastrados', 'value' => $resumoBeneficios['total']],
            ['label' => 'Ativos', 'value' => $resumoBeneficios['ativos']],
        ],
    ])

    <section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <article class="rounded-2xl border border-brand-burgundy/15 bg-white p-5 shadow-sm ring-1 ring-brand-burgundy/5 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="hand-heart" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Benefícios ativos</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoBeneficios['ativos'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-600">
                <i data-lucide="file-text" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">No extrato</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoBeneficios['no_extrato'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Adesão em andamento</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoBeneficios['adesao_andamento'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="calculator" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Nesta página</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $beneficios->count() }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-5">
            @include('rh.beneficios.partials._section_head', [
                'icon' => 'layout-list',
                'title' => 'Cadastro de benefícios',
                'subtitle' => $beneficios->total() . ' registro(s) · exibindo ' . $beneficios->count() . ' nesta página',
            ])

            <form method="GET" class="mt-5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-2 lg:col-span-10">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="search" class="h-3.5 w-3.5"></i>
                            Buscar
                        </span>
                        <input name="busca" value="{{ request('busca') }}" placeholder="Nome, tipo, fornecedor ou código…" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                    </label>
                    <div class="flex gap-2 lg:col-span-2">
                        <button type="submit" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Buscar
                        </button>
                        @if (request()->filled('busca'))
                            <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-brand-gray transition hover:border-zinc-300" title="Limpar">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Benefício</th>
                        <th class="px-5 py-4">Tipo</th>
                        <th class="px-5 py-4">Fornecedor</th>
                        <th class="px-5 py-4">Valor</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($beneficios as $beneficio)
                        <tr class="transition hover:bg-zinc-50/80">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="hand-heart" class="h-5 w-5"></i>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $beneficio->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $beneficio->codigo ?: 'Sem código' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-brand-gray">{{ $beneficio->tipo ?: '—' }}</td>
                            <td class="px-5 py-4 text-brand-gray">{{ $beneficio->fornecedor ?: '—' }}</td>
                            <td class="px-5 py-4 tabular-nums text-brand-gray">{{ $beneficio->valor ? 'R$ ' . number_format((float) $beneficio->valor, 2, ',', '.') : '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $beneficio->status === 'ativo' ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-zinc-100 text-zinc-600 ring-zinc-200' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $beneficio->status === 'ativo' ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                    {{ ucfirst($beneficio->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('rh.beneficios.show', $beneficio) }}" class="inline-flex h-9 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('rh.beneficios.edit', $beneficio) }}" class="inline-flex h-9 items-center gap-2 rounded-xl bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="hand-heart" class="h-8 w-8"></i>
                                </div>
                                <p class="mt-5 text-lg font-bold text-brand-black">Nenhum benefício cadastrado</p>
                                <p class="mt-2 text-sm text-brand-gray">Comece criando o primeiro benefício do RH.</p>
                                <a href="{{ route('rh.beneficios.create') }}" class="mt-6 inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Cadastrar benefício
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-100 bg-zinc-50/50 px-6 py-4">
            {{ $beneficios->links() }}
        </div>
    </section>
@endsection
