@props([
    'label',
    'value',
    'icon' => 'activity',
])

<div class="flex min-h-[112px] items-center gap-4 rounded-xl border border-[#E0E0E0] bg-white p-5 shadow-sm">
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#600020]/25 bg-white text-[#600020]">
        <i data-lucide="{{ $icon }}" class="h-6 w-6" stroke-width="1.5"></i>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">{{ $label }}</p>
        <p class="mt-1.5 text-2xl font-bold leading-none tracking-tight text-zinc-900 tabular-nums">{{ $value }}</p>
    </div>
</div>
