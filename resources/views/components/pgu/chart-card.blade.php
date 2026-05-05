@props([
    'title',
    'subtitle' => null,
    'chartId',
])

<div class="rounded-[1.5rem] border border-pgu-border bg-white p-6 shadow-sm">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-pgu-ink">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-sm text-pgu-muted">{{ $subtitle }}</p>
            @endif
            @isset($description)
                <div class="mt-1 text-sm text-pgu-muted">{{ $description }}</div>
            @endisset
        </div>
        <button
            type="button"
            class="rounded-xl border border-pgu-border p-2 text-pgu-muted transition hover:border-pgu-primary hover:bg-teal-50 hover:text-pgu-primary"
            title="Exportar gráfico como PNG"
            aria-label="Exportar gráfico como PNG"
            @click.stop="exportChartPng(@js($chartId))"
        >
            <i data-lucide="download" class="h-5 w-5"></i>
        </button>
    </div>
    <div id="{{ $chartId }}" class="h-[460px] min-h-[460px] w-full"></div>
    @isset($footer)
        <div class="mt-3">
            {{ $footer }}
        </div>
    @endisset
</div>
