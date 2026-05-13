@props([
    'label',
    'value',
    'icon' => 'activity',
    'labelClass' => 'text-[#600020]',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-4 py-8 text-center shadow-sm sm:gap-3.5 sm:py-9']) }}>
    <i data-lucide="{{ $icon }}" class="h-8 w-8 shrink-0 text-[#600020] sm:h-9 sm:w-9" stroke-width="1.75"></i>
    <p class="text-2xl font-bold tabular-nums leading-none text-[#600020] sm:text-[1.75rem]">{{ $value }}</p>
    <p @class(['max-w-[12rem] text-sm font-normal leading-snug', $labelClass])>{{ $label }}</p>
</div>
