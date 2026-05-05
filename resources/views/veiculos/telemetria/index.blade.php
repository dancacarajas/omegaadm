@extends('layouts.app')

@section('title', 'Telemetria - Veículos - Omega286')
@section('eyebrow', 'Operação')
@section('page-title', 'Telemetria da frota')

@section('actions')
    <a href="{{ route('veiculos.telemetria.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo registro
    </a>
@endsection

@section('content')
    @php
        $fmtMin = fn ($min) => sprintf('%dh %02dmin', intdiv((int) $min, 60), ((int) $min) % 60);
    @endphp

    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Quilometragem rodada</p>
            <p class="mt-2 text-3xl font-black text-brand-black">{{ number_format($indicadores['km_rodado'], 2, ',', '.') }} km</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Horas em operação</p>
            <p class="mt-2 text-3xl font-black text-brand-black">{{ $fmtMin($indicadores['horas_operacao_min']) }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Tempo ocioso</p>
            <p class="mt-2 text-3xl font-black text-brand-black">{{ $fmtMin($indicadores['tempo_ocioso_min']) }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Alertas/eventos</p>
            <p class="mt-2 text-3xl font-black text-brand-black">{{ $indicadores['alertas'] + $indicadores['eventos_criticos'] }}</p>
            <p class="mt-1 text-xs text-brand-gray">Excesso: {{ $indicadores['excesso_velocidade'] }} · Desvios: {{ $indicadores['desvios'] }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Planilha de telemetria</h2>
                <p class="mt-1 text-sm text-brand-gray">Dados mensais para gestão operacional, medição contratual e segurança da frota.</p>
            </div>
            <form method="GET" class="grid gap-2 sm:grid-cols-[150px_1fr_auto] sm:items-center">
                <input type="month" name="mes" value="{{ $mes }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar veículo, placa, motorista..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    Filtrar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1480px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-4">Data</th>
                        <th class="px-4 py-4">Veículo</th>
                        <th class="px-4 py-4">Placa/TAG</th>
                        <th class="px-4 py-4">Motorista</th>
                        <th class="px-4 py-4">KM rodado</th>
                        <th class="px-4 py-4">Operação</th>
                        <th class="px-4 py-4">Ocioso</th>
                        <th class="px-4 py-4">Desvio rota</th>
                        <th class="px-4 py-4">Segurança</th>
                        <th class="px-4 py-4">Alertas</th>
                        <th class="px-4 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($telemetrias as $r)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $r->data?->format('d/m/Y') }}</td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $r->veiculo }}</p>
                                <p class="text-xs text-brand-gray">{{ $r->contrato ?: 'Sem contrato' }}</p>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">{{ $r->placa_tag ?: '-' }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $r->motorista_responsavel ?: '-' }}</td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ number_format((float) $r->km_rodado, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $r->horas_operacao ?: '-' }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $r->tempo_ocioso ?: '-' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $r->desvio_rota ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                                    {{ $r->desvio_rota ? 'Sim' : 'Não' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">
                                <p>Vel. exc.: {{ $r->excesso_velocidade }}</p>
                                <p>Fren./Acel.: {{ $r->frenagens_bruscas }}/{{ $r->aceleracoes_bruscas }}</p>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">{{ $r->alertas_gerados }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('veiculos.telemetria.edit', $r) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('veiculos.telemetria.destroy', $r) }}" onsubmit="return confirm('Excluir este registro de telemetria?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-5 py-12 text-center text-sm text-brand-gray">Nenhum registro de telemetria no período selecionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $telemetrias->links() }}
        </div>
    </section>
@endsection
