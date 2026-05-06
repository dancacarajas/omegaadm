@extends('layouts.app')

@section('title', ($registro ?? null) ? 'Editar Registro Mensal SSMA - Omega286' : 'Novo Registro Mensal SSMA - Omega286')
@section('eyebrow', 'SSMA / Registro Mensal')
@section('page-title', ($registro ?? null) ? 'Editar registro mensal' : 'Novo registro mensal')

@section('actions')
    <a href="{{ route('sesmt.registros.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $isEdit = isset($registro) && $registro;
        $fill = $fill ?? [];
        $competenciaValue = old(
            'competencia',
            $isEdit
                ? $registro->competencia->format('Y-m')
                : (request('competencia') ?: now()->format('Y-m'))
        );
        $stepMap = [
            'auditoria_mensal' => 'Auditoria Mensal',
            'inspecao_mensal_canteiro' => 'Inspeção Mensal - Canteiro',
            'treinamentos_mensais' => 'Treinamentos Mensais',
            'registro_acoes_proativas' => 'Registro - Ações Proativas',
            'boas_praticas_kaizen' => 'Boas Práticas - Kayzen',
            'acoes_reativas' => 'Ações Reativas',
            'campanha_seguranca' => 'Campanha de Segurança',
            'registro_acidente' => 'Registro de Acidente',
        ];
    @endphp

    <form id="form-ssma-registro-mensal" method="POST" action="{{ $isEdit ? route('sesmt.registros.update', $registro) : route('sesmt.registros.store') }}" enctype="multipart/form-data" data-ssma-stepper-form>
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/40 px-5 py-4">
                <h2 class="text-lg font-bold text-brand-black">Identificação do mês</h2>
                <p class="mt-1 text-sm text-brand-gray">Este formulário concentra o <strong>registro da competência</strong> (auditoria, inspeção, treinamentos, ações, campanhas, acidentes/incidentes e evidências). O controle amplo de conformidade por colaborador permanece em <a href="{{ route('sesmt.index') }}" class="font-semibold text-brand-burgundy underline-offset-2 hover:underline">Controle de Conformidade</a>.</p>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 lg:grid-cols-3">
                @if ($isEdit)
                    <input type="hidden" name="competencia" value="{{ $competenciaValue }}">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência</span>
                        <p class="mt-2 h-11 rounded-lg border border-zinc-100 bg-zinc-50 px-3 text-sm font-semibold leading-[2.75rem] text-brand-black">{{ $registro->competencia?->format('m/Y') }}</p>
                        <p class="mt-1 text-xs text-brand-gray">A competência não pode ser alterada.</p>
                    </div>
                @else
                    <label>
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência *</span>
                        <input type="month" name="competencia" value="{{ $competenciaValue }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                @endif
                <label class="lg:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Título do registro (opcional)</span>
                    <input name="titulo" value="{{ old('titulo', $isEdit ? $registro->titulo : null) }}" placeholder="Ex.: SSMA — Maio/2026 — Contrato 312" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável pelo preenchimento</span>
                    <input name="responsavel" value="{{ old('responsavel', $isEdit ? $registro->responsavel : null) }}" placeholder="Nome do responsável" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                    <input name="contrato" value="{{ old('contrato', $isEdit ? $registro->contrato : null) }}" placeholder="Código ou nome do contrato" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Local / base</span>
                    <input name="local_base" value="{{ old('local_base', $isEdit ? $registro->local_base : null) }}" placeholder="Canteiro, usina, site…" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Efetivo ativo no mês</span>
                    <input type="number" name="efetivo_ativo_mes" min="0" value="{{ old('efetivo_ativo_mes', $isEdit ? $registro->efetivo_ativo_mes : null) }}" placeholder="Quantidade de pessoas" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">HHT do mês (horas)</span>
                    <input type="number" name="hht_mes" min="0" step="0.01" value="{{ old('hht_mes', $isEdit ? $registro->hht_mes : null) }}" placeholder="Horas homem trabalhadas" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status do registro</span>
                    <select name="status" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        @foreach (\App\Models\SsmaRegistroMensal::STATUS as $val => $rotulo)
                            <option value="{{ $val }}" @selected(old('status', $isEdit ? $registro->status : 'rascunho') === $val)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="md:col-span-2 lg:col-span-3">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Comentário executivo do mês</span>
                    <textarea name="comentario_executivo" rows="3" placeholder="Síntese para diretoria: destaques, riscos e decisões necessárias." class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('comentario_executivo', $isEdit ? $registro->comentario_executivo : null) }}</textarea>
                </label>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
                <h2 class="text-xl font-bold text-brand-black">Etapas do registro mensal</h2>
                <p class="mt-1 text-sm text-brand-gray">Auditoria, inspeção de canteiro, treinamentos, ações proativas e reativas, Kaizen, campanha, acidentes/incidentes e evidências — todas referentes à competência acima.</p>
            </div>

            <div class="p-5">
                <div class="bs-stepper js-stepper">
                    <div class="bs-stepper-header !overflow-x-auto !pb-2" role="tablist">
                        @foreach ($stepMap as $slug => $label)
                            <div class="step" data-target="#{{ $slug }}">
                                <button type="button" class="step-trigger" id="{{ $slug }}-trigger" aria-controls="{{ $slug }}">
                                    <span class="bs-stepper-circle">{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="bs-stepper-label">{{ $label }}</span>
                                </button>
                            </div>
                            @if (! $loop->last)
                                <div class="line"></div>
                            @endif
                        @endforeach
                    </div>

                    <div class="bs-stepper-content mt-5">
                        @foreach ($stepMap as $slug => $label)
                            <div id="{{ $slug }}" class="content {{ $loop->first ? 'step-visible' : '' }}" role="tabpanel" aria-labelledby="{{ $slug }}-trigger">
                                @if ($slug === 'auditoria_mensal')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Preencha os dados da auditoria mensal e anexe as evidências fotográficas.</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_auditoria_mensal')
                                        </div>
                                    </div>
                                @elseif ($slug === 'inspecao_mensal_canteiro')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Preencha os dados da inspeção mensal e anexe as evidências fotográficas.</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_inspecao_mensal_canteiro')
                                        </div>
                                    </div>
                                @elseif ($slug === 'treinamentos_mensais')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Registre os treinamentos do mês em formato de grade.</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_treinamentos_mensais')
                                        </div>
                                    </div>
                                @elseif ($slug === 'registro_acoes_proativas')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Indicadores proativos: até 4 linhas nos blocos Vale e quase acidente; até 6 linhas nas tabelas Omega Service (interdição e notificação internas).</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_registro_acoes_proativas')
                                        </div>
                                    </div>
                                @elseif ($slug === 'boas_praticas_kaizen')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Projeto Kaizen: fotos antes/depois, equipe do efetivo e ganhos — base para o slide do registro mensal.</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_boas_praticas_kaizen')
                                        </div>
                                    </div>
                                @elseif ($slug === 'acoes_reativas')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Indicadores reativos em blocos separados: até 3 linhas na maioria; até 7 em Tratamento médico e Telemetria.</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_acoes_reativas')
                                        </div>
                                    </div>
                                @elseif ($slug === 'campanha_seguranca')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Uma ou mais campanhas/reuniões. O botão Adicionar campanha duplica o formulário (até 15 blocos); cada um tem campos e evidências próprios.</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_campanha_seguranca')
                                        </div>
                                    </div>
                                @elseif ($slug === 'registro_acidente')
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Classificação do acidente (material, pessoal, ambiental), data, hora, local e descrição — até 7 linhas; anexe até duas fotos de evidência.</p>
                                        <div class="mt-4">
                                            @include('sesmt.registros._step_registro_acidente')
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                        <h3 class="text-base font-bold text-brand-black">{{ $label }}</h3>
                                        <p class="mt-1 text-sm text-brand-gray">Registre status, responsável, data e observações da etapa.</p>

                                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                                            <label class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
                                                <input type="checkbox" name="etapas[{{ $slug }}][realizado]" value="1" @checked(old("etapas.$slug.realizado", data_get($fill, "$slug.realizado"))))>
                                                Etapa realizada
                                            </label>
                                            <label>
                                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de referência</span>
                                                <input type="date" name="etapas[{{ $slug }}][data_referencia]" value="{{ old("etapas.$slug.data_referencia", data_get($fill, "$slug.data_referencia")) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                            </label>
                                            <label>
                                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável da etapa</span>
                                                <input name="etapas[{{ $slug }}][responsavel]" value="{{ old("etapas.$slug.responsavel", data_get($fill, "$slug.responsavel")) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                            </label>
                                        </div>
                                        <label class="mt-4 block">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observações</span>
                                            <textarea name="etapas[{{ $slug }}][observacoes]" rows="4" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old("etapas.$slug.observacoes", data_get($fill, "$slug.observacoes")) }}</textarea>
                                        </label>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-zinc-50 px-5 py-4">
                <h2 class="text-lg font-bold text-brand-black">Observações gerais do mês</h2>
                <p class="mt-1 text-sm text-brand-gray">Contexto adicional que não ficou nas etapas (clima, visitas, integrações, pendências externas).</p>
            </div>
            <div class="p-5">
                <textarea name="observacoes_gerais_mes" rows="5" placeholder="Observações gerais sobre o mês de SSMA nesta competência." class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old('observacoes_gerais_mes', $isEdit ? $registro->observacoes_gerais_mes : null) }}</textarea>
            </div>
        </section>

        <section class="mb-6 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="text-lg font-bold text-brand-black">Resumo automático do que foi registrado</h2>
                <p class="mt-1 text-sm text-brand-gray">Gerado a partir das etapas preenchidas (após salvar, o resumo reflete o último estado).</p>
            </div>
            <div class="p-5">
                <ul class="list-inside list-disc space-y-2 text-sm text-brand-black">
                    @foreach ($resumoLinhas ?? [] as $linha)
                        <li>{{ $linha }}</li>
                    @endforeach
                </ul>
            </div>
        </section>

        <div class="sticky bottom-0 z-10 mt-6 rounded-t-2xl border border-b-0 border-zinc-200 bg-white/95 px-4 py-4 shadow-[0_-12px_30px_rgba(17,17,17,0.06)] backdrop-blur">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="inline-flex h-11 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy disabled:cursor-not-allowed disabled:opacity-50" @if (! $isEdit) disabled @endif @if ($isEdit) onclick="window.open('{{ route('sesmt.registros.preview', $registro) }}', '_blank', 'noopener')" @endif>
                        <i data-lucide="presentation" class="h-4 w-4"></i>
                        Gerar prévia para apresentação
                    </button>
                    <button type="submit" name="acao" value="enviar_validacao" class="inline-flex h-11 items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 text-sm font-semibold text-amber-950 shadow-sm transition hover:bg-amber-100">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        Enviar para validação SSMA
                    </button>
                </div>
                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('sesmt.registros.index', ['competencia' => $competenciaValue]) }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-5 py-3 text-sm font-semibold text-brand-black">
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Salvar registro mensal
                    </button>
                </div>
            </div>
            @if (! $isEdit)
                <p class="mt-3 text-xs text-brand-gray">Salve o registro uma vez para habilitar a prévia em nova aba (layout para impressão/apresentação).</p>
            @endif
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('form-ssma-registro-mensal');
            const stepperEl = document.querySelector('[data-ssma-stepper-form] .js-stepper');
            if (!form || !stepperEl) return;

            const t = (v) => (v == null ? '' : String(v).trim());
            const hasFile = (inp) => inp && inp.type === 'file' && inp.files && inp.files.length > 0;
            const radioChecked = (name) => !!form.querySelector(`input[name="${CSS.escape(name)}"]:checked`);

            function allFilesFilled(panel) {
                const files = panel.querySelectorAll('input[type="file"]');
                if (!files.length) return true;
                return [...files].every(hasFile);
            }

            function proactiveReativoRowInputs(row) {
                return [...row.querySelectorAll('input:not([type="hidden"])')].filter((i) => i.type !== 'button');
            }

            function rowEmptyOrFull(row, inputs) {
                const vals = inputs.map((i) => t(i.value));
                const any = vals.some((v) => v !== '');
                if (!any) return 'empty';
                const all = vals.every((v) => v !== '');
                return all ? 'full' : 'partial';
            }

            const validators = {
                auditoria_mensal(panel) {
                    if (!radioChecked('etapas[auditoria_mensal][passou_auditoria]')) return false;
                    if (!t(panel.querySelector('input[name="etapas[auditoria_mensal][data_auditoria]"]')?.value)) return false;
                    if (!t(panel.querySelector('input[name="etapas[auditoria_mensal][auditor]"]')?.value)) return false;
                    if (!t(panel.querySelector('input[name="etapas[auditoria_mensal][local]"]')?.value)) return false;
                    if (!t(panel.querySelector('textarea[name="etapas[auditoria_mensal][descricao]"]')?.value)) return false;
                    if (!t(panel.querySelector('input[name="etapas[auditoria_mensal][nota]"]')?.value)) return false;
                    return allFilesFilled(panel);
                },
                inspecao_mensal_canteiro(panel) {
                    if (!radioChecked('etapas[inspecao_mensal_canteiro][passou_inspecao]')) return false;
                    if (!t(panel.querySelector('input[name="etapas[inspecao_mensal_canteiro][data_inspecao]"]')?.value)) return false;
                    if (!t(panel.querySelector('input[name="etapas[inspecao_mensal_canteiro][inspetor]"]')?.value)) return false;
                    if (!t(panel.querySelector('input[name="etapas[inspecao_mensal_canteiro][local]"]')?.value)) return false;
                    if (!t(panel.querySelector('textarea[name="etapas[inspecao_mensal_canteiro][descricao]"]')?.value)) return false;
                    if (!t(panel.querySelector('input[name="etapas[inspecao_mensal_canteiro][nota]"]')?.value)) return false;
                    return allFilesFilled(panel);
                },
                treinamentos_mensais(panel) {
                    const rows = panel.querySelectorAll('tr[data-training-row]');
                    let hasFull = false;
                    for (const row of rows) {
                        const rac = row.querySelector('input[name*="[rac]"]')?.checked;
                        const nr = row.querySelector('input[name*="[nr]"]')?.checked;
                        const pro = t(row.querySelector('input[name*="[pro_outros]"]')?.value);
                        const data = t(row.querySelector('input[name*="[data]"]')?.value);
                        const inst = t(row.querySelector('input[name*="[instrutor]"]')?.value);
                        const tit = t(row.querySelector('input[name*="[titulo_descricao]"]')?.value);
                        const any = rac || nr || pro !== '' || data !== '' || inst !== '' || tit !== '';
                        if (!any) continue;
                        const tipo = rac || nr || pro !== '';
                        const full = tipo && data !== '' && inst !== '' && tit !== '';
                        if (!full) return false;
                        hasFull = true;
                    }
                    return hasFull;
                },
                registro_acoes_proativas(panel) {
                    let anyFull = false;
                    const blocks = panel.querySelectorAll('[data-proactive-block]');
                    for (const block of blocks) {
                        const rows = block.querySelectorAll('tr[data-proactive-row]');
                        for (const row of rows) {
                            const inputs = proactiveReativoRowInputs(row);
                            const st = rowEmptyOrFull(row, inputs);
                            if (st === 'partial') return false;
                            if (st === 'full') anyFull = true;
                        }
                    }
                    return anyFull;
                },
                boas_praticas_kaizen(panel) {
                    if (!hasFile(panel.querySelector('input[name="etapas[boas_praticas_kaizen][foto_antes]"]'))) return false;
                    if (!hasFile(panel.querySelector('input[name="etapas[boas_praticas_kaizen][foto_depois]"]'))) return false;
                    if (!t(panel.querySelector('input[name="etapas[boas_praticas_kaizen][titulo]"]')?.value)) return false;
                    if (!t(panel.querySelector('input[name="etapas[boas_praticas_kaizen][responsaveis]"]')?.value)) return false;
                    if (!t(panel.querySelector('textarea[name="etapas[boas_praticas_kaizen][ganhos_processo]"]')?.value)) return false;
                    const colabBoxes = [...panel.querySelectorAll('input[type="checkbox"]')].filter((i) => i.name.includes('colaborador_ids'));
                    if (colabBoxes.length && !colabBoxes.some((i) => i.checked)) return false;
                    return true;
                },
                acoes_reativas(panel) {
                    let anyFull = false;
                    const blocks = panel.querySelectorAll('[data-reativo-block]');
                    for (const block of blocks) {
                        const rows = block.querySelectorAll('tr[data-reativo-row]');
                        for (const row of rows) {
                            const inputs = proactiveReativoRowInputs(row);
                            const st = rowEmptyOrFull(row, inputs);
                            if (st === 'partial') return false;
                            if (st === 'full') anyFull = true;
                        }
                    }
                    return anyFull;
                },
                campanha_seguranca(panel) {
                    const items = panel.querySelectorAll('[data-campanha-item]');
                    if (!items.length) return false;
                    for (const item of items) {
                        if (!t(item.querySelector('input[name*="[titulo]"]')?.value)) return false;
                        if (!t(item.querySelector('input[name*="[data_reuniao]"]')?.value)) return false;
                        if (!t(item.querySelector('input[name*="[local]"]')?.value)) return false;
                        if (!t(item.querySelector('input[name*="[responsavel_campanha]"]')?.value)) return false;
                        if (!t(item.querySelector('input[name*="[gerencia]"]')?.value)) return false;
                        if (!t(item.querySelector('textarea[name*="[descricao]"]')?.value)) return false;
                        if (!allFilesFilled(item)) return false;
                    }
                    return true;
                },
                registro_acidente(panel) {
                    if (!allFilesFilled(panel)) return false;
                    const rows = panel.querySelectorAll('tr[data-acidente-row]');
                    let hasFull = false;
                    for (const row of rows) {
                        const mat = row.querySelector('input[type="checkbox"][name*="[material]"]')?.checked;
                        const pes = row.querySelector('input[type="checkbox"][name*="[pessoal]"]')?.checked;
                        const amb = row.querySelector('input[type="checkbox"][name*="[ambiental]"]')?.checked;
                        const data = t(row.querySelector('input[type="date"]')?.value);
                        const hora = t(row.querySelector('input[type="time"]')?.value);
                        const texts = row.querySelectorAll('input[type="text"]');
                        const local = t(texts[0]?.value);
                        const desc = t(texts[1]?.value);
                        const any = mat || pes || amb || data !== '' || hora !== '' || local !== '' || desc !== '';
                        if (!any) continue;
                        const full = (mat || pes || amb) && data !== '' && hora !== '' && local !== '' && desc !== '';
                        if (!full) return false;
                        hasFull = true;
                    }
                    return hasFull;
                },
            };

            function panelForSlug(slug) {
                return document.getElementById(slug);
            }

            let raf = 0;
            function update() {
                cancelAnimationFrame(raf);
                raf = requestAnimationFrame(() => {
                    stepperEl.querySelectorAll('.step').forEach((stepEl) => {
                        const target = stepEl.getAttribute('data-target');
                        const slug = target && target.startsWith('#') ? target.slice(1) : '';
                        const fn = validators[slug];
                        const panel = slug ? panelForSlug(slug) : null;
                        const ok = panel && fn ? fn(panel) : false;
                        stepEl.classList.toggle('is-complete', !!ok);
                    });
                });
            }

            form.addEventListener('input', update);
            form.addEventListener('change', update);
            form.addEventListener('click', () => setTimeout(update, 0));
            update();
        })();
    </script>
@endpush
