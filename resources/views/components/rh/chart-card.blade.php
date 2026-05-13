@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-[14px] border border-[#E0E0E0] bg-white p-7 shadow-sm']) }}>
    <div class="mb-6">
        @if ($eyebrow)
            <p class="text-[11px] font-black uppercase tracking-[0.12em] text-[#600020]">{{ $eyebrow }}</p>
        @endif
        <h2 class="text-lg font-bold tracking-tight text-zinc-900 {{ $eyebrow ? 'mt-1.5' : '' }}">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-2 text-sm leading-relaxed text-zinc-500">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
