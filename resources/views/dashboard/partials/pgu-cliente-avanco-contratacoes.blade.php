<section
    id="cardClienteContratacoes"
    class="overflow-hidden rounded-[1.5rem] border border-pgu-border bg-white shadow-sm"
    x-show="!loading && !error && data"
    x-cloak
>
    <div class="flex flex-col gap-4 border-b border-pgu-border bg-gradient-to-br from-white to-brand-burgundy-soft/25 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 flex-1 items-start gap-4">
            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-sm ring-1 ring-brand-burgundy/15">
                <i data-lucide="briefcase-business" class="h-6 w-6"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-[clamp(1.5rem,4vw,2.75rem)] font-black leading-tight text-brand-burgundy">
                    Avanço de Contratações — Contrato <span x-text="contrato || '—'"></span>
                </h2>
                <p class="mt-2 text-base text-pgu-muted sm:text-lg">
                    Distribuição das vagas em processo de contratação por etapa do funil (candidatos aprovados, fase atual na ficha RH).
                </p>
            </div>
        </div>
        <div class="w-full max-w-sm shrink-0 overflow-hidden rounded-2xl border border-pgu-border bg-white shadow-sm">
            <div class="flex items-center gap-2 bg-brand-burgundy px-4 py-2.5 text-white">
                <i data-lucide="briefcase-business" class="h-4 w-4 shrink-0"></i>
                <span class="text-sm font-black tracking-wide">Contrato <span x-text="contrato || '—'"></span></span>
            </div>
            <div class="grid grid-cols-2 gap-3 px-4 py-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-pgu-muted">Total geral</p>
                    <p class="mt-0.5 text-xl font-black tabular-nums text-brand-burgundy">
                        <span x-text="formatQtyPtBr(data?.contratacoes_funil?.total ?? 0)"></span>
                        <span class="text-sm font-semibold text-pgu-muted"> vagas</span>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-pgu-muted">Etapas monitoradas</p>
                    <p class="mt-0.5 text-xl font-black tabular-nums text-brand-burgundy" x-text="formatQtyPtBr(data?.contratacoes_funil?.etapas_monitoradas ?? 0)"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,280px)_1fr] lg:items-start">
        <div class="flex flex-col gap-4">
            <div class="rounded-2xl border border-pgu-border bg-brand-burgundy-soft/40 px-4 py-5">
                <div class="flex justify-center">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-white text-brand-burgundy shadow-sm ring-1 ring-brand-burgundy/15">
                        <i data-lucide="users-round" class="h-7 w-7"></i>
                    </span>
                </div>
                <p class="mt-3 text-center text-4xl font-black tabular-nums text-brand-burgundy" x-text="formatQtyPtBr(data?.contratacoes_funil?.total ?? 0)"></p>
                <p class="mt-1 text-center text-sm font-semibold text-pgu-muted">Vagas em contratação</p>
            </div>
            <div class="rounded-2xl border border-pgu-border bg-white px-4 py-4">
                <div class="flex items-center gap-2 text-brand-burgundy">
                    <i data-lucide="lightbulb" class="h-5 w-5 shrink-0"></i>
                    <p class="text-xs font-black uppercase tracking-wide">Leitura executiva</p>
                </div>
                <ul class="mt-3 space-y-2.5 text-[13px] leading-snug text-pgu-ink">
                    <template x-for="(linha, idx) in clienteContratacoesLeituraExecutiva()" :key="`contratacao-insight-${idx}`">
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-burgundy"></span>
                            <span x-text="linha"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div class="min-w-0">
            <div class="rounded-2xl border border-pgu-border bg-zinc-50/80 px-4 py-4 sm:px-5">
                <div class="flex items-center gap-2 text-brand-burgundy">
                    <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
                    <h3 class="text-sm font-black uppercase tracking-wide">Vagas em contratação por etapa do funil</h3>
                </div>
                <div class="mt-4 space-y-3">
                    <template x-for="row in clienteContratacoesFunilChartRows()" :key="`contratacao-bar-${row.key}`">
                        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-burgundy text-sm font-black text-white"
                                x-text="row.rank"
                            ></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex min-w-0 flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-[11px] font-black uppercase leading-tight tracking-wide text-pgu-ink sm:text-xs" x-text="row.label"></p>
                                    <p class="shrink-0 text-sm font-black tabular-nums text-brand-burgundy">
                                        <span x-text="formatQtyPtBr(row.valor)"></span>
                                        <span class="text-xs font-semibold text-pgu-muted" x-text="` ${formatPctPtBr(row.pctDoTotal)}%`"></span>
                                    </p>
                                </div>
                                <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-white ring-1 ring-pgu-border">
                                    <div
                                        class="h-full rounded-full bg-brand-burgundy transition-all"
                                        :style="`width: ${Math.max(0, Math.min(100, row.barWidthPct))}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-pgu-border bg-white px-3 py-4 sm:px-4">
                <p class="mb-3 text-center text-[10px] font-black uppercase tracking-wide text-pgu-muted">Indicadores por etapa</p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7">
                    <template x-for="card in (data?.contratacoes_funil?.itens || [])" :key="`contratacao-card-${card.key}`">
                        <div class="flex flex-col rounded-xl border border-pgu-border bg-white px-2.5 py-3 text-center shadow-sm">
                            <span class="mx-auto inline-flex h-10 w-10 items-center justify-center text-brand-burgundy">
                                <i class="h-5 w-5" :data-lucide="card.icon || 'circle-dot'"></i>
                            </span>
                            <p
                                class="mt-2 min-h-[2.5rem] text-[9px] font-black uppercase leading-tight tracking-wide text-brand-burgundy sm:text-[10px]"
                                x-text="card.label"
                            ></p>
                            <hr class="my-2 border-pgu-border" />
                            <p class="text-lg font-black tabular-nums text-brand-burgundy sm:text-xl">
                                <span x-text="formatQtyPtBr(card.valor)"></span>
                                <span class="block text-[11px] font-semibold normal-case text-pgu-muted sm:inline sm:ml-0.5" x-text="Number(card.valor) === 1 ? 'vaga' : 'vagas'"></span>
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</section>
