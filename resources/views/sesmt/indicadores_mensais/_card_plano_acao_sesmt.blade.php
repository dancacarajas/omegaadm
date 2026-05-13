@php
    /** @var array<string, mixed> $card */
    $numeroDestaque = $card['numeroDestaque'] ?? '0';
    $metricas = $card['metricas'] ?? [];
    $linhasTabela = $card['linhasTabela'] ?? [];
    $leituraPlanoAcao = $card['leituraPlanoAcao'] ?? '';
    $pontosPlanoAcao = $card['pontosPlanoAcao'] ?? [];
@endphp

<div class="flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 xl:flex-row xl:items-start xl:justify-between xl:gap-10">
    <div class="min-w-0 w-full flex-1 xl:min-w-[min(100%,18rem)]">
        <div class="flex flex-wrap items-start gap-4 sm:gap-6">
            <div class="shrink-0 text-5xl font-black tabular-nums leading-none text-[#600020] sm:text-6xl" aria-hidden="true">{{ $numeroDestaque }}</div>
            <div class="min-w-0 max-w-2xl flex-1 basis-[min(100%,20rem)] pt-1 sm:basis-auto">
                <div class="flex gap-4">
                    <div class="relative hidden h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10 sm:flex">
                        <i data-lucide="list-todo" class="h-7 w-7" stroke-width="1.5"></i>
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow ring-2 ring-white">
                            <i data-lucide="shield-check" class="h-3.5 w-3.5 text-[#600020]" stroke-width="2"></i>
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-balance text-xl font-bold uppercase tracking-tight text-[#600020] sm:text-2xl">Plano de ação de SESMT</h2>
                        <p class="mt-2 max-w-prose text-sm leading-relaxed text-zinc-500">Acompanhamento das ações definidas para garantir melhorias contínuas em segurança, saúde e meio ambiente.</p>
                        <div class="mt-3 flex max-w-md items-center gap-2">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                            <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="grid w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 xl:max-w-[560px]">
        <div class="rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Contrato</p>
                    <p class="truncate text-sm font-bold text-zinc-900" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Competência</p>
                    <p class="text-sm font-bold text-zinc-900">{{ $competenciaRotulo }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Período</p>
                    <p class="text-sm font-bold text-zinc-900">{{ $periodoRotulo }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Data limite</p>
                    <p class="text-sm font-bold text-zinc-900">{{ $dataLimiteRotulo }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="px-6 py-8 sm:px-8 sm:py-10">
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-9">
        @foreach ($metricas as $m)
            @if (! empty($m['highlight']))
                <div class="flex min-h-[7.5rem] flex-col items-center justify-center gap-1 rounded-xl bg-[#600020] px-2 py-4 text-center text-white shadow-md sm:min-h-[8rem]">
                    <svg class="h-14 w-14 shrink-0 -rotate-90" viewBox="0 0 56 56" aria-hidden="true">
                        @php
                            $len = (float) ($m['circLen'] ?? 138.23);
                            $dash = min($len, max(0.0, (float) ($m['circDash'] ?? 0)));
                        @endphp
                        <circle cx="28" cy="28" r="22" fill="none" stroke="rgba(255,255,255,.28)" stroke-width="4"></circle>
                        <circle cx="28" cy="28" r="22" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="{{ $dash }} {{ $len }}"></circle>
                    </svg>
                    <p class="text-2xl font-black tabular-nums leading-none">{{ $m['value'] }}</p>
                    @if (! empty($m['sub']))
                        <p class="text-[11px] font-semibold text-white/90">{{ $m['sub'] }}</p>
                    @endif
                    <p class="text-[9px] font-bold uppercase leading-tight tracking-wide text-white/85">{{ $m['label'] }}</p>
                </div>
            @else
                <div class="flex min-h-[7.5rem] flex-col items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50/90 px-2 py-3 text-center shadow-sm sm:min-h-[8rem]">
                    <i data-lucide="{{ $m['icon'] }}" class="h-5 w-5 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                    <p class="text-[9px] font-bold uppercase leading-tight tracking-wide text-[#600020]">{{ $m['label'] }}</p>
                    <p class="text-lg font-bold tabular-nums text-zinc-900 sm:text-xl">{{ $m['value'] }}</p>
                </div>
            @endif
        @endforeach
    </div>

    <div class="mt-10 overflow-hidden rounded-[14px] border border-[#E0E0E0] bg-white shadow-sm">
        <div class="flex items-center gap-3 bg-[#600020] px-4 py-3 text-white">
            <i data-lucide="clipboard-list" class="h-4 w-4 shrink-0 opacity-95" stroke-width="1.5"></i>
            <h3 class="text-xs font-bold uppercase tracking-wide">Acompanhamento das ações</h3>
        </div>
        <div class="overflow-x-auto p-4 sm:p-5">
            <table class="w-full min-w-[920px] text-left text-xs">
                <thead>
                    <tr class="border-b-2 border-[#600020] text-[11px] font-bold uppercase tracking-wide text-[#600020]">
                        <th class="pb-2 pr-3">Ação</th>
                        <th class="pb-2 pr-3">Origem</th>
                        <th class="pb-2 pr-3">Responsável</th>
                        <th class="pb-2 pr-3">Prazo</th>
                        <th class="pb-2 pr-3">Status</th>
                        <th class="pb-2 pr-3">Categoria</th>
                        <th class="pb-2 pr-3">Prioridade</th>
                        <th class="pb-2">Progresso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    @forelse ($linhasTabela as $row)
                        <tr>
                            <td class="max-w-[14rem] py-2.5 pr-3 font-medium leading-snug text-zinc-900">{{ $row['acao'] }}</td>
                            <td class="py-2.5 pr-3">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="{{ $row['origemIcon'] }}" class="h-3.5 w-3.5 shrink-0 text-[#600020]" stroke-width="1.75"></i>
                                    <span>{{ $row['origem'] }}</span>
                                </span>
                            </td>
                            <td class="py-2.5 pr-3">{{ $row['responsavel'] }}</td>
                            <td class="py-2.5 pr-3 font-medium tabular-nums text-zinc-900">{{ $row['prazo'] }}</td>
                            <td class="py-2.5 pr-3">
                                @if (($row['statusVariant'] ?? '') === 'success')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">{{ $row['status'] }}</span>
                                @elseif (($row['statusVariant'] ?? '') === 'warn')
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-900">{{ $row['status'] }}</span>
                                @elseif (($row['statusVariant'] ?? '') === 'danger')
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-semibold text-red-800">{{ $row['status'] }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-0.5 text-[11px] font-semibold text-zinc-700">{{ $row['status'] }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-3">{{ $row['categoria'] }}</td>
                            <td class="py-2.5 pr-3">
                                @php
                                    $tone = $row['prioridadeTone'] ?? 'emerald';
                                    $toneCls = $tone === 'red' ? 'text-red-600' : ($tone === 'orange' ? 'text-amber-600' : 'text-emerald-600');
                                @endphp
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="{{ $row['prioridadeIcon'] }}" class="h-3.5 w-3.5 shrink-0 {{ $toneCls }}" stroke-width="2.25"></i>
                                    <span>{{ $row['prioridade'] }}</span>
                                </span>
                            </td>
                            <td class="py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 min-w-[4.5rem] flex-1 overflow-hidden rounded-full bg-zinc-200">
                                        <div class="h-full rounded-full bg-[#600020]" style="width: {{ (int) ($row['progresso'] ?? 0) }}%"></div>
                                    </div>
                                    <span class="w-9 shrink-0 text-right text-[11px] font-bold tabular-nums text-[#600020]">{{ (int) ($row['progresso'] ?? 0) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-sm text-zinc-500">Nenhuma ação de plano listável no período (exceto canceladas).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                    <i data-lucide="clipboard-list" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                </div>
                <h4 class="text-sm font-bold tracking-tight text-zinc-900">Leitura executiva</h4>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
            </div>
            <p class="mt-4 text-sm leading-relaxed text-zinc-600">{{ $leituraPlanoAcao }}</p>
        </div>
        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                    <i data-lucide="triangle-alert" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                </div>
                <h4 class="text-sm font-bold tracking-tight text-zinc-900">Pontos de atenção</h4>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
            </div>
            <ul class="mt-4 list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                @foreach ($pontosPlanoAcao as $p)
                    <li>{{ $p }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

<div class="flex items-center justify-center gap-4 bg-[#600020] px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wide text-white">
    <i data-lucide="shield-plus" class="h-4 w-4 shrink-0 opacity-95" stroke-width="1.5"></i>
    <span>Plano de ação de SESMT monitorado</span>
    <i data-lucide="trending-up" class="h-4 w-4 shrink-0 opacity-95" stroke-width="1.5"></i>
</div>
