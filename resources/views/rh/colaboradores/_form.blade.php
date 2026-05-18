@php
    $horarioEscalas = $horarioEscalas ?? collect();
    $value = fn (string $field) => old($field, data_get($colaborador, $field));
    $date = function (string $field) use ($colaborador) {
        $current = old($field, data_get($colaborador, $field));
        return $current instanceof \Carbon\CarbonInterface ? $current->format('Y-m-d') : $current;
    };
    $inputClass = 'mt-2 h-12 w-full rounded-xl border border-zinc-200 bg-white px-4 text-sm font-medium text-brand-black outline-none transition placeholder:text-zinc-400 focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10';
    $textareaClass = 'mt-2 min-h-28 w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-brand-black outline-none transition placeholder:text-zinc-400 focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10';
    $labelClass = 'text-[11px] font-bold uppercase tracking-wide text-brand-gray';
@endphp

@if ($errors->any())
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-3 text-sm text-brand-burgundy">
        <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5"></i>
        <div>
            <p class="font-bold">Revise os campos destacados.</p>
            <p class="mt-1 text-xs font-medium text-brand-burgundy/80">Algumas informações obrigatórias ainda precisam ser preenchidas.</p>
        </div>
    </div>
@endif

<div class="space-y-6">
    <section id="dados-pessoais" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20">
                    <i data-lucide="id-card" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">01 / Identificação</p>
                    <h2 class="mt-1 text-xl font-bold text-brand-black">Dados pessoais</h2>
                </div>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-burgundy-soft px-3 py-1.5 text-xs font-bold text-brand-burgundy">
                <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
                Ficha do empregado
            </span>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-3">
            <label class="md:col-span-2">
                <span class="{{ $labelClass }}">Nome completo *</span>
                <input name="nome" value="{{ $value('nome') }}" required class="{{ $inputClass }}">
                @error('nome') <span class="mt-1 block text-xs text-brand-burgundy">{{ $message }}</span> @enderror
            </label>
            <label>
                <span class="{{ $labelClass }}">Matrícula</span>
                <input name="matricula" value="{{ $value('matricula') }}" class="{{ $inputClass }}">
            </label>
            <div class="md:col-span-3 rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-5">
                <div class="flex flex-wrap items-start gap-4">
                    @if (filled($colaborador->foto_path))
                        <div class="shrink-0">
                            <p class="{{ $labelClass }} mb-2">Pré-visualização</p>
                            <img src="{{ $colaborador->urlFotoPerfil() }}" alt="Foto de perfil atual" class="h-28 w-28 rounded-xl object-cover shadow-sm ring-1 ring-zinc-200">
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <span class="{{ $labelClass }}">Anexar foto de perfil</span>
                        <p class="mt-1 text-xs font-medium text-brand-gray">Opcional. JPG, PNG, GIF ou WebP até 5&nbsp;MB. Aparece na ficha do colaborador e em telas SSMA (ex.: equipe do Kaizen).</p>
                        <input type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-3 block w-full text-sm font-medium text-brand-black file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-burgundy">
                        @error('foto_perfil') <span class="mt-1 block text-xs text-brand-burgundy">{{ $message }}</span> @enderror
                        @if (filled($colaborador->foto_path))
                            <p class="mt-2 text-xs text-brand-gray">Para trocar a foto, anexe um novo arquivo e salve o cadastro.</p>
                        @endif
                    </div>
                </div>
            </div>
            <label>
                <span class="{{ $labelClass }}">Telefone</span>
                <input name="telefone" value="{{ $value('telefone') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">CPF</span>
                <input name="cpf" value="{{ $value('cpf') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">RG</span>
                <input name="rg" value="{{ $value('rg') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Data de nascimento</span>
                <input type="date" name="data_nascimento" value="{{ $date('data_nascimento') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Local de nascimento</span>
                <input name="local_nascimento" value="{{ $value('local_nascimento') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">UF de nascimento</span>
                <input name="uf_nascimento" value="{{ $value('uf_nascimento') }}" maxlength="2" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Nacionalidade</span>
                <input name="nacionalidade" value="{{ $value('nacionalidade') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Estado civil</span>
                <input name="estado_civil" value="{{ $value('estado_civil') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Cônjuge</span>
                <input name="conjuge" value="{{ $value('conjuge') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Sexo</span>
                <input name="sexo" value="{{ $value('sexo') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Cor</span>
                <input name="cor" value="{{ $value('cor') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Grau de instrução</span>
                <input name="grau_instrucao" value="{{ $value('grau_instrucao') }}" class="{{ $inputClass }}">
            </label>
            <label class="md:col-span-2">
                <span class="{{ $labelClass }}">Filiação - Pai</span>
                <input name="filiacao_pai" value="{{ $value('filiacao_pai') }}" class="{{ $inputClass }}">
            </label>
            <label class="md:col-span-2">
                <span class="{{ $labelClass }}">Filiação - Mãe</span>
                <input name="filiacao_mae" value="{{ $value('filiacao_mae') }}" class="{{ $inputClass }}">
            </label>
        </div>
    </section>

    <section id="documentos" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="files" class="h-6 w-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">02 / Documentação</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Documentos e endereço</h2>
            </div>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-4">
            <label>
                <span class="{{ $labelClass }}">Carteira profissional</span>
                <input name="carteira_profissional" value="{{ $value('carteira_profissional') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Série CTPS</span>
                <input name="serie_ctps" value="{{ $value('serie_ctps') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Data CTPS</span>
                <input type="date" name="data_ctps" value="{{ $date('data_ctps') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Vencimento CTPS</span>
                <input type="date" name="vencimento_ctps" value="{{ $date('vencimento_ctps') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">PIS</span>
                <input name="pis" value="{{ $value('pis') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Título de eleitor</span>
                <input name="titulo_eleitor" value="{{ $value('titulo_eleitor') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Zona</span>
                <input name="zona_eleitoral" value="{{ $value('zona_eleitoral') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Seção</span>
                <input name="secao_eleitoral" value="{{ $value('secao_eleitoral') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Carteira de identidade</span>
                <input name="carteira_identidade" value="{{ $value('carteira_identidade') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Emissão da identidade</span>
                <input type="date" name="emissao_identidade" value="{{ $date('emissao_identidade') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Órgão emissor</span>
                <input name="orgao_emissor" value="{{ $value('orgao_emissor') }}" class="{{ $inputClass }}">
            </label>
            <label class="md:col-span-2">
                <span class="{{ $labelClass }}">Endereço</span>
                <input name="endereco" value="{{ $value('endereco') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Número</span>
                <input name="numero" value="{{ $value('numero') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Bairro</span>
                <input name="bairro" value="{{ $value('bairro') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Cidade</span>
                <input name="cidade" value="{{ $value('cidade') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Estado</span>
                <input name="estado" value="{{ $value('estado') }}" maxlength="2" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">CEP</span>
                <input name="cep" value="{{ $value('cep') }}" class="{{ $inputClass }}">
            </label>
        </div>
    </section>

    <section id="contrato" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="briefcase" class="h-6 w-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">03 / Vínculo</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Dados do contrato</h2>
                <p class="mt-2 max-w-3xl text-sm text-brand-gray">Cada colaborador pode ter uma <strong class="text-brand-black">escala de horários</strong> diferente. Cadastre as grades em <strong class="text-brand-black">RH → Frequência → Cadastro de horários</strong> e selecione abaixo a escala deste profissional — o ponto diário usará esses horários para calcular falta e extras.</p>
            </div>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-4">
            <label>
                <span class="{{ $labelClass }}">Tipo de contrato</span>
                <input name="tipo_contrato" value="{{ $value('tipo_contrato') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Status</span>
                <select name="status" class="{{ $inputClass }}">
                    @foreach (['ativo' => 'Ativo', 'afastado' => 'Afastado', 'desligado' => 'Desligado'] as $option => $label)
                        <option value="{{ $option }}" @selected($value('status') === $option)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="{{ $labelClass }}">Departamento</span>
                <input name="departamento" value="{{ $value('departamento') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Centro de custo</span>
                <input name="centro_custo" value="{{ $value('centro_custo') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Cargo</span>
                <input name="cargo" value="{{ $value('cargo') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">CBO</span>
                <input name="cbo" value="{{ $value('cbo') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Jornada semanal</span>
                <input name="jornada_semanal" value="{{ $value('jornada_semanal') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Horário (texto livre)</span>
                <input name="horario" value="{{ $value('horario') }}" class="{{ $inputClass }}" placeholder="Opcional — use o cadastro abaixo para escala semanal">
            </label>
            <label class="md:col-span-2">
                <span class="{{ $labelClass }}">Cadastro de horários</span>
                <select name="horario_escala_id" id="colab-horario-escala-id" class="{{ $inputClass }}" data-colab-escala-select>
                    <option value="" data-tipo="">— Sem vínculo —</option>
                    @foreach ($horarioEscalas as $escala)
                        @php
                            $rotuloTipo = match ($escala->tipo) {
                                'rotativa_semanal' => 'rotativa semanal',
                                'rotativa' => 'rotativa',
                                default => 'semanal',
                            };
                        @endphp
                        <option
                            value="{{ $escala->id }}"
                            data-tipo="{{ $escala->tipo }}"
                            @selected((string) old('horario_escala_id', $colaborador->horario_escala_id) === (string) $escala->id)
                        >
                            {{ $escala->nome }} · {{ $rotuloTipo }}@if ($escala->status !== 'ativo') (inativo)@endif
                        </option>
                    @endforeach
                </select>
                @error('horario_escala_id') <span class="mt-1 block text-xs text-brand-burgundy">{{ $message }}</span> @enderror
                <span class="mt-1 block text-xs font-medium text-brand-gray">
                    Motoristas em revezamento: escala <strong>rotativa semanal</strong> e grupo abaixo.
                    <a href="{{ route('rh.horarios.index') }}" class="font-bold text-brand-burgundy hover:underline">Cadastro de horários</a>
                </span>
            </label>
            <label class="md:col-span-2 hidden" data-colab-ciclo-offset-wrap>
                <span class="{{ $labelClass }}" data-colab-ciclo-offset-label>Grupo na rotatividade</span>
                <select name="horario_escala_ciclo_offset" id="colab-horario-ciclo-offset" class="{{ $inputClass }}">
                    <option value="0" data-rotativa-semanal="Grupo 0 — sem.1: seg, qua, sex" data-rotativa="Fase 0 — dia 1 do ciclo" @selected((string) old('horario_escala_ciclo_offset', $colaborador->horario_escala_ciclo_offset ?? 0) === '0')>Grupo 0 — sem.1: seg, qua, sex</option>
                    <option value="1" data-rotativa-semanal="Grupo 1 — sem.1: ter, qui" data-rotativa="Fase 1 — revezamento oposto" @selected((string) old('horario_escala_ciclo_offset', $colaborador->horario_escala_ciclo_offset ?? 0) === '1')>Grupo 1 — sem.1: ter, qui</option>
                </select>
                @error('horario_escala_ciclo_offset') <span class="mt-1 block text-xs text-brand-burgundy">{{ $message }}</span> @enderror
                <span class="mt-1 block text-xs font-medium text-brand-gray" data-colab-ciclo-offset-hint></span>
            </label>
            @if ($colaborador->exists && $colaborador->status === 'ativo')
                <div class="md:col-span-2 rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-4" role="note">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-900">Ponto pelo celular</p>
                            <p class="mt-1 text-sm text-emerald-950">
                                Acesso em <strong>/ponto</strong> com matrícula e CPF (seção Dados pessoais).
                            </p>
                            <ul class="mt-2 space-y-1 text-xs text-emerald-900/90">
                                <li>Matrícula: <span class="font-semibold">{{ $colaborador->matricula ?: '— não informada' }}</span></li>
                                <li>CPF: <span class="font-semibold">{{ $colaborador->cpf ?: '— não informado' }}</span></li>
                            </ul>
                        </div>
                        <a href="{{ url('/ponto') }}" target="_blank" rel="noopener" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-xs font-bold text-white shadow-sm">
                            <i data-lucide="smartphone" class="h-4 w-4"></i>
                            Abrir app de ponto
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section id="admissao" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="calendar-check" class="h-6 w-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">04 / Entrada</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Dados da admissão</h2>
            </div>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-4">
            <label>
                <span class="{{ $labelClass }}">Data de admissão</span>
                <input type="date" name="data_admissao" value="{{ $date('data_admissao') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Data da opção pelo FGTS</span>
                <input type="date" name="data_opcao_fgts" value="{{ $date('data_opcao_fgts') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Data de demissão</span>
                <input type="date" name="data_demissao" value="{{ $date('data_demissao') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Forma de pagamento</span>
                <input name="forma_pagamento" value="{{ $value('forma_pagamento') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Salário inicial</span>
                <input type="number" step="0.01" min="0" name="salario_inicial" value="{{ $value('salario_inicial') }}" class="{{ $inputClass }}">
            </label>
            <label class="md:col-span-2">
                <span class="{{ $labelClass }}">Local de trabalho</span>
                <input name="local_trabalho" value="{{ $value('local_trabalho') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Almoço</span>
                <input name="almoco" value="{{ $value('almoco') }}" class="{{ $inputClass }}">
            </label>
            <label class="md:col-span-2">
                <span class="{{ $labelClass }}">Dependentes</span>
                <textarea name="dependentes" class="{{ $textareaClass }}">{{ $value('dependentes') }}</textarea>
            </label>
            <label>
                <span class="{{ $labelClass }}">Contato de emergência</span>
                <input name="contato_emergencia_nome" value="{{ $value('contato_emergencia_nome') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Telefone de emergência</span>
                <input name="contato_emergencia_telefone" value="{{ $value('contato_emergencia_telefone') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Parentesco</span>
                <input name="contato_emergencia_parentesco" value="{{ $value('contato_emergencia_parentesco') }}" class="{{ $inputClass }}">
            </label>
            <label class="md:col-span-4">
                <span class="{{ $labelClass }}">Observações</span>
                <textarea name="observacoes" class="{{ $textareaClass }}">{{ $value('observacoes') }}</textarea>
            </label>
        </div>
    </section>

    <section id="mobilizacao-sgc" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="badge-check" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">05 / Mobilização</p>
                    <h2 class="mt-1 text-xl font-bold text-brand-black">Mobilização SGC Vale</h2>
                </div>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-burgundy-soft px-3 py-1.5 text-xs font-bold text-brand-burgundy">
                <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                Status automático pelo crachá
            </span>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-4">
            <label>
                <span class="{{ $labelClass }}">Status da mobilização</span>
                <select name="mobilizacao_status" class="{{ $inputClass }}">
                    @foreach ([
                        'pendente' => 'Pendente',
                        'postado_sgc' => 'Postado no SGC',
                        'aprovado' => 'Aprovado',
                        'mobilizacao_concluida' => 'Mobilização concluída',
                    ] as $option => $label)
                        <option value="{{ $option }}" @selected($value('mobilizacao_status') === $option)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="{{ $labelClass }}">Data postado no SGC</span>
                <input type="date" name="sgc_data_postagem" value="{{ $date('sgc_data_postagem') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Número da solicitação</span>
                <input name="sgc_numero_solicitacao" value="{{ $value('sgc_numero_solicitacao') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Data de aprovação</span>
                <input type="date" name="sgc_data_aprovacao" value="{{ $date('sgc_data_aprovacao') }}" class="{{ $inputClass }}">
            </label>
            <label>
                <span class="{{ $labelClass }}">Data de entrega do crachá</span>
                <input type="date" name="sgc_data_entrega_cracha" value="{{ $date('sgc_data_entrega_cracha') }}" class="{{ $inputClass }}">
            </label>
            <label class="md:col-span-3">
                <span class="{{ $labelClass }}">Observações da mobilização</span>
                <textarea name="sgc_observacoes" class="{{ $textareaClass }}">{{ $value('sgc_observacoes') }}</textarea>
            </label>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    const select = document.querySelector('[data-colab-escala-select]');
    const wrap = document.querySelector('[data-colab-ciclo-offset-wrap]');
    const offsetSelect = document.getElementById('colab-horario-ciclo-offset');
    const hint = document.querySelector('[data-colab-ciclo-offset-hint]');
    const label = document.querySelector('[data-colab-ciclo-offset-label]');
    if (!select || !wrap || !offsetSelect) return;

    const hints = {
        rotativa_semanal: 'Na semana 2 os grupos invertem (seg/qua/sex ↔ ter/qui). Sábado e domingo são folga para todos.',
        rotativa: 'Ciclo de 2 dias: fase 0 trabalha no dia 1, fase 1 no dia oposto.',
    };

    function tipoSelecionado() {
        const opt = select.options[select.selectedIndex];
        return opt?.dataset?.tipo || '';
    }

    function sync() {
        const tipo = tipoSelecionado();
        const rotativa = tipo === 'rotativa' || tipo === 'rotativa_semanal';
        wrap.classList.toggle('hidden', !rotativa);
        if (hint) hint.textContent = hints[tipo] || '';
        if (label) {
            label.textContent = tipo === 'rotativa_semanal'
                ? 'Grupo na rotatividade (semanal)'
                : 'Fase no ciclo (dia sim/não)';
        }
        if (rotativa) {
            [...offsetSelect.options].forEach((opt) => {
                const texto = tipo === 'rotativa_semanal'
                    ? opt.dataset.rotativaSemanal
                    : opt.dataset.rotativa;
                if (texto) opt.textContent = texto;
            });
        }
    }

    select.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
