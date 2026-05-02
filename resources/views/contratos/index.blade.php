@extends('layouts.app')

@section('title', 'Contrato - Omega286')
@section('eyebrow', 'Gestão contratual')
@section('page-title', 'Contrato')

@section('actions')
    <a href="{{ route('contratos.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo contrato
    </a>
@endsection

@section('content')
    @php
        $statusLabel = [
            'ativo' => 'Ativo',
            'em_analise' => 'Em análise',
            'suspenso' => 'Suspenso',
            'encerrado' => 'Encerrado',
            'cancelado' => 'Cancelado',
            'vencido' => 'Vencido',
        ];
        $statusClass = [
            'ativo' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'em_analise' => 'border-amber-200 bg-amber-50 text-amber-700',
            'suspenso' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
            'encerrado' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
            'cancelado' => 'border-red-200 bg-red-50 text-red-700',
            'vencido' => 'border-red-200 bg-red-50 text-red-700',
        ];
    @endphp

    <section class="mb-5 grid gap-3 md:grid-cols-5">
        @foreach ([
            ['Ativos', $indicadores['ativos'], 'file-check-2', 'bg-emerald-50 text-emerald-700'],
            ['Em análise', $indicadores['em_analise'], 'search-check', 'bg-amber-50 text-amber-700'],
            ['Vencendo', $indicadores['vencendo'], 'calendar-clock', 'bg-red-50 text-red-700'],
            ['Total', $indicadores['total'], 'files', 'bg-brand-burgundy-soft text-brand-burgundy'],
            ['Valor contratado', 'R$ '.number_format($indicadores['valor_total'], 2, ',', '.'), 'landmark', 'bg-brand-gray text-white'],
        ] as [$label, $value, $icon, $tone])
            <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $tone }}">
                    <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                </span>
                <p class="mt-3 text-xs font-bold text-brand-gray">{{ $label }}</p>
                <p class="mt-1 text-2xl font-black text-brand-black">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Contratos cadastrados</h2>
                <p class="mt-1 text-sm text-brand-gray">Controle vigência, valor, responsáveis, status e pontos de atenção contratuais.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por número, cliente, gestor..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-96">
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
                        <th class="w-[320px] px-5 py-4">Contrato</th>
                        <th class="w-[220px] px-4 py-4">Cliente / contratada</th>
                        <th class="w-[180px] px-4 py-4">Vigência</th>
                        <th class="w-[180px] px-4 py-4">Valor</th>
                        <th class="w-[200px] px-4 py-4">Responsável</th>
                        <th class="w-[150px] px-4 py-4">Status</th>
                        <th class="w-[180px] px-4 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($contratos as $contrato)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="file-text" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $contrato->nome }}</p>
                                        <p class="text-xs text-brand-gray">Nº {{ $contrato->numero }}{{ $contrato->tipo ? ' · '.$contrato->tipo : '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $contrato->cliente ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ $contrato->contratada ?: 'Contratada não informada' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ optional($contrato->data_inicio)->format('d/m/Y') ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">até {{ optional($contrato->data_fim)->format('d/m/Y') ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">
                                {{ $contrato->valor ? 'R$ '.number_format((float) $contrato->valor, 2, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $contrato->gestor ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">Fiscal: {{ $contrato->fiscal ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass[$contrato->status] ?? $statusClass['ativo'] }}">
                                    {{ $statusLabel[$contrato->status] ?? $contrato->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('contratos.show', $contrato) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('contratos.edit', $contrato) }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="file-text" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum contrato cadastrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Cadastre o primeiro contrato para iniciar o controle contratual.</p>
                                <a href="{{ route('contratos.create') }}" class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Novo contrato
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $contratos->links() }}
        </div>
    </section>
@endsection
