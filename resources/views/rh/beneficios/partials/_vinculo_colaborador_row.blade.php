@php
    $nome = $vinculo->colaborador->nome;
    $iniciais = collect(preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->join('');
    $dataDireito = $vinculo->data_direito?->format('d/m/Y') ?: ($vinculo->colaborador->data_admissao?->format('d/m/Y') ?: '—');
    $inputClass = 'mt-1.5 h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10';
    $labelClass = 'text-[10px] font-bold uppercase tracking-wider text-brand-gray';
    if (! $requerAdesao) {
        $resumoGeral = match (true) {
            ! $vinculo->tem_direito => ['Sem direito', 'bg-zinc-100 text-zinc-600 ring-zinc-200', 'bg-zinc-400'],
            $vinculo->tem_direito && ! $vinculo->cartao_entregue => ['Cartão pendente', 'bg-amber-50 text-amber-900 ring-amber-200', 'bg-amber-500'],
            ! $vinculo->beneficio_ativo => ['Inativo', 'bg-zinc-100 text-zinc-600 ring-zinc-200', 'bg-zinc-400'],
            default => ['Em dia', 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'bg-emerald-500'],
        };
    }
@endphp

<details
    id="vinculo-{{ $vinculo->id }}"
    class="group border-b border-zinc-100 bg-white transition open:bg-zinc-50/50"
>
    <summary class="beneficio-vinculo-list-grid cursor-pointer list-none px-4 py-3.5 sm:px-5 [&::-webkit-details-marker]:hidden">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-xs font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">
                {{ $iniciais ?: '?' }}
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-brand-black">{{ $nome }}</p>
                <p class="truncate text-xs text-brand-gray">{{ $vinculo->colaborador->cargo ?: '—' }}</p>
            </div>
        </div>

        <div class="flex sm:justify-center">
            @include('rh.beneficios.partials._vinculo_indicadores', ['vinculo' => $vinculo])
        </div>

        @if ($requerAdesao)
            <div class="flex min-w-0 flex-col items-end gap-1.5">
                <span
                    class="inline-flex max-w-full items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $vinculo->badgeStatusAdesao() }}"
                    title="{{ $vinculo->rotuloStatusAdesao() }}"
                >
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $vinculo->indicadorStatusAdesao() }}"></span>
                    <span class="truncate">{{ $vinculo->rotuloStatusAdesaoCurto() }}</span>
                </span>
                @include('rh.beneficios.partials._indicador_prazo_matriz', ['vinculo' => $vinculo, 'adesaoService' => $adesaoService])
            </div>
        @else
            <div class="min-w-0 sm:text-right">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $resumoGeral[1] }}">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $resumoGeral[2] }}"></span>
                    {{ $resumoGeral[0] }}
                </span>
            </div>
        @endif

        <p class="text-right text-xs font-medium tabular-nums text-brand-gray">
            {{ $vinculo->data_entrega_cartao?->format('d/m/Y') ?: '—' }}
        </p>

        <span class="flex justify-end">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-200/80 bg-white text-brand-gray shadow-sm transition group-open:border-brand-burgundy/20 group-open:text-brand-burgundy">
                <i data-lucide="chevron-down" class="h-4 w-4 transition group-open:rotate-180"></i>
            </span>
        </span>
    </summary>

    <div class="border-t border-zinc-100 bg-zinc-50/30 px-4 pb-5 pt-4 sm:px-5">
        <form method="POST" action="{{ $urlGestaoBeneficio }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm sm:p-5">
            @csrf
            <input type="hidden" name="vinculo_id" value="{{ $vinculo->id }}">

            <p class="text-xs font-bold uppercase tracking-wider text-brand-gray">Editar vínculo</p>

            <div class="grid gap-2 sm:grid-cols-3">
                <input type="hidden" name="tem_direito" value="0">
                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 text-xs font-semibold has-[:checked]:border-brand-burgundy/40 has-[:checked]:bg-brand-burgundy-soft">
                    <input type="checkbox" name="tem_direito" value="1" @checked($vinculo->tem_direito) class="accent-brand-burgundy">
                    Tem direito
                </label>
                <input type="hidden" name="cartao_entregue" value="0">
                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 text-xs font-semibold has-[:checked]:border-brand-burgundy/40 has-[:checked]:bg-brand-burgundy-soft">
                    <input type="checkbox" name="cartao_entregue" value="1" data-cartao-entregue-target @checked($vinculo->cartao_entregue) class="accent-brand-burgundy">
                    Cartão entregue
                </label>
                <input type="hidden" name="beneficio_ativo" value="0">
                <label class="flex h-10 cursor-pointer items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50/50 px-3 text-xs font-semibold has-[:checked]:border-brand-burgundy/40 has-[:checked]:bg-brand-burgundy-soft">
                    <input type="checkbox" name="beneficio_ativo" value="1" @checked($vinculo->beneficio_ativo) class="accent-brand-burgundy">
                    Benefício ativo
                </label>
            </div>

            @if ($requerAdesao)
                @include('rh.beneficios._campos_adesao_vinculo', [
                    'vinculo' => $vinculo,
                    'adesaoService' => $adesaoService,
                    'statusAdesaoOpcoes' => $statusAdesaoOpcoes,
                ])
            @endif

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/50 px-3 py-2.5">
                    <p class="{{ $labelClass }}">Data do direito</p>
                    <p class="mt-1 text-sm font-bold tabular-nums">{{ $dataDireito }}</p>
                </div>
                <label>
                    <span class="{{ $labelClass }}">Entrega do cartão</span>
                    <input type="date" name="data_entrega_cartao" value="{{ $vinculo->data_entrega_cartao?->format('Y-m-d') }}" data-sync-cartao-entregue class="{{ $inputClass }}">
                </label>
                <label class="sm:col-span-2">
                    <span class="{{ $labelClass }}">Número do cartão</span>
                    <input name="numero_cartao" value="{{ $vinculo->numero_cartao }}" placeholder="Nº do cartão" class="{{ $inputClass }}">
                </label>
                <label class="sm:col-span-2 lg:col-span-4">
                    <span class="{{ $labelClass }}">Observações</span>
                    <textarea name="observacoes" rows="2" class="{{ $inputClass }} resize-y">{{ $vinculo->observacoes }}</textarea>
                </label>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-4">
                <button type="submit" name="acao" value="excluir" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-gray transition hover:border-red-200 hover:bg-red-50 hover:text-red-700" onclick="return confirm('Remover este colaborador do benefício?')">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Excluir vínculo
                </button>
                <button type="submit" name="acao" value="salvar" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</details>
