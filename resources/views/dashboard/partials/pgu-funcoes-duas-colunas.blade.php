{{-- Concluídas: sem falta de gente (PGU − Pré ≤ 0) e com PGU informado. Pendentes: falta cobrir PGU ou PGU não informado com Pré > 0. --}}
<div
    class="grid gap-6 lg:grid-cols-2"
    x-show="!loading && !error && data?.ranking"
>
    {{-- Coluna 1: concluídas --}}
    <div class="flex min-h-[200px] flex-col rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-5 shadow-sm">
        <div class="border-b border-emerald-200/60 pb-3">
            <h3 class="text-sm font-bold uppercase tracking-wide text-emerald-900">Funções concluídas</h3>
            <p class="mt-1 text-xs leading-relaxed text-emerald-800/90">Mobilização cobre a necessidade PGU (sem falta por função) e a linha tem PGU informado.</p>
        </div>
        <ul class="mt-4 flex-1 space-y-2.5">
            <template x-for="row in (data?.ranking || []).filter((r) => !r.sem_pgu_informado && (Number(r.pending) || 0) === 0 && ((Number(r.pre_pgu) || 0) + (Number(r.pgu) || 0) > 0))" :key="row.linha_id || ((row.codigo || '') + '|' + (row.funcao || row.function))">
                <li class="rounded-xl border border-emerald-100/80 bg-white/90 px-3.5 py-3 shadow-sm">
                    <p class="text-sm font-semibold leading-snug text-pgu-ink" x-text="row.funcao || row.function"></p>
                    <p class="mt-0.5 text-xs text-pgu-muted" x-show="row.codigo" x-text="'Código ' + row.codigo"></p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div class="rounded-xl border border-emerald-200/90 bg-emerald-50/90 px-2 py-2.5 text-center shadow-inner">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-900/90">Concluídos</p>
                            <p class="mt-0.5 text-2xl font-black tabular-nums leading-none text-emerald-800" x-text="row.completed"></p>
                            <p class="mt-1 text-[10px] font-medium text-emerald-700/80">coberto min(Pré, PGU)</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-2 py-2.5 text-center shadow-inner">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600">Pendentes</p>
                            <p class="mt-0.5 text-2xl font-black tabular-nums leading-none text-slate-800" x-text="row.pending"></p>
                            <p class="mt-1 text-[10px] font-medium text-slate-500">PGU − Pré</p>
                        </div>
                    </div>
                    <p class="mt-2 text-center text-[11px] font-medium tabular-nums text-pgu-muted">
                        Pré (mobilizado): <span class="text-pgu-ink" x-text="row.pre_pgu ?? '—'"></span>
                        · PGU (necessidade): <span class="text-pgu-ink" x-text="row.pgu ?? '—'"></span>
                    </p>
                    <div class="mt-2.5 flex items-center gap-2">
                        <div class="h-2 min-h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-[width] duration-300"
                                :class="row.progress >= 80 ? 'bg-emerald-600' : (row.progress >= 50 ? 'bg-pgu-primary' : (row.progress >= 25 ? 'bg-amber-500' : 'bg-[#B91C1C]'))"
                                :style="`width: ${Math.min(Math.max(Number(row.progress) || 0, 0), 100)}%`"
                            ></div>
                        </div>
                        <span class="w-11 shrink-0 text-right text-xs font-bold tabular-nums text-pgu-ink" x-text="(Number(row.progress) || 0) + '%'"></span>
                    </div>
                </li>
            </template>
        </ul>
        <p
            class="mt-4 rounded-lg border border-dashed border-emerald-200 bg-white/60 px-3 py-2 text-center text-sm text-emerald-800/80"
            x-show="((data?.ranking || []).filter((r) => !r.sem_pgu_informado && (Number(r.pending) || 0) === 0 && ((Number(r.pre_pgu) || 0) + (Number(r.pgu) || 0) > 0)).length === 0)"
        >
            Nenhuma função concluída — todas ainda têm pendência.
        </p>
    </div>

    {{-- Coluna 2: pendentes --}}
    <div class="flex min-h-[200px] flex-col rounded-2xl border border-amber-200/80 bg-amber-50/40 p-5 shadow-sm">
        <div class="border-b border-amber-200/60 pb-3">
            <h3 class="text-sm font-bold uppercase tracking-wide text-amber-950">Funções pendentes</h3>
            <p class="mt-1 text-xs leading-relaxed text-amber-950/85">Falta mobilizar para atingir a PGU (PGU − Pré &gt; 0) ou PGU não informado com Pré &gt; 0.</p>
        </div>
        <ul class="mt-4 flex-1 space-y-2.5">
            <template x-for="row in (data?.ranking || []).filter((r) => (Number(r.pending) || 0) > 0 || r.sem_pgu_informado)" :key="row.linha_id || ((row.codigo || '') + '|' + (row.funcao || row.function))">
                <li class="rounded-xl border border-amber-100/80 bg-white/90 px-3.5 py-3 shadow-sm">
                    <p class="text-sm font-semibold leading-snug text-pgu-ink" x-text="row.funcao || row.function"></p>
                    <p class="mt-0.5 text-xs text-pgu-muted" x-show="row.codigo" x-text="'Código ' + row.codigo"></p>
                    <p class="mt-1 rounded-md bg-amber-100/90 px-2 py-1 text-center text-[10px] font-bold uppercase tracking-wide text-amber-950" x-show="row.sem_pgu_informado">PGU não informado (Pré &gt; 0)</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div class="rounded-xl border border-red-200/90 bg-red-50/90 px-2 py-2.5 text-center shadow-inner">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-red-900/90">Pendentes</p>
                            <p class="mt-0.5 text-2xl font-black tabular-nums leading-none text-red-700" x-text="row.sem_pgu_informado ? '—' : row.pending"></p>
                            <p class="mt-1 text-[10px] font-medium text-red-800/75" x-text="row.sem_pgu_informado ? 'informar PGU' : 'PGU − Pré'"></p>
                        </div>
                        <div class="rounded-xl border border-emerald-200/90 bg-emerald-50/90 px-2 py-2.5 text-center shadow-inner">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-900/90">Coberto</p>
                            <p class="mt-0.5 text-2xl font-black tabular-nums leading-none text-emerald-800" x-text="row.completed"></p>
                            <p class="mt-1 text-[10px] font-medium text-emerald-800/75">min(Pré, PGU)</p>
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-center gap-2">
                        <div class="h-2 min-h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-[width] duration-300"
                                :class="row.progress >= 80 ? 'bg-emerald-600' : (row.progress >= 50 ? 'bg-pgu-primary' : (row.progress >= 25 ? 'bg-amber-500' : 'bg-[#B91C1C]'))"
                                :style="`width: ${Math.min(Math.max(Number(row.progress) || 0, 0), 100)}%`"
                            ></div>
                        </div>
                        <span class="w-11 shrink-0 text-right text-xs font-bold tabular-nums text-pgu-ink" x-text="(Number(row.progress) || 0) + '%'"></span>
                    </div>
                </li>
            </template>
        </ul>
        <p
            class="mt-4 rounded-lg border border-dashed border-amber-200 bg-white/60 px-3 py-2 text-center text-sm text-amber-950/80"
            x-show="((data?.ranking || []).filter((r) => (Number(r.pending) || 0) > 0 || r.sem_pgu_informado).length === 0)"
        >
            Nenhuma função pendente — todas em dia neste recorte.
        </p>
    </div>
</div>
