@extends($layout ?? 'layouts.app')

@section('title', 'PGU Command Center - Omega286')
@section('eyebrow', 'Contrato')
@section('page-title', 'Reporte Vale')

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
            title="Reporte Vale"
            subtitle="Central de gráficos para reunião: concentração, prioridade e evolução do avanço PGU."
        >
            <select x-model="contrato" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
                @foreach ($contratos as $contrato)
                    <option value="{{ $contrato }}">{{ $contrato }}</option>
                @endforeach
            </select>
            <input type="month" x-model="competencia" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
            <input type="date" x-model="dataLimite" class="rounded-xl border border-pgu-border bg-white px-4 py-2 text-sm text-pgu-ink shadow-sm focus:border-pgu-primary">
            <button @click="refresh()" class="inline-flex items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                Atualizar
            </button>
        </x-pgu.page-header>

        <div x-show="error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-text="error"></div>

        <section class="rounded-2xl border border-pgu-border bg-white px-4 py-3 shadow-sm">
            <p class="text-sm font-semibold text-brand-burgundy">Visão Cliente</p>
        </section>

        <div x-show="false" class="space-y-6">
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
                        <h2 class="text-base font-semibold text-pgu-ink">Quadro executivo de cobertura integral PGU por vagas</h2>
                        <p class="mt-1 text-sm text-pgu-muted">Vaga <strong class="font-medium text-pgu-ink">100%</strong> = fluxo concluído em todas as etapas do recrutamento para a vaga prevista.</p>
                    </div>
                    <ul class="max-w-xl space-y-1.5 text-sm text-pgu-muted">
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span><strong class="font-medium text-pgu-ink">Donut</strong> — quantas vagas já atingiram essa cobertura, em relação ao total monitorado.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <span><strong class="font-medium text-pgu-ink">Selos</strong> — lista das vagas concluídas integralmente neste recorte.</span>
                        </li>
                    </ul>
                    <div
                        class="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        x-show="!loading && !error && data?.summary"
                    >
                        <span class="text-pgu-muted">Resumo</span>
                        <span class="hidden h-4 w-px bg-pgu-border sm:inline" aria-hidden="true"></span>
                        <span class="inline-flex items-baseline gap-1.5">
                            <span class="text-2xl font-bold tabular-nums text-pgu-ink" x-text="vagasConcluidasTotal()"></span>
                            <span class="text-pgu-muted">vagas em 100%</span>
                        </span>
                        <span class="text-pgu-muted">/</span>
                        <span class="inline-flex items-baseline gap-1.5">
                            <span class="text-lg font-semibold tabular-nums text-pgu-ink" x-text="data?.summary?.total_functions ?? 0"></span>
                            <span class="text-pgu-muted">vagas monitoradas</span>
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
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-pgu-muted">Vagas com cobertura integral</p>
                    <div id="chartFuncoes100Donut" class="h-[420px] w-full min-w-0 overflow-hidden"></div>
                </div>
                <div class="min-w-0">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-pgu-muted">Mapa de vagas 100% liberadas</p>
                    <div
                        class="rounded-xl border border-dashed border-pgu-border bg-pgu-bg/40 p-4"
                        x-show="!loading && !error && (data?.funcoes_pgu_100?.length ?? 0) === 0"
                    >
                        <p class="text-sm text-pgu-muted">Nenhuma vaga atingiu <strong class="text-pgu-ink">100%</strong> neste recorte.</p>
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
                                    <p class="mt-0.5 text-xs text-pgu-muted"><span class="font-medium text-emerald-800">100%</span> · vaga concluída no recrutamento</p>
                                    <p class="mt-1 text-xs text-pgu-muted" x-show="row.codigo" x-text="'Código ' + row.codigo"></p>
                                    <p class="mt-0.5 text-xs text-pgu-muted"><span x-text="row.completed"></span> vaga(s) concluída(s)</p>
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
                subtitle="Distribuição atual de candidatos por fase do recrutamento."
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
                title="Concentração das pendências por função (vagas indiretas)"
                subtitle="Pareto das funções indiretas (códigos 1.1) no mesmo conjunto do ranking; eixo = vagas."
                chartId="chartParetoIndiretas"
            >
                <x-slot name="footer">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-pgu-muted">Pendências indiretas</p>
                            <p class="mt-1 text-xl font-black text-pgu-ink" x-text="paretoMetrics(data?.pareto_executivo_indiretas).totalPendencias"></p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-pgu-muted">Top 3 (indiretas)</p>
                            <p class="mt-1 text-xl font-black text-pgu-ink" x-text="paretoMetrics(data?.pareto_executivo_indiretas).top3Pendencias"></p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-pgu-muted">Concentração Top 3</p>
                            <p class="mt-1 text-xl font-black text-pgu-ink" x-text="`${formatPctPtBr(paretoMetrics(data?.pareto_executivo_indiretas).concentracaoTop3)}%`"></p>
                        </div>
                    </div>
                </x-slot>
            </x-pgu.chart-card>
            <x-pgu.chart-card
                title="Concentração das pendências por função (vagas diretas)"
                subtitle="Pareto das funções diretas (códigos 1.2) no mesmo conjunto do ranking; eixo = vagas."
                chartId="chartParetoDiretas"
            >
                <x-slot name="footer">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-pgu-muted">Pendências diretas</p>
                            <p class="mt-1 text-xl font-black text-pgu-ink" x-text="paretoMetrics(data?.pareto_executivo_diretas).totalPendencias"></p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-pgu-muted">Top 3 (diretas)</p>
                            <p class="mt-1 text-xl font-black text-pgu-ink" x-text="paretoMetrics(data?.pareto_executivo_diretas).top3Pendencias"></p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-pgu-muted">Concentração Top 3</p>
                            <p class="mt-1 text-xl font-black text-pgu-ink" x-text="`${formatPctPtBr(paretoMetrics(data?.pareto_executivo_diretas).concentracaoTop3)}%`"></p>
                        </div>
                    </div>
                </x-slot>
            </x-pgu.chart-card>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-pgu.chart-card
                title="Evolução por fases do recrutamento"
                subtitle="Histórico mensal da quantidade de candidatos em cada fase."
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

        <div class="space-y-6">
            <section class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-pgu-border px-6 py-5">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-sm ring-1 ring-brand-burgundy/15">
                            <i data-lucide="chart-line" class="h-6 w-6"></i>
                        </span>
                        <div>
                            <h2 class="text-[44px] font-black leading-none text-pgu-ink">1. Panorama Executivo do PGU</h2>
                            <p class="mt-2 text-lg text-pgu-muted">Cobertura e consolidação da base funcional do contrato</p>
                        </div>
                    </div>
                    <div class="relative flex items-center gap-2 text-pgu-muted">
                        <button type="button" @click="clienteInfo()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Sobre o indicador">
                            <i data-lucide="info" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="exportClientePanorama()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Exportar donut como PNG">
                            <i data-lucide="download" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="toggleClienteMenu()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Mais ações">
                            <i data-lucide="ellipsis-vertical" class="h-5 w-5"></i>
                        </button>
                        <div x-show="clienteMenuOpen" @click.outside="closeClienteMenu()" x-cloak class="absolute right-0 top-10 z-20 w-56 overflow-hidden rounded-xl border border-pgu-border bg-white shadow-lg">
                            <button type="button" @click="clienteInfo(); closeClienteMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Ver descrição do indicador
                            </button>
                            <button type="button" @click="exportClientePanorama()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Exportar gráfico (PNG)
                            </button>
                        </div>
                    </div>
                </div>
                <div class="grid gap-0 lg:grid-cols-[58%_42%]">
                    <div class="border-b border-r border-pgu-border p-6 lg:border-b-0">
                        <div class="flex justify-center overflow-x-visible overflow-y-visible px-2 sm:px-4">
                            <div id="chartClientePanorama" class="h-[460px] w-full max-w-[680px] min-w-[320px] overflow-visible"></div>
                        </div>
                        <div class="mt-3 overflow-x-auto border-t border-pgu-border pt-4">
                            <div class="grid min-w-[34rem] grid-cols-6 gap-x-1 gap-y-2 sm:min-w-0 sm:w-full sm:gap-x-2 md:gap-x-3">
                            <template x-for="fase in clienteFasesComPercentual()" :key="fase.name">
                                <div class="flex min-w-0 flex-col items-center text-center">
                                    <span class="mb-1 inline-block h-3 w-3 shrink-0 rounded-sm" :style="`background:${fase.color}`"></span>
                                    <p class="text-[10px] font-semibold leading-snug text-pgu-ink sm:text-xs" x-text="fase.name"></p>
                                    <p class="mt-1 text-lg font-black text-pgu-ink tabular-nums sm:text-2xl" x-text="`${formatPctPtBr(fase.percent)}%`"></p>
                                </div>
                            </template>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="map-pinned" class="h-5 w-5"></i>
                                    </span>
                                    <div>
                                        <p class="text-4xl font-black text-brand-burgundy" x-text="clientePanorama().mapeadas"></p>
                                        <p class="mt-1 text-base text-pgu-muted">Vagas mapeadas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                                    </span>
                                    <div>
                                        <p class="text-4xl font-black text-brand-burgundy" x-text="clientePanorama().consolidadas"></p>
                                        <p class="mt-1 text-base text-pgu-muted">Vagas consolidadas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="refresh-cw" class="h-5 w-5"></i>
                                    </span>
                                    <div>
                                        <p class="text-4xl font-black text-brand-burgundy" x-text="clientePanorama().emEvolucao"></p>
                                        <p class="mt-1 text-base text-pgu-muted">Vagas em evolução</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                                    </span>
                                    <div>
                                        <p class="text-4xl font-black text-brand-burgundy" x-text="`${formatPctPtBr(clientePanorama().monitoradas)}%`"></p>
                                        <p class="mt-1 text-base text-pgu-muted">Vagas monitoradas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 rounded-xl border border-pgu-border bg-zinc-50/80 px-4 py-4 text-sm leading-relaxed text-pgu-ink">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-pgu-border bg-white text-brand-burgundy">
                                    <i data-lucide="shield-user" class="h-5 w-5"></i>
                                </span>
                                <p>
                                    O PGU fortalece a governança do contrato ao consolidar informações funcionais, ampliar a rastreabilidade e apoiar decisões com base em dados atualizados.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid gap-3 border-t border-pgu-border bg-zinc-50/60 px-6 py-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-3xl font-black text-brand-burgundy" x-text="`${formatPctPtBr(clientePanorama().pctConsolidada)}%`"></p>
                                <p class="text-sm text-pgu-muted">base já consolidada</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="rotate-cw" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-3xl font-black text-brand-burgundy" x-text="`${formatPctPtBr(clientePanorama().pctEvolucao)}%`"></p>
                                <p class="text-sm text-pgu-muted">base em atualização contínua</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="users-round" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-3xl font-black text-brand-burgundy">Gestão ativa</p>
                                <p class="text-sm text-pgu-muted">monitoramento mensal</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @include('dashboard.partials.pgu-cliente-avanco-contratacoes')
            @include('dashboard.partials.pgu-cliente-avanco-ciclo')

            <section id="cardClienteMaturidade" class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-pgu-border px-6 py-4 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
                    <div class="flex min-w-0 flex-1 items-start gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-sm ring-1 ring-brand-burgundy/15">
                            <i data-lucide="workflow" class="h-8 w-8"></i>
                        </span>
                        <div>
                            <h2 class="text-[44px] font-black leading-none text-pgu-ink">3. Maturidade do Fluxo PGU</h2>
                            <p class="mt-1 text-lg text-pgu-muted">Acompanhamento das etapas do processo de mobilização, desde a aprovação dos candidatos até a liberação para início das atividades.</p>
                        </div>
                    </div>
                    <div class="w-full max-w-[420px] shrink-0 self-end rounded-2xl border border-pgu-border bg-white overflow-hidden lg:self-auto">
                        <div class="grid grid-cols-2">
                            <div class="flex items-center gap-2 border-b border-r border-pgu-border px-4 py-3">
                                <i data-lucide="clipboard-list" class="h-4 w-4 text-brand-burgundy"></i>
                                <div class="leading-tight">
                                    <p class="text-[11px] font-semibold text-pgu-muted">Contrato</p>
                                    <p class="text-[30px] font-black text-brand-burgundy" x-text="clienteCicloResumo().contrato"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 border-b border-pgu-border px-4 py-3">
                                <i data-lucide="calendar-days" class="h-4 w-4 text-brand-burgundy"></i>
                                <div class="leading-tight">
                                    <p class="text-[11px] font-semibold text-pgu-muted">Competência</p>
                                    <p class="text-[30px] font-black text-brand-burgundy" x-text="cicloPeriodoLabel()"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 border-r border-pgu-border px-4 py-3">
                                <i data-lucide="calendar-check-2" class="h-4 w-4 text-brand-burgundy"></i>
                                <div class="leading-tight">
                                    <p class="text-[11px] font-semibold text-pgu-muted">Data limite para conclusão</p>
                                    <p class="text-[30px] font-black text-brand-burgundy" x-text="clienteCicloResumo().dataLimite"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-4 py-3">
                                <i data-lucide="clock-3" class="h-4 w-4 text-brand-burgundy"></i>
                                <div class="leading-tight">
                                    <p class="text-[11px] font-semibold text-pgu-muted">Dias restantes</p>
                                    <p class="text-[30px] font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteCicloResumo().diasRestantes)"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-b border-pgu-border">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
                        <div class="rounded-xl border border-pgu-border bg-white px-3 py-3">
                            <div class="flex min-h-[64px] items-start gap-2">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="users-round" class="h-5 w-5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="min-h-[28px] text-[11px] font-black uppercase leading-[1.05] tracking-wide text-pgu-muted">Vagas Preenchidas</p>
                                    <p class="mt-1 text-4xl font-black leading-none text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeResumo().aprovados)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[13px] leading-snug text-pgu-muted" x-text="`${formatQtyPtBr(clienteMaturidadeResumo().aprovados)} / ${formatQtyPtBr(clienteMaturidadeResumo().totalVagas)} vagas · ${formatPctPtBr(clienteMaturidadeEtapas()[0]?.pct || 0)}% do total`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white px-3 py-3">
                            <div class="flex min-h-[64px] items-start gap-2">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="stethoscope" class="h-5 w-5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="min-h-[28px] text-[11px] font-black uppercase leading-[1.05] tracking-wide text-pgu-muted">Exame médico</p>
                                    <p class="mt-1 text-4xl font-black leading-none text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeResumo().exameMedico)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[13px] leading-snug text-pgu-muted" x-text="`${formatQtyPtBr(clienteMaturidadeResumo().exameMedico)} / ${formatQtyPtBr(clienteMaturidadeResumo().totalVagas)} vagas · ${formatPctPtBr(clienteMaturidadeEtapas()[1]?.pct || 0)}% do total`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white px-3 py-3">
                            <div class="flex min-h-[64px] items-start gap-2">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="graduation-cap" class="h-5 w-5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="min-h-[28px] text-[11px] font-black uppercase leading-[1.05] tracking-wide text-pgu-muted">Treinamentos</p>
                                    <p class="mt-1 text-4xl font-black leading-none text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeResumo().treinamentos)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[13px] leading-snug text-pgu-muted" x-text="`${formatQtyPtBr(clienteMaturidadeResumo().treinamentos)} / ${formatQtyPtBr(clienteMaturidadeResumo().totalVagas)} vagas · ${formatPctPtBr(clienteMaturidadeEtapas()[2]?.pct || 0)}% do total`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white px-3 py-3">
                            <div class="flex min-h-[64px] items-start gap-2">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="file-signature" class="h-5 w-5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="min-h-[28px] text-[11px] font-black uppercase leading-[1.05] tracking-wide text-pgu-muted">Assinatura documental</p>
                                    <p class="mt-1 text-4xl font-black leading-none text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeResumo().assinatura)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[13px] leading-snug text-pgu-muted" x-text="`${formatQtyPtBr(clienteMaturidadeResumo().assinatura)} / ${formatQtyPtBr(clienteMaturidadeResumo().totalVagas)} vagas · ${formatPctPtBr(clienteMaturidadeEtapas()[3]?.pct || 0)}% do total`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white px-3 py-3">
                            <div class="flex min-h-[64px] items-start gap-2">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="shield-check" class="h-5 w-5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="min-h-[28px] text-[11px] font-black uppercase leading-[1.05] tracking-wide text-pgu-muted">SGC concluído</p>
                                    <p class="mt-1 text-4xl font-black leading-none text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeResumo().sgc)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[13px] leading-snug text-pgu-muted" x-text="`${formatQtyPtBr(clienteMaturidadeResumo().sgc)} / ${formatQtyPtBr(clienteMaturidadeResumo().totalVagas)} vagas · ${formatPctPtBr(clienteMaturidadeEtapas()[4]?.pct || 0)}% do total`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white px-3 py-3">
                            <div class="flex min-h-[64px] items-start gap-2">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="flag" class="h-5 w-5"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="min-h-[28px] text-[11px] font-black uppercase leading-[1.05] tracking-wide text-pgu-muted">Vagas liberadas</p>
                                    <p class="mt-1 text-4xl font-black leading-none text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeResumo().liberacao)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[13px] leading-snug text-pgu-muted" x-text="`${formatQtyPtBr(clienteMaturidadeResumo().liberacao)} / ${formatQtyPtBr(clienteMaturidadeResumo().totalVagas)} vagas · ${formatPctPtBr(clienteMaturidadeEtapas()[5]?.pct || 0)}% do total`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white px-3 py-3">
                            <div class="flex min-h-[64px] items-start gap-3">
                                <svg viewBox="0 0 44 44" class="h-11 w-11 shrink-0">
                                    <circle cx="22" cy="22" r="18" fill="none" stroke="#E6EAF0" stroke-width="6"></circle>
                                    <circle
                                        cx="22"
                                        cy="22"
                                        r="18"
                                        fill="none"
                                        stroke="#6F1731"
                                        stroke-width="6"
                                        stroke-linecap="round"
                                        stroke-dasharray="113.1"
                                        :stroke-dashoffset="`${113.1 - (Math.max(0, Math.min(100, clienteMaturidadeResumo().maturidade)) / 100) * 113.1}`"
                                        transform="rotate(-90 22 22)"
                                    ></circle>
                                </svg>
                                <div class="min-w-0 flex-1">
                                    <p class="min-h-[28px] text-[11px] font-black uppercase leading-[1.05] tracking-wide text-pgu-muted">Maturidade do fluxo</p>
                                    <p class="mt-1 text-4xl font-black leading-none text-brand-burgundy" x-text="`${formatPctPtBr(clienteMaturidadeResumo().maturidade)}%`"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[13px] leading-snug text-pgu-muted">Liberações concluídas sobre o total de vagas do contrato</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-b border-pgu-border">
                    <style>
                        #cardClienteMaturidade .pgu-funil-card {
                            width: 100%;
                            background: #fff;
                            border: 1px solid #e5ebef;
                            border-radius: 14px;
                            padding: 14px 18px 16px;
                            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
                            font-family: inherit;
                            overflow: hidden;
                        }
                        #cardClienteMaturidade .pgu-funil-header {
                            display: flex;
                            align-items: flex-start;
                            justify-content: space-between;
                            gap: 16px;
                            margin-bottom: 12px;
                        }
                        #cardClienteMaturidade .pgu-funil-header h2 {
                            margin: 0;
                            color: #6f1731;
                            font-size: 15px;
                            font-weight: 800;
                            line-height: 1.1;
                            letter-spacing: 0.01em;
                            text-transform: uppercase;
                        }
                        #cardClienteMaturidade .pgu-funil-header p {
                            margin: 3px 0 0;
                            color: #203040;
                            font-size: 12px;
                            font-weight: 500;
                        }
                        #cardClienteMaturidade .pgu-funil-legenda {
                            display: flex;
                            align-items: center;
                            gap: 24px;
                            white-space: nowrap;
                            color: #344054;
                            font-size: 12px;
                            font-weight: 500;
                            padding-top: 2px;
                        }
                        #cardClienteMaturidade .pgu-funil-legenda span {
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                        }
                        #cardClienteMaturidade .pgu-funil-legenda i {
                            width: 13px;
                            height: 13px;
                            display: inline-block;
                            border-radius: 3px;
                        }
                        #cardClienteMaturidade .legenda-concluido {
                            background: #6f1731;
                        }
                        #cardClienteMaturidade .legenda-andamento {
                            background: #d4a3b3;
                        }
                        #cardClienteMaturidade .legenda-iniciar {
                            background: #dce2e7;
                        }
                        #cardClienteMaturidade .pgu-funil-grid {
                            display: grid;
                            grid-template-columns: repeat(5, minmax(0, 1fr)) minmax(130px, 150px);
                            gap: 8px;
                            align-items: stretch;
                        }
                        #cardClienteMaturidade .pgu-funil-step {
                            position: relative;
                            min-height: 138px;
                            background: #fff;
                            border-radius: 8px;
                            overflow: visible;
                        }
                        #cardClienteMaturidade .pgu-funil-step-top {
                            height: 44px;
                            display: flex;
                            align-items: center;
                            padding: 0 16px 0 18px;
                            color: #fff;
                            background: linear-gradient(135deg, #6f1731 0%, #521223 100%);
                            clip-path: polygon(0 0, calc(100% - 18px) 0, 100% 50%, calc(100% - 18px) 100%, 0 100%);
                            border-radius: 7px 0 0 7px;
                        }
                        #cardClienteMaturidade .pgu-funil-step.is-andamento .pgu-funil-step-top {
                            color: #6f1731;
                            background: linear-gradient(135deg, #f8ecef 0%, #e8c9d4 100%);
                        }
                        #cardClienteMaturidade .pgu-funil-step.is-iniciar .pgu-funil-step-top {
                            color: #4a5565;
                            background: linear-gradient(135deg, #eef2f5 0%, #d9e0e6 100%);
                        }
                        #cardClienteMaturidade .pgu-funil-step-title {
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            min-width: 0;
                        }
                        #cardClienteMaturidade .pgu-funil-step-title strong {
                            display: block;
                            overflow: hidden;
                            color: currentColor;
                            font-size: 11px;
                            font-weight: 800;
                            line-height: 1.15;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        }
                        @media (min-width: 1100px) {
                            #cardClienteMaturidade .pgu-funil-step-title strong {
                                font-size: 12px;
                            }
                        }
                        #cardClienteMaturidade .pgu-funil-icon {
                            width: 26px;
                            height: 26px;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            flex: 0 0 auto;
                        }
                        #cardClienteMaturidade .pgu-funil-icon svg {
                            width: 22px;
                            height: 22px;
                            fill: currentColor;
                        }
                        #cardClienteMaturidade .pgu-funil-step-body {
                            min-height: 94px;
                            padding: 12px 12px 0;
                            text-align: center;
                            border: 1px solid #edf1f4;
                            border-top: none;
                            border-radius: 0 0 8px 8px;
                        }
                        #cardClienteMaturidade .pgu-funil-value-line {
                            display: flex;
                            align-items: baseline;
                            justify-content: center;
                            flex-wrap: wrap;
                            gap: 2px 6px;
                            color: #6f1731;
                            font-size: 20px;
                            font-weight: 900;
                            line-height: 1.1;
                        }
                        #cardClienteMaturidade .pgu-funil-value-real {
                            color: #6f1731;
                        }
                        #cardClienteMaturidade .pgu-funil-value-slash {
                            color: #94a3b8;
                            font-weight: 700;
                            font-size: 0.85em;
                        }
                        #cardClienteMaturidade .pgu-funil-value-total {
                            color: #64748b;
                            font-size: 0.88em;
                            font-weight: 800;
                        }
                        #cardClienteMaturidade .pgu-funil-percent {
                            display: block;
                            margin-top: 6px;
                            color: #1f2937;
                            font-size: 12px;
                            font-weight: 800;
                        }
                        #cardClienteMaturidade .pgu-funil-progress {
                            width: 100%;
                            height: 11px;
                            margin-top: 14px;
                            overflow: hidden;
                            background: #dfe5e9;
                            border-radius: 999px;
                        }
                        #cardClienteMaturidade .pgu-funil-progress span {
                            display: block;
                            height: 100%;
                            border-radius: inherit;
                            background: linear-gradient(90deg, #6f1731 0%, #8b2c4a 100%);
                            transition: width 0.25s ease;
                        }
                        #cardClienteMaturidade .pgu-funil-step.is-andamento .pgu-funil-progress span {
                            background: linear-gradient(90deg, #c17a8f 0%, #d4a3b3 100%);
                        }
                        #cardClienteMaturidade .pgu-funil-step.is-iniciar .pgu-funil-progress span {
                            background: #cfd8df;
                        }
                        #cardClienteMaturidade .pgu-funil-step-body small {
                            display: block;
                            margin-top: 8px;
                            color: #344054;
                            font-size: 11px;
                            font-weight: 500;
                            line-height: 1.3;
                        }
                        #cardClienteMaturidade .pgu-funil-arrow {
                            position: absolute;
                            top: 69px;
                            right: -17px;
                            z-index: 20;
                            width: 26px;
                            height: 26px;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            background: #fff;
                            border: 2px solid #6f1731;
                            border-radius: 999px;
                            box-shadow: 0 2px 8px rgba(111, 23, 49, 0.15);
                        }
                        #cardClienteMaturidade .pgu-funil-arrow svg {
                            width: 18px;
                            height: 18px;
                            fill: #6f1731;
                        }
                        #cardClienteMaturidade .pgu-funil-final {
                            min-height: 138px;
                            overflow: hidden;
                            background: #fff;
                            border: 1px solid #edf1f4;
                            border-radius: 9px;
                            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
                        }
                        #cardClienteMaturidade .pgu-funil-final-top {
                            height: 44px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background: linear-gradient(135deg, #8b2c4a 0%, #6f1731 100%);
                            color: #fff;
                            font-size: 11px;
                            font-weight: 900;
                            line-height: 1.2;
                            text-align: center;
                            text-transform: uppercase;
                            padding: 0 6px;
                        }
                        #cardClienteMaturidade .pgu-funil-final-body {
                            min-height: 94px;
                            padding: 10px 8px 12px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                        }
                        #cardClienteMaturidade .pgu-funil-check {
                            width: 36px;
                            height: 36px;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            border: 3px solid #6f1731;
                            border-radius: 999px;
                            margin-bottom: 4px;
                            background: #fff;
                        }
                        #cardClienteMaturidade .pgu-funil-check svg {
                            width: 22px;
                            height: 22px;
                            fill: #6f1731;
                        }
                        #cardClienteMaturidade .pgu-funil-final-nums {
                            display: flex;
                            align-items: baseline;
                            justify-content: center;
                            flex-wrap: wrap;
                            gap: 4px 6px;
                            font-size: 22px;
                            font-weight: 900;
                            line-height: 1;
                            color: #6f1731;
                        }
                        #cardClienteMaturidade .pgu-funil-final-nums .sep {
                            color: #94a3b8;
                            font-weight: 700;
                            font-size: 0.85em;
                        }
                        #cardClienteMaturidade .pgu-funil-final-nums .tot {
                            color: #64748b;
                            font-size: 0.88em;
                        }
                        #cardClienteMaturidade .pgu-funil-final-pct {
                            margin-top: 4px;
                            color: #344054;
                            font-size: 12px;
                            font-weight: 800;
                        }
                        #cardClienteMaturidade .pgu-funil-final-body p {
                            margin: 6px 0 0;
                            color: #344054;
                            font-size: 11px;
                            font-weight: 500;
                            line-height: 1.3;
                            text-align: center;
                        }
                        @media (max-width: 1180px) {
                            #cardClienteMaturidade .pgu-funil-card {
                                overflow-x: auto;
                            }
                            #cardClienteMaturidade .pgu-funil-grid {
                                min-width: 1020px;
                            }
                        }
                        @media (max-width: 768px) {
                            #cardClienteMaturidade .pgu-funil-header {
                                flex-direction: column;
                            }
                            #cardClienteMaturidade .pgu-funil-legenda {
                                gap: 14px;
                                flex-wrap: wrap;
                            }
                        }
                    </style>
                    <div class="pgu-funil-card">
                        <div class="pgu-funil-header">
                            <div>
                                <h2>FUNIL DO FLUXO DE MOBILIZAÇÃO</h2>
                                <p><strong>Realizado / total de vagas</strong> do contrato em cada etapa (base: recortamento PGU ou histograma).</p>
                            </div>
                            <div class="pgu-funil-legenda">
                                <span><i class="legenda-concluido"></i> Concluído</span>
                                <span><i class="legenda-andamento"></i> Em andamento</span>
                                <span><i class="legenda-iniciar"></i> A iniciar</span>
                            </div>
                        </div>
                        <div class="pgu-funil-grid">
                            <template x-for="(etapa, idx) in clienteMaturidadeEtapas()" :key="`maturidade-funil-${etapa.key}`">
                                <article class="pgu-funil-step" :class="'is-' + clienteMaturidadeEtapaStatus(etapa)">
                                    <div class="pgu-funil-step-top">
                                        <div class="pgu-funil-step-title">
                                            <span class="pgu-funil-icon">
                                                <svg x-show="idx === 0" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3ZM8 11c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13Zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5Z" />
                                                </svg>
                                                <svg x-show="idx === 1" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M12 3 1 8l11 5 9-4.09V16h2V8L12 3Zm0 12L5 11.82V16c0 1.66 3.13 3 7 3s7-1.34 7-3v-4.18L12 15Z" />
                                                </svg>
                                                <svg x-show="idx === 2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h9v-2H6V4h7v5h5v4h2V8l-6-6Zm3.5 19.5 4.5-4.5-1.4-1.4-3.1 3.1-1.4-1.4-1.4 1.4 2.8 2.8Z" />
                                                </svg>
                                                <svg x-show="idx === 3" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M12 1 4 4v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V4l-8-3Zm-1 15-4-4 1.41-1.41L11 13.17l5.59-5.59L18 9l-7 7Z" />
                                                </svg>
                                                <svg x-show="idx === 4" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M5 3v18h2v-7h11l-2-4 2-4H7V3H5Z" />
                                                </svg>
                                            </span>
                                            <strong x-text="`${idx + 1}. ${etapa.label.toUpperCase()}`"></strong>
                                        </div>
                                    </div>
                                    <div class="pgu-funil-step-body">
                                        <strong class="pgu-funil-value-line">
                                            <span class="pgu-funil-value-real" x-text="formatQtyPtBr(etapa.value)"></span>
                                            <span class="pgu-funil-value-slash">/</span>
                                            <span class="pgu-funil-value-total" x-text="formatQtyPtBr(etapa.total)"></span>
                                        </strong>
                                        <span class="pgu-funil-percent" x-text="`${formatPctPtBr(Math.max(0, Math.min(100, etapa.pct)))}%`"></span>
                                        <div class="pgu-funil-progress">
                                            <span :style="`width: ${Math.max(0, Math.min(100, etapa.pct))}%`"></span>
                                        </div>
                                        <small x-text="`${formatPctPtBr(etapa.pct)}% do total de vagas`"></small>
                                    </div>
                                    <span class="pgu-funil-arrow" x-show="idx < 4" aria-hidden="true">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M9.29 6.71a1 1 0 0 1 1.42 0L16 12l-5.29 5.29a1 1 0 1 1-1.42-1.42L13.17 12 9.29 8.12a1 1 0 0 1 0-1.41Z" />
                                        </svg>
                                    </span>
                                </article>
                            </template>
                            <article class="pgu-funil-final">
                                <div class="pgu-funil-final-top">VAGAS LIBERADAS</div>
                                <div class="pgu-funil-final-body">
                                    <div class="pgu-funil-check">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M9.2 16.2 5.7 12.7a1 1 0 0 0-1.4 1.4l4.2 4.2c.4.4 1 .4 1.4 0l9.8-9.8a1 1 0 0 0-1.4-1.4l-9.1 9.1Z" />
                                        </svg>
                                    </div>
                                    <div class="pgu-funil-final-nums">
                                        <span x-text="formatQtyPtBr(clienteMaturidadeResumo().liberacao)"></span>
                                        <span class="sep">/</span>
                                        <span class="tot" x-text="formatQtyPtBr(clienteMaturidadeResumo().totalVagas)"></span>
                                    </div>
                                    <span class="pgu-funil-final-pct" x-text="`${formatPctPtBr(clienteMaturidadeResumo().maturidade)}%`"></span>
                                    <p>do total de vagas do contrato</p>
                                </div>
                            </article>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        <div class="rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-3 text-pgu-ink">
                            Acompanhamos todas as etapas do fluxo para garantir que os profissionais estejam
                            <strong>100% aptos e liberados</strong> até a data limite contratual.
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50 px-4 py-3 text-sm text-pgu-ink">
                            <p class="font-semibold mb-1">CRITÉRIOS CONSIDERADOS CONCLUÍDOS</p>
                            <p>Treinamentos obrigatórios realizados · Contrato assinado · SGC concluído · Liberação para início</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 bg-zinc-50/60 px-6 py-4 lg:grid-cols-3">
                    <div class="rounded-xl border border-pgu-border bg-white p-4">
                        <p class="text-lg font-black text-brand-burgundy">EVOLUÇÃO POR ETAPA</p>
                        <div id="chartClienteMaturidadeEtapas" class="mt-2 h-[210px] w-full"></div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white p-4">
                        <p class="text-lg font-black text-brand-burgundy">COMPARATIVO COM A PROJEÇÃO</p>
                        <div id="chartClienteMaturidadeComparativo" class="mt-2 h-[210px] w-full"></div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white p-4">
                        <p class="text-lg font-black text-brand-burgundy">SITUAÇÃO DO FLUXO</p>
                        <div class="mt-3 rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-white/70">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-burgundy text-white">
                                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                                    </span>
                                </span>
                                <div>
                                    <p class="text-[30px] font-black leading-none text-brand-burgundy" x-text="clienteCicloResumo().situacaoNoPrazo ? 'NO PRAZO' : 'ATENÇÃO'"></p>
                                    <p class="mt-1 text-[13px] leading-tight text-pgu-ink">O fluxo de mobilização está evoluindo conforme o planejado e dentro do prazo.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 rounded-xl border border-pgu-border bg-white px-4 py-3">
                            <p class="text-[15px] font-black text-pgu-ink">PRÓXIMOS FOCOS</p>
                            <div class="mt-2 space-y-1.5 text-[13px] text-pgu-ink">
                                <p class="flex items-start gap-2"><span class="mt-1.5 h-2 w-2 rounded-full bg-brand-burgundy"></span><span>Manter ritmo de treinamentos e assinatura de contratos</span></p>
                                <p class="flex items-start gap-2"><span class="mt-1.5 h-2 w-2 rounded-full bg-brand-burgundy"></span><span>Acompanhar conclusão de SGC</span></p>
                                <p class="flex items-start gap-2"><span class="mt-1.5 h-2 w-2 rounded-full bg-brand-burgundy"></span><span>Garantir liberação das vagas até <span x-text="clienteCicloResumo().dataLimite"></span></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-pgu-border bg-white px-4 py-3">
                    <div class="flex items-center gap-3 rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy text-white">
                                <i data-lucide="target" class="h-6 w-6"></i>
                            </span>
                            <p class="text-base leading-tight text-pgu-ink">
                                Nosso compromisso é conduzir todo o fluxo com segurança, agilidade e qualidade, garantindo a liberação de
                                <strong class="text-brand-burgundy">100% das vagas até <span x-text="clienteCicloResumo().dataLimite"></span>.</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="cardClienteMapaConsolidacao" class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm overflow-hidden">
                <div class="border-b border-pgu-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col items-stretch gap-5 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
                        {{-- Bloco título: à esquerda; caixa de resumo: à direita na mesma linha (lg+) --}}
                        <div class="flex min-w-0 flex-1 items-start gap-3 sm:gap-4">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-sm ring-1 ring-brand-burgundy/15">
                                <i data-lucide="bar-chart-3" class="h-6 w-6"></i>
                            </span>
                            <div class="min-w-0 flex-1 pr-0 lg:max-w-[calc(100%-28rem)] lg:pr-4">
                                <h2 class="text-[44px] font-black leading-none text-pgu-ink">4. Mapa de Consolidação por Função PGU</h2>
                                <p class="mt-2 max-w-3xl text-base leading-snug text-pgu-muted sm:text-lg">
                                    Visão detalhada do avanço da mobilização por função, com acompanhamento das vagas previstas, consolidadas e em evolução no ciclo contratual.
                                </p>
                            </div>
                        </div>
                        <div class="flex w-full shrink-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-end lg:w-auto">
                            <div class="w-full overflow-hidden rounded-2xl border border-pgu-border bg-white shadow-sm sm:max-w-md lg:max-w-[min(100%,28rem)]">
                                <div class="grid grid-cols-3 divide-x divide-pgu-border border-b border-pgu-border">
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="clipboard-list" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Contrato</p>
                                            <p class="mt-0.5 truncate text-lg font-black text-brand-burgundy sm:text-xl" x-text="clienteCicloResumo().contrato"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="calendar-days" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Competência</p>
                                            <p class="mt-0.5 truncate text-lg font-black text-brand-burgundy sm:text-xl" x-text="cicloPeriodoLabel()"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="calendar-clock" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Data limite</p>
                                            <p class="mt-0.5 text-lg font-black text-brand-burgundy sm:text-xl" x-text="clienteCicloResumo().dataLimite"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-pgu-border">
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="target" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium leading-tight text-pgu-muted">Progresso consolidado do ciclo</p>
                                            <p class="mt-0.5 text-lg font-black text-brand-burgundy sm:text-xl" x-text="`${formatPctPtBr(clienteProgressoConsolidadoCicloPct())}%`"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="clock-3" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Dias restantes</p>
                                            <p class="mt-0.5 text-lg font-black text-brand-burgundy sm:text-xl"><span x-text="formatQtyPtBr(clienteCicloResumo().diasRestantes)"></span> dias</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative flex shrink-0 justify-end text-pgu-muted sm:flex-col sm:items-end">
                                <button type="button" @click="clienteMapaInfo()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Sobre o indicador">
                                    <i data-lucide="info" class="h-5 w-5"></i>
                                </button>
                                <button type="button" @click="exportClienteMapa()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Exportar gráfico (PNG)">
                                    <i data-lucide="download" class="h-5 w-5"></i>
                                </button>
                                <button type="button" @click="toggleClienteMapaMenu()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Mais ações">
                                    <i data-lucide="ellipsis-vertical" class="h-5 w-5"></i>
                                </button>
                                <div x-show="clienteMapaMenuOpen" @click.outside="closeClienteMapaMenu()" x-cloak class="absolute right-0 top-10 z-20 w-56 overflow-hidden rounded-xl border border-pgu-border bg-white shadow-lg">
                                    <button type="button" @click="clienteMapaInfo(); closeClienteMapaMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                        Ver descrição do indicador
                                    </button>
                                    <button type="button" @click="exportClienteMapa(); closeClienteMapaMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                        Exportar donut (PNG)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 px-4 py-5 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="users-round" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas mapeadas</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteConsolidacaoResumo().mapeadas)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted">Total de vagas no PGU (soma por função).</p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas consolidadas</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteConsolidacaoResumo().consolidadas)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted" x-text="clienteConsolidacaoResumo().mapeadas > 0 ? `${formatPctPtBr((clienteConsolidacaoResumo().consolidadas / clienteConsolidacaoResumo().mapeadas) * 100)}% do total mapeado` : '—'"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="refresh-cw" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas em evolução</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteConsolidacaoResumo().emEvolucao)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted" x-text="clienteConsolidacaoResumo().mapeadas > 0 ? `${formatPctPtBr((clienteConsolidacaoResumo().emEvolucao / clienteConsolidacaoResumo().mapeadas) * 100)}% do total mapeado` : '—'"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="flag" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Funções 100% concluídas</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteConsolidacaoResumo().funcoes100)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted">Do total de funções monitoradas.</p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3 sm:col-span-2 lg:col-span-1">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="search" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Funções monitoradas</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteConsolidacaoResumo().funcoesMonitoradas)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted">Funções com vagas no ciclo.</p>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,1fr)]">
                        <div class="min-w-0 rounded-xl border border-pgu-border bg-white p-4">
                            <p class="text-lg font-black text-brand-burgundy">DESEMPENHO POR FUNÇÃO</p>
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-pgu-border text-[11px] font-black uppercase text-pgu-muted">
                                            <th class="pb-2 pr-2 w-10">#</th>
                                            <th class="pb-2 pr-2">Função</th>
                                            <th class="pb-2 pr-2 text-right">Previstas</th>
                                            <th class="pb-2 pr-2 text-right">Consolidadas</th>
                                            <th class="pb-2 pr-2 text-right">Em evolução</th>
                                            <th class="pb-2 pr-2 min-w-[160px]">Índice</th>
                                            <th class="pb-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, idx) in clienteConsolidacaoRows()" :key="`cons-func-${idx}-${row.funcao}`">
                                            <tr class="border-b border-pgu-border/80 align-middle">
                                                <td class="py-2 pr-2 text-pgu-muted" x-text="idx + 1"></td>
                                                <td class="py-2 pr-2 font-semibold text-pgu-ink" x-text="row.funcao"></td>
                                                <td class="py-2 pr-2 text-right tabular-nums" x-text="formatQtyPtBr(row.total)"></td>
                                                <td class="py-2 pr-2 text-right tabular-nums" x-text="formatQtyPtBr(row.consolidadas)"></td>
                                                <td class="py-2 pr-2 text-right tabular-nums" x-text="formatQtyPtBr(row.emEvolucao)"></td>
                                                <td class="py-2 pr-2">
                                                    <div class="flex items-center gap-2">
                                                        <div class="h-2 min-w-[72px] flex-1 overflow-hidden rounded-full bg-zinc-200">
                                                            <div class="h-full rounded-full bg-brand-burgundy" :style="`width:${Math.min(100, row.indice)}%`"></div>
                                                        </div>
                                                        <span class="shrink-0 tabular-nums font-semibold text-brand-burgundy" x-text="`${formatPctPtBr(row.indice)}%`"></span>
                                                    </div>
                                                </td>
                                                <td class="py-2">
                                                    <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-pgu-ink">
                                                        <span class="h-2 w-2 shrink-0 rounded-full"
                                                              :class="clienteConsolidacaoStatusFuncao(row) === 'Concluída' ? 'bg-brand-burgundy' : 'bg-zinc-400'"></span>
                                                        <span x-text="clienteConsolidacaoStatusFuncao(row)"></span>
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t-2 border-pgu-border font-black text-pgu-ink">
                                            <td class="py-2 pr-2" colspan="2">Total geral</td>
                                            <td class="py-2 pr-2 text-right tabular-nums" x-text="formatQtyPtBr(clienteConsolidacaoResumo().mapeadas)"></td>
                                            <td class="py-2 pr-2 text-right tabular-nums" x-text="formatQtyPtBr(clienteConsolidacaoResumo().consolidadas)"></td>
                                            <td class="py-2 pr-2 text-right tabular-nums" x-text="formatQtyPtBr(clienteConsolidacaoResumo().emEvolucao)"></td>
                                            <td class="py-2 pr-2">
                                                <span class="font-semibold text-brand-burgundy" x-text="`${formatPctPtBr(clienteConsolidacaoResumo().indice)}%`"></span>
                                            </td>
                                            <td class="py-2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-xl border border-pgu-border bg-white p-4">
                                <p class="text-lg font-black uppercase tracking-wide text-brand-burgundy">Distribuição por faixa de consolidação</p>
                                <p class="mt-1 text-xs text-pgu-muted">Participação das funções em cada faixa de índice (por função).</p>
                                <div class="mt-4 grid items-center gap-5 lg:grid-cols-2 lg:gap-6">
                                    <div class="flex min-h-[260px] items-center justify-center lg:justify-end lg:pr-2">
                                        {{-- Texto central obrigatório: overlay HTML no miolo exato do donut (ECharts title não garante alinhamento). --}}
                                        <div class="relative mx-auto h-[280px] w-full max-w-[300px]">
                                            <div id="chartClienteMapaDonut" class="absolute inset-0 h-full w-full"></div>
                                            <div class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center px-2 text-center select-none">
                                                <span class="text-[34px] font-black leading-none tracking-tight text-pgu-ink" x-text="formatQtyPtBr(clienteConsolidacaoResumo().funcoesMonitoradas)"></span>
                                                <div class="mt-1.5 text-[12px] font-bold leading-snug text-pgu-muted">
                                                    <span class="block">funções</span>
                                                    <span class="block">monitoradas</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="divide-y divide-pgu-border overflow-hidden rounded-lg border border-pgu-border bg-zinc-50/50">
                                            <template x-for="fatia in clienteConsolidacaoFaixasDonut()" :key="`faixa-donut-${fatia.key}`">
                                                <div class="flex items-center gap-2 px-3 py-2.5 sm:gap-3 sm:px-4">
                                                    <span class="h-3 w-3 shrink-0 rounded-full shadow-sm ring-1 ring-black/10" :style="`background-color:${fatia.color}`"></span>
                                                    <span class="min-w-0 flex-1 text-[13px] font-medium leading-tight text-pgu-ink" x-text="fatia.label"></span>
                                                    <span class="shrink-0 whitespace-nowrap tabular-nums text-[13px] text-pgu-ink" x-text="formatFuncoesCountLabel(fatia.value)"></span>
                                                    <span class="shrink-0 w-[3.15rem] text-right text-[13px] font-semibold tabular-nums text-pgu-ink sm:w-16" x-text="`${formatPctPtBr(fatia.pctOfFuncs)}%`"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-3 rounded-xl border border-slate-200 bg-slate-100/90 px-4 py-3" x-show="clienteConsolidacaoRows().length > 0" x-cloak>
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-burgundy shadow-sm ring-1 ring-slate-200/80">
                                        <i data-lucide="network" class="h-5 w-5"></i>
                                    </span>
                                    <div class="min-w-0 text-[13px] leading-relaxed text-pgu-ink">
                                        <p x-text="clienteConsolidacaoDonutResumoLinhas().linha1"></p>
                                        <p class="mt-1" x-text="clienteConsolidacaoDonutResumoLinhas().linha2"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-white p-4">
                                <p class="text-lg font-black text-brand-burgundy">TOP 10 – MAIORES ÍNDICES DE CONSOLIDAÇÃO</p>
                                <div class="mt-3 space-y-3">
                                    <template x-for="(row, idx) in clienteConsolidacaoTop10PorIndice()" :key="`top10-cons-${idx}-${row.funcao}`">
                                        <div>
                                            <div class="flex items-center justify-between gap-2 text-sm font-semibold text-pgu-ink">
                                                <span class="min-w-0 truncate" x-text="row.funcao"></span>
                                                <span class="shrink-0 tabular-nums text-brand-burgundy" x-text="`${formatPctPtBr(row.indice)}%`"></span>
                                            </div>
                                            <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-zinc-200">
                                                <div class="h-full rounded-full bg-brand-burgundy" :style="`width:${Math.min(100, row.indice)}%`"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-pgu-border bg-zinc-100/90 p-4 sm:p-5">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-stretch lg:gap-0">
                            <div class="flex min-w-0 flex-1 gap-3 lg:pr-6">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy text-white shadow-sm">
                                    <i data-lucide="bar-chart-3" class="h-6 w-6"></i>
                                </span>
                                <p class="text-[13px] leading-relaxed text-pgu-ink">
                                    O mapa de consolidação permite acompanhar, função a função, o avanço da mobilização, garantindo foco nas funções com maior volume de vagas em evolução.
                                </p>
                            </div>
                            <div class="min-w-0 flex-1 border-t border-pgu-border pt-5 lg:border-l lg:border-t-0 lg:px-6 lg:pt-0">
                                <p class="text-[11px] font-black uppercase tracking-wide text-brand-burgundy">Critérios de consideração</p>
                                <ul class="mt-3 space-y-2.5 text-[13px] leading-snug text-pgu-ink">
                                    <li class="flex gap-2.5">
                                        <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <span><strong class="font-semibold text-pgu-ink">Consolidada:</strong> vaga com candidato liberado para início</span>
                                    </li>
                                    <li class="flex gap-2.5">
                                        <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <span><strong class="font-semibold text-pgu-ink">Em evolução:</strong> vaga com processo em andamento</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="min-w-0 flex-1 border-t border-pgu-border pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                                <ul class="space-y-2.5 text-[13px] leading-snug text-pgu-ink lg:mt-7">
                                    <li class="flex gap-2.5">
                                        <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <span><strong class="font-semibold text-pgu-ink">Índice de consolidação</strong> = (Vagas consolidadas / Vagas previstas) × 100</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="cardClienteDestaquesOperacionais" class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm overflow-hidden">
                <div class="border-b border-pgu-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col items-stretch gap-5 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
                        <div class="flex min-w-0 flex-1 items-start gap-3 sm:gap-4">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-sm ring-1 ring-brand-burgundy/15">
                                <i data-lucide="megaphone" class="h-6 w-6"></i>
                            </span>
                            <div class="min-w-0 flex-1 pr-0 lg:max-w-[calc(100%-28rem)] lg:pr-4">
                                <h2 class="text-[44px] font-black leading-none text-pgu-ink">5. Destaques Operacionais do Ciclo</h2>
                                <p class="mt-2 max-w-3xl text-base leading-snug text-pgu-muted sm:text-lg">
                                    Principais avanços, movimentações e pontos de atenção da mobilização PGU no ciclo contratual. Visão executiva do que mais impactou o avanço da consolidação até o momento.
                                </p>
                            </div>
                        </div>
                        <div class="flex w-full shrink-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-end lg:w-auto">
                            <div class="w-full overflow-hidden rounded-2xl border border-pgu-border bg-white shadow-sm sm:max-w-md lg:max-w-[min(100%,28rem)]">
                                <div class="grid grid-cols-3 divide-x divide-pgu-border border-b border-pgu-border">
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="clipboard-list" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Contrato</p>
                                            <p class="mt-0.5 truncate text-lg font-black text-brand-burgundy sm:text-xl" x-text="clienteCicloResumo().contrato"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="calendar-days" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Competência</p>
                                            <p class="mt-0.5 truncate text-lg font-black text-brand-burgundy sm:text-xl" x-text="cicloPeriodoLabel()"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="calendar-clock" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Data limite</p>
                                            <p class="mt-0.5 text-lg font-black text-brand-burgundy sm:text-xl" x-text="clienteCicloResumo().dataLimite"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-pgu-border">
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="target" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium leading-tight text-pgu-muted">Progresso consolidado do ciclo</p>
                                            <p class="mt-0.5 text-lg font-black text-brand-burgundy sm:text-xl" x-text="`${formatPctPtBr(clienteProgressoConsolidadoCicloPct())}%`"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="clock-3" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Dias restantes</p>
                                            <p class="mt-0.5 text-lg font-black text-brand-burgundy sm:text-xl"><span x-text="formatQtyPtBr(clienteCicloResumo().diasRestantes)"></span> dias</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative flex shrink-0 justify-end text-pgu-muted sm:flex-col sm:items-end">
                                <button type="button" @click="clienteDestaquesInfo()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Sobre o indicador">
                                    <i data-lucide="info" class="h-5 w-5"></i>
                                </button>
                                <button type="button" @click="exportClienteDestaques()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Exportar gráfico (PNG)">
                                    <i data-lucide="download" class="h-5 w-5"></i>
                                </button>
                                <button type="button" @click="toggleClienteDestaquesMenu()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Mais ações">
                                    <i data-lucide="ellipsis-vertical" class="h-5 w-5"></i>
                                </button>
                                <div x-show="clienteDestaquesMenuOpen" @click.outside="closeClienteDestaquesMenu()" x-cloak class="absolute right-0 top-10 z-20 w-56 overflow-hidden rounded-xl border border-pgu-border bg-white shadow-lg">
                                    <button type="button" @click="clienteDestaquesInfo(); closeClienteDestaquesMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">Ver descrição do indicador</button>
                                    <button type="button" @click="exportClienteDestaques(); closeClienteDestaquesMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">Exportar evolução (PNG)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 px-4 py-5 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7">
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="line-chart" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas consolidadas</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteCicloResumo().consolidadas)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] font-semibold text-brand-burgundy" x-text="`${clienteDestaquesDeltaUltimoPeriodo().dCons >= 0 ? '+' : '−'}${formatQtyPtBr(Math.abs(clienteDestaquesDeltaUltimoPeriodo().dCons))} desde o início do ciclo`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="users-round" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas preenchidas</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeEtapas()[0]?.value ?? 0)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] font-semibold text-brand-burgundy" x-text="`${clienteDestaquesDeltaUltimoPeriodo().dRecrutamento >= 0 ? '+' : '−'}${formatQtyPtBr(Math.abs(clienteDestaquesDeltaUltimoPeriodo().dRecrutamento))} desde o início do ciclo`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="stethoscope" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Exame médico</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeEtapas()[1]?.value ?? 0)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] font-semibold text-brand-burgundy" x-text="`${clienteDestaquesDeltaUltimoPeriodo().dExameMedico >= 0 ? '+' : '−'}${formatQtyPtBr(Math.abs(clienteDestaquesDeltaUltimoPeriodo().dExameMedico))} desde o início do ciclo`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="badge-check" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Treinamentos</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteDestaquesTreinamentosCount())"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] font-semibold text-brand-burgundy" x-text="`${clienteDestaquesDeltaUltimoPeriodo().dTreinamentos >= 0 ? '+' : '−'}${formatQtyPtBr(Math.abs(clienteDestaquesDeltaUltimoPeriodo().dTreinamentos))} desde o início do ciclo`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="file-signature" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Assinatura documental</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeEtapas()[3]?.value ?? 0)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] font-semibold text-brand-burgundy" x-text="`${clienteDestaquesDeltaUltimoPeriodo().dAssinaturaDocumental >= 0 ? '+' : '−'}${formatQtyPtBr(Math.abs(clienteDestaquesDeltaUltimoPeriodo().dAssinaturaDocumental))} desde o início do ciclo`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="shield-check" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">SGC concluído</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeEtapas()[4]?.value ?? 0)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] font-semibold text-brand-burgundy" x-text="`${clienteDestaquesDeltaUltimoPeriodo().dSgc >= 0 ? '+' : '−'}${formatQtyPtBr(Math.abs(clienteDestaquesDeltaUltimoPeriodo().dSgc))} desde o início do ciclo`"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3 sm:col-span-2 lg:col-span-1">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="flag" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas liberadas</p>
                                    <p class="mt-1 text-3xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteMaturidadeEtapas()[5]?.value ?? 0)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] font-semibold text-brand-burgundy" x-text="`${clienteDestaquesDeltaUltimoPeriodo().dLib >= 0 ? '+' : '−'}${formatQtyPtBr(Math.abs(clienteDestaquesDeltaUltimoPeriodo().dLib))} desde o início do ciclo`"></p>
                        </div>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-3">
                        <div class="rounded-xl border border-pgu-border bg-white p-4">
                            <div class="flex items-center gap-2 text-brand-burgundy">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-brand-burgundy/25 bg-brand-burgundy-soft">
                                    <i data-lucide="arrow-up-right" class="h-4 w-4 shrink-0"></i>
                                </span>
                                <p class="text-sm font-black uppercase tracking-wide">Principais avanços do ciclo</p>
                            </div>
                            <ul class="mt-4 space-y-3">
                                <template x-for="(item, idx) in clienteDestaquesPrincipaisAvancos()" :key="`avanco-${idx}`">
                                    <li class="flex gap-2.5 border-b border-pgu-border/80 pb-3 text-[13px] leading-snug last:border-0 last:pb-0">
                                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-burgundy text-white">
                                            <i data-lucide="check" class="h-3 w-3 stroke-2"></i>
                                        </span>
                                        <div>
                                            <p class="font-bold text-pgu-ink" x-text="item.titulo"></p>
                                            <p class="mt-0.5 text-pgu-muted" x-text="item.desc"></p>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white p-4">
                            <div class="flex items-center gap-2 text-brand-burgundy">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-brand-burgundy/25 bg-brand-burgundy-soft">
                                    <i data-lucide="history" class="h-4 w-4 shrink-0"></i>
                                </span>
                                <p class="text-sm font-black uppercase tracking-wide">Movimentações recentes</p>
                            </div>
                            <div class="mt-3 overflow-hidden rounded-lg border border-pgu-border">
                                <table class="w-full min-w-[320px] border-collapse text-left text-[13px]">
                                    <thead>
                                        <tr class="bg-brand-burgundy text-white">
                                            <th class="px-3 py-3 text-left text-[10px] font-black uppercase tracking-wide">Data</th>
                                            <th class="px-3 py-3 text-left text-[10px] font-black uppercase tracking-wide">Movimentação</th>
                                            <th class="px-3 py-3 text-center text-[10px] font-black uppercase tracking-wide">Quantidade</th>
                                            <th class="px-3 py-3 text-center text-[10px] font-black uppercase tracking-wide">Impacto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, idx) in clienteDestaquesMovimentacoesRows()" :key="`mov-${idx}-${row.data}`">
                                            <tr
                                                class="border-b border-pgu-border/60 last:border-b-0"
                                                :class="idx % 2 === 1 ? 'bg-zinc-50/90' : 'bg-white'"
                                            >
                                                <td class="px-3 py-2.5 tabular-nums text-pgu-muted" x-text="row.data"></td>
                                                <td class="px-3 py-2.5 font-medium text-pgu-ink" x-text="row.mov"></td>
                                                <td class="px-3 py-2.5 text-center tabular-nums font-semibold text-pgu-ink" x-text="row.qtd"></td>
                                                <td class="px-3 py-2.5 text-center">
                                                    <span
                                                        class="inline-flex items-center justify-center gap-0.5 font-semibold tabular-nums"
                                                        :class="row.impactoPos ? 'text-brand-burgundy' : 'text-pgu-muted'"
                                                    >
                                                        <i data-lucide="arrow-up" class="h-3.5 w-3.5 shrink-0" x-show="row.impactoPos"></i>
                                                        <i data-lucide="arrow-down" class="h-3.5 w-3.5 shrink-0" x-show="!row.impactoPos"></i>
                                                        <span x-text="row.impacto"></span>
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-3 text-center text-[11px] text-pgu-muted" x-text="clienteDestaquesMovimentacoesFooter()"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white p-4">
                            <div class="flex items-center gap-2 text-brand-burgundy">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-brand-burgundy/25 bg-brand-burgundy-soft">
                                    <i data-lucide="triangle-alert" class="h-4 w-4 shrink-0"></i>
                                </span>
                                <p class="text-sm font-black uppercase tracking-wide">Pontos de atenção</p>
                            </div>
                            <ul class="mt-4 space-y-3">
                                <template x-for="(pt, idx) in clienteDestaquesPontosAtencao()" :key="`at-${idx}`">
                                    <li class="flex gap-2.5 border-b border-pgu-border/80 pb-3 text-[13px] leading-snug last:border-0 last:pb-0">
                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-burgundy"></span>
                                        <div>
                                            <p class="font-bold text-pgu-ink" x-text="pt.titulo"></p>
                                            <p class="mt-0.5 text-pgu-muted" x-text="pt.texto"></p>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-3">
                        <div class="rounded-xl border border-pgu-border bg-white p-4">
                            <p class="text-sm font-black uppercase tracking-wide text-brand-burgundy">Evolução do período de consolidação</p>
                            <p class="mt-1 text-xs text-pgu-muted">Progresso médio (Pré/PGU) no intervalo do ciclo: início, posição atual e meta na data limite.</p>
                            <div id="chartClienteDestaquesLinha" class="mt-2 h-[240px] w-full min-h-[200px]"></div>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white p-4">
                            <p class="text-sm font-black uppercase tracking-wide text-brand-burgundy">Contribuição das etapas para o avanço</p>
                            <p class="mt-1 text-xs text-pgu-muted">Distribuição aproximada do avanço consolidado entre etapas de maturidade.</p>
                            <div class="mt-3 flex flex-col items-stretch gap-4 lg:flex-row lg:items-center">
                                <div class="relative mx-auto h-[220px] w-full max-w-[220px] shrink-0">
                                    <div id="chartClienteDestaquesDonut" class="absolute inset-0 h-full w-full"></div>
                                    <div class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center px-2 text-center select-none">
                                        <span class="text-lg font-black leading-tight text-brand-burgundy sm:text-xl" x-text="`+${formatPctPtBr(clienteProgressoConsolidadoCicloPct())} p.p.`"></span>
                                        <div class="mt-1 text-[10px] font-bold leading-tight text-pgu-muted sm:text-[11px]">
                                            <span class="block">Total de avanço</span>
                                            <span class="block">no ciclo</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 space-y-2.5">
                                    <template x-for="(sl, idx) in clienteDestaquesContribuicaoEtapas()" :key="`contrib-${idx}-${sl.label}`">
                                        <div class="flex items-start gap-2" x-show="sl.value > 0">
                                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full ring-1 ring-black/10" :style="`background-color:${sl.color}`"></span>
                                            <div class="min-w-0 text-[12px] leading-tight">
                                                <span class="font-semibold text-pgu-ink" x-text="sl.label"></span>
                                                <span class="text-pgu-muted"> — +</span>
                                                <span class="font-bold tabular-nums text-brand-burgundy" x-text="formatPctPtBr(sl.pp)"></span>
                                                <span class="text-pgu-muted"> p.p. (</span>
                                                <span class="tabular-nums font-semibold text-pgu-ink" x-text="formatPctPtBr(sl.pctShare)"></span>
                                                <span class="text-pgu-muted">%)</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white p-4">
                            <p class="text-sm font-black uppercase tracking-wide text-brand-burgundy">Resumo operacional do ciclo</p>
                            <div class="mt-4 rounded-xl bg-zinc-100/90 p-4">
                                <div class="flex items-center gap-3 rounded-lg bg-brand-burgundy-soft/90 px-4 py-3.5">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-brand-burgundy shadow-sm ring-1 ring-brand-burgundy/20">
                                        <i data-lucide="target" class="h-5 w-5"></i>
                                    </span>
                                    <p class="min-w-0 flex-1 text-[14px] font-semibold leading-snug text-pgu-ink" x-text="clienteDestaquesResumoOperacionalTexto()"></p>
                                </div>
                                <div class="mt-4 overflow-hidden rounded-lg border border-pgu-border/80 bg-white">
                                    <div class="flex items-center gap-4 border-b border-pgu-border/70 px-4 py-3.5 last:border-b-0">
                                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                            <i data-lucide="users-round" class="h-5 w-5"></i>
                                        </span>
                                        <span class="shrink-0 text-3xl font-black tabular-nums leading-none text-brand-burgundy sm:text-[2rem]" x-text="formatQtyPtBr(clienteCicloResumo().mapeadas)"></span>
                                        <p class="min-w-0 flex-1 text-[13px] font-medium leading-snug text-pgu-muted">Vagas mapeadas no PGU</p>
                                    </div>
                                    <div class="flex items-center gap-4 border-b border-pgu-border/70 px-4 py-3.5 last:border-b-0">
                                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                            <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                                        </span>
                                        <span class="shrink-0 text-3xl font-black tabular-nums leading-none text-brand-burgundy sm:text-[2rem]" x-text="formatQtyPtBr(clienteCicloResumo().consolidadas)"></span>
                                        <p class="min-w-0 flex-1 text-[13px] font-medium leading-snug text-pgu-muted">
                                            Vagas consolidadas (<span class="tabular-nums text-pgu-ink" x-text="formatPctPtBr(clienteProgressoConsolidadoCicloPct())"></span>%)
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4 px-4 py-3.5">
                                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                            <i data-lucide="refresh-cw" class="h-5 w-5"></i>
                                        </span>
                                        <span class="shrink-0 text-3xl font-black tabular-nums leading-none text-brand-burgundy sm:text-[2rem]" x-text="formatQtyPtBr(clienteCicloResumo().emEvolucao)"></span>
                                        <p class="min-w-0 flex-1 text-[13px] font-medium leading-snug text-pgu-muted">
                                            Vagas em evolução (<span class="tabular-nums text-pgu-ink" x-text="formatPctPtBr(clienteCicloResumo().mapeadas > 0 ? (clienteCicloResumo().emEvolucao / clienteCicloResumo().mapeadas) * 100 : 0)"></span>%)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 rounded-xl border border-pgu-border bg-zinc-100/90 px-4 py-5 sm:flex-row sm:items-center sm:px-6">
                        <div class="flex min-w-0 items-start gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-brand-burgundy shadow-sm ring-1 ring-pgu-border sm:h-12 sm:w-12">
                                <i data-lucide="sparkles" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                            </span>
                            <p class="min-w-0 text-base leading-relaxed text-pgu-ink sm:text-lg">
                                Seguimos com foco na conclusão de todas as fases até <strong class="text-brand-burgundy" x-text="clienteCicloResumo().dataLimite"></strong>, garantindo mobilização qualificada, segura e dentro do prazo contratual.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="cardClientePlanoAcompanhamento" class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm overflow-hidden">
                <div class="border-b border-pgu-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col items-stretch gap-5 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
                        <div class="flex min-w-0 flex-1 items-start gap-3 sm:gap-4">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-sm ring-1 ring-brand-burgundy/15">
                                <i data-lucide="calendar-range" class="h-6 w-6"></i>
                            </span>
                            <div class="min-w-0 flex-1 pr-0 lg:max-w-[calc(100%-28rem)] lg:pr-4">
                                <h2 class="text-[44px] font-black leading-none text-pgu-ink">6. Plano de Acompanhamento até a Data Limite</h2>
                                <p class="mt-2 max-w-3xl text-base leading-snug text-pgu-muted sm:text-lg">
                                    Roteiro de fases, responsabilidades e marcos até o encerramento do ciclo de mobilização, com projeção de conclusão e focos para cumprir 100% das etapas no prazo contratual.
                                </p>
                            </div>
                        </div>
                        <div class="flex w-full shrink-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-end lg:w-auto">
                            <div class="w-full overflow-hidden rounded-2xl border border-pgu-border bg-white shadow-sm sm:max-w-lg lg:max-w-[min(100%,32rem)]">
                                <div class="grid grid-cols-3 divide-x divide-pgu-border border-b border-pgu-border">
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="clipboard-list" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Contrato</p>
                                            <p class="mt-0.5 truncate text-base font-black text-brand-burgundy sm:text-lg" x-text="clienteCicloResumo().contrato"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="calendar-days" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Competência</p>
                                            <p class="mt-0.5 truncate text-base font-black text-brand-burgundy sm:text-lg" x-text="cicloPeriodoLabel()"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="calendar-clock" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Data limite</p>
                                            <p class="mt-0.5 text-base font-black text-brand-burgundy sm:text-lg" x-text="clientePlanoDataLimiteCompleta()"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-pgu-border">
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="target" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Progresso consolidado</p>
                                            <p class="mt-0.5 text-base font-black text-brand-burgundy sm:text-lg" x-text="`${formatPctPtBr(clienteProgressoConsolidadoCicloPct())}%`"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 px-3 py-3 sm:px-3.5">
                                        <i data-lucide="clock-3" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[11px] font-medium text-pgu-muted">Dias restantes</p>
                                            <p class="mt-0.5 text-base font-black text-brand-burgundy sm:text-lg"><span x-text="formatQtyPtBr(clienteCicloResumo().diasRestantes)"></span> dias</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="relative flex shrink-0 justify-end text-pgu-muted sm:flex-col sm:items-end">
                                <button type="button" @click="clientePlanoInfo()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Sobre o indicador"><i data-lucide="info" class="h-5 w-5"></i></button>
                                <button type="button" @click="exportClientePlano()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Exportar gráfico"><i data-lucide="download" class="h-5 w-5"></i></button>
                                <button type="button" @click="toggleClientePlanoMenu()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Mais ações"><i data-lucide="ellipsis-vertical" class="h-5 w-5"></i></button>
                                <div x-show="clientePlanoMenuOpen" @click.outside="closeClientePlanoMenu()" x-cloak class="absolute right-0 top-10 z-20 w-56 overflow-hidden rounded-xl border border-pgu-border bg-white shadow-lg">
                                    <button type="button" @click="clientePlanoInfo(); closeClientePlanoMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">Ver descrição</button>
                                    <button type="button" @click="exportClientePlano(); closeClientePlanoMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">Exportar semicírculo (PNG)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 px-4 py-5 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="users-round" class="h-5 w-5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas mapeadas</p>
                                    <p class="mt-1 text-2xl font-black text-brand-burgundy sm:text-3xl" x-text="formatQtyPtBr(clienteCicloResumo().mapeadas)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted">Total de vagas no PGU (panorama do ciclo).</p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="check-circle-2" class="h-5 w-5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas consolidadas</p>
                                    <p class="mt-1 text-2xl font-black text-brand-burgundy sm:text-3xl" x-text="formatQtyPtBr(clienteCicloResumo().consolidadas)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted" x-text="clienteCicloResumo().mapeadas > 0 ? `${formatPctPtBr((clienteCicloResumo().consolidadas / clienteCicloResumo().mapeadas) * 100)}% do total mapeado` : '—'"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="refresh-cw" class="h-5 w-5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Vagas em evolução</p>
                                    <p class="mt-1 text-2xl font-black text-brand-burgundy sm:text-3xl" x-text="formatQtyPtBr(clienteCicloResumo().emEvolucao)"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted" x-text="clienteCicloResumo().mapeadas > 0 ? `${formatPctPtBr((clienteCicloResumo().emEvolucao / clienteCicloResumo().mapeadas) * 100)}% do total mapeado` : '—'"></p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="shield-check" class="h-5 w-5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Fases concluídas</p>
                                    <p class="mt-1 text-2xl font-black text-brand-burgundy sm:text-3xl">
                                        <span x-text="formatQtyPtBr(clientePlanoFasesConcluidasContagem().concluidas)"></span>
                                        <span class="text-lg font-black text-pgu-muted"> de </span>
                                        <span x-text="formatQtyPtBr(clientePlanoFasesConcluidasContagem().total)"></span>
                                    </p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted">Com base nas etapas de maturidade PGU.</p>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 px-3 py-3 sm:col-span-2 lg:col-span-1">
                            <div class="flex items-start gap-2">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy"><i data-lucide="flag" class="h-5 w-5"></i></span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black uppercase leading-tight text-pgu-muted">Data limite</p>
                                    <p class="mt-1 text-lg font-black leading-tight text-brand-burgundy sm:text-xl" x-text="clientePlanoDataLimiteCompleta()"></p>
                                </div>
                            </div>
                            <p class="mt-2 text-[12px] text-pgu-muted">Conclusão de todas as fases do ciclo.</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-pgu-border bg-white p-4 sm:p-5">
                        <p class="text-sm font-black uppercase tracking-wide text-brand-burgundy">Roteiro do ciclo até a data limite</p>
                        <p class="mt-1 text-xs text-pgu-muted sm:text-sm">
                            Marcos alinhados aos prazos operacionais:
                            <strong class="text-pgu-ink" x-text="`${clienteCicloSlaReferencia().diasAceiteAteSgc} dias`"></strong>
                            até SGC e liberação em
                            <strong class="text-pgu-ink" x-text="clientePlanoSlaJanelaDiasLabel()"></strong>,
                            respeitando a data limite contratual.
                        </p>

                        <div class="mt-6 overflow-x-auto pb-1">
                            <div class="grid min-w-[1040px] w-full grid-cols-[repeat(6,minmax(0,1fr))_minmax(100px,112px)] items-start gap-x-2 sm:gap-x-3">
                                <template x-for="(ms, idx) in clientePlanoRoteiroEtapas()" :key="`roteiro-head-${ms.step}`">
                                    <div class="flex min-h-[5.25rem] min-w-0 flex-col items-center justify-start text-center">
                                        <p class="max-w-[min(100%,11rem)] text-[11px] font-black uppercase leading-tight text-pgu-ink sm:text-xs" x-text="`${ms.step}. ${ms.tituloRoteiro}`"></p>
                                        <p class="mt-1 max-w-[min(100%,11rem)] text-[11px] font-semibold text-pgu-ink" x-text="ms.statusTxt"></p>
                                        <p
                                            class="mt-2 text-xl font-black tabular-nums leading-none sm:text-2xl"
                                            :class="ms.pct > 0 ? 'text-brand-burgundy' : 'text-slate-400'"
                                            x-text="`${formatPctPtBr(ms.pct)}%`"
                                        ></p>
                                    </div>
                                </template>
                                <div class="min-h-[5.25rem] min-w-0" aria-hidden="true"></div>

                                <div class="relative col-span-6 w-full pt-0.5">
                                    <div class="pointer-events-none absolute left-0 right-0 top-[22px] z-0 h-2 rounded-full bg-zinc-200"></div>
                                    <div
                                        class="pointer-events-none absolute left-0 top-[22px] z-0 h-2 rounded-l-full bg-brand-burgundy transition-[width] duration-500"
                                        :style="`width: ${clientePlanoRoteiroLinhaPct()}`"
                                    ></div>
                                    <div class="relative z-[1] grid w-full grid-cols-6 gap-x-2 sm:gap-x-3">
                                        <template x-for="(ms, idx) in clientePlanoRoteiroEtapas()" :key="`roteiro-dot-${ms.step}`">
                                            <div class="flex flex-col items-center">
                                                <span
                                                    class="flex h-9 w-9 items-center justify-center rounded-full border-2 shadow-sm sm:h-10 sm:w-10"
                                                    :class="ms.statusKey === 'concluido'
                                                        ? 'border-brand-burgundy bg-brand-burgundy text-white'
                                                        : (ms.statusKey === 'andamento'
                                                            ? 'border-brand-burgundy bg-white'
                                                            : 'border-zinc-300 bg-white')"
                                                >
                                                    <i data-lucide="check" class="h-4 w-4 sm:h-5 sm:w-5" x-show="ms.statusKey === 'concluido'"></i>
                                                    <span class="h-2.5 w-2.5 rounded-full bg-brand-burgundy" x-show="ms.statusKey === 'andamento'"></span>
                                                </span>
                                                <p class="mt-2.5 max-w-[5.5rem] text-center text-[10px] font-semibold leading-tight text-pgu-muted sm:max-w-none sm:text-[11px]" x-text="ms.prazoAte"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex min-w-0 flex-col items-center border-l border-dashed border-pgu-border/80 pl-2 text-center sm:pl-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand-burgundy text-white shadow-sm ring-2 ring-brand-burgundy/20 sm:h-12 sm:w-12">
                                        <i data-lucide="flag" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                                    </span>
                                    <p class="mt-2 text-[10px] font-black uppercase leading-tight text-brand-burgundy">Data limite</p>
                                    <p class="mt-1 text-[11px] font-bold leading-snug text-pgu-ink" x-text="clientePlanoDataLimiteCompleta()"></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 flex gap-3 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft/80 px-3 py-3.5 sm:px-4">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-brand-burgundy shadow-sm ring-1 ring-brand-burgundy/15">
                                <i data-lucide="calendar-days" class="h-5 w-5"></i>
                            </span>
                            <p class="min-w-0 text-sm leading-relaxed text-pgu-ink sm:text-base">
                                Acompanhamento contínuo com janela operacional de liberação entre
                                <strong class="text-brand-burgundy" x-text="clientePlanoSlaJanelaDatasLabel()"></strong>
                                (<span x-text="clientePlanoSlaJanelaDiasLabel()"></span>) e compromisso contratual até
                                <strong class="text-brand-burgundy" x-text="clientePlanoDataLimiteCompleta()"></strong>.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,1fr)]">
                        <div class="min-w-0 rounded-xl border border-pgu-border bg-white p-4">
                            <p class="text-sm font-black uppercase tracking-wide text-brand-burgundy">Plano de ações e acompanhamento</p>
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full min-w-[640px] border-collapse text-left text-[13px]">
                                    <thead>
                                        <tr class="bg-brand-burgundy text-white">
                                            <th class="px-3 py-2.5 text-left text-[10px] font-black uppercase">Ação principal</th>
                                            <th class="px-3 py-2.5 text-left text-[10px] font-black uppercase">Descrição</th>
                                            <th class="px-3 py-2.5 text-left text-[10px] font-black uppercase">Responsável</th>
                                            <th class="px-3 py-2.5 text-left text-[10px] font-black uppercase">Prazo</th>
                                            <th class="px-3 py-2.5 text-left text-[10px] font-black uppercase">Status</th>
                                            <th class="px-3 py-2.5 text-left text-[10px] font-black uppercase">Impacto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, ridx) in clientePlanoAcoesRows()" :key="`plano-ac-${ridx}`">
                                            <tr class="border-b border-pgu-border/60 align-top last:border-b-0" :class="ridx % 2 === 0 ? 'bg-white' : 'bg-zinc-50/40'">
                                                <td class="px-3 py-2.5">
                                                    <div class="flex items-center gap-2.5 font-semibold text-pgu-ink">
                                                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full" :class="row.iconClass || 'bg-zinc-100 text-pgu-ink'">
                                                            <i :data-lucide="row.icon || 'list-todo'" class="h-3.5 w-3.5"></i>
                                                        </span>
                                                        <span x-text="row.acao"></span>
                                                    </div>
                                                </td>
                                                <td class="max-w-xs px-3 py-2.5 text-[12px] text-pgu-muted" x-text="row.desc"></td>
                                                <td class="px-3 py-2.5 text-[12px] font-medium text-pgu-ink" x-text="row.resp"></td>
                                                <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-pgu-ink" x-text="row.prazo"></td>
                                                <td class="px-3 py-2.5">
                                                    <span class="inline-flex h-6 w-[98px] items-center justify-center whitespace-nowrap rounded-full px-2.5 text-[11px] font-bold leading-none ring-1" :class="row.pill.class" x-text="row.pill.text"></span>
                                                </td>
                                                <td class="px-3 py-2.5 font-semibold" :class="row.impacto === 'Crítico' ? 'text-red-700' : 'text-pgu-ink'" x-text="row.impacto"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-pgu-border bg-zinc-50/70 p-4 shadow-sm">
                                <p class="text-[12px] font-black uppercase tracking-wide text-brand-burgundy">Projeção de conclusão</p>

                                <div class="relative mx-auto mt-2 h-[210px] w-full max-w-[320px]">
                                    <div id="chartClientePlanoSemiDonut" class="absolute inset-0 h-full w-full"></div>
                                    <div class="pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-end pb-8 text-center select-none">
                                        <span class="text-[32px] font-black leading-none text-brand-burgundy" x-text="`${formatPctPtBr(clienteProgressoConsolidadoCicloPct())}%`"></span>
                                        <span class="mt-1 text-[14px] font-bold leading-none text-pgu-ink">Progresso atual</span>
                                    </div>
                                </div>

                                <ul class="mt-2 space-y-3 border-t border-pgu-border/90 pt-3">
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-brand-burgundy-soft text-brand-burgundy">
                                            <i data-lucide="calendar-days" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-pgu-muted">Dias restantes</p>
                                            <p class="mt-0.5 text-[24px] font-black leading-none text-brand-burgundy">
                                                <span x-text="formatQtyPtBr(clienteCicloResumo().diasRestantes)"></span>
                                                <span class="text-[17px]"> dias</span>
                                            </p>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-brand-burgundy-soft text-brand-burgundy">
                                            <i data-lucide="chart-column" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-pgu-muted">Projeção de conclusão</p>
                                            <p class="mt-0.5 text-[24px] font-black leading-none text-brand-burgundy" x-text="clientePlanoDataLimiteCompleta()"></p>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-brand-burgundy-soft text-brand-burgundy">
                                            <i data-lucide="shield-check" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0 leading-tight">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-pgu-muted">Situação do ciclo</p>
                                            <p
                                                class="mt-1 inline-flex rounded-lg px-2.5 py-1 text-[14px] font-black leading-none"
                                                :class="clienteCicloResumo().situacaoNoPrazo ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'bg-amber-50 text-amber-700'"
                                                x-text="clientePlanoSituacaoCicloLabel()"
                                            ></p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-zinc-50/70 p-4">
                                <p class="text-sm font-black uppercase tracking-wide text-brand-burgundy">Focos para garantir a conclusão</p>
                                <ul class="mt-2 divide-y divide-pgu-border/80 overflow-hidden rounded-lg border border-pgu-border/70 bg-white">
                                    <template x-for="(fc, fidx) in clientePlanoFocosLista()" :key="`foco-${fidx}`">
                                        <li class="flex items-start gap-2.5 px-3 py-2.5 text-[12px] leading-snug text-pgu-ink">
                                            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-brand-burgundy">
                                                <i :data-lucide="fc.icon || 'check-circle-2'" class="h-4 w-4"></i>
                                            </span>
                                            <span class="pt-0.5" x-text="fc.texto"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 rounded-xl bg-brand-burgundy px-4 py-4 text-white sm:flex-row sm:items-center sm:px-5">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/30"><i data-lucide="trophy" class="h-5 w-5"></i></span>
                            <p class="min-w-0 text-[13px] leading-relaxed">
                                Nosso compromisso é concluir <strong>100% das fases do ciclo</strong> até <strong x-text="clientePlanoDataLimiteCompleta()"></strong>, com mobilização qualificada, segura e alinhada ao contrato.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>
@endsection
