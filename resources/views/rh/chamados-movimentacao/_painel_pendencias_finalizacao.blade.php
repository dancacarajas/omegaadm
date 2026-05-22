@php
    $grupos = [
        'etapas' => ['titulo' => 'Etapas do fluxo', 'icone' => 'route', 'ancora' => '#timeline-etapas', 'itens' => [], 'iconBg' => 'bg-brand-burgundy-soft', 'iconText' => 'text-brand-burgundy'],
        'anexos' => ['titulo' => 'Anexos obrigatórios', 'icone' => 'paperclip', 'ancora' => '#secao-sigo-anexos', 'itens' => [], 'iconBg' => 'bg-violet-50', 'iconText' => 'text-violet-700'],
        'sigo' => ['titulo' => 'Cadastro no SIGO', 'icone' => 'database', 'ancora' => '#secao-sigo', 'itens' => [], 'iconBg' => 'bg-sky-50', 'iconText' => 'text-sky-700'],
        'nada_consta' => ['titulo' => 'Nada Consta Demissional', 'icone' => 'clipboard-check', 'ancora' => '#secao-nada-consta', 'itens' => [], 'iconBg' => 'bg-amber-50', 'iconText' => 'text-amber-800'],
        'checklist' => ['titulo' => 'Checklists', 'icone' => 'list-checks', 'ancora' => '#timeline-etapas', 'itens' => [], 'iconBg' => 'bg-zinc-100', 'iconText' => 'text-zinc-600'],
        'outros' => ['titulo' => 'Demais pendências', 'icone' => 'info', 'ancora' => null, 'itens' => [], 'iconBg' => 'bg-zinc-100', 'iconText' => 'text-zinc-600'],
    ];

    foreach ($pendenciasFinalizacao as $texto) {
        $item = ['texto' => $texto];
        if (str_starts_with($texto, 'Etapa pendente:')) {
            $item['texto'] = trim(str_replace('Etapa pendente:', '', $texto));
            $grupos['etapas']['itens'][] = $item;
        } elseif (str_contains($texto, 'Anexo obrigatório')) {
            $grupos['anexos']['itens'][] = $item;
        } elseif (stripos($texto, 'SIGO') !== false) {
            $grupos['sigo']['itens'][] = $item;
        } elseif (stripos($texto, 'Nada Consta') !== false) {
            $grupos['nada_consta']['itens'][] = $item;
        } elseif (str_starts_with($texto, 'Checklist:') || str_starts_with($texto, 'DP/Folha:')) {
            $item['texto'] = trim(preg_replace('/^(Checklist:|DP\/Folha:)\s*/', '', $texto));
            $grupos['checklist']['itens'][] = $item;
        } else {
            $grupos['outros']['itens'][] = $item;
        }
    }

    $gruposVisiveis = array_values(array_filter($grupos, fn ($g) => count($g['itens']) > 0));
    $totalPendencias = count($pendenciasFinalizacao);
@endphp

<details data-accordion-pendencias class="mb-6 overflow-hidden rounded-2xl border border-amber-200/70 bg-white shadow-sm ring-1 ring-amber-100">
    <summary class="flex cursor-pointer list-none items-center gap-3 bg-gradient-to-r from-amber-50/90 to-white px-4 py-3.5 transition hover:from-amber-50 [&::-webkit-details-marker]:hidden">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-sm">
            <i data-lucide="shield-alert" class="h-4 w-4"></i>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-zinc-900">Pendências para finalizar</span>
            <span class="block text-[11px] text-zinc-500">Clique para expandir · {{ count($gruposVisiveis) }} grupo(s)</span>
        </span>
        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold tabular-nums text-amber-900">{{ $totalPendencias }}</span>
        <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 text-amber-700 transition-transform duration-200"></i>
    </summary>

    <div class="border-t border-amber-100/80 px-3 py-3 sm:px-4">
        <p class="mb-3 px-1 text-[10px] text-zinc-500">
            <i data-lucide="lightbulb" class="mr-1 inline h-3 w-3 text-amber-600"></i>
            Salvar SIGO ou enviar o pacote não zera tudo de uma vez: etapas prontas são concluídas automaticamente; com pacote único, falta validar pelo RH e concluir checklists das etapas seguintes.
        </p>

        <div class="space-y-2">
            @foreach ($gruposVisiveis as $grupo)
                @php $qtd = count($grupo['itens']); @endphp
                <details data-accordion-grupo class="rounded-xl border border-zinc-200/80 bg-zinc-50/50">
                    <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-xl px-3 py-2.5 transition hover:bg-white [&::-webkit-details-marker]:hidden">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $grupo['iconBg'] }} {{ $grupo['iconText'] }}">
                            <i data-lucide="{{ $grupo['icone'] }}" class="h-3.5 w-3.5"></i>
                        </span>
                        <span class="min-w-0 flex-1 truncate text-xs font-bold text-zinc-800">{{ $grupo['titulo'] }}</span>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-900">{{ $qtd }}</span>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-200"></i>
                    </summary>
                    <div class="border-t border-zinc-200/60 bg-white px-3 pb-3 pt-2">
                        <ul class="space-y-1.5">
                            @foreach ($grupo['itens'] as $item)
                                <li class="flex items-start gap-2 text-[11px] leading-snug text-zinc-600">
                                    <i data-lucide="circle-dashed" class="mt-0.5 h-3 w-3 shrink-0 text-amber-500"></i>
                                    <span>{{ $item['texto'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($grupo['ancora'])
                            <a href="{{ $grupo['ancora'] }}" class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-brand-burgundy hover:underline">
                                Ir para seção
                                <i data-lucide="arrow-down" class="h-3 w-3"></i>
                            </a>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</details>

