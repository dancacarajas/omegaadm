@extends('layouts.app')

@section('title', 'Medição contratual - Omega286')
@section('eyebrow', 'Operação / Medição')
@section('page-title', 'Medição contratual')

@section('actions')
    <a href="{{ route('medicao.contratual.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo item
    </a>
@endsection

@section('content')
    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Receita prevista</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($indicadores['receita_prevista'], 2, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Receita medida</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($indicadores['receita_medida'], 2, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Glosas + não executado</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($indicadores['glosado'] + $indicadores['nao_executado'], 2, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Extras/adicionais</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($indicadores['hora_extra'] + $indicadores['adicionais'] + $indicadores['mobilizacao'] + $indicadores['nao_programado'], 2, ',', '.') }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Planilha financeira mensal</h2>
                <p class="mt-1 text-sm text-brand-gray">Diferencia previsto, medido, glosa, não executado e valores adicionais para decisão executiva.</p>
            </div>
            <form method="GET" class="grid gap-2 sm:grid-cols-[160px_1fr_auto] sm:items-center">
                <input type="month" name="mes" value="{{ $mes }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar item, contrato..." class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <button class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">Filtrar</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1480px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-4">Competência</th>
                        <th class="px-4 py-4">Item contratual</th>
                        <th class="px-4 py-4">Previsto</th>
                        <th class="px-4 py-4">Medido</th>
                        <th class="px-4 py-4">Diferença</th>
                        <th class="px-4 py-4">Desvio %</th>
                        <th class="px-4 py-4">Glosa/Não exec.</th>
                        <th class="px-4 py-4">Extras/Adic.</th>
                        <th class="px-4 py-4">Justificativa</th>
                        <th class="px-4 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($itens as $item)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $item->competencia?->format('m/Y') }}</td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $item->item_contratual }}</p>
                                <p class="text-xs text-brand-gray">{{ $item->descricao ?: '-' }} · {{ $item->contrato ?: 'Sem contrato' }}</p>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_previsto, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_medido, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 font-semibold {{ (float) $item->diferenca < 0 ? 'text-red-700' : 'text-emerald-700' }}">R$ {{ number_format((float) $item->diferenca, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ number_format((float) $item->desvio_percentual, 2, ',', '.') }}%</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_glosado + (float) $item->valor_nao_executado, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_hora_extra + (float) $item->valor_adicional + (float) $item->valor_mobilizacao + (float) $item->valor_nao_programado, 2, ',', '.') }}</td>
                            <td class="px-4 py-4">
                                <p class="max-w-xs truncate text-brand-gray">{{ $item->justificativa ?: '-' }}</p>
                                @if ($item->evidencia_path)
                                    <a href="{{ asset('storage/'.$item->evidencia_path) }}" target="_blank" class="text-xs font-bold text-brand-burgundy">Evidência</a>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('medicao.contratual.edit', $item) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">Editar</a>
                                    <form method="POST" action="{{ route('medicao.contratual.destroy', $item) }}" onsubmit="return confirm('Excluir item de medição contratual?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:bg-red-100">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-12 text-center text-sm text-brand-gray">Nenhum item de medição contratual no período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-5">
            {{ $itens->links() }}
        </div>
    </section>
@endsection
