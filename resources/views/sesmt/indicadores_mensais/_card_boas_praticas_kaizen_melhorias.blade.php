@php
    /** @var array<string, mixed> $card */
    $chartBarrasVert = $card['chartBarrasVert'] ?? [];
    $faixaSobGrafico = $card['faixaSobGrafico'] ?? [];
    $gridResumo = $card['gridResumo'] ?? [];
    $faixaLarga = $card['faixaLarga'] ?? [];
    $kaizenDestaque = $card['kaizenDestaque'] ?? ['vazio' => true];
    $destaques = $card['destaques'] ?? [];
    $leituraBoasPraticas = $card['leituraBoasPraticas'] ?? '';
    $pontosBoasPraticas = $card['pontosBoasPraticas'] ?? [];
@endphp

<div class="flex min-w-0 flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 xl:flex-row xl:items-start xl:justify-between xl:gap-10">
    <div class="min-w-0 flex-1 basis-0">
        <div class="flex min-w-0 w-full gap-5">
            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                <i data-lucide="trending-up" class="h-8 w-8" stroke-width="1.5"></i>
                <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow ring-2 ring-white">
                    <i data-lucide="settings" class="h-3.5 w-3.5 text-[#600020]" stroke-width="2"></i>
                </span>
            </div>
            <div class="min-w-0 max-w-2xl flex-1 pt-0.5">
                <h2 class="text-balance text-2xl font-bold tracking-tight text-[#600020] sm:text-[1.65rem]">Boas Práticas, Kaizen e Melhorias Implementadas</h2>
                <p class="mt-2 max-w-prose text-sm leading-relaxed text-zinc-500">Evolução das melhorias aplicadas, ganhos obtidos e valorização das boas práticas no período.</p>
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
    <div class="grid gap-8 xl:grid-cols-12 xl:items-start">
        <div class="space-y-6 xl:col-span-7">
            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3 border-b border-[#600020]/25 pb-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="bar-chart-3" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Melhorias implementadas por tipo</h3>
                </div>
                <div class="mt-8 flex h-52 items-end justify-between gap-2 border-b border-zinc-200 pb-2 sm:gap-3">
                    @foreach ($chartBarrasVert as $bar)
                        @php
                            $pct = max((int) ($bar['pct'] ?? 0), ((int) ($bar['value'] ?? 0) > 0 ? 8 : 0));
                        @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                            <span class="text-sm font-bold tabular-nums text-[#600020]">{{ $bar['value'] }}</span>
                            <div class="flex h-40 w-full max-w-[4rem] flex-col justify-end rounded-t-md bg-zinc-100 sm:max-w-[4.5rem]">
                                <div class="w-full min-h-0 rounded-t-md bg-[#600020] transition-all" style="height: {{ $pct }}%"></div>
                            </div>
                            <span class="max-w-[5.5rem] text-center text-[10px] font-semibold leading-tight text-zinc-700">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4 border-t border-zinc-100 pt-6 sm:grid-cols-4">
                    @foreach ($faixaSobGrafico as $fx)
                        <div class="flex flex-col items-center gap-2 text-center">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f5e8ec] text-[#600020]">
                                <i data-lucide="{{ $fx['icon'] }}" class="h-5 w-5" stroke-width="1.5"></i>
                            </div>
                            <span class="text-[11px] font-semibold text-zinc-700">{{ $fx['label'] }}</span>
                            <span class="text-lg font-bold tabular-nums text-[#600020]">{{ $fx['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4 xl:col-span-5">
            <div class="grid grid-cols-2 gap-3">
                @foreach ($gridResumo as $cell)
                    <div class="flex min-h-[6rem] flex-col items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50/90 px-3 py-4 text-center shadow-sm">
                        <i data-lucide="{{ $cell['icon'] }}" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                        <p class="text-[10px] font-semibold leading-tight text-[#600020]">{{ $cell['label'] }}</p>
                        <p class="text-xl font-bold tabular-nums text-zinc-900">{{ $cell['value'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($faixaLarga as $fl)
                    <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50/90 px-4 py-4 shadow-sm">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-[#600020] ring-1 ring-zinc-200">
                            <i data-lucide="{{ $fl['icon'] }}" class="h-5 w-5" stroke-width="1.5"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold text-[#600020]">{{ $fl['label'] }}</p>
                            <p class="text-lg font-bold text-zinc-900">{{ $fl['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-7">
            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3 border-b border-[#600020]/25 pb-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                        <i data-lucide="star" class="h-4 w-4" stroke-width="1.5"></i>
                    </div>
                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Kaizen destaque do período</h3>
                </div>

                @if (($kaizenDestaque['vazio'] ?? true) === true)
                    <p class="mt-6 text-sm leading-relaxed text-zinc-500">Nenhum projeto Kaizen preenchido no registro mensal desta competência. Inclua título, responsáveis, participantes, ganhos e fotos antes/depois para exibir o destaque aqui.</p>
                @else
                    <div class="mt-6 grid gap-6 lg:grid-cols-12">
                        <div class="lg:col-span-3">
                            <p class="mb-2 text-center text-xs font-bold uppercase tracking-wide text-[#600020]">Antes</p>
                            @if (! empty($kaizenDestaque['urlAntes']))
                                <img src="{{ $kaizenDestaque['urlAntes'] }}" alt="Antes" class="mx-auto max-h-48 w-full max-w-xs rounded-lg border border-zinc-200 object-cover shadow-sm">
                            @else
                                <div class="flex h-48 max-w-xs items-center justify-center rounded-lg border border-dashed border-zinc-200 bg-zinc-50 text-center text-xs text-zinc-400">Sem foto «antes»</div>
                            @endif
                        </div>
                        <div class="space-y-4 lg:col-span-6">
                            <dl class="space-y-3 text-sm">
                                <div class="flex gap-2">
                                    <i data-lucide="file-text" class="mt-0.5 h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <div><dt class="font-bold text-zinc-900">Título</dt><dd class="mt-0.5 text-zinc-700">{{ $kaizenDestaque['titulo'] }}</dd></div>
                                </div>
                                <div class="flex gap-2">
                                    <i data-lucide="user" class="mt-0.5 h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <div><dt class="font-bold text-zinc-900">Responsável</dt><dd class="mt-0.5 text-zinc-700">{{ $kaizenDestaque['responsavel'] }}</dd></div>
                                </div>
                                <div class="flex gap-2">
                                    <i data-lucide="users" class="mt-0.5 h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <div><dt class="font-bold text-zinc-900">Participantes</dt><dd class="mt-0.5 text-zinc-700">{{ $kaizenDestaque['participantes'] }}</dd></div>
                                </div>
                                <div class="flex gap-2">
                                    <i data-lucide="calendar" class="mt-0.5 h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <div><dt class="font-bold text-zinc-900">Data da implementação</dt><dd class="mt-0.5 tabular-nums text-zinc-700">{{ $kaizenDestaque['data'] }}</dd></div>
                                </div>
                                <div class="flex gap-2">
                                    <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <div><dt class="font-bold text-zinc-900">Problema identificado</dt><dd class="mt-0.5 leading-relaxed text-zinc-700">{{ $kaizenDestaque['problema'] }}</dd></div>
                                </div>
                                <div class="flex gap-2">
                                    <i data-lucide="settings" class="mt-0.5 h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <div><dt class="font-bold text-zinc-900">Solução aplicada</dt><dd class="mt-0.5 leading-relaxed text-zinc-700">{{ $kaizenDestaque['solucao'] }}</dd></div>
                                </div>
                                <div class="flex gap-2">
                                    <i data-lucide="trophy" class="mt-0.5 h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <div><dt class="font-bold text-zinc-900">Ganho obtido</dt><dd class="mt-0.5 leading-relaxed text-zinc-700">{{ $kaizenDestaque['ganho'] }}</dd></div>
                                </div>
                            </dl>
                        </div>
                        <div class="lg:col-span-3">
                            <p class="mb-2 text-center text-xs font-bold uppercase tracking-wide text-[#600020]">Depois</p>
                            @if (! empty($kaizenDestaque['urlDepois']))
                                <img src="{{ $kaizenDestaque['urlDepois'] }}" alt="Depois" class="mx-auto max-h-48 w-full max-w-xs rounded-lg border border-zinc-200 object-cover shadow-sm">
                            @else
                                <div class="flex h-48 max-w-xs items-center justify-center rounded-lg border border-dashed border-zinc-200 bg-zinc-50 text-center text-xs text-zinc-400">Sem foto «depois»</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-6 lg:col-span-5">
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
                <p class="mt-4 text-sm leading-relaxed text-zinc-600">{{ $leituraBoasPraticas }}</p>
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
                    @foreach ($pontosBoasPraticas as $p)
                        <li>{{ $p }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($destaques as $d)
            <div class="flex gap-3 rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[#600020] ring-1 ring-zinc-200">
                    <i data-lucide="{{ $d['icon'] }}" class="h-5 w-5" stroke-width="1.5"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-[#600020]">{{ $d['titulo'] }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-zinc-600">{{ $d['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="relative flex flex-wrap items-center gap-4 overflow-hidden rounded-b-2xl border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
    <div class="pointer-events-none absolute -right-8 top-0 h-32 w-32 rotate-12 rounded-3xl bg-white/10"></div>
    <div class="pointer-events-none absolute right-16 top-6 h-20 w-20 opacity-30" style="background-image: radial-gradient(circle, rgba(255,255,255,0.35) 1px, transparent 1px); background-size: 6px 6px;"></div>
    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white">
        <i data-lucide="shield-plus" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
    </div>
    <p class="relative min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.18em] sm:text-sm">Melhorias implementadas e evidenciadas</p>
    <div class="relative hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
</div>
