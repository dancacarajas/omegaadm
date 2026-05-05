@props([
    'label',
    'status' => 'warning',
])
@php
    $classes = [
        'critical' => 'bg-red-100 text-red-700',
        'high' => 'bg-orange-100 text-orange-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'success' => 'bg-emerald-100 text-emerald-700',
    ];
@endphp
<span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $classes[$status] ?? $classes['warning'] }}">
    {{ $label }}
</span>
