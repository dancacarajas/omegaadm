{{-- $cards: [['icon','label','valor','href'?, 'destaque'?], ...] --}}
<section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ($cards as $card)
        @php
            $tag = ! empty($card['href']) ? 'a' : 'article';
            $destaque = ! empty($card['destaque']);
        @endphp
        <{{ $tag }}
            @if (! empty($card['href'])) href="{{ $card['href'] }}" @endif
            class="block rounded-2xl border p-5 shadow-sm ring-1 transition hover:shadow-md {{ $destaque ? 'border-brand-burgundy/15 bg-white ring-brand-burgundy/5' : 'border-zinc-200/80 bg-white ring-zinc-100' }}"
        >
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $destaque ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'bg-zinc-100 text-zinc-600' }}">
                <i data-lucide="{{ $card['icon'] ?? 'package' }}" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">{{ $card['label'] }}</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black tabular-nums">{{ $card['valor'] }}</p>
            @if (! empty($card['href']))
                <p class="mt-2 text-[11px] font-semibold text-brand-burgundy">Ver na lista →</p>
            @endif
        </{{ $tag }}>
    @endforeach
</section>
