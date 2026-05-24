@extends('layouts.app')

@section('title', 'Material — Mobilização')
@section('eyebrow', 'Almoxarifado')
@section('page-title', 'Detalhe do material')

@section('actions')
    <a href="{{ route('almoxarifado.mobilizacao-materiais.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Lista
    </a>
    @if ($acesso['editar'])
        <a href="{{ route('almoxarifado.mobilizacao-materiais.edit', $material) }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="pencil" class="h-4 w-4"></i>
            Editar
        </a>
    @endif
@endsection

@section('content')
    @php
        $badge = $statusBadges[$material->status] ?? 'border-zinc-200 bg-zinc-50 text-zinc-700';
    @endphp

    @include('almoxarifado.mobilizacao.partials.flash')

    @if ($alertas['atrasado'])
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200/80 bg-gradient-to-r from-red-50 to-white px-5 py-4 text-sm font-semibold text-red-800 shadow-sm">
            <i data-lucide="alarm-clock" class="mt-0.5 h-5 w-5 shrink-0 text-red-600"></i>
            <span>ATRASADO — previsão de entrega vencida. {{ $material->acao_do_dia }}</span>
        </div>
    @endif
    @if ($alertas['sem_previsao'])
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200/80 bg-gradient-to-r from-amber-50 to-white px-5 py-4 text-sm font-semibold text-amber-900 shadow-sm">
            <i data-lucide="calendar-x" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"></i>
            <span>SEM PREVISÃO — item em compras sem data de entrega.</span>
        </div>
    @endif
    @if ($alertas['divergencia'])
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-orange-200/80 bg-gradient-to-r from-orange-50 to-white px-5 py-4 text-sm font-semibold text-orange-900 shadow-sm">
            <i data-lucide="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-orange-600"></i>
            <span>DIVERGÊNCIA — quantidade recebida acima da necessária.</span>
        </div>
    @endif

    <header class="relative mb-8 overflow-hidden rounded-3xl border border-brand-burgundy/15 bg-white shadow-xl shadow-brand-burgundy/10 ring-1 ring-brand-burgundy/10">
        <div class="relative overflow-hidden bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36] px-6 pb-12 pt-7 sm:px-8 sm:pb-14">
            <div class="pointer-events-none absolute -right-16 -top-10 h-64 w-64 rounded-full bg-white/[0.08] blur-3xl"></div>
            <div class="relative">
                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $badge }}">{{ $statusLabels[$material->status] ?? $material->status }}</span>
                <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-burgundy-soft/80">{{ $material->disciplina }} · {{ $material->categoria_descricao }}</p>
                <h2 class="mt-2 text-xl font-bold leading-snug text-white sm:text-2xl">{{ $material->descricao_material }}</h2>
                <p class="mt-3 text-sm font-semibold text-brand-burgundy-soft">{{ $material->acao_do_dia }}</p>
                <p class="mt-2 text-xs text-white/70">Contrato {{ $material->contrato?->numero }} — {{ $material->contrato?->nome }}</p>
            </div>
        </div>
        <div class="relative -mt-8 mx-6 mb-6 grid grid-cols-2 gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-lg sm:grid-cols-4 lg:mx-8">
            @foreach ([
                ['Necessária', $material->quantidade_necessaria],
                ['Pedida SIGO', $material->quantidade_pedida_sigo],
                ['Em compra', $material->quantidade_em_compra],
                ['Recebida', $material->quantidade_recebida],
            ] as [$lbl, $val])
                <div class="text-center sm:text-left">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-gray">{{ $lbl }}</p>
                    <p class="mt-0.5 font-mono text-lg font-bold tabular-nums text-brand-black">{{ number_format($val, 2, ',', '.') }}</p>
                </div>
            @endforeach
        </div>
        <div class="border-t border-zinc-100 px-6 pb-5 pt-2 text-xs text-brand-gray lg:px-8">
            @if ($material->situacao_tratativa)<span class="mr-3">{{ $material->situacao_tratativa }}</span>@endif
            @if ($material->situacao_sigo_descricao)<span>SIGO: {{ $material->situacao_sigo_descricao }}</span>@endif
            · Falta comprar <strong class="text-brand-black">{{ number_format($material->saldo_a_comprar, 2, ',', '.') }}</strong>
            · Falta receber <strong class="text-brand-black">{{ number_format($material->saldo_a_receber, 2, ',', '.') }}</strong>
        </div>
    </header>

    @php
        $inputClass = 'mt-2 h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10';
    @endphp

    <div class="grid gap-6 lg:grid-cols-2">
        @if ($acesso['sigo'])
        <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
            <div class="flex items-center gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy"><i data-lucide="send" class="h-5 w-5"></i></span>
                <h3 class="text-sm font-bold text-brand-black">Dados do SIGO</h3>
            </div>
            <form method="POST" action="{{ route('almoxarifado.mobilizacao-materiais.sigo', $material) }}" class="space-y-4 p-6">
                @csrf
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Número da PM<input name="numero_pm" value="{{ old('numero_pm', $material->numero_pm) }}" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Data pedido SIGO<input type="date" name="data_pedido_sigo" value="{{ old('data_pedido_sigo', $material->data_pedido_sigo?->format('Y-m-d')) }}" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Qtd pedida SIGO<input type="number" step="0.01" min="0" name="quantidade_pedida_sigo" value="{{ old('quantidade_pedida_sigo', $material->quantidade_pedida_sigo) }}" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Observação<textarea name="observacao_pedido" rows="2" class="mt-2 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-3 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10"></textarea></label>
                <button class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">Salvar SIGO</button>
            </form>
        </section>
        @endif

        @if ($acesso['compras'])
        <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
            <div class="flex items-center gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy"><i data-lucide="shopping-cart" class="h-5 w-5"></i></span>
                <h3 class="text-sm font-bold text-brand-black">Dados de Compras</h3>
            </div>
            <form method="POST" action="{{ route('almoxarifado.mobilizacao-materiais.compras', $material) }}" class="space-y-4 p-6">
                @csrf
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Número da OC<input name="numero_oc" value="{{ old('numero_oc', $material->numero_oc) }}" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Comprador<input name="comprador_responsavel" value="{{ old('comprador_responsavel', $material->comprador_responsavel) }}" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Fornecedor<input name="fornecedor" value="{{ old('fornecedor', $material->fornecedor) }}" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Qtd em compra *<input type="number" step="0.01" min="0" name="quantidade_em_compra" value="{{ old('quantidade_em_compra', $material->quantidade_em_compra) }}" required class="{{ $inputClass }}"></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Início compra<input type="date" name="data_inicio_compra" value="{{ old('data_inicio_compra', $material->data_inicio_compra?->format('Y-m-d')) }}" class="{{ $inputClass }}"></label>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Previsão entrega<input type="date" name="previsao_entrega" value="{{ old('previsao_entrega', $material->previsao_entrega?->format('Y-m-d')) }}" class="{{ $inputClass }}"></label>
                </div>
                <button class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">Salvar Compras</button>
            </form>
        </section>
        @endif

        @if ($acesso['recebimento'])
        <section class="overflow-hidden rounded-3xl border border-emerald-200/60 bg-white shadow-lg ring-1 ring-emerald-100">
            <div class="flex items-center gap-3 border-b border-emerald-100 bg-gradient-to-r from-emerald-50/80 to-white px-6 py-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"><i data-lucide="package-check" class="h-5 w-5"></i></span>
                <h3 class="text-sm font-bold text-brand-black">Registrar recebimento</h3>
            </div>
            <form method="POST" action="{{ route('almoxarifado.mobilizacao-materiais.recebimentos.store', $material) }}" enctype="multipart/form-data" class="space-y-4 p-6">
                @csrf
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Data *<input type="date" name="data_recebimento" value="{{ now()->format('Y-m-d') }}" required class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Quantidade *<input type="number" step="0.01" min="0.01" name="quantidade_recebida" required class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Responsável<input name="responsavel_recebimento" value="{{ auth()->user()?->name }}" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">NF<input name="numero_nf" class="{{ $inputClass }}"></label>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-brand-gray">Anexo<input type="file" name="anexo" class="mt-2 text-sm"></label>
                <button class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-emerald-700 text-sm font-bold text-white shadow-md hover:bg-emerald-800">Registrar recebimento</button>
            </form>
        </section>
        @endif

        @if ($acesso['anexo'])
        <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
            <div class="flex items-center gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy"><i data-lucide="paperclip" class="h-5 w-5"></i></span>
                <h3 class="text-sm font-bold text-brand-black">Anexos</h3>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('almoxarifado.mobilizacao-materiais.anexos.store', $material) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <select name="tipo_anexo" required class="{{ $inputClass }}">
                        @foreach ($anexoTipos as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="arquivo" required class="text-sm">
                    <button class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold shadow-sm hover:border-brand-burgundy">Enviar</button>
                </form>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($material->anexos as $anexo)
                        <li class="rounded-xl border border-zinc-100 bg-zinc-50/50 px-3 py-2">
                            <a href="{{ $anexo->urlPublica() }}" target="_blank" class="font-semibold text-brand-burgundy hover:underline">{{ $anexo->nome_arquivo }}</a>
                            <span class="text-xs text-brand-gray"> — {{ $anexoTipos[$anexo->tipo_anexo] ?? $anexo->tipo_anexo }}</span>
                        </li>
                    @empty
                        <li class="text-brand-gray">Nenhum anexo.</li>
                    @endforelse
                </ul>
            </div>
        </section>
        @endif
    </div>

    <section class="mt-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
            <h3 class="text-sm font-bold text-brand-black">Recebimentos lançados</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <tr><th class="px-5 py-3">Data</th><th class="px-5 py-3">Qtd</th><th class="px-5 py-3">Responsável</th><th class="px-5 py-3">NF</th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($material->recebimentos as $rec)
                        <tr class="hover:bg-zinc-50/80">
                            <td class="px-5 py-3">{{ $rec->data_recebimento->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 font-mono tabular-nums">{{ number_format($rec->quantidade_recebida, 2, ',', '.') }}</td>
                            <td class="px-5 py-3">{{ $rec->responsavel_recebimento ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $rec->numero_nf ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-brand-gray">Nenhum recebimento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
            <h3 class="text-sm font-bold text-brand-black">Histórico de alterações</h3>
        </div>
        <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="sticky top-0 border-b border-zinc-200 bg-zinc-50/95 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <tr><th class="px-4 py-3">Data</th><th class="px-4 py-3">Usuário</th><th class="px-4 py-3">Campo</th><th class="px-4 py-3">Anterior</th><th class="px-4 py-3">Novo</th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($material->historicos as $h)
                        <tr class="hover:bg-zinc-50/80">
                            <td class="whitespace-nowrap px-4 py-2">{{ $h->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $h->usuario?->name ?? '—' }}</td>
                            <td class="px-4 py-2 font-medium">{{ $h->campo_alterado }}</td>
                            <td class="max-w-[140px] truncate px-4 py-2 text-brand-gray">{{ $h->valor_anterior ?? '—' }}</td>
                            <td class="max-w-[140px] truncate px-4 py-2">{{ $h->valor_novo ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-brand-gray">Sem alterações registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($acesso['cancelar'] || $acesso['reabrir'])
    <section class="mt-6 overflow-hidden rounded-3xl border border-amber-200/60 bg-gradient-to-br from-amber-50/50 to-white shadow-sm">
        <div class="border-b border-amber-100 px-6 py-4">
            <h3 class="text-sm font-bold text-brand-black">Gestão do item</h3>
        </div>
        <div class="grid gap-6 p-6 lg:grid-cols-2">
            @if ($acesso['cancelar'] && $material->status !== \App\Support\Almoxarifado\MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO)
            <form method="POST" action="{{ route('almoxarifado.mobilizacao-materiais.cancelar', $material) }}">
                @csrf
                <label class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Cancelar / não necessário</label>
                <textarea name="justificativa" required minlength="10" rows="3" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm outline-none focus:ring-4 focus:ring-brand-burgundy/10"></textarea>
                <button class="mt-3 inline-flex h-10 items-center gap-2 rounded-xl bg-zinc-700 px-4 text-sm font-bold text-white hover:bg-zinc-800">Cancelar item</button>
            </form>
            @endif
            @if ($acesso['reabrir'] && $material->status === \App\Support\Almoxarifado\MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO)
            <form method="POST" action="{{ route('almoxarifado.mobilizacao-materiais.reabrir', $material) }}">
                @csrf
                <label class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Reabrir item</label>
                <textarea name="justificativa" required minlength="10" rows="3" class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm outline-none focus:ring-4 focus:ring-brand-burgundy/10"></textarea>
                <button class="mt-3 inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md hover:bg-brand-burgundy-dark">Reabrir</button>
            </form>
            @endif
        </div>
    </section>
    @endif
@endsection
