@php
    /** @var array<string, mixed> $card */
    $barChartCapacitacoes = $card['barChartCapacitacoes'] ?? [];
    $escalaMax = (float) ($card['escalaMax'] ?? 6.0);
    $escalaTicks = $card['escalaTicks'] ?? [0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0];
    $cartoesResumo = $card['cartoesResumo'] ?? [];
    $tabelaTreinos = $card['tabelaTreinos'] ?? [];
    $campanhaTitulo = $card['campanhaTitulo'] ?? '—';
    $campanhaDescricao = $card['campanhaDescricao'] ?? '';
    $campanhasRealizadas = (int) ($card['campanhasRealizadas'] ?? 0);
    $publicoCampanhaColaboradores = (int) ($card['publicoCampanhaColaboradores'] ?? 0);
    $leituraTreinos = $card['leituraTreinos'] ?? '';
    $pontosTreinos = $card['pontosTreinos'] ?? [];
@endphp

<div class="flex min-w-0 flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 xl:flex-row xl:items-start xl:justify-between xl:gap-10">
    <div class="min-w-0 flex-1 basis-0">
        <div class="flex min-w-0 w-full gap-5">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                <i data-lucide="graduation-cap" class="h-9 w-9" stroke-width="1.5"></i>
            </div>
            <div class="min-w-0 max-w-2xl flex-1 pt-0.5">
                <h2 class="text-balance text-2xl font-bold tracking-tight text-[#600020] sm:text-[1.65rem]">Treinamentos, Integrações e Campanhas</h2>
                <p class="mt-2 max-w-prose text-sm leading-relaxed text-zinc-500">Capacitação, conscientização e aderência aos requisitos obrigatórios no período.</p>
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
        <div class="space-y-5 xl:col-span-5">
            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="bar-chart-3" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Capacitações do período</h3>
                </div>
                <div class="mt-6 space-y-4">
                    @foreach ($barChartCapacitacoes as $row)
                        <div class="flex items-center gap-3">
                            <div class="flex w-32 shrink-0 items-center gap-2 sm:w-36">
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
                    <div class="flex w-full max-w-[min(100%,28rem)] justify-between pl-[8.5rem] pr-9 text-[10px] font-medium tabular-nums text-zinc-400 sm:pl-[9.5rem]">
                        @foreach ($escalaTicks as $t)
                            <span>{{ rtrim(rtrim(number_format((float) $t, 2, ',', ''), '0'), ',') }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="calendar-days" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Treinamentos do mês</h3>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-xs">
                        <thead>
                            <tr class="border-b-2 border-[#600020] text-[11px] font-bold uppercase tracking-wide text-[#600020]">
                                <th class="pb-2 pr-3">Data</th>
                                <th class="pb-2 pr-3">Categoria</th>
                                <th class="pb-2 pr-3">Título</th>
                                <th class="pb-2">Instrutor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-700">
                            @forelse ($tabelaTreinos as $tr)
                                <tr>
                                    <td class="py-2.5 pr-3 font-medium tabular-nums text-zinc-900">{{ $tr['data'] }}</td>
                                    <td class="py-2.5 pr-3">{{ $tr['categoria'] }}</td>
                                    <td class="py-2.5 pr-3">{{ $tr['titulo'] }}</td>
                                    <td class="py-2.5">{{ $tr['instrutor'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-sm text-zinc-500">Nenhuma linha de treinamento registrada na competência.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                        <p class="flex-1 text-center text-[11px] font-normal leading-snug text-zinc-700">{{ $mini['label'] }}</p>
                        <p class="text-lg font-bold leading-none text-[#600020] sm:text-xl">{{ $mini['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-amber-300 text-amber-950 shadow-inner ring-2 ring-amber-400/60">
                        <i data-lucide="award" class="h-9 w-9" stroke-width="1.5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wide text-[#600020]">Campanha do mês</p>
                        <p class="mt-1 text-base font-bold text-[#600020]">{{ $campanhaTitulo }}</p>
                        <p class="mt-3 text-sm leading-relaxed text-zinc-600">{{ $campanhaDescricao }}</p>
                        <div class="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-zinc-100 pt-4 text-sm text-zinc-700">
                            <span class="inline-flex items-center gap-2">
                                <i data-lucide="megaphone" class="h-4 w-4 text-[#600020]" stroke-width="1.5"></i>
                                <span>Campanhas realizadas: <strong class="text-[#600020]">{{ $campanhasRealizadas }}</strong></span>
                            </span>
                            <span class="hidden h-4 w-px bg-zinc-200 sm:inline-block" aria-hidden="true"></span>
                            <span class="inline-flex items-center gap-2">
                                <i data-lucide="users" class="h-4 w-4 text-[#600020]" stroke-width="1.5"></i>
                                <span>Público alcançado: <strong class="text-[#600020]">{{ $publicoCampanhaColaboradores }} colaboradores</strong></span>
                            </span>
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
                    <p class="mt-4 text-sm font-normal leading-relaxed text-zinc-600">{{ $leituraTreinos }}</p>
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
                        @foreach ($pontosTreinos as $ponto)
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
    <p class="relative min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.18em] sm:text-sm">Capacitação e conscientização realizadas</p>
    <div class="relative hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
</div>
