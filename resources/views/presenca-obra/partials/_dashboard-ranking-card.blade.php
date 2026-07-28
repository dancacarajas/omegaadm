@php
    $temaClasses = match ($tema) {
        'sky' => [
            'card' => 'border-sky-200/80',
            'header' => 'from-sky-50 via-white to-cyan-50/60',
            'icon' => 'bg-sky-100 text-sky-700',
            'badge' => 'bg-sky-100 text-sky-800',
            'valor' => 'text-sky-700',
            'hover' => 'hover:bg-sky-50/50',
            'medal' => ['#f59e0b', '#94a3b8', '#d97706'],
        ],
        default => [
            'card' => 'border-red-200/80',
            'header' => 'from-red-50 via-white to-rose-50/60',
            'icon' => 'bg-red-100 text-red-700',
            'badge' => 'bg-red-100 text-red-800',
            'valor' => 'text-red-700',
            'hover' => 'hover:bg-red-50/40',
            'medal' => ['#f59e0b', '#94a3b8', '#d97706'],
        ],
    };
@endphp

<section class="presenca-dashboard-ranking overflow-hidden rounded-2xl border bg-white shadow-sm {{ $temaClasses['card'] }}">
    <div class="border-b bg-gradient-to-r px-5 py-4 {{ $temaClasses['header'] }}">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $temaClasses['icon'] }}">
                <i data-lucide="{{ $icone }}" class="h-5 w-5"></i>
            </span>
            <div>
                <h2 class="text-sm font-bold text-brand-black">{{ $titulo }}</h2>
                <p class="mt-1 text-xs text-brand-gray">{{ $subtitulo }}</p>
            </div>
        </div>
    </div>

    @if ($itens === [])
        <p class="px-5 py-14 text-center text-sm text-brand-gray">{{ $vazio }}</p>
    @else
        <ol class="divide-y divide-zinc-100 p-3 sm:p-4">
            @foreach ($itens as $index => $item)
                @php
                    $posicao = $index + 1;
                    $medalTone = match ($posicao) {
                        1 => 'presenca-ranking-medal--gold',
                        2 => 'presenca-ranking-medal--silver',
                        3 => 'presenca-ranking-medal--bronze',
                        default => 'presenca-ranking-medal--default',
                    };
                @endphp
                <li class="presenca-ranking-item flex items-center gap-3 rounded-xl px-2 py-3 transition {{ $temaClasses['hover'] }} sm:gap-4 sm:px-3">
                    <span class="presenca-ranking-medal {{ $medalTone }}">{{ $posicao }}</span>

                    @if (filled($item['foto_url'] ?? null))
                        <div class="presenca-ranking-avatar shrink-0 overflow-hidden ring-2 ring-white shadow-md">
                            <img src="{{ $item['foto_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @else
                        <div class="presenca-ranking-avatar presenca-ranking-avatar--iniciais shrink-0 shadow-md ring-2 ring-white">
                            {{ $item['iniciais'] ?? '?' }}
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-brand-black">{{ $item['nome'] }}</p>
                        <p class="mt-0.5 truncate text-xs text-brand-gray">
                            {{ $item['matricula'] ?: '—' }}
                            @if (filled($item['cargo'] ?? null))
                                · {{ $item['cargo'] }}
                            @endif
                        </p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-brand-gray/80">
                            CC {{ $item['centro_custo'] ?: '—' }}
                            @if (($campo ?? 'faltas') === 'faltas' && ($item['presentes'] ?? 0) > 0)
                                · {{ $item['presentes'] }} presença{{ $item['presentes'] === 1 ? '' : 's' }}
                            @endif
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-2xl font-black leading-none {{ $temaClasses['valor'] }}">{{ $item['valor'] }}</p>
                        <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-brand-gray">{{ $labelValor }}</p>
                        @if (($campo ?? 'faltas') === 'faltas' && ($item['taxa_falta'] ?? 0) > 0)
                            <p class="mt-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $temaClasses['badge'] }}">
                                {{ number_format($item['taxa_falta'], 1, ',', '.') }}% falta
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
