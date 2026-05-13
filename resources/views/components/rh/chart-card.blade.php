@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'titleIcon' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-[14px] border border-[#E0E0E0] bg-white p-7 shadow-sm']) }}>
    <div class="mb-6">
        @if ($eyebrow)
            <p class="text-[11px] font-black uppercase tracking-[0.12em] text-[#600020]">{{ $eyebrow }}</p>
        @endif
        <div class="flex items-start gap-3 {{ $eyebrow ? 'mt-1.5' : '' }}">
            @if ($titleIcon)
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                    <i data-lucide="{{ $titleIcon }}" class="h-5 w-5" stroke-width="1.5"></i>
                </div>
            @endif
            <h2 class="min-w-0 flex-1 text-lg font-bold tracking-tight text-zinc-900">{{ $title }}</h2>
        </div>
        @if ($subtitle)
            <p class="mt-2 text-sm leading-relaxed text-zinc-500">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
