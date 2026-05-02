@csrf

@php
    $input = 'h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10';
    $label = 'text-xs font-bold uppercase tracking-wide text-brand-gray';
@endphp

<div class="space-y-5">
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            Revise os campos destacados antes de salvar.
        </div>
    @endif

    <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Identificação</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Dados do equipamento</h2>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="scan-barcode" class="h-5 w-5"></i>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <label class="space-y-2">
                <span class="{{ $label }}">TAG patrimonial *</span>
                <input name="tag_patrimonial" value="{{ old('tag_patrimonial', $patrimonio->tag_patrimonial) }}" class="{{ $input }}" required placeholder="Ex.: PAT-0001">
                @error('tag_patrimonial') <span class="text-xs font-semibold text-red-700">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="{{ $label }}">Equipamento *</span>
                <input name="nome" value="{{ old('nome', $patrimonio->nome) }}" class="{{ $input }}" required placeholder="Ex.: Notebook Dell Latitude">
                @error('nome') <span class="text-xs font-semibold text-red-700">{{ $message }}</span> @enderror
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Categoria</span>
                <input name="categoria" value="{{ old('categoria', $patrimonio->categoria) }}" class="{{ $input }}" placeholder="Informática, ferramenta, EPI...">
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Tipo</span>
                <input name="tipo" value="{{ old('tipo', $patrimonio->tipo) }}" class="{{ $input }}" placeholder="Notebook, impressora, rádio...">
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Nº de série</span>
                <input name="numero_serie" value="{{ old('numero_serie', $patrimonio->numero_serie) }}" class="{{ $input }}">
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Marca</span>
                <input name="marca" value="{{ old('marca', $patrimonio->marca) }}" class="{{ $input }}">
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Modelo</span>
                <input name="modelo" value="{{ old('modelo', $patrimonio->modelo) }}" class="{{ $input }}">
            </label>
            <label class="space-y-2">
                <span class="{{ $label }}">Fornecedor</span>
                <input name="fornecedor" value="{{ old('fornecedor', $patrimonio->fornecedor) }}" class="{{ $input }}">
            </label>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Contrato</p>
            <h2 class="mt-1 text-lg font-bold text-brand-black">Vínculo contratual e aquisição</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="{{ $label }}">Contrato</span>
                    <input name="contrato" value="{{ old('contrato', $patrimonio->contrato) }}" class="{{ $input }}" placeholder="Contrato / frente">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Centro de custo</span>
                    <input name="centro_custo" value="{{ old('centro_custo', $patrimonio->centro_custo) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Data de aquisição</span>
                    <input type="date" name="data_aquisicao" value="{{ old('data_aquisicao', optional($patrimonio->data_aquisicao)->format('Y-m-d')) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Data de entrada</span>
                    <input type="date" name="data_entrada" value="{{ old('data_entrada', optional($patrimonio->data_entrada)->format('Y-m-d')) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="{{ $label }}">Valor</span>
                    <input type="number" step="0.01" min="0" name="valor" value="{{ old('valor', $patrimonio->valor) }}" class="{{ $input }}" placeholder="0,00">
                </label>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Responsabilidade</p>
            <h2 class="mt-1 text-lg font-bold text-brand-black">Localização e controle</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="{{ $label }}">Responsável</span>
                    <input name="responsavel" value="{{ old('responsavel', $patrimonio->responsavel) }}" class="{{ $input }}" placeholder="Colaborador responsável">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Setor</span>
                    <input name="setor" value="{{ old('setor', $patrimonio->setor) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="{{ $label }}">Localização</span>
                    <input name="localizacao" value="{{ old('localizacao', $patrimonio->localizacao) }}" class="{{ $input }}" placeholder="Unidade, sala, almoxarifado, frente...">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Status</span>
                    <select name="status" class="{{ $input }}">
                        @foreach (['ativo' => 'Ativo', 'em_uso' => 'Em uso', 'em_manutencao' => 'Em manutenção', 'reserva' => 'Reserva', 'baixado' => 'Baixado'] as $value => $name)
                            <option value="{{ $value }}" @selected(old('status', $patrimonio->status) === $value)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Condição</span>
                    <select name="condicao" class="{{ $input }}">
                        @foreach (['novo' => 'Novo', 'bom' => 'Bom', 'regular' => 'Regular', 'danificado' => 'Danificado', 'inutilizado' => 'Inutilizado'] as $value => $name)
                            <option value="{{ $value }}" @selected(old('condicao', $patrimonio->condicao) === $value)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Última conferência</span>
                    <input type="date" name="ultima_conferencia" value="{{ old('ultima_conferencia', optional($patrimonio->ultima_conferencia)->format('Y-m-d')) }}" class="{{ $input }}">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Próxima conferência</span>
                    <input type="date" name="proxima_conferencia" value="{{ old('proxima_conferencia', optional($patrimonio->proxima_conferencia)->format('Y-m-d')) }}" class="{{ $input }}">
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <label class="space-y-2">
            <span class="{{ $label }}">Observações</span>
            <textarea name="observacoes" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Informações adicionais, histórico rápido, pendências ou cuidados.">{{ old('observacoes', $patrimonio->observacoes) }}</textarea>
        </label>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('patrimonial.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Voltar
        </a>
        <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="save" class="h-4 w-4"></i>
            Salvar patrimônio
        </button>
    </div>
</div>
