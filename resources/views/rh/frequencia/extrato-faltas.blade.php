@extends('layouts.app')

@section('title', 'Extrato de Faltas - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Extrato de Faltas')

@section('content')
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
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Absenteísmo</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">Extrato de faltas injustificadas</h2>
            <p class="mt-1 text-sm text-brand-gray">
                Período: <strong>{{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}</strong>
                a <strong>{{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</strong>
                ({{ $extrato['dias'] }} dia(s) no intervalo)
                @if ($colaboradorFiltro)
                    · <strong>{{ $colaboradorFiltro->nome }}</strong>@if ($colaboradorFiltro->matricula) ({{ $colaboradorFiltro->matricula }})@endif
                @else
                    · todo o efetivo ativo
                @endif
            </p>
        </div>

        <form method="GET" action="{{ route('rh.frequencia.extrato-faltas') }}" class="grid gap-3 border-b border-zinc-100 p-5 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
            <label class="space-y-1.5 sm:col-span-2">
                <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Colaborador</span>
                <select name="colaborador_id" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Todos com falta no período</option>
                    @foreach ($colaboradoresAtivos as $c)
                        <option value="{{ $c->id }}" @selected($colaboradorId === $c->id)>{{ $c->nome }}@if ($c->matricula) ({{ $c->matricula }})@endif</option>
                    @endforeach
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
            <div class="sm:col-span-2 lg:col-span-4">
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white hover:bg-brand-burgundy-dark">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Atualizar extrato
                </button>
            </div>
        </form>

        <div class="grid gap-4 border-b border-zinc-100 p-5 sm:grid-cols-3">
            <div class="rounded-xl bg-red-50 p-4">
                <p class="text-xs font-bold uppercase text-red-700">Total de faltas</p>
                <p class="mt-2 text-3xl font-black text-red-700">{{ $extrato['total_faltas'] }}</p>
            </div>
            <div class="rounded-xl bg-brand-gray-soft p-4">
                <p class="text-xs font-bold uppercase text-brand-gray">Colaboradores com falta</p>
                <p class="mt-2 text-3xl font-black text-brand-black">{{ count($extrato['colaboradores']) }}</p>
            </div>
            <div class="rounded-xl bg-amber-50 p-4">
                <p class="text-xs font-bold uppercase text-amber-700">Média por colaborador</p>
                <p class="mt-2 text-3xl font-black text-amber-700">
                    @if (count($extrato['colaboradores']) > 0)
                        {{ number_format($extrato['total_faltas'] / count($extrato['colaboradores']), 1, ',', '.') }}
                    @else
                        0
                    @endif
                </p>
            </div>
        </div>
    </section>

    @if ($extrato['total_faltas'] === 0)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-10 text-center">
            <i data-lucide="check-circle" class="mx-auto h-12 w-12 text-emerald-600"></i>
            <p class="mt-3 text-lg font-bold text-emerald-800">Nenhuma falta injustificada no período</p>
            <p class="mt-1 text-sm text-emerald-700">Não há registros com status «falta» entre {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} e {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}.</p>
        </div>
    @else
        <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h3 class="text-sm font-bold text-brand-black">Resumo por colaborador</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-[10px] font-bold uppercase tracking-wide text-brand-gray">
                        <tr>
                            <th class="px-4 py-3">Colaborador</th>
                            <th class="px-4 py-3">Matrícula</th>
                            <th class="px-4 py-3">Função</th>
                            <th class="px-4 py-3 text-center">Qtd. faltas</th>
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
                                    <span class="inline-flex min-w-[2rem] justify-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-bold text-red-700">{{ $item['total_faltas'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('rh.frequencia.apuracao.index', ['colaborador_id' => $c->id, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim]) }}" class="text-xs font-semibold text-brand-burgundy hover:underline">Apuração</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-100 text-xs font-bold">
                        <tr>
                            <td class="px-4 py-3" colspan="3">TOTAL</td>
                            <td class="px-4 py-3 text-center text-red-700">{{ $extrato['total_faltas'] }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h3 class="text-sm font-bold text-brand-black">Detalhamento dia a dia</h3>
                <p class="mt-1 text-xs text-brand-gray">Cada linha é um dia com status «falta» (falta injustificada).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-[10px] font-bold uppercase tracking-wide text-brand-gray">
                        <tr>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Dia</th>
                            <th class="px-4 py-3">Colaborador</th>
                            <th class="px-4 py-3">Matrícula</th>
                            <th class="px-4 py-3">Origem</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($extrato['colaboradores'] as $item)
                            @foreach ($item['faltas'] as $falta)
                                <tr class="hover:bg-red-50/30">
                                    <td class="px-4 py-2.5 font-semibold text-red-700">{{ $falta['data_fmt'] }}</td>
                                    <td class="px-4 py-2.5 text-brand-gray">{{ $falta['dia_semana'] }}</td>
                                    <td class="px-4 py-2.5 text-brand-black">{{ $item['colaborador']->nome }}</td>
                                    <td class="px-4 py-2.5 text-brand-gray">{{ $item['colaborador']->matricula ?: '—' }}</td>
                                    <td class="px-4 py-2.5 text-brand-gray">{{ $falta['origem'] }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <a href="{{ route('rh.frequencia.apuracao.index', ['colaborador_id' => $item['colaborador']->id, 'data_inicio' => $falta['data'], 'data_fim' => $falta['data']]) }}" class="text-xs font-semibold text-brand-burgundy hover:underline">Abrir dia</a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
