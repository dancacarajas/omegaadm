@props([
    'label',
    'value',
    'icon' => 'user-plus',
    'compact' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl border border-[#E0E0E0] bg-white p-5 shadow-sm']) }}>
    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
        <i data-lucide="{{ $icon }}" class="h-7 w-7" stroke-width="1.5"></i>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold leading-tight text-zinc-700">{{ $label }}</p>
        <p @class([
            'mt-1 font-bold tabular-nums leading-none text-[#600020]',
            'text-3xl' => ! $compact,
            'text-2xl tracking-tight' => $compact,
        ])>{{ $value }}</p>
    </div>
</div>
