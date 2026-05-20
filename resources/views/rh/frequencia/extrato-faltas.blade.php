@extends('layouts.app')

@section('title', 'Extrato de ausências - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Extrato de ausências')

@section('content')
    @php
        $abs = $extrato['resumo_exibicao'] ?? $absenteismo ?? $extrato['absenteismo'] ?? [];
        $absPeriodo = $extrato['absenteismo'] ?? $absenteismo ?? [];
        $natureza = $naturezaFiltro ?? $extrato['natureza_filtro'] ?? 'todas';
        $cardsDoFiltro = $natureza !== 'todas';
        $naturezaLabel = match ($natureza) {
            'justificada' => 'Somente justificadas',
            'injustificada' => 'Somente injustificadas',
            default => 'Todas as ausências',
        };
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('rh.frequencia.index', array_filter([
            'absenteismo_inicio' => $dataInicio,
            'absenteismo_fim' => $dataFim,
            'absenteismo_colaborador_id' => $colaboradorId,
            'absenteismo_calcular' => 1,
        ])) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-gray hover:bg-zinc-50">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Voltar à frequência
        </a>
    </div>

    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/60 p-5">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Absenteísmo gerencial</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">Extrato de ausências no período</h2>
            <p class="mt-1 text-sm text-brand-gray">
                <strong>{{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}</strong>
                a <strong>{{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</strong>
                · {{ $extrato['dias'] }} dia(s)
                · {{ $naturezaLabel }}
                @if ($colaboradorFiltro)
                    · <strong>{{ $colaboradorFiltro->nome }}</strong>@if ($colaboradorFiltro->matricula) ({{ $colaboradorFiltro->matricula }})@endif
                @else
                    · todo o efetivo ativo
                @endif
            </p>
            <p class="mt-2 text-xs leading-relaxed text-brand-gray">
                Horas de ausência ÷ horas previstas = taxa de absenteísmo. Atestados e abonos entram no indicador geral (impacto operacional);
                faltas injustificadas e atrasos alimentam também a visão de folha/disciplinar.
            </p>
        </div>

        <form method="GET" action="{{ route('rh.frequencia.extrato-faltas') }}" class="grid gap-3 border-b border-zinc-100 p-5 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
            <label class="space-y-1.5 sm:col-span-2">
                <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Colaborador</span>
                <select name="colaborador_id" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Todos com ausência no período</option>
                    @foreach ($colaboradoresAtivos as $c)
                        <option value="{{ $c->id }}" @selected($colaboradorId === $c->id)>{{ $c->nome }}@if ($c->matricula) ({{ $c->matricula }})@endif</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1.5">
                <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Natureza</span>
                <select name="natureza" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="todas" @selected($natureza === 'todas')>Todas</option>
                    <option value="justificada" @selected($natureza === 'justificada')>Justificadas</option>
                    <option value="injustificada" @selected($natureza === 'injustificada')>Injustificadas</option>
                </select>
            </label>
            <label class="space-y-1.5">
                <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Início</span>
                <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-1.5">
                <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Fim</span>
                <input type="date" name="data_fim" value="{{ $dataFim }}" class="h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <div class="sm:col-span-2 lg:col-span-5">
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white hover:bg-brand-burgundy-dark">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Atualizar extrato
                </button>
            </div>
        </form>

        <div class="grid gap-4 border-b border-zinc-100 p-5 sm:grid-cols-2 lg:grid-cols-4">
            @if ($cardsDoFiltro)
                <p class="sm:col-span-2 lg:col-span-4 text-[11px] font-semibold text-brand-burgundy">
                    Taxas alinhadas ao <strong>filtro atual</strong> (denominador: {{ number_format($absPeriodo['horas_previstas'] ?? 0, 1, ',', '.') }}h previstas no período).
                </p>
            @endif
            <div class="rounded-xl bg-amber-50 p-4">
                <p class="text-xs font-bold uppercase text-amber-700">Absenteísmo geral{{ $cardsDoFiltro ? ' (filtro)' : '' }}</p>
                <p class="mt-2 text-3xl font-black text-amber-700">{{ number_format($abs['taxa_geral'] ?? $abs['taxa'] ?? 0, 1, ',', '.') }}%</p>
                <p class="mt-1 text-[11px] text-amber-800">{{ number_format($abs['horas_ausencia_geral'] ?? 0, 1, ',', '.') }}h ÷ {{ number_format($absPeriodo['horas_previstas'] ?? 0, 1, ',', '.') }}h previstas</p>
            </div>
            <div class="rounded-xl bg-blue-50 p-4">
                <p class="text-xs font-bold uppercase text-blue-800">Justificado{{ $cardsDoFiltro ? ' (filtro)' : '' }}</p>
                <p class="mt-2 text-3xl font-black text-blue-900">{{ number_format($abs['taxa_justificada'] ?? 0, 1, ',', '.') }}%</p>
                <p class="mt-1 text-[11px] text-blue-800">{{ number_format($abs['horas_ausencia_justificada'] ?? 0, 1, ',', '.') }}h{{ $cardsDoFiltro ? ' no filtro' : ' no período' }}</p>
            </div>
            <div class="rounded-xl bg-red-50 p-4">
                <p class="text-xs font-bold uppercase text-red-700">Injustificado{{ $cardsDoFiltro ? ' (filtro)' : '' }}</p>
                <p class="mt-2 text-3xl font-black text-red-700">{{ number_format($abs['taxa_injustificada'] ?? 0, 1, ',', '.') }}%</p>
                <p class="mt-1 text-[11px] text-red-800">{{ number_format($abs['horas_ausencia_injustificada'] ?? 0, 1, ',', '.') }}h{{ $cardsDoFiltro ? ' no filtro' : ' no período' }}</p>
            </div>
            <div class="rounded-xl bg-brand-gray-soft p-4">
                <p class="text-xs font-bold uppercase text-brand-gray">Ocorrências no filtro</p>
                <p class="mt-2 text-3xl font-black text-brand-black">{{ $extrato['total_ocorrencias'] }}</p>
                <p class="mt-1 text-[11px] text-brand-gray">{{ number_format($extrato['total_horas_ausencia'], 1, ',', '.') }}h · {{ count($extrato['colaboradores']) }} colaborador(es)</p>
            </div>
        </div>
    </section>

    @if ($extrato['total_ocorrencias'] === 0)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-10 text-center">
            <i data-lucide="check-circle" class="mx-auto h-12 w-12 text-emerald-600"></i>
            <p class="mt-3 text-lg font-bold text-emerald-800">Nenhuma ausência no filtro selecionado</p>
            <p class="mt-1 text-sm text-emerald-700">
                Não há registros com horas de ausência entre {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}
                e {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }} para «{{ strtolower($naturezaLabel) }}».
            </p>
        </div>
    @else
        <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h3 class="text-sm font-bold text-brand-black">Resumo por colaborador</h3>
                <p class="mt-1 text-xs text-brand-gray">Horas de ausência no recorte do filtro (atestados, abonos, faltas, atrasos).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-[10px] font-bold uppercase tracking-wide text-brand-gray">
                        <tr>
                            <th class="px-4 py-3">Colaborador</th>
                            <th class="px-4 py-3">Matrícula</th>
                            <th class="px-4 py-3">Função</th>
                            <th class="px-4 py-3 text-center">Ocorrências</th>
                            <th class="px-4 py-3 text-right">h. ausência</th>
                            <th class="px-4 py-3 text-right">h. justif.</th>
                            <th class="px-4 py-3 text-right">h. injustif.</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($extrato['colaboradores'] as $item)
                            @php $c = $item['colaborador']; @endphp
                            <tr class="hover:bg-zinc-50/80">
                                <td class="px-4 py-3 font-semibold text-brand-black">{{ $c->nome }}</td>
                                <td class="px-4 py-3 text-brand-gray">{{ $c->matricula ?: '—' }}</td>
                                <td class="px-4 py-3 text-brand-gray">{{ $c->cargo ?: '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex min-w-[2rem] justify-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-bold text-zinc-700">{{ $item['qtd_ocorrencias'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-amber-800">{{ number_format($item['horas_ausencia'], 1, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-blue-800">{{ number_format($item['horas_justificada'], 1, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-700">{{ number_format($item['horas_injustificada'], 1, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('rh.frequencia.apuracao.index', ['colaborador_id' => $c->id, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim]) }}" class="text-xs font-semibold text-brand-burgundy hover:underline">Apuração</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-100 text-xs font-bold">
                        <tr>
                            <td class="px-4 py-3" colspan="3">TOTAL (filtro)</td>
                            <td class="px-4 py-3 text-center">{{ $extrato['total_ocorrencias'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-amber-800">{{ number_format($extrato['total_horas_ausencia'], 1, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-blue-800">{{ number_format($extrato['total_horas_justificada'], 1, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-red-700">{{ number_format($extrato['total_horas_injustificada'], 1, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h3 class="text-sm font-bold text-brand-black">Detalhamento dia a dia</h3>
                <p class="mt-1 text-xs text-brand-gray">Cada linha representa um dia com horas de ausência computadas no absenteísmo.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-[10px] font-bold uppercase tracking-wide text-brand-gray">
                        <tr>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Dia</th>
                            <th class="px-4 py-3">Colaborador</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Natureza</th>
                            <th class="px-4 py-3 text-right">h. previstas</th>
                            <th class="px-4 py-3 text-right">h. ausência</th>
                            <th class="px-4 py-3">Origem</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($extrato['linhas'] as $linha)
                            <tr class="hover:bg-zinc-50/80 {{ $linha['natureza'] === 'injustificada' ? 'bg-red-50/20' : 'bg-blue-50/10' }}">
                                <td class="px-4 py-2.5 font-semibold text-brand-black">{{ $linha['data_fmt'] }}</td>
                                <td class="px-4 py-2.5 text-brand-gray">{{ $linha['dia_semana'] }}</td>
                                <td class="px-4 py-2.5 text-brand-black">{{ $linha['colaborador']->nome }}</td>
                                <td class="px-4 py-2.5 text-brand-gray">{{ $linha['tipo_label'] }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($linha['natureza'] === 'injustificada')
                                        <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold uppercase text-red-700">Injustificada</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase text-blue-800">Justificada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-brand-gray">{{ number_format($linha['horas_previstas'], 1, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold tabular-nums {{ $linha['natureza'] === 'injustificada' ? 'text-red-700' : 'text-blue-800' }}">{{ number_format($linha['horas_ausencia'], 1, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-brand-gray">{{ $linha['origem'] }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ route('rh.frequencia.apuracao.index', ['colaborador_id' => $linha['colaborador']->id, 'data_inicio' => $linha['data'], 'data_fim' => $linha['data']]) }}" class="text-xs font-semibold text-brand-burgundy hover:underline">Abrir dia</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
