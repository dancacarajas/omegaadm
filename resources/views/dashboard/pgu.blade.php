@extends('layouts.app')

@section('title', 'PGU Command Center - Omega286')
@section('eyebrow', 'Contrato')
@section('page-title', 'PGU — Visão executiva')

@section('content')
<div
    class="min-h-screen bg-pgu-bg px-2 py-2 sm:px-4"
    data-pgu-dashboard
    data-contrato="{{ $contratoDefault }}"
    data-competencia="{{ $competenciaDefault }}"
    data-data-limite="{{ $dataLimiteDefault }}"
    x-data="pguDashboard()"
    x-init="init()"
>
    <div class="mx-auto max-w-[1600px] space-y-6">
        <x-pgu.page-header
            title="PGU — Visão executiva"
            subtitle="Central de gráficos para reunião: concentração, prioridade e evolução do avanço PGU."
        >
            <select x-model="contrato" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
                @foreach ($contratos as $contrato)
                    <option value="{{ $contrato }}">{{ $contrato }}</option>
                @endforeach
            </select>
            <input type="month" x-model="competencia" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
            <input type="date" x-model="dataLimite" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
            <button @click="refresh()" class="inline-flex items-center gap-2 rounded-xl bg-pgu-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">
                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                Atualizar
            </button>
        </x-pgu.page-header>

        <div x-show="error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="error"></div>

        <section class="rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 max-w-full flex-1 space-y-3">
                    <div>
                        <h2 class="text-base font-semibold text-pgu-ink">Quadro executivo de cobertura integral PGU</h2>
                        <p class="mt-1 text-sm text-pgu-muted">PGU integral = pré-PGU totalmente coberto pela PGU nesta competência.</p>
                    </div>
                    <ul class="max-w-xl space-y-1.5 text-sm text-pgu-muted">
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span><strong class="font-medium text-pgu-ink">Donut</strong> — quantas funções já estão integralmente na PGU, em relação ao total monitorado.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span><strong class="font-medium text-pgu-ink">Selos</strong> — lista das funções liberadas (sem folga de PGU neste recorte).</span>
                        </li>
                    </ul>
                    <div
                        class="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        x-show="!loading && !error && data?.summary"
                    >
                        <span class="text-pgu-muted">Resumo</span>
                        <span class="hidden h-4 w-px bg-pgu-border sm:inline" aria-hidden="true"></span>
                        <span class="inline-flex items-baseline gap-1.5">
                            <span class="text-2xl font-bold tabular-nums text-pgu-ink" x-text="data?.funcoes_pgu_100?.length ?? 0"></span>
                            <span class="text-pgu-muted">em 100%</span>
                        </span>
                        <span class="text-pgu-muted">/</span>
                        <span class="inline-flex items-baseline gap-1.5">
                            <span class="text-lg font-semibold tabular-nums text-pgu-ink" x-text="data?.summary?.total_functions ?? 0"></span>
                            <span class="text-pgu-muted">monitoradas</span>
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-xl border border-pgu-border p-2 text-pgu-muted transition hover:border-pgu-primary hover:bg-teal-50 hover:text-pgu-primary"
                    title="Exportar donut como PNG"
                    aria-label="Exportar donut como PNG"
                    @click.stop="exportChartPng('chartFuncoes100Donut')"
                >
                    <i data-lucide="download" class="h-5 w-5"></i>
                </button>
            </div>
            <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
                <div class="min-w-0">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-pgu-muted">Funções com PGU integral</p>
                    <div id="chartFuncoes100Donut" class="mx-auto h-[360px] w-full min-w-0 max-w-[420px] overflow-hidden"></div>
                </div>
                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-pgu-muted">Mapa de funções 100% liberadas</p>
                    <div
                        class="rounded-xl border border-dashed border-pgu-border bg-pgu-bg/40 p-4"
                        x-show="!loading && !error && (data?.funcoes_pgu_100?.length ?? 0) === 0"
                    >
                        <p class="text-sm text-pgu-muted">Nenhuma função atingiu <strong class="text-pgu-ink">100%</strong> de avanço PGU neste recorte — todas seguem com folga ou pendência no pré-PGU.</p>
                    </div>
                    <ul
                        class="grid gap-3 sm:grid-cols-2"
                        x-show="!loading && !error && (data?.funcoes_pgu_100?.length ?? 0) > 0"
                    >
                        <template x-for="row in (data?.funcoes_pgu_100 || [])" :key="(row.codigo || '') + '|' + row.funcao">
                            <li class="flex gap-3 rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 shadow-sm">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white" aria-hidden="true">✓</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold leading-snug text-pgu-ink" x-text="row.funcao"></p>
                                    <p class="mt-0.5 text-xs text-pgu-muted"><span class="font-medium text-emerald-800">100% PGU</span> · PGU integral</p>
                                    <p class="mt-1 text-xs text-pgu-muted" x-show="row.codigo" x-text="'Código ' + row.codigo"></p>
                                    <p class="mt-0.5 text-xs text-pgu-muted"><span x-text="row.completed"></span> concluídos (volume PGU)</p>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </section>

        <section class="grid gap-6">
            <x-pgu.chart-card
                title="Mão de obra"
                subtitle="Totais por etapa — mesma soma do rodapé do histograma (todas as linhas: itens e grupos) para o contrato e competência selecionados."
                chartId="chartMaoDeObra"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-pgu.chart-card
                title="Avanço geral PGU"
                subtitle="Fase 2 (PGU) × Fase 1 (pré-PGU): média das funções no contrato e competência selecionados."
                chartId="chartDonut"
            />
            <x-pgu.chart-card
                title="Onde estão concentradas as pendências PGU?"
                subtitle="Top funções com maior impacto operacional (máx. 5 + demais agrupadas)"
                chartId="chartRanking"
            />
        </section>

        <section class="grid gap-6">
            <x-pgu.chart-card
                title="Concentração das pendências por função"
                subtitle="Pareto sobre o mesmo conjunto executivo — poucas categorias, leitura de diretoria"
                chartId="chartPareto"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-pgu.chart-card
                title="Evolução do avanço PGU"
                subtitle="Últimas competências (histograma mensal salvo)"
                chartId="chartTrend"
            >
                <x-slot name="footer">
                    <p class="text-xs leading-relaxed text-pgu-muted" x-show="data?.trend_notas" x-text="data?.trend_notas"></p>
                </x-slot>
            </x-pgu.chart-card>
            <x-pgu.chart-card title="Matriz de criticidade" chartId="chartHeatmap">
                <x-slot name="description">
                    <p class="leading-snug text-pgu-ink">Cada célula combina <strong>uma função</strong> com <strong>um indicador</strong>.</p>
                    <ul class="mt-2 list-disc space-y-1.5 pl-4 leading-relaxed">
                        <li><strong class="text-pgu-ink">Pendências</strong> e <strong class="text-pgu-ink">risco</strong> — quanto maior o número, mais “quente” a cor (pior).</li>
                        <li><strong class="text-pgu-ink">Avanço</strong> — o contrário: <strong>% baixo</strong> fica mais quente (pior); <strong>% alto</strong> fica mais verde (melhor).</li>
                    </ul>
                </x-slot>
            </x-pgu.chart-card>
        </section>

        <section class="grid gap-6">
            <x-pgu.chart-card
                title="Mapa de concentração das pendências"
                subtitle="Tamanho do bloco proporcional ao volume pendente (conjunto executivo)"
                chartId="chartTreemap"
            />
        </section>

        <section class="rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-base font-semibold text-pgu-ink">Funções concluídas × pendentes</h2>
                <p class="mt-1 text-sm text-pgu-muted">
                    <strong class="font-medium text-pgu-ink">Primeira coluna</strong>: funções sem pendência (PGU cobre o pré-PGU).
                    <strong class="font-medium text-pgu-ink">Segunda coluna</strong>: funções que ainda têm volume pendente.
                    Mesmo contrato e competência dos filtros acima; linhas <strong class="font-medium text-pgu-ink">Grupo</strong> do histograma não entram.
                </p>
            </div>
            @include('dashboard.partials.pgu-funcoes-duas-colunas')
        </section>

        <section class="rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-pgu-ink">Detalhe por função</h2>
                    <p class="mt-1 text-sm text-pgu-muted">Tabela completa com avanço, status e link ao histograma. Concluídos = PGU; pendentes = pré-PGU − PGU.</p>
                </div>
            </div>
            @include('dashboard.partials.pgu-progress-table')
        </section>
    </div>
</div>
@endsection
