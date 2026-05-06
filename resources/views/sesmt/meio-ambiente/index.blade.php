@extends('layouts.app')

@section('title', 'Meio Ambiente — SSMA - Omega286')
@section('eyebrow', 'SSMA')
@section('page-title', 'Indicadores ambientais')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Controle de Conformidade
        </a>
        @if ($podeEditar)
            <a href="{{ route('sesmt.meio-ambiente.create', ['competencia' => $competenciaFiltro]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Novo mês
            </a>
        @endif
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">
            {{ session('success') }}
        </div>
    @endif

    <p class="mb-6 max-w-3xl text-sm text-brand-gray">
        Registro <strong class="text-brand-black">mensal por competência</strong> (um consolidado por mês), com campos para resíduos, consumos, ocorrências e evidências. O painel abaixo reflete a competência selecionada no filtro.
    </p>

    @php
        $p = $painel;
        $maxRes = max(0.0001, (float) collect($tendencia)->max('residuos'));
        $maxEvt = max(1, (int) collect($tendencia)->max(fn ($x) => max($x['ocorrencias'], $x['vazamentos'], $x['acoes'], $x['nc'])));
    @endphp

    <section class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Resíduos destinados corretamente</p>
            @if ($p['tem_registro'])
                <p class="mt-2 text-2xl font-bold text-brand-black">{{ $p['residuos_destinados_corretamente'] !== null ? number_format((float) $p['residuos_destinados_corretamente'], 3, ',', '.') : '—' }}</p>
                <p class="mt-1 text-xs text-brand-gray">Quantidade informada no mês (unidade no cadastro)</p>
            @else
                <p class="mt-2 text-sm font-semibold text-brand-gray">Sem registro nesta competência</p>
            @endif
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-900/80">Ocorrências ambientais</p>
            <p class="mt-2 text-3xl font-bold text-amber-950">{{ $p['tem_registro'] ? $p['ocorrencias_ambientais'] : '—' }}</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-red-900/80">Derramamentos / vazamentos</p>
            <p class="mt-2 text-3xl font-bold text-red-950">{{ $p['tem_registro'] ? $p['vazamentos_derramamentos'] : '—' }}</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-900/80">Ações ambientais concluídas</p>
            <p class="mt-2 text-3xl font-bold text-emerald-950">{{ $p['tem_registro'] ? $p['acoes_ambientais_concluidas'] : '—' }}</p>
        </article>
        <article class="rounded-xl border border-orange-200 bg-orange-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-orange-900/80">Não conformidades ambientais</p>
            <p class="mt-2 text-3xl font-bold text-orange-950">{{ $p['tem_registro'] ? $p['nao_conformidades_ambientais'] : '—' }}</p>
        </article>
    </section>

    <section class="mb-8 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Tendência mensal (últimos 12 meses)</h2>
        <p class="mt-1 text-xs text-brand-gray">Barras por competência: volume destinado (escala própria) e, na mesma coluna, ocorrências, vazamentos, ações concluídas e NC (escala comum).</p>
        <div class="mt-4 flex flex-wrap gap-4 text-xs">
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded bg-emerald-600"></span> Destinado corretamente</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded bg-amber-500"></span> Ocorrências</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded bg-red-500"></span> Vazamentos</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded bg-blue-500"></span> Ações</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded bg-orange-500"></span> NC</span>
        </div>
        <div class="mt-6 flex h-44 items-end gap-1 overflow-x-auto pb-6 sm:gap-2">
            @foreach ($tendencia as $ponto)
                @php
                    $hRes = round(((float) $ponto['residuos'] / $maxRes) * 100);
                    $hOc = round(($ponto['ocorrencias'] / $maxEvt) * 100);
                    $hVz = round(($ponto['vazamentos'] / $maxEvt) * 100);
                    $hAc = round(($ponto['acoes'] / $maxEvt) * 100);
                    $hNc = round(($ponto['nc'] / $maxEvt) * 100);
                @endphp
                <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1 border-r border-zinc-100 pr-1 last:border-0 sm:pr-2">
                    <div class="flex h-36 items-end gap-0.5 sm:gap-1">
                        <div class="w-1.5 rounded-t bg-emerald-600 sm:w-2" style="height: {{ max(4, $hRes) }}%" title="Destinado: {{ $ponto['residuos'] }}"></div>
                        <div class="w-1.5 rounded-t bg-amber-500 sm:w-2" style="height: {{ max(4, $hOc) }}%" title="Ocorrências: {{ $ponto['ocorrencias'] }}"></div>
                        <div class="w-1.5 rounded-t bg-red-500 sm:w-2" style="height: {{ max(4, $hVz) }}%" title="Vazamentos: {{ $ponto['vazamentos'] }}"></div>
                        <div class="w-1.5 rounded-t bg-blue-500 sm:w-2" style="height: {{ max(4, $hAc) }}%" title="Ações: {{ $ponto['acoes'] }}"></div>
                        <div class="w-1.5 rounded-t bg-orange-500 sm:w-2" style="height: {{ max(4, $hNc) }}%" title="NC: {{ $ponto['nc'] }}"></div>
                    </div>
                    <span class="mt-1 truncate text-center text-[9px] text-brand-gray sm:text-[10px]">{{ $ponto['rotulo'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Registros por competência</h2>
                <p class="mt-1 text-sm text-brand-gray">Filtre o mês ou visualize o histórico completo.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end">
                <label>
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-brand-gray">Competência</span>
                    <input type="month" name="competencia" value="{{ $competenciaFiltro }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="relative min-w-[200px]">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-[2.1rem] h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-brand-gray">Busca</span>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Texto nos campos descritivos..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-64">
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm">
                    <input type="hidden" name="historico" value="0">
                    <input type="checkbox" name="historico" value="1" @checked($historico) class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                    <span class="font-semibold text-brand-black">Histórico completo</span>
                </label>
                <button type="submit" class="h-11 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white hover:bg-brand-burgundy-dark">Aplicar</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Competência</th>
                        <th class="px-5 py-4">Destinado (qtd.)</th>
                        <th class="px-5 py-4">Ocorr.</th>
                        <th class="px-5 py-4">Vaz.</th>
                        <th class="px-5 py-4">Ações</th>
                        <th class="px-5 py-4">NC</th>
                        <th class="px-5 py-4">Água / energia</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($registros as $registro)
                        <tr class="transition hover:bg-brand-gray-soft/60">
                            <td class="px-5 py-4 font-bold text-brand-black">{{ $registro->competencia?->format('m/Y') }}</td>
                            <td class="px-5 py-4 text-brand-gray">{{ $registro->quantidade_residuos_destinados_corretamente !== null ? number_format((float) $registro->quantidade_residuos_destinados_corretamente, 3, ',', '.') : '—' }}</td>
                            <td class="px-5 py-4">{{ $registro->ocorrencias_ambientais }}</td>
                            <td class="px-5 py-4">{{ $registro->vazamentos_derramamentos }}</td>
                            <td class="px-5 py-4">{{ $registro->acoes_ambientais_concluidas }}</td>
                            <td class="px-5 py-4">{{ $registro->nao_conformidades_ambientais }}</td>
                            <td class="px-5 py-4 text-xs text-brand-gray">
                                @if ($registro->consumo_agua_m3 !== null)
                                    <span class="block">{{ number_format((float) $registro->consumo_agua_m3, 2, ',', '.') }} m³</span>
                                @endif
                                @if ($registro->consumo_energia_kwh !== null)
                                    <span class="block">{{ number_format((float) $registro->consumo_energia_kwh, 2, ',', '.') }} kWh</span>
                                @endif
                                @if ($registro->consumo_agua_m3 === null && $registro->consumo_energia_kwh === null)
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                @if ($podeEditar)
                                    <a href="{{ route('sesmt.meio-ambiente.edit', $registro) }}" class="text-sm font-bold text-brand-burgundy hover:underline">Editar</a>
                                    <form action="{{ route('sesmt.meio-ambiente.destroy', $registro) }}" method="POST" class="mt-2 inline" onsubmit="return confirm('Excluir este registro mensal ambiental?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">Excluir</button>
                                    </form>
                                @else
                                    <span class="text-xs text-brand-gray">Somente leitura</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-brand-gray">Nenhum registro encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($registros->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4">{{ $registros->links() }}</div>
        @endif
    </section>
@endsection
