@extends('layouts.app')

@section('title', 'Painel de preenchimento - Recrutamento')
@section('eyebrow', 'RH')
@section('page-title', 'Painel de vagas e candidatos')

@section('actions')
    <a href="{{ route('rh.recrutamento.index', request()->only(['contrato', 'busca', 'ordem_nome'])) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar ao recrutamento
    </a>
@endsection

@section('content')
    @php
        $statusCandidatoClass = [
            'aprovado' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'pendente' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
        ];
    @endphp

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Todas as posições do contrato</h2>
                <p class="mt-1 text-sm text-brand-gray">Candidatos com ficha iniciada (nome, telefone ou data de aceite), fase atual do fluxo, e posições ainda sem cadastro.</p>
            </div>
            <form method="GET" action="{{ route('rh.recrutamento.painel-preenchimento') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end sm:flex-wrap">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Filtrar por vaga, gestor..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-72">
                </label>
                <label>
                    <select name="contrato" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-72">
                        <option value="">Selecione o centro de custo</option>
                        @foreach (($centrosDeCusto ?? collect()) as $centroDeCusto)
                            <option value="{{ $centroDeCusto }}" @selected(($contratoSelecionado ?? '') === $centroDeCusto)>{{ $centroDeCusto }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="min-w-[11rem]">
                    <span class="mb-1 block text-[11px] font-semibold text-brand-gray">Ordenar por nome</span>
                    <select name="ordem_nome" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-44">
                        <option value="padrao" @selected(($ordemNome ?? 'padrao') === 'padrao')>Padrão (vaga)</option>
                        <option value="az" @selected(($ordemNome ?? '') === 'az')>Nome A → Z</option>
                        <option value="za" @selected(($ordemNome ?? '') === 'za')>Nome Z → A</option>
                    </select>
                </label>
                <button type="submit" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 self-end rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy sm:self-end">
                    <i data-lucide="layout-list" class="h-4 w-4"></i>
                    Atualizar
                </button>
                @if (trim((string) request('contrato')) !== '')
                    <a href="{{ route('rh.recrutamento.painel-preenchimento.export-excel', request()->only(['contrato', 'busca', 'ordem_nome'])) }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 self-end rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-900 shadow-sm transition hover:border-emerald-400 hover:bg-emerald-100 sm:self-end">
                        <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                        Exportar Excel
                    </a>
                @endif
            </form>
        </div>

        @if (($contratoSelecionado ?? '') === '')
            <div class="p-8 text-center text-sm text-brand-gray">
                Selecione um <strong class="text-brand-black">centro de custo</strong> acima para carregar a lista de vagas e candidatos.
            </div>
        @else
            @php
                $t = $totaisPainel ?? ['fichas' => 0, 'posicoes' => 0, 'preenchidas' => 0, 'faltando' => 0, 'pct_preenchido' => 0];
            @endphp
            <div class="border-b border-zinc-200 bg-zinc-50/80 px-5 py-4 sm:px-6">
                <p class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Conferência rápida</p>
                <p class="mt-1 text-xs text-brand-gray">
                    <strong class="text-brand-black">Posição preenchida</strong> = há nome, telefone ou data de aceite na ficha.
                    <strong class="text-brand-black"> Posições</strong> = soma das vagas previstas (quantidade) de cada ficha no filtro.
                </p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Fichas de vaga</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-brand-black">{{ $t['fichas'] }}</p>
                        <p class="mt-0.5 text-xs text-brand-gray">Registros no filtro</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Total de posições</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-brand-black">{{ $t['posicoes'] }}</p>
                        <p class="mt-0.5 text-xs text-brand-gray">Preenchidas + faltando</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-800">Preenchidas</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-emerald-900">{{ $t['preenchidas'] }}</p>
                        <p class="mt-0.5 text-xs text-emerald-800/90">Com dado na ficha</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-amber-900/90">Faltam</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-amber-950">{{ $t['faltando'] }}</p>
                        <p class="mt-0.5 text-xs text-amber-900/80">Sem cadastro na posição</p>
                    </div>
                    <div class="rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft/60 px-4 py-3 shadow-sm sm:col-span-2 lg:col-span-1">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-brand-burgundy">Preenchimento</p>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/80">
                                <div class="h-full rounded-full bg-brand-burgundy transition-all" style="width: {{ $t['pct_preenchido'] }}%"></div>
                            </div>
                            <span class="text-lg font-black tabular-nums text-brand-burgundy">{{ $t['pct_preenchido'] }}%</span>
                        </div>
                        <p class="mt-2 text-xs text-brand-burgundy/90">{{ $t['preenchidas'] }} de {{ $t['posicoes'] }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-8 p-5 sm:p-6">
                <div>
                    <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Candidatos com dados preenchidos</p>
                            <p class="text-sm text-brand-gray">Nome, telefone e data de aceite (quando houver). Fase alinhada ao fluxo da ficha.</p>
                        </div>
                        <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">{{ count($preenchidos ?? []) }} registro(s)</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-zinc-200">
                        <table class="w-full min-w-[960px] text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-bold uppercase tracking-wide text-brand-gray">
                                <tr>
                                    <th class="px-4 py-3">Vaga</th>
                                    <th class="px-4 py-3">Pos.</th>
                                    <th class="px-4 py-3">
                                        <span class="block">Nome</span>
                                        @if (($ordemNome ?? 'padrao') === 'az')
                                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-wide text-brand-burgundy">A → Z</span>
                                        @elseif (($ordemNome ?? 'padrao') === 'za')
                                            <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-wide text-brand-burgundy">Z → A</span>
                                        @endif
                                    </th>
                                    <th class="px-4 py-3">Telefone</th>
                                    <th class="px-4 py-3">Data de aceite</th>
                                    <th class="px-4 py-3">Fase atual</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @forelse(($preenchidos ?? []) as $row)
                                    <tr class="hover:bg-brand-gray-soft/40">
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-brand-black">{{ $row['vaga_titulo'] ?: 'Sem título' }}</p>
                                            <p class="text-xs text-brand-gray">{{ $row['contrato'] ?: '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3 font-bold tabular-nums text-brand-black">{{ $row['posicao'] }}</td>
                                        <td class="px-4 py-3 font-medium text-brand-black">{{ $row['nome'] }}</td>
                                        <td class="px-4 py-3 text-brand-gray">{{ $row['telefone'] }}</td>
                                        <td class="px-4 py-3 tabular-nums text-brand-black">{{ $row['data_aceite_br'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-brand-black">{{ $row['fase'] }}</td>
                                        <td class="px-4 py-3">
                                            @php $st = $row['status_candidato'] ?? 'pendente'; @endphp
                                            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusCandidatoClass[$st] ?? $statusCandidatoClass['pendente'] }}">{{ ucfirst($st) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('rh.recrutamento.edit', $row['vaga_id']) }}" class="inline-flex h-9 items-center gap-1 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy">Abrir ficha</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-sm text-brand-gray">Nenhuma posição com dados de candidato neste contrato (ou filtro).</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-amber-700">Posições ainda sem preenchimento</p>
                            <p class="text-sm text-brand-gray">Agrupado por <strong class="text-brand-black">função</strong>. Os indicadores ficam sempre visíveis; abra o bloco para ver a lista detalhada.</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">{{ count($vagasAbertas ?? []) }} posição(ões) · {{ count($vagasAbertasPorFuncao ?? []) }} função(ões)</span>
                    </div>
                    <div class="space-y-3">
                        @forelse(($vagasAbertasPorFuncao ?? []) as $bloco)
                            @php $ind = $bloco['indicadores'] ?? []; @endphp
                            <div class="overflow-hidden rounded-xl border border-amber-200/90 bg-amber-50/25 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-200/70 bg-amber-50/60 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-base font-bold text-brand-black">{{ $bloco['titulo'] }}</p>
                                        <p class="text-xs text-amber-900/85">{{ $ind['a_preencher'] ?? 0 }} posição(ões) em aberto · {{ $ind['fichas'] ?? 0 }} ficha(s) de vaga</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-amber-200/90 px-3 py-1 text-xs font-black tabular-nums text-amber-950">{{ $ind['a_preencher'] ?? 0 }} abertas</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 border-b border-amber-200/50 p-3 sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="rounded-lg border border-amber-200 bg-white px-3 py-2.5 shadow-sm">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-amber-900/90">A preencher</p>
                                        <p class="mt-0.5 text-xl font-black tabular-nums text-amber-950">{{ $ind['a_preencher'] ?? 0 }}</p>
                                        <p class="mt-0.5 text-[10px] text-amber-900/80">Posições sem dados</p>
                                    </div>
                                    <div class="rounded-lg border border-zinc-200 bg-white px-3 py-2.5 shadow-sm">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gray">Fichas</p>
                                        <p class="mt-0.5 text-xl font-black tabular-nums text-brand-black">{{ $ind['fichas'] ?? 0 }}</p>
                                        <p class="mt-0.5 text-[10px] text-brand-gray">Registros de vaga</p>
                                    </div>
                                    <div class="rounded-lg border border-emerald-200/80 bg-emerald-50/50 px-3 py-2.5 shadow-sm">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-800">Já com dados</p>
                                        <p class="mt-0.5 text-xl font-black tabular-nums text-emerald-900">{{ $ind['com_dados'] ?? 0 }}</p>
                                        <p class="mt-0.5 text-[10px] text-emerald-800/90">Mesma função</p>
                                    </div>
                                    <div class="rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft/50 px-3 py-2.5 shadow-sm">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-burgundy">Preenchido</p>
                                        <p class="mt-0.5 text-xl font-black tabular-nums text-brand-burgundy">{{ $ind['pct'] ?? 0 }}%</p>
                                        <p class="mt-0.5 text-[10px] text-brand-burgundy/90">{{ $ind['com_dados'] ?? 0 }} / {{ $ind['total_posicoes'] ?? 0 }} posições</p>
                                    </div>
                                </div>
                                <details class="group bg-white/90">
                                    <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-sm font-semibold text-amber-950 transition hover:bg-amber-50/80 [&::-webkit-details-marker]:hidden">
                                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-amber-800 transition group-open:-rotate-180"></i>
                                        <span>Lista de posições ({{ count($bloco['linhas'] ?? []) }}) — clique para expandir</span>
                                    </summary>
                                    <div class="overflow-x-auto border-t border-amber-100/90">
                                        <table class="w-full min-w-[780px] text-left text-sm">
                                            <thead class="border-b border-amber-200 bg-amber-50/80 text-xs font-bold uppercase tracking-wide text-amber-900/80">
                                                <tr>
                                                    <th class="px-4 py-3">Vaga</th>
                                                    <th class="px-4 py-3">Contrato / local</th>
                                                    <th class="px-4 py-3">Posição vaga</th>
                                                    <th class="px-4 py-3 text-right">Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-amber-100/80">
                                                @foreach ($bloco['linhas'] ?? [] as $row)
                                                    <tr class="hover:bg-amber-50/40">
                                                        <td class="px-4 py-3 font-semibold text-brand-black">{{ $row['vaga_titulo'] ?: 'Sem título' }}</td>
                                                        <td class="px-4 py-3 text-brand-gray">
                                                            <span class="font-medium text-brand-black">{{ $row['contrato'] ?: '—' }}</span>
                                                            @if (! empty($row['local']))
                                                                <span class="text-xs"> · {{ $row['local'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 font-bold tabular-nums text-amber-900">{{ $row['posicao'] }}</td>
                                                        <td class="px-4 py-3 text-right">
                                                            <a href="{{ route('rh.recrutamento.edit', $row['vaga_id']) }}" class="inline-flex h-9 items-center gap-1 rounded-lg border border-amber-300 bg-white px-3 text-xs font-semibold text-amber-900 shadow-sm transition hover:bg-amber-50">Preencher ficha</a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </div>
                        @empty
                            <div class="rounded-xl border border-amber-200/80 bg-amber-50/50 px-4 py-8 text-center text-sm text-brand-gray">
                                Todas as posições já têm algum dado de candidato ou não há vagas no filtro.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
