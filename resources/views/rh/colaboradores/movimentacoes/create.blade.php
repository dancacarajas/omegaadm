@extends('layouts.app')

@section('title', 'Registrar movimentação - Omega286')
@section('eyebrow', 'Recursos Humanos / Efetivo')
@section('page-title', 'Registrar movimentação')

@section('actions')
    <a href="{{ route('rh.efetivo.show', $colaborador) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar à ficha
    </a>
@endsection

@section('content')
    <div class="mb-5 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
        <p class="text-sm text-brand-gray">Colaborador</p>
        <p class="mt-1 text-lg font-bold text-brand-black">{{ $colaborador->nome }}</p>
        <p class="text-sm text-brand-gray">{{ $colaborador->cargo ?: 'Cargo não informado' }} · {{ $colaborador->centro_custo ?: 'Sem centro de custo' }}</p>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($tipos as $key => $label)
            <a href="{{ route('rh.efetivo.movimentacoes.create', ['colaborador' => $colaborador, 'tipo' => $key]) }}"
               class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $tipo === $key ? 'bg-brand-burgundy text-white shadow-sm' : 'border border-zinc-200 bg-white text-brand-black hover:border-brand-burgundy' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('rh.efetivo.movimentacoes.store', $colaborador) }}" class="space-y-6">
        @csrf
        <input type="hidden" name="tipo" value="{{ $tipo }}">

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-brand-black">{{ $tipos[$tipo] ?? $tipo }}</h2>
            <p class="mt-1 text-sm text-brand-gray">O cadastro do colaborador será atualizado automaticamente após salvar.</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="data_inicio" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">
                        @if ($tipo === 'desligamento') Data do desligamento @else Data de início @endif
                    </label>
                    <input type="date" name="data_inicio" id="data_inicio" value="{{ old('data_inicio', today()->format('Y-m-d')) }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    @error('data_inicio')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>

                @if (in_array($tipo, ['ferias', 'afastamento_inss'], true))
                    <div>
                        <label for="data_fim" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Data fim @if ($tipo === 'ferias')<span class="text-red-600">*</span>@endif</label>
                        <input type="date" name="data_fim" id="data_fim" value="{{ old('data_fim') }}" @if ($tipo === 'ferias') required @endif class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                        @error('data_fim')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>

            @if ($tipo === 'desligamento')
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="tipo_rescisao" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo de rescisão</label>
                        <select name="tipo_rescisao" id="tipo_rescisao" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                            <option value="">Selecione</option>
                            @foreach ($tiposRescisao as $key => $label)
                                <option value="{{ $key }}" @selected(old('tipo_rescisao') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('tipo_rescisao')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="motivo_texto" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Motivo / observação</label>
                        <input type="text" name="motivo_texto" id="motivo_texto" value="{{ old('motivo_texto') }}" maxlength="500" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                </div>
            @endif

            @if ($tipo === 'transferencia_contrato')
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato / centro de custo atual</label>
                        <p class="mt-2 rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2 text-sm font-semibold text-brand-black">{{ $colaborador->centro_custo ?: '—' }}</p>
                    </div>
                    <div>
                        <label for="centro_custo_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Novo centro de custo / contrato</label>
                        <input type="text" name="centro_custo_novo" id="centro_custo_novo" value="{{ old('centro_custo_novo') }}" list="centros-custo-list" required maxlength="80" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                        <datalist id="centros-custo-list">
                            @foreach ($centrosCusto as $cc)
                                <option value="{{ $cc }}"></option>
                            @endforeach
                            @foreach ($contratos as $c)
                                <option value="{{ $c->centro_custo ?: $c->numero }}">{{ $c->numero }} — {{ $c->nome }}</option>
                            @endforeach
                        </datalist>
                        @error('centro_custo_novo')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="tipo_contrato_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo de contrato</label>
                        <input type="text" name="tipo_contrato_novo" id="tipo_contrato_novo" value="{{ old('tipo_contrato_novo', $colaborador->tipo_contrato) }}" maxlength="80" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                    <div>
                        <label for="local_trabalho_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Local de trabalho</label>
                        <input type="text" name="local_trabalho_novo" id="local_trabalho_novo" value="{{ old('local_trabalho_novo', $colaborador->local_trabalho) }}" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="departamento_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Departamento</label>
                        <input type="text" name="departamento_novo" id="departamento_novo" value="{{ old('departamento_novo', $colaborador->departamento) }}" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                </div>
            @endif

            @if ($tipo === 'promocao')
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Cargo atual</label>
                        <p class="mt-2 rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2 text-sm font-semibold">{{ $colaborador->cargo ?: '—' }}</p>
                    </div>
                    <div>
                        <label for="cargo_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Novo cargo</label>
                        <input type="text" name="cargo_novo" id="cargo_novo" value="{{ old('cargo_novo') }}" required maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                        @error('cargo_novo')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Salário atual</label>
                        <p class="mt-2 rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2 text-sm font-semibold">
                            {{ $colaborador->salario_inicial ? 'R$ '.number_format((float) $colaborador->salario_inicial, 2, ',', '.') : '—' }}
                        </p>
                    </div>
                    <div>
                        <label for="salario_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Novo salário</label>
                        <input type="number" name="salario_novo" id="salario_novo" value="{{ old('salario_novo') }}" min="0" step="0.01" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="departamento_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Departamento</label>
                        <input type="text" name="departamento_novo" id="departamento_novo" value="{{ old('departamento_novo', $colaborador->departamento) }}" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                </div>
            @endif

            @if ($tipo === 'mudanca_funcao')
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Função / cargo atual</label>
                        <p class="mt-2 rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2 text-sm font-semibold">{{ $colaborador->cargo ?: '—' }}</p>
                    </div>
                    <div>
                        <label for="cargo_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Nova função / cargo</label>
                        <input type="text" name="cargo_novo" id="cargo_novo" value="{{ old('cargo_novo') }}" required maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                        @error('cargo_novo')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="departamento_novo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Departamento</label>
                        <input type="text" name="departamento_novo" id="departamento_novo" value="{{ old('departamento_novo', $colaborador->departamento) }}" maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                </div>
            @endif

            @if ($tipo === 'ferias')
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="dias_ferias" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Dias de férias (opcional)</label>
                        <input type="number" name="dias_ferias" id="dias_ferias" value="{{ old('dias_ferias') }}" min="1" max="60" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                        <p class="mt-1 text-xs text-brand-gray">Se vazio, calcula pelas datas início e fim.</p>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-brand-black">
                            <input type="hidden" name="abono_pecuniario" value="0">
                            <input type="checkbox" name="abono_pecuniario" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy" @checked(old('abono_pecuniario'))>
                            Abono pecuniário (venda de férias)
                        </label>
                    </div>
                </div>
            @endif

            @if ($tipo === 'afastamento_inss')
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="especie_beneficio_inss" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Espécie do benefício</label>
                        <select name="especie_beneficio_inss" id="especie_beneficio_inss" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                            <option value="">Selecione</option>
                            @foreach ($especiesInss as $key => $label)
                                <option value="{{ $key }}" @selected(old('especie_beneficio_inss') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('especie_beneficio_inss')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="cid" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">CID (opcional)</label>
                        <input type="text" name="cid" id="cid" value="{{ old('cid') }}" maxlength="20" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                    <div>
                        <label for="data_fim" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Previsão de alta (opcional)</label>
                        <input type="date" name="data_fim" id="data_fim" value="{{ old('data_fim') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                    </div>
                </div>
            @endif

            <div class="mt-5">
                <label for="observacoes" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Observações internas</label>
                <textarea name="observacoes" id="observacoes" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">{{ old('observacoes') }}</textarea>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('rh.efetivo.show', $colaborador) }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black">Cancelar</a>
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-burgundy-dark">
                <i data-lucide="save" class="h-4 w-4"></i>
                Registrar movimentação
            </button>
        </div>
    </form>
@endsection
