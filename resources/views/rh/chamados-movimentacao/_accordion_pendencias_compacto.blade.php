{{-- $pendenciasItens: list<string>  |  $titulo: string opcional --}}
@php
    $titulo = $titulo ?? 'Pendências desta etapa';
    $itens = $pendenciasItens ?? [];
    $total = count($itens);

    $grupos = [
        ['titulo' => 'Nada Consta', 'icone' => 'clipboard-check', 'iconBg' => 'bg-amber-50', 'iconText' => 'text-amber-800', 'itens' => []],
        ['titulo' => 'SIGO / DP-Folha', 'icone' => 'database', 'iconBg' => 'bg-sky-50', 'iconText' => 'text-sky-700', 'itens' => []],
        ['titulo' => 'Anexos', 'icone' => 'paperclip', 'iconBg' => 'bg-violet-50', 'iconText' => 'text-violet-700', 'itens' => []],
        ['titulo' => 'Checklist', 'icone' => 'list-checks', 'iconBg' => 'bg-zinc-100', 'iconText' => 'text-zinc-600', 'itens' => []],
        ['titulo' => 'Outros', 'icone' => 'alert-circle', 'iconBg' => 'bg-amber-50', 'iconText' => 'text-amber-800', 'itens' => []],
    ];

    foreach ($itens as $texto) {
        $limpo = $texto;
        if (str_starts_with($texto, 'DP/Folha:')) {
            $limpo = trim(substr($texto, 9));
            $grupos[1]['itens'][] = $limpo;
        } elseif (str_starts_with($texto, 'Checklist:')) {
            $limpo = trim(substr($texto, 10));
            $grupos[3]['itens'][] = $limpo;
        } elseif (str_contains($texto, 'Anexo obrigatório')) {
            $grupos[2]['itens'][] = $texto;
        } elseif (stripos($texto, 'Nada Consta') !== false) {
            $grupos[0]['itens'][] = $texto;
        } elseif (str_starts_with($texto, 'Etapa pendente:')) {
            $grupos[4]['itens'][] = trim(str_replace('Etapa pendente:', '', $texto));
        } else {
            $grupos[4]['itens'][] = $texto;
        }
    }

    $gruposVisiveis = array_values(array_filter($grupos, fn ($g) => count($g['itens']) > 0));
    $usarSubgrupos = count($gruposVisiveis) > 1;
@endphp

@if ($total > 0)
    <details data-accordion-pendencias class="mt-3 w-full max-w-xl overflow-hidden rounded-xl border border-amber-200/70 bg-white shadow-sm ring-1 ring-amber-100/80">
        <summary class="flex cursor-pointer list-none items-center gap-2.5 bg-gradient-to-r from-amber-50/90 to-white px-3 py-2.5 transition hover:from-amber-50 [&::-webkit-details-marker]:hidden">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-sm">
                <i data-lucide="octagon-alert" class="h-3.5 w-3.5"></i>
            </span>
            <span class="min-w-0 flex-1 text-[11px] font-bold uppercase tracking-wider text-amber-900">{{ $titulo }}</span>
            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold tabular-nums text-amber-900">{{ $total }}</span>
            <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-amber-700 transition-transform duration-200"></i>
        </summary>

        <div class="border-t border-amber-100/80 px-2.5 py-2.5">
            @if ($usarSubgrupos)
                <div class="space-y-1.5">
                    @foreach ($gruposVisiveis as $grupo)
                        @php $qtd = count($grupo['itens']); @endphp
                        <details data-accordion-grupo class="rounded-lg border border-zinc-200/70 bg-zinc-50/60">
                            <summary class="flex cursor-pointer list-none items-center gap-2 px-2.5 py-2 [&::-webkit-details-marker]:hidden">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md {{ $grupo['iconBg'] }} {{ $grupo['iconText'] }}">
                                    <i data-lucide="{{ $grupo['icone'] }}" class="h-3 w-3"></i>
                                </span>
                                <span class="flex-1 truncate text-[11px] font-semibold text-zinc-700">{{ $grupo['titulo'] }}</span>
                                <span class="text-[10px] font-bold text-amber-800">{{ $qtd }}</span>
                                <i data-lucide="chevron-down" class="h-3.5 w-3.5 shrink-0 text-zinc-400 transition-transform duration-200"></i>
                            </summary>
                            <ul class="space-y-1 border-t border-zinc-200/50 bg-white px-2.5 py-2">
                                @foreach ($grupo['itens'] as $linha)
                                    <li class="flex items-start gap-2 text-[11px] leading-snug text-zinc-600">
                                        <i data-lucide="circle-dashed" class="mt-0.5 h-3 w-3 shrink-0 text-amber-500"></i>
                                        <span>{{ $linha }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>
            @else
                <ul class="space-y-1">
                    @foreach ($itens as $linha)
                        <li class="flex items-start gap-2 rounded-lg bg-zinc-50/80 px-2 py-1.5 text-[11px] leading-snug text-zinc-700">
                            <i data-lucide="circle-dashed" class="mt-0.5 h-3 w-3 shrink-0 text-amber-500"></i>
                            <span>{{ $linha }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </details>
@endif
