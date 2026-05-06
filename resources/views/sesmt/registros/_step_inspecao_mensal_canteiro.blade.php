@php
    $fill = $fill ?? [];
    $slug = 'inspecao_mensal_canteiro';
    $passou = old("etapas.$slug.passou_inspecao", data_get($fill, "$slug.passou_inspecao", ''));
@endphp

<div class="space-y-5">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[1fr_220px]">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">O canteiro passou por inspeção mensal?</p>
                <div class="mt-2 flex items-center gap-3">
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-brand-black">
                        <input type="radio" name="etapas[{{ $slug }}][passou_inspecao]" value="nao" @checked($passou === 'nao')>
                        Não
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-brand-black">
                        <input type="radio" name="etapas[{{ $slug }}][passou_inspecao]" value="sim" @checked($passou === 'sim')>
                        Sim
                    </label>
                </div>
            </div>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data</span>
                <input type="date" name="etapas[{{ $slug }}][data_inspecao]" value="{{ old("etapas.$slug.data_inspecao", data_get($fill, "$slug.data_inspecao")) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Inspetor</span>
                <input type="text" name="etapas[{{ $slug }}][inspetor]" value="{{ old("etapas.$slug.inspetor", data_get($fill, "$slug.inspetor")) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Local</span>
                <input type="text" name="etapas[{{ $slug }}][local]" value="{{ old("etapas.$slug.local", data_get($fill, "$slug.local") ?: 'Canteiro de Obras | Site S11D | Canaã dos Carajás - PA') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_180px]">
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição</span>
                <textarea name="etapas[{{ $slug }}][descricao]" rows="5" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old("etapas.$slug.descricao", data_get($fill, "$slug.descricao")) }}</textarea>
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Nota</span>
                <input type="text" name="etapas[{{ $slug }}][nota]" value="{{ old("etapas.$slug.nota", data_get($fill, "$slug.nota")) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ([1 => 'evidencia_1', 2 => 'evidencia_2'] as $num => $field)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Foto | Evidência {{ $num }}</p>
                <input type="file" name="etapas[{{ $slug }}][{{ $field }}]" accept="image/*,.pdf" class="mt-3 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                @php $evPath = data_get($fill, "{$slug}.{$field}_path"); @endphp
                @if ($evPath)
                    <p class="mt-2 text-xs font-medium text-brand-burgundy"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($evPath) }}" target="_blank" rel="noopener" class="underline underline-offset-2">Arquivo atual (evidência {{ $num }})</a></p>
                @endif
                <p class="mt-2 text-xs text-brand-gray">PNG, JPG ou PDF · máximo 5MB</p>
            </div>
        @endforeach
    </div>
</div>
