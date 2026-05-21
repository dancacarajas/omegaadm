{{-- Espera: $id, $numero, $titulo, $icone, $campos (label, value, wide?) --}}
<section id="{{ $id }}" class="ficha-secao scroll-mt-28 overflow-hidden rounded-3xl border border-zinc-200/90 bg-white shadow-md shadow-zinc-200/40 ring-1 ring-zinc-100">
    <div class="flex items-center gap-4 border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/25">{{ $numero }}</span>
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy ring-1 ring-brand-burgundy/10">
                <i data-lucide="{{ $icone }}" class="h-5 w-5"></i>
            </span>
            <h3 class="text-lg font-bold tracking-tight text-zinc-900">{{ $titulo }}</h3>
        </div>
    </div>
    <div class="divide-y divide-zinc-100/90 px-6">
        @foreach ($campos as $campo)
            @php $vazio = ($campo['value'] ?? '—') === '—'; @endphp
            <div class="group/row -mx-2 rounded-xl px-2 py-4 transition-colors hover:bg-brand-burgundy/[0.03] sm:mx-0 sm:grid sm:grid-cols-[minmax(12rem,14rem)_1fr] sm:items-baseline sm:gap-x-10 sm:px-0 {{ ($campo['wide'] ?? false) ? 'sm:grid-cols-1' : '' }}">
                <dt class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 transition-colors group-hover/row:text-brand-burgundy/70">{{ $campo['label'] }}</dt>
                <dd class="mt-1.5 whitespace-pre-line text-[15px] leading-relaxed sm:mt-0 {{ $vazio ? 'italic text-zinc-400' : 'font-semibold text-zinc-900' }}">{{ $campo['value'] ?? '—' }}</dd>
            </div>
        @endforeach
    </div>
</section>
