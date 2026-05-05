@props([
    'title',
    'value',
    'description' => null,
    'icon' => 'activity',
    'tone' => 'primary',
    'progress' => null,
])
@php
    $tones = [
        'primary' => 'bg-pgu-primary-soft text-pgu-primary',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'danger' => 'bg-red-100 text-red-700',
        'purple' => 'bg-purple-100 text-purple-700',
    ];
    $toneClass = $tones[$tone] ?? $tones['primary'];
@endphp

<div class="rounded-[1.25rem] border border-pgu-border bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-pgu-muted">{{ $title }}</p>
            <div class="mt-3 text-3xl font-semibold tracking-tight text-pgu-ink">{{ $value }}</div>
            @if ($description)
                <p class="mt-2 text-sm text-pgu-muted">{{ $description }}</p>
            @endif
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $toneClass }}">
            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
        </div>
    </div>
    @if (! is_null($progress))
        <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-pgu-primary" style="width: {{ $progress }}%"></div>
        </div>
    @endif
</div>
