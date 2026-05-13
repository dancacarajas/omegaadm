@php
    /** @var array<string, mixed> $card */
    $barConformidade = $card['barConformidade'] ?? [];
    $escalaMax = (float) ($card['escalaMax'] ?? 30.0);
    $escalaTicks = $card['escalaTicks'] ?? [0, 5, 10, 15, 20, 25, 30];
    $faixaResumo = $card['faixaResumo'] ?? [];
    $cartoesResumo = $card['cartoesResumo'] ?? [];
    $aud = $card['auditoriaBox'] ?? [];
    $insp = $card['inspecaoBox'] ?? [];
    $leituraConformidade = $card['leituraConformidade'] ?? '';
    $pontosConformidade = $card['pontosConformidade'] ?? [];
@endphp

<div class="flex min-w-0 flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 xl:flex-row xl:items-start xl:justify-between xl:gap-10">
    <div class="min-w-0 flex-1 basis-0">
        <div class="flex min-w-0 w-full gap-5">
            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                <i data-lucide="clipboard-list" class="h-8 w-8" stroke-width="1.5"></i>
                <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow ring-2 ring-white">
                    <i data-lucide="search" class="h-3 w-3 text-[#600020]" stroke-width="2.5"></i>
                </span>
            </div>
            <div class="min-w-0 max-w-2xl flex-1 pt-0.5">
                <h2 class="text-balance text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Inspeções, Auditorias e Conformidade</h2>
                <p class="mt-2 max-w-prose text-sm leading-relaxed text-zinc-500">Controle de campo, verificação de conformidade e acompanhamento das avaliações do período.</p>
                <div class="mt-3 flex max-w-md items-center gap-2">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                    <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="grid w-full max-w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 xl:w-auto xl:max-w-[560px]">
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
        <div class="space-y-5 xl:col-span-5">
            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="shield-check" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Conformidade do período</h3>
                </div>
                <div class="mt-6 space-y-4">
                    @foreach ($barConformidade as $row)
                        <div class="flex items-center gap-3">
                            <div class="flex w-40 shrink-0 items-center gap-2 sm:w-44">
                                <i data-lucide="{{ $row['icon'] }}" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                <span class="text-xs font-medium leading-tight text-zinc-700">{{ $row['label'] }}</span>
                            </div>
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <div class="relative h-7 min-w-0 flex-1 overflow-hidden rounded-md bg-zinc-100">
                                    <div class="absolute inset-y-0 left-0 rounded-md bg-[#600020]" style="width: {{ $row['pct'] }}%"></div>
                                </div>
                                <span class="w-8 shrink-0 text-right text-sm font-bold tabular-nums text-zinc-900">{{ $row['value'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex justify-end">
                    <div class="flex w-full max-w-[min(100%,28rem)] justify-between pl-[11rem] pr-10 text-[10px] font-medium tabular-nums text-zinc-400 sm:pl-[12rem]">
                        @foreach ($escalaTicks as $t)
                            <span>{{ rtrim(rtrim(number_format((float) $t, 2, ',', ''), '0'), ',') }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-[14px] border border-[#E0E0E0] bg-white px-4 py-5 shadow-sm sm:px-6">
                <div class="flex flex-col divide-y divide-zinc-100 sm:flex-row sm:divide-x sm:divide-y-0">
                    @foreach ($faixaResumo as $item)
                        <div class="flex flex-1 items-center gap-3 py-4 first:pt-0 last:pb-0 sm:px-4 sm:py-0 sm:first:pl-0 sm:first:pr-4 sm:last:pr-0">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                                <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-[#600020]">{{ $item['label'] }}</p>
                                <p class="text-xl font-bold tabular-nums text-[#600020]">{{ $item['value'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-7">
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                @foreach ($cartoesResumo as $mini)
                    <div class="flex min-h-[9rem] flex-col items-center justify-between gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-5 text-center shadow-sm sm:min-h-[9.25rem]">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                            <i data-lucide="{{ $mini['icon'] }}" class="h-6 w-6 text-[#600020]" stroke-width="1.75"></i>
                        </div>
                        <p class="flex-1 text-center text-[11px] font-normal leading-snug text-[#600020]">{{ $mini['label'] }}</p>
                        <p class="text-lg font-bold leading-none text-zinc-900 sm:text-xl">{{ $mini['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                            <i data-lucide="clipboard-list" class="h-5 w-5" stroke-width="1.5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-[#600020]">Auditoria mensal</h4>
                            <dl class="mt-4 space-y-2 text-xs text-zinc-700 sm:text-sm">
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Auditor:</dt>
                                    <dd>{{ $aud['auditor'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Local:</dt>
                                    <dd>{{ $aud['local'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Data:</dt>
                                    <dd class="tabular-nums">{{ $aud['data'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Status:</dt>
                                    <dd>{{ $aud['status'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <dt class="font-semibold text-zinc-900">Resultado:</dt>
                                    <dd class="leading-relaxed text-zinc-600">{{ $aud['resultado'] ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                            <i data-lucide="hard-hat" class="h-5 w-5" stroke-width="1.5"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-[#600020]">Inspeção do canteiro</h4>
                            <dl class="mt-4 space-y-2 text-xs text-zinc-700 sm:text-sm">
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Inspetor:</dt>
                                    <dd>{{ $insp['inspetor'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Local:</dt>
                                    <dd>{{ $insp['local'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Data:</dt>
                                    <dd class="tabular-nums">{{ $insp['data'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-semibold text-zinc-900">Nota:</dt>
                                    <dd class="font-bold text-[#600020]">{{ $insp['nota'] ?? '—' }}</dd>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <dt class="font-semibold text-zinc-900">Referência:</dt>
                                    <dd class="leading-relaxed text-zinc-600">{{ $insp['referencia'] ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
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
                    <p class="mt-4 text-sm font-normal leading-relaxed text-zinc-600">{{ $leituraConformidade }}</p>
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
                        @foreach ($pontosConformidade as $ponto)
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
        <i data-lucide="shield" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
    </div>
    <p class="relative min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.18em] sm:text-sm">Conformidade operacional acompanhada</p>
    <div class="relative hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
</div>
