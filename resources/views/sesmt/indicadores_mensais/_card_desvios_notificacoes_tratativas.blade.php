@php
    /** @var array<string, mixed> $card */
    $fluxoDesvios = $card['fluxoDesvios'] ?? [];
    $origemRegistros = $card['origemRegistros'] ?? [];
    $percentualResolucaoFmt = $card['percentualResolucaoFmt'] ?? '—';
    $gridMini = $card['gridMini'] ?? [];
    $faixaMedia = $card['faixaMedia'] ?? [];
    $tabelaPrincipais = $card['tabelaPrincipais'] ?? [];
    $leituraDesvios = $card['leituraDesvios'] ?? '';
    $pontosDesvios = $card['pontosDesvios'] ?? [];
@endphp

<div class="flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0 flex-1">
        <div class="flex gap-5">
            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                <i data-lucide="clipboard-check" class="h-8 w-8" stroke-width="1.5"></i>
                <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow ring-2 ring-white">
                    <i data-lucide="shield" class="h-3.5 w-3.5 text-[#600020]" stroke-width="2"></i>
                </span>
            </div>
            <div class="min-w-0 pt-0.5">
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Desvios, Notificações e Tratativas</h2>
                <p class="mt-2 text-sm leading-relaxed text-zinc-500">Controle dos desvios identificados, notificações emitidas e status das tratativas no período.</p>
                <div class="mt-3 flex max-w-md items-center gap-2">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                    <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="grid w-full shrink-0 grid-cols-2 gap-3 lg:max-w-[560px] lg:grid-cols-4">
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
    <div class="grid gap-8 xl:grid-cols-12 xl:items-start xl:gap-6">
        <div class="space-y-6 xl:col-span-7">
            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3 border-b border-[#600020]/25 pb-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="bar-chart-3" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Tratativa dos desvios</h3>
                </div>
                <div class="mt-6 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-center sm:gap-1">
                    @foreach ($fluxoDesvios as $idx => $step)
                        @if ($idx > 0)
                            <div class="hidden shrink-0 items-center justify-center px-1 sm:flex" aria-hidden="true">
                                <i data-lucide="chevron-right" class="h-7 w-7 text-[#600020]/35" stroke-width="1.5"></i>
                            </div>
                        @endif
                        <div class="flex min-w-0 flex-1 flex-col items-center justify-center rounded-xl bg-[#600020] px-4 py-5 text-center text-white shadow-md sm:min-h-[5.5rem]">
                            <i data-lucide="{{ $step['icon'] }}" class="mx-auto h-6 w-6 opacity-95" stroke-width="1.5"></i>
                            <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-white/90">{{ $step['label'] }}</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums">{{ $step['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 border-t border-zinc-100 pt-6">
                    <p class="text-center text-[11px] font-bold uppercase tracking-wide text-[#600020]">Origem dos registros</p>
                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ($origemRegistros as $og)
                            <div class="flex flex-col items-center gap-2 text-center">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-zinc-100 text-[#600020]">
                                    <i data-lucide="{{ $og['icon'] }}" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <span class="text-xs font-semibold text-zinc-700">{{ $og['label'] }}</span>
                                <span class="text-lg font-bold tabular-nums text-[#600020]">{{ $og['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between gap-4 border-t border-zinc-100 pt-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec] text-[#600020]">
                            <i data-lucide="pie-chart" class="h-5 w-5" stroke-width="1.5"></i>
                        </div>
                        <span class="text-sm font-semibold text-zinc-800">Percentual de resolução</span>
                    </div>
                    <span class="text-2xl font-bold tabular-nums text-[#600020]">{{ $percentualResolucaoFmt }}</span>
                </div>
            </div>

            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3 border-b border-[#600020]/25 pb-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="clipboard-list" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Principais registros</h3>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-xs">
                        <thead>
                            <tr class="border-b-2 border-[#600020] text-[11px] font-bold uppercase tracking-wide text-[#600020]">
                                <th class="pb-2 pr-3">Origem</th>
                                <th class="pb-2 pr-3">Tipo</th>
                                <th class="pb-2 pr-3">Descrição</th>
                                <th class="pb-2 pr-3">Status</th>
                                <th class="pb-2">Prazo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-700">
                            @forelse ($tabelaPrincipais as $row)
                                <tr>
                                    <td class="py-2.5 pr-3 font-medium text-zinc-900">{{ $row['origem'] }}</td>
                                    <td class="py-2.5 pr-3">{{ $row['tipo'] }}</td>
                                    <td class="py-2.5 pr-3">{{ $row['descricao'] }}</td>
                                    <td class="py-2.5 pr-3">
                                        @if (($row['statusVariant'] ?? '') === 'ok')
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">{{ $row['status'] }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-900">{{ $row['status'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 font-medium tabular-nums text-zinc-900">{{ $row['prazo'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-sm text-zinc-500">Nenhum registro de desvio ou ação proativa listável no período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-5">
            <div class="grid grid-cols-2 gap-3 sm:gap-3">
                @foreach ($gridMini as $cell)
                    <div class="flex min-h-[5.75rem] flex-col items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50/90 px-3 py-4 text-center shadow-sm">
                        <i data-lucide="{{ $cell['icon'] }}" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        <p class="text-[10px] font-semibold leading-tight text-[#600020]">{{ $cell['label'] }}</p>
                        <p class="text-xl font-bold tabular-nums text-zinc-900">{{ $cell['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($faixaMedia as $faixa)
                    <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50/90 px-4 py-4 shadow-sm">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-[#600020] ring-1 ring-zinc-200">
                            <i data-lucide="{{ $faixa['icon'] }}" class="h-5 w-5" stroke-width="1.5"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold text-[#600020]">{{ $faixa['label'] }}</p>
                            <p class="text-lg font-bold text-zinc-900">{{ $faixa['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-1">
                <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="lightbulb" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        </div>
                        <h4 class="text-sm font-bold tracking-tight text-zinc-900">Leitura executiva</h4>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                        <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-zinc-600">{{ $leituraDesvios }}</p>
                </div>
                <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="flag" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        </div>
                        <h4 class="text-sm font-bold tracking-tight text-zinc-900">Pontos de atenção</h4>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                        <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
                    </div>
                    <ul class="mt-4 list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                        @foreach ($pontosDesvios as $p)
                            <li>{{ $p }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="relative flex flex-wrap items-center gap-4 overflow-hidden rounded-b-2xl border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
    <div class="pointer-events-none absolute -right-8 top-0 h-32 w-32 rotate-12 rounded-3xl bg-white/10"></div>
    <div class="pointer-events-none absolute right-16 top-6 h-20 w-20 opacity-30" style="background-image: radial-gradient(circle, rgba(255,255,255,0.35) 1px, transparent 1px); background-size: 6px 6px;"></div>
    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white">
        <i data-lucide="shield-plus" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
    </div>
    <p class="relative min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.18em] sm:text-sm">Desvios e tratativas sob controle</p>
    <div class="relative hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
</div>
