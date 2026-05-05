@props([
    'value' => 0,
])
@php
    $barClass = match (true) {
        $value >= 80 => 'bg-emerald-600',
        $value >= 50 => 'bg-pgu-primary',
        $value >= 25 => 'bg-amber-500',
        default => 'bg-red-600',
    };
@endphp
<div class="flex items-center gap-3">
    <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $value }}%"></div>
    </div>
    <span class="w-12 text-right text-sm font-semibold text-pgu-ink">{{ $value }}%</span>
</div>
