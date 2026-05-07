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

        <section class="rounded-2xl border border-pgu-border bg-white p-2 shadow-sm">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button type="button" @click="setVisaoAba('diretoria')"
                    :class="visaoAba === 'diretoria' ? 'bg-pgu-primary text-white' : 'bg-zinc-50 text-pgu-ink hover:bg-zinc-100'"
                    class="rounded-xl px-4 py-3 text-sm font-semibold transition">
                    Visão Diretoria
                </button>
                <button type="button" @click="setVisaoAba('cliente')"
                    :class="visaoAba === 'cliente' ? 'bg-pgu-primary text-white' : 'bg-zinc-50 text-pgu-ink hover:bg-zinc-100'"
                    class="rounded-xl px-4 py-3 text-sm font-semibold transition">
                    Visão Cliente
                </button>
            </div>
        </section>

        <div x-show="visaoAba === 'diretoria'" class="space-y-6">
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

        <div x-show="visaoAba === 'cliente'" class="space-y-6" x-cloak>
            <section class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-pgu-border px-6 py-5">
                    <div>
                        <h2 class="text-[44px] font-black leading-none text-pgu-ink">Panorama Executivo do PGU</h2>
                        <p class="mt-2 text-lg text-pgu-muted">Cobertura e consolidação da base funcional do contrato</p>
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
                        <div class="flex justify-center">
                            <div id="chartClientePanorama" class="h-[460px] w-[540px] max-w-full"></div>
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

            <section class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-pgu-border px-6 py-5">
                    <div>
                        <h2 class="text-4xl font-black leading-none text-pgu-ink">2. EVOLUÇÃO DA BASE FUNCIONAL</h2>
                        <p class="mt-2 text-lg text-pgu-muted">Acompanhamento da evolução mensal da base funcional do contrato, com foco na atualização, organização e fortalecimento das informações no PGU.</p>
                    </div>
                    <div class="relative flex items-center gap-3 text-pgu-muted">
                        <div class="inline-flex items-center gap-2 rounded-xl border border-pgu-border bg-zinc-50 px-4 py-3 text-brand-burgundy">
                            <i data-lucide="calendar-days" class="h-5 w-5"></i>
                            <div class="text-left">
                                <p class="text-xs font-semibold uppercase tracking-wide text-pgu-muted">Competência</p>
                                <p class="text-sm font-black" x-text="competenciaLabelVisaoCliente()"></p>
                            </div>
                        </div>
                        <button type="button" @click="clienteEvolucaoInfo()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Sobre o indicador">
                            <i data-lucide="info" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="exportClienteEvolucao()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Exportar gráfico como PNG">
                            <i data-lucide="download" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="toggleClienteEvolucaoMenu()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Mais ações">
                            <i data-lucide="ellipsis-vertical" class="h-5 w-5"></i>
                        </button>
                        <div x-show="clienteEvolucaoMenuOpen" @click.outside="closeClienteEvolucaoMenu()" x-cloak class="absolute right-0 top-10 z-20 w-56 overflow-hidden rounded-xl border border-pgu-border bg-white shadow-lg">
                            <button type="button" @click="clienteEvolucaoInfo(); closeClienteEvolucaoMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Ver descrição do indicador
                            </button>
                            <button type="button" @click="exportClienteEvolucao()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Exportar gráfico (PNG)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="border-b border-pgu-border p-6">
                    <p class="mb-3 text-sm font-black uppercase tracking-wide text-brand-burgundy">Evolução mensal da base funcional</p>
                    <div id="chartClienteEvolucao" class="h-[380px] w-full"></div>
                </div>

                <div class="grid gap-3 px-6 py-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="users-round" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-pgu-muted">Funções mapeadas</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteEvolucaoResumo().mapeadas)"></p>
                                <p class="text-sm text-pgu-muted">Base total identificada no contrato</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="award" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-pgu-muted">Funções consolidadas</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteEvolucaoResumo().consolidadas)"></p>
                                <p class="text-sm text-pgu-muted">Informações estruturadas e validadas</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-zinc-200 text-zinc-600">
                                <i data-lucide="loader-circle" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-pgu-muted">Funções em evolução</p>
                                <p class="text-4xl font-black text-zinc-600" x-text="formatQtyPtBr(clienteEvolucaoResumo().emEvolucao)"></p>
                                <p class="text-sm text-pgu-muted">Em atualização e consolidação</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="trending-up" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-pgu-muted">Índice de evolução</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="`${formatPctPtBr(clienteEvolucaoResumo().indice)}%`"></p>
                                <p class="text-sm text-pgu-muted">Evolução da base funcional no mês</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                <i data-lucide="crosshair" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-pgu-muted">Base 100% monitorada</p>
                                <p class="text-4xl font-black text-emerald-700" x-text="`${formatPctPtBr(clienteEvolucaoResumo().coberturaMonitorada)}%`"></p>
                                <p class="text-sm text-pgu-muted">Todas as funções acompanhadas no PGU</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-pgu-border bg-zinc-50/60 px-6 py-4">
                    <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-emerald-900">
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                        <p class="text-base">
                            A base funcional do contrato apresenta evolução consistente, com avanço contínuo na consolidação das informações e fortalecimento da gestão.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-pgu-border px-6 py-5">
                    <div>
                        <h2 class="text-4xl font-black leading-none text-pgu-ink">3. COBERTURA OPERACIONAL MONITORADA</h2>
                        <p class="mt-2 text-lg text-pgu-muted">Todas as funções do contrato estão no radar de acompanhamento do PGU, garantindo gestão ativa, rastreabilidade e controle contínuo das informações.</p>
                    </div>
                    <div class="relative flex items-center gap-3 text-pgu-muted">
                        <div class="inline-flex items-center gap-3 rounded-xl border border-pgu-border bg-zinc-50 px-4 py-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                <i data-lucide="shield-check" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-pgu-muted">Cobertura Atual</p>
                                <p class="text-4xl font-black text-emerald-700" x-text="`${formatPctPtBr(clienteCoberturaResumo().coberturaOperacional)}%`"></p>
                                <p class="text-sm text-pgu-muted">Funções Monitoradas</p>
                            </div>
                        </div>
                        <button type="button" @click="clienteCoberturaInfo()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Sobre o indicador">
                            <i data-lucide="info" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="exportClienteCobertura()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Exportar gráfico como PNG">
                            <i data-lucide="download" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="toggleClienteCoberturaMenu()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Mais ações">
                            <i data-lucide="ellipsis-vertical" class="h-5 w-5"></i>
                        </button>
                        <div x-show="clienteCoberturaMenuOpen" @click.outside="closeClienteCoberturaMenu()" x-cloak class="absolute right-0 top-10 z-20 w-56 overflow-hidden rounded-xl border border-pgu-border bg-white shadow-lg">
                            <button type="button" @click="clienteCoberturaInfo(); closeClienteCoberturaMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Ver descrição do indicador
                            </button>
                            <button type="button" @click="exportClienteCobertura()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Exportar gráfico (PNG)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-0 lg:grid-cols-[58%_42%]">
                    <div class="border-b border-r border-pgu-border p-6 lg:border-b-0">
                        <p class="mb-3 text-2xl font-black text-brand-burgundy">COBERTURA GERAL DO CONTRATO</p>
                        <div id="chartClienteCoberturaDonut" class="h-[360px] w-full"></div>
                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-sm text-emerald-900">
                            <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0"></i>
                            <p>Todas as funções do contrato estão sendo acompanhadas de forma ativa no PGU.</p>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-2">
                                <p class="text-[11px] font-black uppercase tracking-wide text-pgu-muted">Funções mapeadas</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteCoberturaResumo().mapeadas)"></p>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-2">
                                <p class="text-[11px] font-black uppercase tracking-wide text-pgu-muted">Funções monitoradas</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="formatQtyPtBr(clienteCoberturaResumo().monitoradas)"></p>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-2">
                                <p class="text-[11px] font-black uppercase tracking-wide text-pgu-muted">Cobertura atual</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="`${formatPctPtBr(clienteCoberturaResumo().coberturaOperacional)}%`"></p>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-2">
                                <p class="text-[11px] font-black uppercase tracking-wide text-pgu-muted">Evolução da cobertura</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="`${formatPctPtBr(data?.summary?.progress_delta ?? 0)} p.p.`"></p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="mb-3 text-2xl font-black text-brand-burgundy">COBERTURA POR GRUPOS DE FUNÇÕES</p>
                        <div class="overflow-hidden rounded-xl border border-pgu-border">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-zinc-50 text-[11px] font-black uppercase tracking-wide text-pgu-muted">
                                    <tr>
                                        <th class="px-3 py-2">Grupo de Funções</th>
                                        <th class="px-3 py-2 text-center">Funções Mapeadas</th>
                                        <th class="px-3 py-2 text-center">Funções Monitoradas</th>
                                        <th class="px-3 py-2">Cobertura</th>
                                        <th class="px-3 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-pgu-border">
                                    <template x-for="grupo in clienteCoberturaGrupos()" :key="grupo.grupo">
                                        <tr>
                                            <td class="px-3 py-2.5 font-semibold text-pgu-ink" x-text="grupo.grupo"></td>
                                            <td class="px-3 py-2.5 text-center font-bold text-pgu-ink" x-text="formatQtyPtBr(grupo.mapeadas)"></td>
                                            <td class="px-3 py-2.5 text-center font-bold text-pgu-ink" x-text="formatQtyPtBr(grupo.monitoradas)"></td>
                                            <td class="px-3 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-12 text-sm font-black text-emerald-700" x-text="`${formatPctPtBr(grupo.cobertura)}%`"></span>
                                                    <div class="h-2.5 flex-1 rounded-full bg-zinc-200">
                                                        <div class="h-2.5 rounded-full bg-emerald-700" :style="`width:${Math.max(0, Math.min(100, grupo.cobertura))}%`"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    <span x-text="grupo.status"></span>
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr class="bg-zinc-50/80">
                                        <td class="px-3 py-2.5 font-black text-pgu-ink">TOTAL GERAL</td>
                                        <td class="px-3 py-2.5 text-center font-black text-pgu-ink" x-text="formatQtyPtBr(clienteCoberturaResumo().mapeadas)"></td>
                                        <td class="px-3 py-2.5 text-center font-black text-pgu-ink" x-text="formatQtyPtBr(clienteCoberturaResumo().monitoradas)"></td>
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="w-12 text-sm font-black text-emerald-700" x-text="`${formatPctPtBr(clienteCoberturaResumo().coberturaOperacional)}%`"></span>
                                                <div class="h-2.5 flex-1 rounded-full bg-zinc-200">
                                                    <div class="h-2.5 rounded-full bg-emerald-700" :style="`width:${Math.max(0, Math.min(100, clienteCoberturaResumo().coberturaOperacional))}%`"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Monitorado
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="border-t border-pgu-border bg-zinc-50/60 px-6 py-4">
                    <div class="grid gap-3 lg:grid-cols-[2fr_3fr]">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-emerald-700">
                                    <i data-lucide="crosshair" class="h-5 w-5"></i>
                                </span>
                                <div>
                                    <p class="text-lg font-black uppercase tracking-wide text-emerald-800">Gestão ativa e contínua</p>
                                    <p class="text-sm text-emerald-900/90">A cobertura 100% demonstra o compromisso com acompanhamento integral e suporte às decisões com base em informações confiáveis.</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-4">
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-3 text-center">
                                <i data-lucide="eye" class="mx-auto h-5 w-5 text-emerald-700"></i>
                                <p class="mt-1 text-sm font-bold text-emerald-800">Visibilidade Completa</p>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-3 text-center">
                                <i data-lucide="shield-check" class="mx-auto h-5 w-5 text-emerald-700"></i>
                                <p class="mt-1 text-sm font-bold text-emerald-800">Rastreabilidade Garantida</p>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-3 text-center">
                                <i data-lucide="trending-up" class="mx-auto h-5 w-5 text-emerald-700"></i>
                                <p class="mt-1 text-sm font-bold text-emerald-800">Gestão Preventiva</p>
                            </div>
                            <div class="rounded-xl border border-pgu-border bg-white px-3 py-3 text-center">
                                <i data-lucide="award" class="mx-auto h-5 w-5 text-emerald-700"></i>
                                <p class="mt-1 text-sm font-bold text-emerald-800">Excelência Operacional</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.5rem] border border-pgu-border bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-pgu-border px-6 py-5">
                    <div>
                        <h2 class="text-4xl font-black leading-none text-pgu-ink">4. MAPA DE CONSOLIDAÇÃO POR FUNÇÃO</h2>
                        <p class="mt-2 text-lg text-pgu-muted">Visão consolidada do status da base funcional por função, demonstrando o avanço contínuo da estruturação e organização das informações no PGU.</p>
                    </div>
                    <div class="relative flex items-center gap-3 text-pgu-muted">
                        <div class="inline-flex items-center gap-3 rounded-xl border border-pgu-border bg-zinc-50 px-4 py-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="target" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-pgu-muted">Progresso Geral de Consolidação</p>
                                <p class="text-4xl font-black text-brand-burgundy" x-text="`${formatPctPtBr(clienteConsolidacaoResumo().indice)}%`"></p>
                                <p class="text-sm text-pgu-muted">Base consolidada do contrato</p>
                            </div>
                        </div>
                        <button type="button" @click="clienteMapaInfo()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Sobre o indicador">
                            <i data-lucide="info" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="exportClienteMapa()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Exportar gráfico como PNG">
                            <i data-lucide="download" class="h-5 w-5"></i>
                        </button>
                        <button type="button" @click="toggleClienteMapaMenu()" class="rounded-lg p-2 transition hover:bg-zinc-100 hover:text-pgu-ink" title="Mais ações">
                            <i data-lucide="ellipsis-vertical" class="h-5 w-5"></i>
                        </button>
                        <div x-show="clienteMapaMenuOpen" @click.outside="closeClienteMapaMenu()" x-cloak class="absolute right-0 top-10 z-20 w-56 overflow-hidden rounded-xl border border-pgu-border bg-white shadow-lg">
                            <button type="button" @click="clienteMapaInfo(); closeClienteMapaMenu()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Ver descrição do indicador
                            </button>
                            <button type="button" @click="exportClienteMapa()" class="block w-full px-4 py-2.5 text-left text-sm text-pgu-ink transition hover:bg-zinc-50">
                                Exportar gráfico (PNG)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 p-4 lg:grid-cols-[22%_58%_20%]">
                    <aside class="space-y-3">
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 p-3">
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Resumo Geral</p>
                            <div class="mt-3 space-y-3">
                                <div class="flex items-center justify-between"><span class="text-sm text-pgu-muted">Funções mapeadas</span><strong class="text-2xl text-pgu-ink" x-text="formatQtyPtBr(clienteConsolidacaoResumo().mapeadas)"></strong></div>
                                <div class="flex items-center justify-between"><span class="text-sm text-pgu-muted">Funções consolidadas</span><strong class="text-2xl text-emerald-700" x-text="formatQtyPtBr(clienteConsolidacaoResumo().consolidadas)"></strong></div>
                                <div class="flex items-center justify-between"><span class="text-sm text-pgu-muted">Funções em evolução</span><strong class="text-2xl text-amber-600" x-text="formatQtyPtBr(clienteConsolidacaoResumo().emEvolucao)"></strong></div>
                                <div class="flex items-center justify-between"><span class="text-sm text-pgu-muted">Cobertura monitorada</span><strong class="text-2xl text-emerald-700" x-text="`${formatPctPtBr(clienteConsolidacaoResumo().coberturaMonitorada)}%`"></strong></div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white p-3">
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Legenda de Status</p>
                            <div class="mt-3 space-y-2 text-sm">
                                <p><span class="mr-2 inline-block h-2.5 w-2.5 rounded bg-emerald-700"></span>Consolidada</p>
                                <p><span class="mr-2 inline-block h-2.5 w-2.5 rounded bg-amber-400"></span>Em evolução</p>
                                <p><span class="mr-2 inline-block h-2.5 w-2.5 rounded bg-zinc-400"></span>Não aplicável</p>
                            </div>
                        </div>
                    </aside>

                    <div class="rounded-xl border border-pgu-border bg-white p-3">
                        <p class="mb-3 text-lg font-black text-brand-burgundy">STATUS DE CONSOLIDAÇÃO POR FUNÇÃO</p>
                        <div class="overflow-hidden rounded-lg border border-pgu-border">
                            <table class="min-w-full text-sm">
                                <thead class="bg-zinc-50 text-[11px] font-black uppercase tracking-wide text-pgu-muted">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Função</th>
                                        <th class="px-3 py-2 text-center">Total de funções</th>
                                        <th class="px-3 py-2 text-center text-emerald-700">Consolidadas</th>
                                        <th class="px-3 py-2 text-center text-amber-600">Em evolução</th>
                                        <th class="px-3 py-2 text-center">Índice de consolidação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-pgu-border">
                                    <template x-for="row in clienteConsolidacaoRows()" :key="row.funcao">
                                        <tr>
                                            <td class="px-3 py-2.5 font-semibold text-pgu-ink" x-text="row.funcao"></td>
                                            <td class="px-3 py-2.5 text-center font-bold text-pgu-ink" x-text="formatQtyPtBr(row.total)"></td>
                                            <td class="px-3 py-2.5">
                                                <div class="h-7 rounded-md bg-zinc-200">
                                                    <div class="h-7 rounded-l-md bg-emerald-700 text-center text-xs font-bold leading-7 text-white" :style="`width:${row.total > 0 ? (row.consolidadas / row.total) * 100 : 0}%`" x-text="formatQtyPtBr(row.consolidadas)"></div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <div class="h-7 rounded-md bg-zinc-200">
                                                    <div class="h-7 rounded-r-md bg-amber-400 text-center text-xs font-bold leading-7 text-zinc-900" :style="`width:${row.total > 0 ? (row.emEvolucao / row.total) * 100 : 0}%`" x-text="formatQtyPtBr(row.emEvolucao)"></div>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2.5 text-center font-black text-emerald-700" x-text="`${formatPctPtBr(row.indice)}%`"></td>
                                        </tr>
                                    </template>
                                    <tr class="bg-zinc-50/80">
                                        <td class="px-3 py-2.5 font-black text-pgu-ink">TOTAL GERAL</td>
                                        <td class="px-3 py-2.5 text-center font-black text-pgu-ink" x-text="formatQtyPtBr(clienteConsolidacaoResumo().mapeadas)"></td>
                                        <td class="px-3 py-2.5 text-center font-black text-pgu-ink" x-text="formatQtyPtBr(clienteConsolidacaoResumo().consolidadas)"></td>
                                        <td class="px-3 py-2.5 text-center font-black text-pgu-ink" x-text="formatQtyPtBr(clienteConsolidacaoResumo().emEvolucao)"></td>
                                        <td class="px-3 py-2.5 text-center font-black text-emerald-700" x-text="`${formatPctPtBr(clienteConsolidacaoResumo().indice)}%`"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <aside class="space-y-3">
                        <div class="rounded-xl border border-pgu-border bg-zinc-50/70 p-3">
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Distribuição do Status</p>
                            <div id="chartClienteMapaDonut" class="mt-2 h-[240px] w-full"></div>
                        </div>
                        <div class="rounded-xl border border-pgu-border bg-white p-3">
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Evolução da Consolidação</p>
                            <div class="mt-3 rounded-xl bg-zinc-50 px-3 py-3 text-center">
                                <p class="text-3xl font-black text-emerald-700" x-text="`${clienteConsolidacaoResumo().delta >= 0 ? '+' : ''}${formatPctPtBr(clienteConsolidacaoResumo().delta)} p.p.`"></p>
                                <p class="text-xs text-pgu-muted">Evolução no mês</p>
                            </div>
                        </div>
                    </aside>
                </div>

                <div class="grid gap-3 border-t border-pgu-border bg-zinc-50/60 px-6 py-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <p class="text-lg font-black text-emerald-800">INSIGHTS PRINCIPAIS</p>
                        <p class="mt-1 text-sm text-pgu-muted">Mais da metade da base funcional já está consolidada.</p>
                    </div>
                    <div class="rounded-xl border border-pgu-border bg-white px-4 py-3">
                        <p class="text-lg font-black text-emerald-800">DESTAQUES DO MÊS</p>
                        <p class="mt-1 text-sm text-pgu-muted" x-text="`Avanço de ${clienteConsolidacaoResumo().delta >= 0 ? '+' : ''}${formatPctPtBr(clienteConsolidacaoResumo().delta)} pontos percentuais na consolidação geral.`"></p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3">
                        <p class="text-lg font-black text-emerald-800">FOCO CONTÍNUO</p>
                        <p class="mt-1 text-sm text-emerald-900/90">Manter a evolução da base funcional com qualidade, consistência e alinhamento às necessidades do contrato.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
