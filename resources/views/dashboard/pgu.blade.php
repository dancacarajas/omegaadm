@extends($layout ?? 'layouts.app')

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

        <section
            class="rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm"
            x-show="!loading && !error && data?.summary?.kpis_mao_de_obra_itens"
        >
            <h2 class="text-base font-semibold text-pgu-ink">Indicadores (soma linha a linha, só itens)</h2>
            <p class="mt-1 text-sm text-pgu-muted">Pré-PGU = mobilizados · PGU = necessidade · Pendência = PGU − Pré · Coberto = min(Pré, PGU). O saldo <span class="font-medium text-pgu-ink">ΣPGU − ΣPré</span> no total geral pode compensar funções entre si; use estes números para pendência real.</p>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                    <dt class="text-xs font-medium text-pgu-muted">Vagas PGU previstas</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-pgu-ink" x-text="data?.summary?.kpis_mao_de_obra_itens?.vagas_pgu_previstas ?? '—'"></dd>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 px-4 py-3">
                    <dt class="text-xs font-medium text-emerald-900/80">Concluídas (coberto)</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-emerald-900" x-text="data?.summary?.kpis_mao_de_obra_itens?.vagas_concluidas_no_pgu ?? '—'"></dd>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50/50 px-4 py-3">
                    <dt class="text-xs font-medium text-amber-950/80">Pendentes por função</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-amber-950" x-text="data?.summary?.kpis_mao_de_obra_itens?.vagas_pendentes_por_funcao ?? '—'"></dd>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-pgu-muted">Pré sem PGU informado</dt>
                    <dd class="mt-1 text-2xl font-bold tabular-nums text-pgu-ink" x-text="data?.summary?.kpis_mao_de_obra_itens?.vagas_pre_sem_pgu_informado ?? '—'"></dd>
                </div>
            </dl>
            <div class="mt-4 overflow-x-auto rounded-xl border border-pgu-border">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-pgu-muted">
                        <tr>
                            <th class="px-4 py-2">Grupo</th>
                            <th class="px-4 py-2 text-right">PGU</th>
                            <th class="px-4 py-2 text-right">Concluídas</th>
                            <th class="px-4 py-2 text-right">Pendentes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-pgu-border">
                        <template x-for="g in (data?.summary?.kpis_mao_de_obra_itens?.por_grupo || [])" :key="g.grupo">
                            <tr>
                                <td class="px-4 py-2 font-medium text-pgu-ink" x-text="g.grupo"></td>
                                <td class="px-4 py-2 text-right tabular-nums" x-text="g.pgu"></td>
                                <td class="px-4 py-2 text-right tabular-nums text-emerald-800" x-text="g.concluidas"></td>
                                <td class="px-4 py-2 text-right tabular-nums text-amber-950" x-text="g.pendentes"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 max-w-full flex-1 space-y-3">
                    <div>
                        <h2 class="text-base font-semibold text-pgu-ink">Quadro executivo de cobertura integral PGU</h2>
                        <p class="mt-1 text-sm text-pgu-muted">Função <strong class="font-medium text-pgu-ink">100%</strong> = mobilização (Pré-PGU) cobre ou supera a necessidade (PGU): sem pendência <span class="whitespace-nowrap">(PGU − Pré ≤ 0)</span> e PGU informado.</p>
                    </div>
                    <ul class="max-w-xl space-y-1.5 text-sm text-pgu-muted">
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span><strong class="font-medium text-pgu-ink">Donut</strong> — quantas funções já atingiram essa cobertura, em relação ao total monitorado.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span><strong class="font-medium text-pgu-ink">Selos</strong> — lista das funções sem falta de gente frente à PGU neste recorte.</span>
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
            <div class="grid gap-6 lg:grid-cols-[minmax(0,440px)_1fr] lg:items-start">
                <div class="min-w-0 w-full">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-pgu-muted">Funções com PGU integral</p>
                    <div id="chartFuncoes100Donut" class="h-[420px] w-full min-w-0 overflow-hidden"></div>
                </div>
                <div class="min-w-0">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-pgu-muted">Mapa de funções 100% liberadas</p>
                    <div
                        class="rounded-xl border border-dashed border-pgu-border bg-pgu-bg/40 p-4"
                        x-show="!loading && !error && (data?.funcoes_pgu_100?.length ?? 0) === 0"
                    >
                        <p class="text-sm text-pgu-muted">Nenhuma função atingiu <strong class="text-pgu-ink">100%</strong> neste recorte — todas têm falta de mobilização (PGU − Pré &gt; 0) ou PGU não informado com Pré &gt; 0.</p>
                    </div>
                    <ul
                        class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"
                        x-show="!loading && !error && (data?.funcoes_pgu_100?.length ?? 0) > 0"
                    >
                        <template x-for="row in (data?.funcoes_pgu_100 || [])" :key="(row.codigo || '') + '|' + row.funcao">
                            <li class="flex gap-3 rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3 shadow-sm">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white" aria-hidden="true">✓</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold leading-snug text-pgu-ink" x-text="row.funcao"></p>
                                    <p class="mt-0.5 text-xs text-pgu-muted"><span class="font-medium text-emerald-800">100%</span> · Pré cobre PGU</p>
                                    <p class="mt-1 text-xs text-pgu-muted" x-show="row.codigo" x-text="'Código ' + row.codigo"></p>
                                    <p class="mt-0.5 text-xs text-pgu-muted"><span x-text="row.completed"></span> cobertos min(Pré, PGU)</p>
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
                subtitle="Soma apenas das linhas-item (sem subtotais de grupo), alinhada ao cálculo dos indicadores PGU."
                chartId="chartMaoDeObra"
            />
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-pgu.chart-card
                title="Avanço geral PGU"
                subtitle="Média do % em que a mobilização (Pré-PGU) cobre a necessidade (PGU) por função."
                chartId="chartDonut"
            />
            <x-pgu.chart-card
                title="Onde estão concentradas as pendências PGU?"
                subtitle="Top funções por volume: PGU − Pré (falta mobilizar) ou Pré quando PGU não informado; máx. 5 + demais agrupadas."
                chartId="chartRanking"
            />
        </section>

        <section class="grid gap-6">
            <x-pgu.chart-card
                title="Concentração das pendências por função"
                subtitle="Pareto no mesmo conjunto do ranking (pendência real ou Pré sem PGU); eixo = vagas."
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
                        <li><strong class="text-pgu-ink">Pendências</strong> (PGU − Pré) e <strong class="text-pgu-ink">risco</strong> — quanto maior o número, mais “quente” a cor (pior).</li>
                        <li><strong class="text-pgu-ink">Avanço</strong> (Pré/PGU) — <strong>% baixo</strong> fica mais quente (pior); <strong>% alto</strong> fica mais verde (melhor).</li>
                    </ul>
                </x-slot>
            </x-pgu.chart-card>
        </section>

        <section class="grid gap-6">
            <x-pgu.chart-card
                title="Mapa de concentração das pendências"
                subtitle="Área ∝ volume do ranking executivo (mesma regra do gráfico de barras horizontais)."
                chartId="chartTreemap"
            />
        </section>

        <section class="rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-base font-semibold text-pgu-ink">Funções concluídas × pendentes</h2>
                <p class="mt-1 text-sm text-pgu-muted">
                    <strong class="font-medium text-pgu-ink">Primeira coluna</strong>: funções sem pendência real (Pré cobre PGU) e com PGU informado.
                    <strong class="font-medium text-pgu-ink">Segunda coluna</strong>: falta mobilizar (PGU − Pré &gt; 0) ou PGU não informado.
                    Mesmo contrato e competência dos filtros acima; linhas <strong class="font-medium text-pgu-ink">Grupo</strong> do histograma não entram.
                </p>
            </div>
            @include('dashboard.partials.pgu-funcoes-duas-colunas')
        </section>
    </div>
</div>
@endsection
