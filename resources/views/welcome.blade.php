@extends('layouts.app')

@section('title', 'Painel Executivo - Omega286')
@section('eyebrow', 'Visão geral')
@section('page-title', 'Painel executivo')

@section('actions')
    <a href="{{ route('rdo.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="clipboard-plus" class="h-4 w-4"></i>
        Novo RDO
    </a>
@endsection

@section('content')
    @php
        $json = fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tones = [
            'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
            'red' => 'bg-red-50 text-red-700 border-red-100',
            'slate' => 'bg-brand-gray text-white border-brand-gray',
        ];
    @endphp

    <section class="mb-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Atualizado em {{ $atualizadoEm }}</p>
                <h2 class="mt-1 text-2xl font-black text-brand-black">Cenário estratégico da operação</h2>
                <p class="mt-1 max-w-3xl text-sm text-brand-gray">Indicadores consolidados para enxergar gargalos, atrasos e produtividade dos módulos em uma única visão executiva.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('rh.dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="briefcase-business" class="h-4 w-4"></i>
                    RH
                </a>
                <a href="{{ route('veiculos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="truck" class="h-4 w-4"></i>
                    Veículos
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl border {{ $tones[$kpi['tone']] ?? $tones['slate'] }}">
                        <i data-lucide="{{ $kpi['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <span class="rounded-full bg-brand-gray-soft px-3 py-1 text-xs font-bold text-brand-gray">Sistema</span>
                </div>
                <p class="mt-5 text-sm font-bold text-brand-gray">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-3xl font-black text-brand-black">{{ $kpi['value'] }}</p>
                <p class="mt-2 text-xs font-medium leading-5 text-brand-gray">{{ $kpi['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1.45fr_0.8fr]">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Performance por módulo</p>
                    <h3 class="mt-1 text-xl font-black text-brand-black">Concluídos, andamento e atrasados</h3>
                </div>
                <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">Visão executiva</span>
            </div>
            <div class="mt-5" data-apex-chart="#chart-modulos"></div>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Saúde geral</p>
            <h3 class="mt-1 text-xl font-black text-brand-black">Distribuição dos processos</h3>
            <div class="mt-4" data-apex-chart="#chart-saude"></div>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1fr_1fr]">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Tendência operacional</p>
            <h3 class="mt-1 text-xl font-black text-brand-black">RDOs x faltas nos últimos meses</h3>
            <div class="mt-4" data-apex-chart="#chart-tendencia"></div>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Gargalos</p>
            <h3 class="mt-1 text-xl font-black text-brand-black">Pendências por módulo</h3>
            <div class="mt-4" data-apex-chart="#chart-gargalos"></div>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Mapa de cobrança</p>
                    <h3 class="mt-1 text-xl font-black text-brand-black">Onde a diretoria deve atuar</h3>
                </div>
                <i data-lucide="radar" class="h-5 w-5 text-brand-burgundy"></i>
            </div>
            <div class="mt-5 divide-y divide-zinc-100">
                @forelse ($gargalos as $item)
                    <a href="{{ $item['rota'] }}" class="grid gap-3 py-4 transition hover:bg-brand-gray-soft/50 sm:grid-cols-[1fr_120px_120px] sm:items-center">
                        <div>
                            <p class="font-black text-brand-black">{{ $item['label'] }}</p>
                            <p class="mt-1 text-sm text-brand-gray">{{ $item['principal'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-brand-gray">Pendências</p>
                            <p class="mt-1 text-xl font-black text-brand-black">{{ $item['pendencias'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-brand-gray">Atrasos</p>
                            <p class="mt-1 text-xl font-black {{ $item['atrasados'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $item['atrasados'] }}</p>
                        </div>
                    </a>
                @empty
                    <p class="py-6 text-sm font-semibold text-brand-gray">Nenhum gargalo crítico identificado.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Frequência</p>
            <h3 class="mt-1 text-xl font-black text-brand-black">Top 5 faltas no mês</h3>
            <div class="mt-5 space-y-3">
                @forelse ($topFaltas as $item)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-4">
                        <div class="min-w-0">
                            <p class="truncate font-black text-brand-black">{{ $item['nome'] }}</p>
                            <p class="text-sm text-brand-gray">Ocorrências de falta</p>
                        </div>
                        <span class="rounded-full bg-red-50 px-3 py-1 text-sm font-black text-red-700">{{ $item['faltas'] }}</span>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-6 text-center text-sm font-semibold text-brand-gray">
                        Sem faltas registradas no mês atual.
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($modulos as $modulo)
            <a href="{{ $modulo['rota'] }}" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-burgundy/40 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">{{ $modulo['label'] }}</p>
                        <p class="mt-1 text-sm font-semibold text-brand-gray">{{ $modulo['principal'] }}</p>
                    </div>
                    <i data-lucide="arrow-up-right" class="h-5 w-5 text-brand-gray"></i>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="text-lg font-black text-emerald-700">{{ $modulo['concluidos'] }}</p>
                        <p class="text-[11px] font-bold uppercase text-emerald-700">OK</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3">
                        <p class="text-lg font-black text-amber-700">{{ $modulo['andamento'] }}</p>
                        <p class="text-[11px] font-bold uppercase text-amber-700">And.</p>
                    </div>
                    <div class="rounded-lg bg-red-50 p-3">
                        <p class="text-lg font-black text-red-700">{{ $modulo['atrasados'] }}</p>
                        <p class="text-[11px] font-bold uppercase text-red-700">Atr.</p>
                    </div>
                </div>
            </a>
        @endforeach
    </section>
@endsection

@push('scripts')
    <script type="application/json" id="chart-modulos">{!! $json($charts['modulos']) !!}</script>
    <script type="application/json" id="chart-saude">{!! $json($charts['saude']) !!}</script>
    <script type="application/json" id="chart-tendencia">{!! $json($charts['tendencia']) !!}</script>
    <script type="application/json" id="chart-gargalos">{!! $json($charts['gargalos']) !!}</script>
@endpush
