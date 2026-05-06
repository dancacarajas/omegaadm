@php
    $fill = $fill ?? [];
    $slug = 'boas_praticas_kaizen';
    $colaboradores = $colaboradores ?? collect();
    $defaultIds = data_get($fill, "$slug.colaborador_ids", []);
    if (! is_array($defaultIds)) {
        $defaultIds = [];
    }
    $colabOldIds = old("etapas.$slug.colaborador_ids", $defaultIds);
    if (! is_array($colabOldIds)) {
        $colabOldIds = [];
    }
@endphp

<div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-b border-zinc-200 bg-amber-400 px-4 py-2">
        <p class="text-xs font-bold uppercase tracking-wide text-brand-black">Boas práticas — projeto Kaizen (equipe do efetivo)</p>
        <p class="mt-1 text-[11px] font-medium text-brand-black/80">Fotos antes/depois, equipe envolvida (cadastro RH) e ganhos do processo — base para o slide do registro mensal.</p>
    </div>
    <div class="grid gap-4 p-4 md:grid-cols-2">
        <label class="block">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Foto — situação anterior (antes)</span>
            <input type="file" name="etapas[{{ $slug }}][foto_antes]" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-sm text-brand-black file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-burgundy">
            @if ($pAntes = data_get($fill, "{$slug}.foto_antes_path"))
                <p class="mt-2 text-xs font-medium text-brand-burgundy"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($pAntes) }}" target="_blank" rel="noopener" class="underline underline-offset-2">Foto atual (antes)</a></p>
            @endif
        </label>
        <label class="block">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Foto — situação melhorada (depois)</span>
            <input type="file" name="etapas[{{ $slug }}][foto_depois]" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-sm text-brand-black file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-burgundy">
            @if ($pDepois = data_get($fill, "{$slug}.foto_depois_path"))
                <p class="mt-2 text-xs font-medium text-brand-burgundy"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($pDepois) }}" target="_blank" rel="noopener" class="underline underline-offset-2">Foto atual (depois)</a></p>
            @endif
        </label>
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Título do Kaizen</span>
            <input type="text" name="etapas[{{ $slug }}][titulo]" value="{{ old("etapas.$slug.titulo", data_get($fill, "$slug.titulo")) }}" placeholder="Ex.: Suporte para transportar vara de manobra" class="mt-2 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsáveis pelo Kaizen</span>
            <input type="text" name="etapas[{{ $slug }}][responsaveis]" value="{{ old("etapas.$slug.responsaveis", data_get($fill, "$slug.responsaveis")) }}" placeholder="Nomes dos responsáveis pela melhoria" class="mt-2 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ganhos no processo</span>
            <textarea name="etapas[{{ $slug }}][ganhos_processo]" rows="4" placeholder="Descreva eficiência, segurança, custos, organização…" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old("etapas.$slug.ganhos_processo", data_get($fill, "$slug.ganhos_processo")) }}</textarea>
        </label>
    </div>
    <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-3">
        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Colaboradores da equipe (efetivo ativo)</p>
        <p class="mt-1 text-xs text-brand-gray">Marque quem participou, a foto de perfil cadastrada no RH aparecerá nos relatórios/slides.</p>
        @if ($colaboradores->isEmpty())
            <p class="mt-3 text-sm font-medium text-brand-burgundy">Nenhum colaborador ativo no efetivo. Cadastre ou ative colaboradores em RH → Efetivo.</p>
        @else
            <div class="mt-3 max-h-52 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 bg-white p-3">
                @foreach ($colaboradores as $c)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 text-sm transition hover:bg-brand-gray-soft">
                        <input type="checkbox" name="etapas[{{ $slug }}][colaborador_ids][]" value="{{ $c->id }}" @checked(in_array((string) $c->id, array_map('strval', $colabOldIds), true)) class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                        @if ($c->foto_path)
                            <img src="{{ $c->urlFotoPerfil() }}" alt="" class="h-9 w-9 shrink-0 rounded-md object-cover ring-1 ring-zinc-200">
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-burgundy-soft text-xs font-bold text-brand-burgundy">{{ mb_substr($c->nome, 0, 1) }}</span>
                        @endif
                        <span class="font-medium text-brand-black">{{ $c->nome }}@if (filled($c->matricula))<span class="text-brand-gray"> — mat. {{ $c->matricula }}</span>@endif</span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
</div>
