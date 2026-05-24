@extends('layouts.app')

@php
    $rotulos = $planilha['rotulos'];
@endphp

@section('title', ($material->exists ? 'Editar' : 'Novo').' material — Mobilização')
@section('eyebrow', 'Almoxarifado')
@section('page-title', $material->exists ? 'Editar material' : 'Novo material')

@section('actions')
    <a href="{{ $material->exists ? route('almoxarifado.mobilizacao-materiais.show', $material) : route('almoxarifado.mobilizacao-materiais.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @include('almoxarifado.mobilizacao.partials.flash')

    @include('almoxarifado.mobilizacao.partials.hero', [
        'icone' => $material->exists ? 'pencil' : 'plus',
        'titulo' => $material->exists ? 'Editar material' : 'Cadastrar material',
        'subtitulo' => 'Cadastro e atualização dos materiais da mobilização.',
    ])

    <form method="POST" action="{{ $material->exists ? route('almoxarifado.mobilizacao-materiais.update', $material) : route('almoxarifado.mobilizacao-materiais.store') }}">
        @csrf
        @if ($material->exists) @method('PUT') @endif

        <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                        <i data-lucide="table-2" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-brand-black">Dados do material</h2>
                    </div>
                </div>
            </div>

            <div class="space-y-6 p-6">
                <label class="block space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Contrato *</span>
                    <select name="contrato_id" required class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                        <option value="">Selecione</option>
                        @foreach ($contratos as $c)
                            <option value="{{ $c->id }}" @selected(old('contrato_id', $material->contrato_id) == $c->id)>{{ $c->numero }} — {{ $c->nome }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">{{ $rotulos['disciplina'] }} *</span>
                        <select name="disciplina" required class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                            @foreach ($planilha['disciplinas'] as $d)
                                <option value="{{ $d }}" @selected(old('disciplina', $material->disciplina) === $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2 sm:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">{{ $rotulos['categoria_descricao'] }} *</span>
                        <select name="categoria_descricao" required class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="">Selecione</option>
                            @foreach ($planilha['categorias'] as $cat)
                                <option value="{{ $cat }}" @selected(old('categoria_descricao', $material->categoria_descricao) === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">{{ $rotulos['situacao_tratativa'] }} *</span>
                        <select name="situacao_tratativa" required class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                            @foreach ($planilha['situacoesTratativa'] as $sit)
                                <option value="{{ $sit }}" @selected(old('situacao_tratativa', $material->situacao_tratativa) === $sit)>{{ $sit }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">{{ $rotulos['situacao_sigo_descricao'] }}</span>
                        <select name="situacao_sigo_descricao" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="">—</option>
                            @foreach ($planilha['situacoesSigo'] as $sit)
                                @if ($sit !== '')
                                    <option value="{{ $sit }}" @selected(old('situacao_sigo_descricao', $material->situacao_sigo_descricao) === $sit)>{{ $sit }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="block space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">{{ $rotulos['descricao_material'] }} *</span>
                    <textarea name="descricao_material" rows="3" required class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-3 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">{{ old('descricao_material', $material->descricao_material) }}</textarea>
                </label>

                <div class="rounded-2xl border border-zinc-200/80 bg-zinc-50/40 p-4">
                    <p class="mb-4 text-[11px] font-bold uppercase tracking-wider text-brand-burgundy">Quantidades</p>
                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        @php
                            $camposQtd = [
                                ['name' => 'unidade_medida', 'label' => $rotulos['unidade_medida'], 'value' => old('unidade_medida', $material->unidade_medida ?? 'UND'), 'type' => 'text', 'edit' => true],
                                ['name' => 'quantidade_necessaria', 'label' => $rotulos['quantidade_necessaria'], 'value' => old('quantidade_necessaria', $material->quantidade_necessaria ?? 0), 'type' => 'number', 'edit' => $podeAlterarNecessaria],
                                ['name' => 'quantidade_pedida_sigo', 'label' => $rotulos['quantidade_pedida_sigo'], 'value' => old('quantidade_pedida_sigo', $material->quantidade_pedida_sigo ?? 0), 'type' => 'number', 'edit' => $podeEditarSigo],
                                ['name' => 'quantidade_em_compra', 'label' => $rotulos['quantidade_em_compra'], 'value' => old('quantidade_em_compra', $material->quantidade_em_compra ?? 0), 'type' => 'number', 'edit' => $podeEditarCompras],
                                ['name' => 'quantidade_recebida', 'label' => $rotulos['quantidade_recebida'], 'value' => old('quantidade_recebida', $material->quantidade_recebida ?? 0), 'type' => 'number', 'edit' => $podeAlterarNecessaria],
                            ];
                        @endphp
                        @foreach ($camposQtd as $campo)
                            <label class="space-y-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-brand-gray">{{ $campo['label'] }}</span>
                                @if ($campo['edit'])
                                    <input type="{{ $campo['type'] }}" step="0.01" min="0" name="{{ $campo['name'] }}" value="{{ $campo['value'] }}" @if($campo['name'] === 'quantidade_necessaria') required @endif class="h-11 w-full rounded-2xl border border-zinc-200 bg-white px-3 text-sm tabular-nums outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                                @else
                                    <input type="text" readonly value="{{ is_numeric($campo['value']) ? number_format((float)$campo['value'], 2, ',', '.') : $campo['value'] }}" class="h-11 w-full rounded-2xl border border-zinc-100 bg-zinc-100/80 px-3 text-sm tabular-nums text-brand-gray">
                                @endif
                            </label>
                        @endforeach
                        <label class="space-y-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-brand-gray">Prioridade</span>
                            <select name="prioridade" class="h-11 w-full rounded-2xl border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                                @foreach ($prioridadeLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('prioridade', $material->prioridade) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @if ($material->exists)
                        <div class="mt-4 flex flex-wrap gap-6 text-sm">
                            <p><span class="font-bold text-brand-gray">{{ $rotulos['saldo_a_comprar'] }}:</span> <span class="font-mono font-semibold">{{ number_format($material->saldo_a_comprar, 2, ',', '.') }}</span></p>
                            <p><span class="font-bold text-brand-gray">{{ $rotulos['saldo_a_receber'] }}:</span> <span class="font-mono font-semibold">{{ number_format($material->saldo_a_receber, 2, ',', '.') }}</span></p>
                        </div>
                    @endif
                </div>

                <label class="block space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Código do material (opcional)</span>
                    <input name="codigo_material" value="{{ old('codigo_material', $material->codigo_material) }}" class="h-12 w-full max-w-xs rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                </label>

                <div class="grid gap-4 lg:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Observação da gestão</span>
                        <textarea name="observacao_gestao" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-3 text-sm outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">{{ old('observacao_gestao', $material->observacao_gestao) }}</textarea>
                    </label>
                    <label class="space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Observação do almoxarife</span>
                        <textarea name="observacao_almoxarife" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-3 text-sm outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">{{ old('observacao_almoxarife', $material->observacao_almoxarife) }}</textarea>
                    </label>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white px-4 py-3">
                    <input type="hidden" name="ativo" value="0">
                    <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $material->ativo ?? true)) class="h-5 w-5 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                    <span class="text-sm font-semibold text-brand-black">Item ativo no controle</span>
                </label>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-zinc-100 bg-zinc-50/50 px-6 py-4">
                <button type="submit" class="inline-flex h-12 items-center gap-2 rounded-2xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar material
                </button>
                <a href="{{ $material->exists ? route('almoxarifado.mobilizacao-materiais.show', $material) : route('almoxarifado.mobilizacao-materiais.index') }}" class="inline-flex h-12 items-center rounded-2xl border border-zinc-200 bg-white px-5 text-sm font-semibold text-brand-black shadow-sm hover:border-zinc-300">Cancelar</a>
            </div>
        </section>
    </form>
@endsection
