{{-- Variáveis: $badgeIcon, $badgeText, $title, $description, $stats (opcional [['label','value']]), $heroSlot (opcional HTML) --}}
<section class="relative mb-6 overflow-hidden rounded-3xl border border-brand-burgundy/20 bg-brand-burgundy-dark shadow-lg shadow-brand-burgundy/15">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36]"></div>
    <div class="pointer-events-none absolute -right-10 top-0 h-56 w-56 rounded-full bg-white/[0.07] blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-72 rounded-full bg-black/10 blur-2xl"></div>
    <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between sm:p-8">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-brand-burgundy-soft backdrop-blur-sm">
                <i data-lucide="{{ $badgeIcon ?? 'hand-heart' }}" class="h-3.5 w-3.5 text-white/90"></i>
                {{ $badgeText ?? 'Benefícios' }}
            </span>
            <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $title }}</h2>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-brand-burgundy-soft/90">{!! $description !!}</p>
            @if (! empty($heroSlot))
                <div class="mt-5 flex flex-wrap gap-3">{!! $heroSlot !!}</div>
            @endif
        </div>
        @if (! empty($stats))
            <div class="flex shrink-0 flex-wrap gap-3 sm:justify-end">
                @foreach ($stats as $stat)
                    <div class="min-w-[5.5rem] rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-center backdrop-blur-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy-soft/80">{{ $stat['label'] }}</p>
                        <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
