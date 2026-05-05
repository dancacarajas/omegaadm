<x-pgu.data-table>
    <thead class="bg-slate-50">
        <tr>
            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-pgu-muted">Função</th>
            <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wide text-pgu-muted">Concluídos</th>
            <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wide text-pgu-muted">Pendentes</th>
            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-pgu-muted">Avanço</th>
            <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wide text-pgu-muted">Status</th>
            <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wide text-pgu-muted">Ação</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-pgu-border bg-white" x-show="!loading && !error">
        <template x-for="row in (data?.ranking || [])" :key="row.linha_id || ((row.codigo || '') + '|' + (row.funcao || row.function))">
            <tr class="transition hover:bg-slate-50">
                <td class="px-5 py-4">
                    <div class="text-base font-semibold text-pgu-ink" x-text="row.funcao || row.function"></div>
                    <div class="mt-1 text-xs text-pgu-muted" x-text="row.codigo ? ('Código ' + row.codigo) : 'Sem código cadastrado'"></div>
                    <p class="mt-1.5 text-[11px] leading-relaxed text-pgu-muted tabular-nums">
                        Pré-PGU no histograma: <span class="font-medium text-slate-600" x-text="row.pre_pgu ?? '—'"></span>
                    </p>
                </td>
                <td class="px-5 py-4 text-right text-base font-semibold tabular-nums text-pgu-ink" x-text="row.completed"></td>
                <td class="px-5 py-4 text-right text-base font-semibold tabular-nums text-pgu-ink" x-text="row.pending"></td>
                <td class="px-5 py-4">
                    <div class="flex min-w-[140px] items-center gap-3">
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full"
                                 :class="row.progress >= 80 ? 'bg-emerald-600' : (row.progress >= 50 ? 'bg-pgu-primary' : (row.progress >= 25 ? 'bg-amber-500' : 'bg-[#B91C1C]'))"
                                 :style="`width: ${row.progress}%`"></div>
                        </div>
                        <span class="w-12 shrink-0 text-right text-sm font-semibold text-pgu-ink" x-text="`${row.progress}%`"></span>
                    </div>
                </td>
                <td class="px-5 py-4 text-center">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                          :class="row.status === 'critical' ? 'bg-red-100 text-[#B91C1C]' : (row.status === 'high' ? 'bg-orange-100 text-orange-800' : (row.status === 'warning' ? 'bg-amber-100 text-amber-800' : (row.status === 'neutral' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800')))"
                          x-text="row.status_label"></span>
                </td>
                <td class="px-5 py-4 text-right">
                    <a
                        :href="histogramaDetalheUrl(row)"
                        class="inline-flex items-center gap-2 rounded-xl border border-pgu-border px-3 py-2 text-xs font-semibold text-pgu-ink transition hover:border-pgu-primary hover:text-pgu-primary"
                    >
                        Ver detalhes
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </td>
            </tr>
        </template>
    </tbody>
</x-pgu.data-table>
