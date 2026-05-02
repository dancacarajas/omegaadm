@extends('layouts.app')

@section('title', 'Patrimonial - Omega286')
@section('eyebrow', 'Gestão patrimonial')
@section('page-title', 'Patrimonial')

@section('actions')
    <a href="{{ route('patrimonial.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Cadastrar patrimônio
    </a>
@endsection

@section('content')
    @php
        $statusLabel = [
            'ativo' => 'Ativo',
            'em_uso' => 'Em uso',
            'em_manutencao' => 'Em manutenção',
            'reserva' => 'Reserva',
            'baixado' => 'Baixado',
        ];
        $statusClass = [
            'ativo' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'em_uso' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
            'em_manutencao' => 'border-amber-200 bg-amber-50 text-amber-700',
            'reserva' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
            'baixado' => 'border-red-200 bg-red-50 text-red-700',
        ];
        $condicaoLabel = [
            'novo' => 'Novo',
            'bom' => 'Bom',
            'regular' => 'Regular',
            'danificado' => 'Danificado',
            'inutilizado' => 'Inutilizado',
        ];
    @endphp

    <style>
        .patrimonial-kpis article { min-height: 112px; padding: 1rem; }
        .patrimonial-kpis article > div { width: 2rem; height: 2rem; }
        .patrimonial-kpis article > div svg { width: 1rem; height: 1rem; }
        .patrimonial-kpis article p:nth-of-type(1) { margin-top: .75rem; font-size: .75rem; line-height: 1rem; }
        .patrimonial-kpis article p:nth-of-type(2) { font-size: 1.5rem; line-height: 2rem; }
        .patrimonial-kpis article:last-child p:nth-of-type(2) { font-size: 1.25rem; line-height: 1.75rem; }
        @media (min-width: 1024px) {
            .patrimonial-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)) !important; }
        }
    </style>

    <section class="patrimonial-kpis mb-5 grid gap-3 sm:grid-cols-2">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="boxes" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Itens no inventário</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['total'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <i data-lucide="check-circle-2" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Ativos / em uso</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['ativos'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                <i data-lucide="wrench" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Em manutenção</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['manutencao'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-700">
                <i data-lucide="calendar-clock" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Conferência pendente</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['pendentes_conferencia'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-brand-gray p-5 text-white shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15">
                <i data-lucide="badge-dollar-sign" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-white/80">Valor inventariado</p>
            <p class="mt-1 text-2xl font-bold">R$ {{ number_format($indicadores['valor_total'], 2, ',', '.') }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Inventário patrimonial</h2>
                <p class="mt-1 text-sm text-brand-gray">Controle equipamentos, TAG patrimonial, contrato, responsável, localização e situação.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por TAG, equipamento, contrato..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-96">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="w-[300px] px-5 py-4">Equipamento</th>
                        <th class="w-[170px] px-4 py-4">TAG patrimonial</th>
                        <th class="w-[180px] px-4 py-4">Contrato</th>
                        <th class="w-[230px] px-4 py-4">Responsável / local</th>
                        <th class="w-[170px] px-4 py-4">Status</th>
                        <th class="w-[150px] px-4 py-4">Próx. conferência</th>
                        <th class="w-[190px] px-4 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($patrimonios as $patrimonio)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="package-check" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $patrimonio->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $patrimonio->categoria ?: 'Categoria não informada' }}{{ $patrimonio->modelo ? ' · '.$patrimonio->modelo : '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $patrimonio->tag_patrimonial }}</p>
                                <p class="text-xs text-brand-gray">Série: {{ $patrimonio->numero_serie ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $patrimonio->contrato ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ $patrimonio->centro_custo ?: 'Centro de custo não informado' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $patrimonio->responsavel ?: 'Sem responsável' }}</p>
                                <p class="text-xs text-brand-gray">{{ $patrimonio->localizacao ?: $patrimonio->setor ?: 'Local não informado' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass[$patrimonio->status] ?? $statusClass['ativo'] }}">
                                    {{ $statusLabel[$patrimonio->status] ?? $patrimonio->status }}
                                </span>
                                <p class="mt-1 text-xs text-brand-gray">Condição: {{ $condicaoLabel[$patrimonio->condicao] ?? $patrimonio->condicao }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ optional($patrimonio->proxima_conferencia)->format('d/m/Y') ?: '-' }}</p>
                                @if ($patrimonio->proxima_conferencia && $patrimonio->proxima_conferencia->isPast() && $patrimonio->status !== 'baixado')
                                    <p class="text-xs font-semibold text-red-700">Pendente</p>
                                @else
                                    <p class="text-xs text-brand-gray">Em dia</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('patrimonial.show', $patrimonio) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('patrimonial.edit', $patrimonio) }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('patrimonial.destroy', $patrimonio) }}" onsubmit="return confirm('Deseja realmente excluir este patrimônio?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-100">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="warehouse" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum patrimônio cadastrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Cadastre o primeiro equipamento para iniciar o inventário.</p>
                                <a href="{{ route('patrimonial.create') }}" class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Cadastrar patrimônio
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $patrimonios->links() }}
        </div>
    </section>
@endsection
