{{-- $numero, $titulo, $icone; opcional: $badge (html string ou texto) --}}
<div class="flex flex-col gap-4 border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/25">{{ $numero }}</span>
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy ring-1 ring-brand-burgundy/10">
                <i data-lucide="{{ $icone }}" class="h-5 w-5"></i>
            </span>
            <h2 class="text-lg font-bold tracking-tight text-zinc-900">{{ $titulo }}</h2>
        </div>
    </div>
    @if (! empty($badge))
        <span class="inline-flex shrink-0 items-center gap-2 rounded-full bg-brand-burgundy-soft px-3 py-1.5 text-xs font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">
            {!! $badge !!}
        </span>
    @endif
</div>
