@props([
    'title',
    'subtitle' => null,
])

<header class="flex flex-col gap-4 rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-pgu-primary text-white shadow-sm">
            <i data-lucide="layout-dashboard" class="h-6 w-6"></i>
        </div>
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-pgu-ink">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-pgu-muted">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }}>
        {{ $slot }}
    </div>
</header>
