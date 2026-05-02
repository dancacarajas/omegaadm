@extends('layouts.app')

@section('title', 'Benefícios - Omega286')
@section('eyebrow', 'RH')
@section('page-title', 'Benefícios')

@section('actions')
    <a href="{{ route('rh.beneficios.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo benefício
    </a>
@endsection

@section('content')
    <section class="mb-5 grid gap-4 md:grid-cols-3">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="hand-heart" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Benefícios cadastrados</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $beneficios->total() }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-gray-soft text-brand-black">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Ativos na página</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $beneficios->where('status', 'ativo')->count() }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-brand-gray p-5 text-white shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/20">
                <i data-lucide="wallet-cards" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-white/80">Módulo</p>
            <p class="mt-1 text-3xl font-bold">Benefícios</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Cadastro de benefícios</h2>
                <p class="mt-1 text-sm text-brand-gray">Gerencie benefícios oferecidos aos colaboradores.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar benefício..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-80">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
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
                        <tr class="transition hover:bg-brand-gray-soft/60">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="hand-heart" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $beneficio->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $beneficio->codigo ?: 'Sem código' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-medium text-brand-gray">{{ $beneficio->tipo ?: '-' }}</td>
                            <td class="px-5 py-4 font-medium text-brand-gray">{{ $beneficio->fornecedor ?: '-' }}</td>
                            <td class="px-5 py-4 font-medium text-brand-gray">{{ $beneficio->valor ? 'R$ ' . number_format((float) $beneficio->valor, 2, ',', '.') : '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-bold text-brand-burgundy">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-burgundy"></span>
                                    {{ ucfirst($beneficio->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('rh.beneficios.show', $beneficio) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 px-3 text-xs font-semibold text-brand-black transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('rh.beneficios.edit', $beneficio) }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="hand-heart" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum benefício cadastrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Comece criando o primeiro benefício do RH.</p>
                                <a href="{{ route('rh.beneficios.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Cadastrar benefício
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $beneficios->links() }}
        </div>
    </section>
@endsection
