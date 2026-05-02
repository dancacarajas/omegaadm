@csrf

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
        Corrija os campos destacados para continuar.
    </div>
@endif

<div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
    <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Cadastro da frota</p>
            <h2 class="mt-1 text-lg font-bold text-brand-black">Dados do veiculo</h2>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Placa *</span>
                <input name="placa" value="{{ old('placa', $veiculo->placa) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm uppercase outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" required>
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">RENAVAM</span>
                <input name="renavam" value="{{ old('renavam', $veiculo->renavam) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo</span>
                <input name="tipo" value="{{ old('tipo', $veiculo->tipo) }}" placeholder="Caminhonete, van..." class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Marca</span>
                <input name="marca" value="{{ old('marca', $veiculo->marca) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Modelo</span>
                <input name="modelo" value="{{ old('modelo', $veiculo->modelo) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Cor</span>
                <input name="cor" value="{{ old('cor', $veiculo->cor) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ano fabricacao</span>
                <input name="ano_fabricacao" value="{{ old('ano_fabricacao', $veiculo->ano_fabricacao) }}" maxlength="4" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ano modelo</span>
                <input name="ano_modelo" value="{{ old('ano_modelo', $veiculo->ano_modelo) }}" maxlength="4" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
                <select name="status" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    @foreach (['ativo' => 'Ativo', 'inativo' => 'Inativo', 'manutencao' => 'Manutencao'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $veiculo->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="mb-5">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Mobilizacao</p>
            <h2 class="mt-1 text-lg font-bold text-brand-black">Contrato e planejamento</h2>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                <input name="contrato" value="{{ old('contrato', $veiculo->contrato) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Linha contratual</span>
                <input name="linha_contratual" value="{{ old('linha_contratual', $veiculo->linha_contratual) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Proprietario</span>
                <input name="proprietario" value="{{ old('proprietario', $veiculo->proprietario) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Fornecedor</span>
                <input name="fornecedor" value="{{ old('fornecedor', $veiculo->fornecedor) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Inicio atividade</span>
                <input type="date" name="data_inicio_atividade" value="{{ old('data_inicio_atividade', optional($veiculo->data_inicio_atividade)->format('Y-m-d')) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Fim atividade</span>
                <input type="date" name="data_fim_atividade" value="{{ old('data_fim_atividade', optional($veiculo->data_fim_atividade)->format('Y-m-d')) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Liberacao para inspecao</span>
                <input type="date" name="data_liberacao_inspecao" value="{{ old('data_liberacao_inspecao', optional($veiculo->data_liberacao_inspecao)->format('Y-m-d')) }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Criterio tecnico</span>
                <input name="criterio_tecnico" value="{{ old('criterio_tecnico', $veiculo->criterio_tecnico) }}" placeholder="Checklist Excel, Anexo 6, requisito contratual..." class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
        </div>
    </section>
</div>

<section class="mt-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
    <label class="block space-y-2">
        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
        <textarea name="observacoes" rows="4" class="w-full rounded-lg border border-zinc-200 px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('observacoes', $veiculo->observacoes) }}</textarea>
    </label>

    <div class="mt-5 flex justify-end gap-2 border-t border-zinc-200 pt-5">
        <a href="{{ route('veiculos.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            Voltar
        </a>
        <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="save" class="h-4 w-4"></i>
            Salvar veiculo
        </button>
    </div>
</section>
