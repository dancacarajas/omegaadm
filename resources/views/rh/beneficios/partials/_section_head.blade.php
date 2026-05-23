{{-- $icon, $title, $subtitle, $actions (opcional HTML) --}}
<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
            <i data-lucide="{{ $icon ?? 'layout-list' }}" class="h-5 w-5"></i>
        </span>
        <div>
            <h2 class="text-lg font-bold text-brand-black">{{ $title }}</h2>
            @if (! empty($subtitle))
                <p class="text-xs text-brand-gray">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @if (! empty($actions))
        <div class="flex flex-wrap items-center gap-2">{!! $actions !!}</div>
    @endif
</div>
