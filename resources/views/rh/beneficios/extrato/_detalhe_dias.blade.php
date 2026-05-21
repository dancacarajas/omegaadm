@php
    $dias = $dias ?? [];
    $diasDesconto = array_values(array_filter($dias, fn ($d) => ($d['impacto'] ?? '') === 'desconto'));
    $diasCredito = array_values(array_filter($dias, fn ($d) => ($d['impacto'] ?? '') === 'credito'));
    $temCafe = collect($dias)->contains(fn ($d) => ($d['tipo'] ?? '') === 'trabalhado');
@endphp

@if (count($dias) === 0)
    <p class="text-xs text-brand-gray">Nenhum dia detalhado para este período.</p>
@else
    <div class="space-y-4 text-left">
        @if (count($diasDesconto) > 0)
            <div>
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-rose-800">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-rose-100">
                            <i data-lucide="minus-circle" class="h-3.5 w-3.5"></i>
                        </span>
                        Dias sem pagamento ({{ count($diasDesconto) }})
                    </p>
                </div>
                <div class="overflow-hidden rounded-xl border border-rose-200/60 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-xs">
                            <thead class="bg-rose-50 text-[10px] font-bold uppercase tracking-wider text-rose-900">
                                <tr>
                                    <th class="px-4 py-2.5">Data</th>
                                    <th class="px-4 py-2.5">Tipo</th>
                                    <th class="px-4 py-2.5">Motivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50">
                                @foreach ($diasDesconto as $dia)
                                    <tr class="transition hover:bg-rose-50/40">
                                        <td class="whitespace-nowrap px-4 py-2.5">
                                            <span class="font-semibold text-brand-black">{{ $dia['data_fmt'] }}</span>
                                            <span class="ml-1.5 rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-brand-gray">{{ $dia['dia_semana'] ?? '' }}</span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-900">{{ $dia['tipo_label'] ?? $dia['tipo'] }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-brand-gray">{{ $dia['descricao'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if ($temCafe && count($diasCredito) > 0)
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-emerald-800 marker:content-none [&::-webkit-details-marker]:hidden">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100">
                        <i data-lucide="check-circle" class="h-3.5 w-3.5"></i>
                    </span>
                    Dias pagos ({{ count($diasCredito) }})
                    <i data-lucide="chevron-down" class="h-4 w-4 transition group-open:rotate-180"></i>
                </summary>
                <div class="mt-2 overflow-hidden rounded-xl border border-emerald-200/60 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-xs">
                            <thead class="bg-emerald-50 text-[10px] font-bold uppercase tracking-wider text-emerald-900">
                                <tr>
                                    <th class="px-4 py-2.5">Data</th>
                                    <th class="px-4 py-2.5">Horas</th>
                                    <th class="px-4 py-2.5">Motivo</th>
                                    <th class="px-4 py-2.5 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-50">
                                @foreach ($diasCredito as $dia)
                                    <tr class="transition hover:bg-emerald-50/40">
                                        <td class="whitespace-nowrap px-4 py-2.5">
                                            <span class="font-semibold text-brand-black">{{ $dia['data_fmt'] }}</span>
                                            <span class="ml-1.5 rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-brand-gray">{{ $dia['dia_semana'] ?? '' }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 tabular-nums text-brand-gray">
                                            @if (($dia['minutos_trabalhado'] ?? null) !== null)
                                                {{ (int) $dia['minutos_trabalhado'] }} min
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-brand-gray">{{ $dia['descricao'] }}</td>
                                        <td class="px-4 py-2.5 text-right font-bold tabular-nums text-emerald-800">
                                            R$ {{ number_format((float) ($dia['valor'] ?? 0), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        @endif

        @if (! $temCafe && count($diasDesconto) === 0)
            <p class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-xs text-emerald-900">
                <i data-lucide="check" class="h-4 w-4 shrink-0"></i>
                Nenhuma falta injustificada nem dia sem direito no mês.
            </p>
        @endif
    </div>
@endif
