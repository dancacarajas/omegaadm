@php
    /** @var array<string, mixed> $card */
    $barChartOcorrencias = $card['barChartOcorrencias'] ?? [];
    $escalaMax = (float) ($card['escalaMax'] ?? 1.5);
    $escalaTicks = $card['escalaTicks'] ?? [0.0, 0.5, 1.0, 1.5];
    $resumoTipoAcidente = $card['resumoTipoAcidente'] ?? ['pessoal' => 0, 'material' => 0, 'ambiental' => 0, 'total' => 0];
    $cartoesMini = $card['cartoesMini'] ?? [];
    $leituraReativos = $card['leituraReativos'] ?? '';
    $pontosReativos = $card['pontosReativos'] ?? [];
@endphp

<div class="flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0 flex-1">
        <div class="flex gap-5">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                <i data-lucide="shield-alert" class="h-9 w-9" stroke-width="1.5"></i>
            </div>
            <div class="min-w-0 pt-0.5">
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Indicadores Reativos de Segurança</h2>
                <p class="mt-2 text-sm leading-relaxed text-zinc-500">Ocorrências registradas e impactos de segurança no período.</p>
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
                    <p class="truncate text-sm font-bold text-[#600020]" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Competência</p>
                    <p class="text-sm font-bold text-[#600020]">{{ $competenciaRotulo }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Período</p>
                    <p class="text-sm font-bold text-[#600020]">{{ $periodoRotulo }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Data limite</p>
                    <p class="text-sm font-bold text-[#600020]">{{ $dataLimiteRotulo }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="px-6 py-8 sm:px-8 sm:py-10">
    <div class="grid gap-8 xl:grid-cols-12 xl:items-start xl:gap-6">
        <div class="space-y-5 xl:col-span-4">
            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="bar-chart-3" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Ocorrências reativas do período</h3>
                </div>
                <div class="mt-6 space-y-4">
                    @foreach ($barChartOcorrencias as $row)
                        <div class="flex items-center gap-3">
                            <div class="flex w-36 shrink-0 items-center gap-2 sm:w-40">
                                <i data-lucide="{{ $row['icon'] }}" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                <span class="text-xs font-medium leading-tight text-zinc-700">{{ $row['label'] }}</span>
                            </div>
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <div class="relative h-7 min-w-0 flex-1 overflow-hidden rounded-md bg-zinc-100">
                                    <div class="absolute inset-y-0 left-0 rounded-md bg-[#600020]" style="width: {{ $row['pct'] }}%"></div>
                                </div>
                                <span class="w-7 shrink-0 text-right text-sm font-bold tabular-nums text-[#600020]">{{ $row['value'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex justify-end">
                    <div class="flex w-full max-w-[min(100%,28rem)] justify-between pl-[9.5rem] pr-9 text-[10px] font-medium tabular-nums text-zinc-400 sm:pl-[10.5rem]">
                        @foreach ($escalaTicks as $t)
                            <span>{{ rtrim(rtrim(number_format((float) $t, 2, ',', ''), '0'), ',') }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="rounded-[14px] border border-[#E0E0E0] bg-white px-4 py-4 shadow-sm sm:px-5">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-2">
                    <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-center sm:gap-3 sm:text-left">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="user" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-[#600020]">Pessoal</p>
                            <p class="text-lg font-bold text-[#600020]">{{ $resumoTipoAcidente['pessoal'] }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-center sm:gap-3 sm:text-left">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="box" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-[#600020]">Material</p>
                            <p class="text-lg font-bold text-[#600020]">{{ $resumoTipoAcidente['material'] }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center gap-2 text-center sm:flex-row sm:items-center sm:gap-3 sm:text-left">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="leaf" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-[#600020]">Ambiental</p>
                            <p class="text-lg font-bold text-[#600020]">{{ $resumoTipoAcidente['ambiental'] }}</p>
                        </div>
                    </div>
                    <div class="col-span-2 flex flex-col items-center gap-2 border-t border-zinc-100 pt-4 text-center sm:col-span-1 sm:flex-row sm:border-l sm:border-t-0 sm:pl-4 sm:pt-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="package-plus" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold leading-tight text-[#600020]">Total de ocorrências reativas</p>
                            <p class="text-xl font-bold text-[#600020]">{{ $resumoTipoAcidente['total'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-8">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                @foreach ($cartoesMini as $mini)
                    <div class="flex min-h-[9rem] flex-col items-center justify-between gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-5 text-center shadow-sm sm:min-h-[9.25rem]">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="{{ $mini['icon'] }}" class="h-6 w-6 text-[#600020]" stroke-width="1.75"></i>
                        </div>
                        <p class="flex-1 text-center text-[11px] font-normal leading-snug text-zinc-700">{{ $mini['label'] }}</p>
                        <p class="text-lg font-bold leading-none text-[#600020] sm:text-xl">{{ $mini['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-2 lg:gap-5">
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
                    <p class="mt-4 text-sm font-normal leading-relaxed text-zinc-600">{{ $leituraReativos }}</p>
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
                    <ul class="mt-4 list-inside list-disc space-y-2 text-sm font-normal leading-relaxed text-zinc-600">
                        @foreach ($pontosReativos as $ponto)
                            <li>{{ $ponto }}</li>
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
    <p class="relative min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.18em] sm:text-sm">Ocorrências reativas monitoradas</p>
    <div class="relative hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
</div>
