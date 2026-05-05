@extends('layouts.app')

@section('title', 'Fluxo Financeiro - Medição - Omega286')
@section('eyebrow', 'Operação / Medição')
@section('page-title', 'Fluxo Financeiro')

@section('content')
    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Leitura financeira mensal do contrato</h2>
            <p class="mt-1 text-sm text-brand-gray">Visão executiva para previsto, medido, pendências, glosas e impactos operacionais/financeiros.</p>
        </div>
        <form method="GET" class="grid gap-3 p-5 md:grid-cols-[150px_1fr_220px_auto] md:items-end">
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Mês</span>
                <input type="month" name="mes" value="{{ $mes }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                <select name="contrato" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Todos</option>
                    @foreach ($contratos as $c)
                        <option value="{{ $c }}" @selected($contrato === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Valor global do contrato</span>
                <input type="number" step="0.01" min="0" name="valor_global_contrato" value="{{ request('valor_global_contrato', $fluxo['valor_global_contrato']) }}" placeholder="Opcional" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <button class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                Atualizar visão
            </button>
        </form>
    </section>

    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Valor medido no mês</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($fluxo['valor_medido_mes'], 2, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Acumulado medido</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($fluxo['valor_acumulado_medido'], 2, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Saldo contratual</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($fluxo['saldo_contratual'], 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-brand-gray">Com base no valor global informado</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Não medidos + pendentes</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($fluxo['valores_nao_medidos'] + $fluxo['valores_pendentes'], 2, ',', '.') }}</p>
        </article>
    </section>

    <section class="mb-5 grid gap-4 md:grid-cols-3">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Impacto de glosas</p>
            <p class="mt-2 text-3xl font-black text-red-700">R$ {{ number_format($fluxo['impacto_glosas'], 2, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Indisponibilidade de equipamentos</p>
            <p class="mt-2 text-3xl font-black text-amber-700">R$ {{ number_format($fluxo['impacto_indisponibilidade_equip'], 2, ',', '.') }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Faltas/mobilizações/desmobilizações</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($fluxo['impacto_faltas_mobilizacao_desmobilizacao'], 2, ',', '.') }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 p-5">
            <h2 class="text-xl font-bold text-brand-black">Itens financeiros do mês</h2>
            <p class="mt-1 text-sm text-brand-gray">Leitura detalhada para apresentação e tomada de decisão.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-4">Item</th>
                        <th class="px-4 py-4">Previsto</th>
                        <th class="px-4 py-4">Medido</th>
                        <th class="px-4 py-4">Diferença</th>
                        <th class="px-4 py-4">Glosa</th>
                        <th class="px-4 py-4">Não executado</th>
                        <th class="px-4 py-4">Executado não medido</th>
                        <th class="px-4 py-4">Extras/Adicionais</th>
                        <th class="px-4 py-4">Justificativa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($resumoMes as $item)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $item->item_contratual }}</p>
                                <p class="text-xs text-brand-gray">{{ $item->descricao ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_previsto, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_medido, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 font-semibold {{ (float) $item->diferenca < 0 ? 'text-red-700' : 'text-emerald-700' }}">R$ {{ number_format((float) $item->diferenca, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_glosado, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_nao_executado, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_executado_nao_medido, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $item->valor_hora_extra + (float) $item->valor_adicional + (float) $item->valor_nao_programado + (float) $item->valor_mobilizacao, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray max-w-xs truncate">{{ $item->justificativa ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-sm text-brand-gray">Sem itens financeiros para o mês selecionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
