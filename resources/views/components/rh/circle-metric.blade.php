@props([
    'label',
    'value',
    'icon' => 'activity',
])

<div class="flex min-w-0 flex-1 flex-col items-center px-1 text-center sm:px-2">
    <div class="flex h-[4.25rem] w-[4.25rem] shrink-0 items-center justify-center rounded-full border-2 border-[#600020]/30 bg-white text-[#600020] shadow-sm">
        <i data-lucide="{{ $icon }}" class="h-7 w-7" stroke-width="1.5"></i>
    </div>
    <p class="mt-3 max-w-[7.5rem] text-[11px] font-semibold leading-tight text-zinc-500">{{ $label }}</p>
    <p class="mt-1.5 text-xl font-bold leading-none text-[#600020] tabular-nums sm:text-2xl">{{ $value }}</p>
</div>
