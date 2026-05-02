@csrf

@php
    $input = 'h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10';
    $label = 'text-xs font-bold uppercase tracking-wide text-brand-gray';
@endphp

<div class="space-y-5">
    <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Identificação</p>
        <h2 class="mt-1 text-xl font-bold text-brand-black">Dados principais do contrato</h2>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <label class="space-y-2">
                <span class="{{ $label }}">Número do contrato *</span>
                <input name="numero" value="{{ old('numero', $contrato->numero) }}" required class="{{ $input }}" placeholder="Ex.: 286/2026">
                @error('numero') <span class="text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="{{ $label }}">Nome do contrato *</span>
                <input name="nome" value="{{ old('nome', $contrato->nome) }}" required class="{{ $input }}" placeholder="Ex.: Manutenção industrial">
                @error('nome') <span class="text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Cliente</span>
                <input name="cliente" value="{{ old('cliente', $contrato->cliente) }}" class="{{ $input }}" placeholder="Ex.: Vale">
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Contratada</span>
                <input name="contratada" value="{{ old('contratada', $contrato->contratada) }}" class="{{ $input }}" placeholder="Ex.: Omega286">
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Tipo</span>
                <select name="tipo" class="{{ $input }}">
                    <option value="">Selecione...</option>
                    @foreach (['Prestação de serviço', 'Fornecimento', 'Locação', 'Manutenção', 'Outro'] as $tipo)
                        <option value="{{ $tipo }}" @selected(old('tipo', $contrato->tipo) === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Centro de custo</span>
                <input name="centro_custo" value="{{ old('centro_custo', $contrato->centro_custo) }}" class="{{ $input }}">
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="{{ $label }}">Local de execução</span>
                <input name="local_execucao" value="{{ old('local_execucao', $contrato->local_execucao) }}" class="{{ $input }}" placeholder="Unidade, frente ou local do contrato">
            </label>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Gestão</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">Responsáveis e status</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="{{ $label }}">Gestor</span>
                    <input name="gestor" value="{{ old('gestor', $contrato->gestor) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Fiscal</span>
                    <input name="fiscal" value="{{ old('fiscal', $contrato->fiscal) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Status *</span>
                    <select name="status" required class="{{ $input }}">
                        @foreach (['ativo' => 'Ativo', 'em_analise' => 'Em análise', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado', 'cancelado' => 'Cancelado', 'vencido' => 'Vencido'] as $value => $labelStatus)
                            <option value="{{ $value }}" @selected(old('status', $contrato->status ?: 'ativo') === $value)>{{ $labelStatus }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Valor</span>
                    <input type="number" step="0.01" min="0" name="valor" value="{{ old('valor', $contrato->valor) }}" class="{{ $input }}" placeholder="0,00">
                </label>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Vigência</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">Período contratual</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="{{ $label }}">Data de início</span>
                    <input type="date" name="data_inicio" value="{{ old('data_inicio', optional($contrato->data_inicio)->format('Y-m-d')) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Data de fim</span>
                    <input type="date" name="data_fim" value="{{ old('data_fim', optional($contrato->data_fim)->format('Y-m-d')) }}" class="{{ $input }}">
                    @error('data_fim') <span class="text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        <label class="space-y-2 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <span class="{{ $label }}">Objeto do contrato</span>
            <textarea name="objeto" rows="5" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Descreva o objeto principal...">{{ old('objeto', $contrato->objeto) }}</textarea>
        </label>
        <label class="space-y-2 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <span class="{{ $label }}">Observações</span>
            <textarea name="observacoes" rows="5" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Riscos, pontos de atenção, histórico...">{{ old('observacoes', $contrato->observacoes) }}</textarea>
        </label>
    </section>

    <label class="space-y-2 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <span class="{{ $label }}">Descrição complementar</span>
        <textarea name="descricao" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('descricao', $contrato->descricao) }}</textarea>
    </label>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('contratos.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">Cancelar</a>
        <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="save" class="h-4 w-4"></i>
            Salvar contrato
        </button>
    </div>
</div>
