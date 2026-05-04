@extends('layouts.app')

@section('title', ($vaga?->exists ? 'Editar vaga' : 'Nova vaga').' - Omega286')
@section('eyebrow', 'RH / Recrutamento')
@section('page-title', $vaga?->exists ? 'Editar vaga' : 'Nova vaga')

@section('actions')
    <a href="{{ route('rh.recrutamento.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-recrutamento', 'number' => '01', 'label' => 'Recrutamento e Seleção'],
            ['id' => 'step-treinamentos', 'number' => '02', 'label' => 'Treinamentos'],
            ['id' => 'step-assinatura', 'number' => '03', 'label' => 'Assinatura Documental'],
            ['id' => 'step-sgc', 'number' => '04', 'label' => 'Postagem SGC'],
            ['id' => 'step-liberacao', 'number' => '05', 'label' => 'Liberação para Atividades'],
        ];
        $state = old('form_state', json_encode($vaga?->form_state ?? [], JSON_UNESCAPED_UNICODE));
        $stateArray = json_decode($state ?: '{}', true) ?: [];
        $field = fn (string $key, string $default = '') => $stateArray[$key] ?? $default;
    @endphp

    <form method="POST" action="{{ $vaga?->exists ? route('rh.recrutamento.update', $vaga) : route('rh.recrutamento.store') }}" class="space-y-5">
        @csrf
        @if ($vaga?->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="form_state" value="{{ $state }}" data-rh-state>

    <section class="bs-stepper js-stepper rounded-xl border border-zinc-200 bg-white shadow-sm" data-rh-stepper>
        <div class="bs-stepper-header overflow-x-auto border-b border-zinc-200 p-4" role="tablist">
            @foreach ($steps as $index => $step)
                <div class="step {{ $index === 0 ? 'active' : '' }}" data-target="#{{ $step['id'] }}">
                    <button type="button" class="step-trigger min-w-max gap-2 rounded-lg px-2 py-2" role="tab" aria-controls="{{ $step['id'] }}" id="{{ $step['id'] }}-trigger">
                        <span class="bs-stepper-circle">{{ $step['number'] }}</span>
                        <span class="bs-stepper-label">{{ $step['label'] }}</span>
                    </button>
                </div>
                @if (! $loop->last)
                    <div class="line"></div>
                @endif
            @endforeach
        </div>

        <div class="border-b border-zinc-200 px-5 py-3">
            <div class="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-brand-gray">
                <span>Progresso do fluxo RH</span>
                <span data-rh-progress-label>0%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                <div data-rh-progress-bar class="h-full w-0 rounded-full bg-brand-burgundy transition-all duration-300 ease-out"></div>
            </div>
        </div>

        <div class="bs-stepper-content p-5 lg:p-6">
            <div id="step-recrutamento" class="content active dstepper-block step-visible" role="tabpanel" aria-labelledby="step-recrutamento-trigger" data-rh-step="step-recrutamento">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                        <h2 class="text-3xl font-black text-[#3f0812]">Recrutamento e Seleção</h2>
                        <p class="mt-2 text-sm text-brand-gray">Cadastre a vaga, valide a aprovação e conduza a seleção do candidato.</p>

                        <div class="mt-6 rounded-2xl border border-zinc-200 bg-brand-gray-soft/40 p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Cadastro da vaga</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Dados principais da demanda</h3>
                                </div>
                                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-bold text-brand-gray shadow-sm">
                                    <i data-lucide="briefcase-business" class="h-4 w-4 text-brand-burgundy"></i>
                                    Abertura
                                </span>
                            </div>

                            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                                <label class="space-y-2 lg:col-span-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Título da vaga</span>
                                    <input data-rh-field="vaga_titulo" value="{{ $field('vaga_titulo') }}" placeholder="Ex.: Motorista de ônibus" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Quantidade</span>
                                    <input type="number" min="1" data-rh-field="vaga_quantidade" value="{{ $field('vaga_quantidade', '1') }}" placeholder="1" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Prioridade</span>
                                    <select data-rh-field="vaga_prioridade" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        <option value="">Selecione...</option>
                                        <option @selected($field('vaga_prioridade') === 'Baixa')>Baixa</option>
                                        <option @selected($field('vaga_prioridade') === 'Média')>Média</option>
                                        <option @selected($field('vaga_prioridade') === 'Alta')>Alta</option>
                                        <option @selected($field('vaga_prioridade') === 'Urgente')>Urgente</option>
                                    </select>
                                </label>

                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo de vaga</span>
                                    <select data-rh-field="vaga_tipo" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        <option value="">Selecione...</option>
                                        <option @selected($field('vaga_tipo') === 'Nova vaga')>Nova vaga</option>
                                        <option @selected($field('vaga_tipo') === 'Reposição')>Reposição</option>
                                        <option @selected($field('vaga_tipo') === 'Aumento de quadro')>Aumento de quadro</option>
                                        <option @selected($field('vaga_tipo') === 'Temporária')>Temporária</option>
                                    </select>
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                                    <input data-rh-field="vaga_contrato" value="{{ $field('vaga_contrato') }}" placeholder="Contrato / centro de custo" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Gestor solicitante</span>
                                    <input data-rh-field="vaga_gestor" value="{{ $field('vaga_gestor') }}" placeholder="Nome do gestor" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Local de trabalho</span>
                                    <input data-rh-field="vaga_local" value="{{ $field('vaga_local') }}" placeholder="Unidade / frente" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>

                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da solicitação</span>
                                    <input type="date" data-rh-field="vaga_data_solicitacao" value="{{ $field('vaga_data_solicitacao') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Previsão de início</span>
                                    <input type="date" data-rh-field="vaga_previsao_inicio" value="{{ $field('vaga_previsao_inicio') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Salário / faixa</span>
                                    <input data-rh-field="vaga_salario" value="{{ $field('vaga_salario') }}" placeholder="Ex.: R$ 3.500,00" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status da vaga</span>
                                    <select data-rh-field="vaga_status" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        <option @selected($field('vaga_status', 'Em abertura') === 'Em abertura')>Em abertura</option>
                                        <option @selected($field('vaga_status') === 'Aprovada')>Aprovada</option>
                                        <option @selected($field('vaga_status') === 'Em divulgação')>Em divulgação</option>
                                        <option @selected($field('vaga_status') === 'Em seleção')>Em seleção</option>
                                        <option @selected($field('vaga_status') === 'Congelada')>Congelada</option>
                                    </select>
                                </label>

                                <label class="space-y-2 lg:col-span-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição da vaga</span>
                                    <textarea data-rh-field="vaga_descricao" rows="4" placeholder="Responsabilidades, rotina e objetivo da contratação..." class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $field('vaga_descricao') }}</textarea>
                                </label>
                                <label class="space-y-2 lg:col-span-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Requisitos</span>
                                    <textarea data-rh-field="vaga_requisitos" rows="4" placeholder="Experiência, documentos, CNH, escolaridade, treinamentos desejados..." class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $field('vaga_requisitos') }}</textarea>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <label class="flex gap-3 rounded-xl border border-zinc-200 p-4"><input type="checkbox" class="mt-1 h-5 w-5 rounded border-zinc-300 accent-[#6f1731]" data-rh-check="step-recrutamento"><span><strong class="block">Vaga aberta e aprovada</strong><small class="text-brand-gray">Demanda formalizada com gestor e contrato.</small></span></label>
                            <label class="flex gap-3 rounded-xl border border-zinc-200 p-4"><input type="checkbox" class="mt-1 h-5 w-5 rounded border-zinc-300 accent-[#6f1731]" data-rh-check="step-recrutamento"><span><strong class="block">Triagem e entrevistas concluídas</strong><small class="text-brand-gray">Perfil técnico e comportamental avaliados.</small></span></label>
                            <label class="flex gap-3 rounded-xl border border-zinc-200 p-4"><input type="checkbox" class="mt-1 h-5 w-5 rounded border-zinc-300 accent-[#6f1731]" data-rh-check="step-recrutamento" data-rh-approved-toggle><span><strong class="block">Iniciar controle dos candidatos aprovados</strong><small class="text-brand-gray">Criar uma ficha independente para cada posição aberta na vaga.</small></span></label>
                        </div>

                        <div class="mt-4 hidden rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5" data-rh-approved-fields>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-emerald-700">Candidatos aprovados</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Controle por posição da vaga</h3>
                                </div>
                                <span class="rounded-full bg-white px-3 py-2 text-xs font-bold text-emerald-700 shadow-sm" data-rh-approved-count>0 posições</span>
                            </div>
                            <div class="mt-4 grid gap-4" data-rh-approved-candidates></div>
                        </div>
                    </section>
                    <aside class="h-fit min-w-0 self-start rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm lg:sticky lg:top-28 lg:z-[5] lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto">
                        <h3 class="text-lg font-black text-brand-black">Ação da etapa</h3>
                        <p class="mt-2 text-xs text-brand-gray">Quando concluir os itens, avance para Treinamentos.</p>
                        <button type="button" class="mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white" data-rh-next-step="step-treinamentos" data-rh-save-next>Concluir Passo 01</button>
                        <button type="submit" class="mt-2 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Salvar vaga
                        </button>
                    </aside>
                </div>
            </div>

            <div id="step-treinamentos" class="content" role="tabpanel" aria-labelledby="step-treinamentos-trigger" data-rh-step="step-treinamentos">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                        <h2 class="text-3xl font-black text-[#3f0812]">Treinamentos</h2>
                        <p class="mt-2 text-sm text-brand-gray">Cada candidato aprovado segue com treinamento próprio, sem depender das outras posições da vaga.</p>
                        <div class="mt-6 space-y-4" data-rh-candidate-step="treinamentos"></div>
                    </section>
                    <aside class="h-fit min-w-0 self-start rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm lg:sticky lg:top-28 lg:z-[5] lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto">
                        <button type="button" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-zinc-200 bg-white text-sm font-semibold text-brand-black" data-rh-prev-step="step-recrutamento">Voltar Passo 01</button>
                        <button type="button" class="mt-2 inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-burgundy text-sm font-semibold text-white" data-rh-next-step="step-assinatura" data-rh-save-next>Concluir Passo 02</button>
                    </aside>
                </div>
            </div>

            <div id="step-assinatura" class="content" role="tabpanel" aria-labelledby="step-assinatura-trigger" data-rh-step="step-assinatura">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                        <h2 class="text-3xl font-black text-[#3f0812]">Assinatura Documental</h2>
                        <p class="mt-2 text-sm text-brand-gray">A assinatura documental é feita por candidato aprovado.</p>
                        <div class="mt-6 space-y-4" data-rh-candidate-step="assinatura"></div>
                    </section>
                    <aside class="h-fit min-w-0 self-start rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm lg:sticky lg:top-28 lg:z-[5] lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto">
                        <button type="button" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-zinc-200 bg-white text-sm font-semibold text-brand-black" data-rh-prev-step="step-treinamentos">Voltar Passo 02</button>
                        <button type="button" class="mt-2 inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-burgundy text-sm font-semibold text-white" data-rh-next-step="step-sgc" data-rh-save-next>Concluir Passo 03</button>
                    </aside>
                </div>
            </div>

            <div id="step-sgc" class="content" role="tabpanel" aria-labelledby="step-sgc-trigger" data-rh-step="step-sgc">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                        <h2 class="text-3xl font-black text-[#3f0812]">Postagem SGC</h2>
                        <p class="mt-2 text-sm text-brand-gray">Postagem e acompanhamento no SGC por candidato.</p>
                        <div class="mt-6 space-y-4" data-rh-candidate-step="sgc"></div>
                    </section>
                    <aside class="h-fit min-w-0 self-start rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm lg:sticky lg:top-28 lg:z-[5] lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto">
                        <button type="button" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-zinc-200 bg-white text-sm font-semibold text-brand-black" data-rh-prev-step="step-assinatura">Voltar Passo 03</button>
                        <button type="button" class="mt-2 inline-flex h-10 w-full items-center justify-center rounded-lg bg-brand-burgundy text-sm font-semibold text-white" data-rh-next-step="step-liberacao" data-rh-save-next>Concluir Passo 04</button>
                    </aside>
                </div>
            </div>

            <div id="step-liberacao" class="content" role="tabpanel" aria-labelledby="step-liberacao-trigger" data-rh-step="step-liberacao">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                        <h2 class="text-3xl font-black text-[#3f0812]">Liberação para Atividades</h2>
                        <p class="mt-2 text-sm text-brand-gray">Liberação final individual para início das atividades.</p>
                        <div class="mt-6 space-y-4" data-rh-candidate-step="liberacao"></div>
                    </section>
                    <aside class="h-fit min-w-0 self-start rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm lg:sticky lg:top-28 lg:z-[5] lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto">
                        <button type="button" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-zinc-200 bg-white text-sm font-semibold text-brand-black" data-rh-prev-step="step-sgc">Voltar Passo 04</button>
                        <div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">Fluxo RH concluído quando todos os itens dos 5 passos estiverem completos.</div>
                    </aside>
                </div>
            </div>
        </div>
    </section>
        <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('rh.recrutamento.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Voltar para lista
            </a>
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="save" class="h-4 w-4"></i>
                Salvar vaga
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.querySelector('[data-rh-stepper]');
            if (!wrapper) return;

            const stateInput = wrapper.closest('form')?.querySelector('[data-rh-state]');
            const progressBar = wrapper.querySelector('[data-rh-progress-bar]');
            const progressLabel = wrapper.querySelector('[data-rh-progress-label]');
            const triggers = Array.from(wrapper.querySelectorAll('.step-trigger'));
            const checks = Array.from(wrapper.querySelectorAll('[data-rh-check]'));
            let fields = Array.from(wrapper.querySelectorAll('[data-rh-field]'));
            const approvedToggle = wrapper.querySelector('[data-rh-approved-toggle]');
            const approvedFields = wrapper.querySelector('[data-rh-approved-fields]');
            const approvedCandidates = wrapper.querySelector('[data-rh-approved-candidates]');
            const approvedCount = wrapper.querySelector('[data-rh-approved-count]');
            const quantityField = wrapper.querySelector('[data-rh-field="vaga_quantidade"]');
            const candidateStepContainers = Array.from(wrapper.querySelectorAll('[data-rh-candidate-step]'));
            const candidateStepConfig = {
                treinamentos: {
                    checks: [
                        ['matriz', 'Matriz definida', 'Cursos alinhados ao cargo e contrato.'],
                        ['realizados', 'Treinamentos realizados', 'Presença e conclusão registradas.'],
                        ['certificados', 'Certificados emitidos', 'Comprovantes prontos para anexar.'],
                    ],
                },
                assinatura: {
                    checks: [
                        ['pendencias', 'Pendências tratadas', 'Documentação completa antes de assinatura.'],
                        ['contrato', 'Contrato assinado', 'Assinatura do colaborador validada.'],
                        ['kit', 'Kit documental pronto', 'Pronto para envio ao SGC.'],
                    ],
                },
                sgc: {
                    checks: [
                        ['postagem', 'Kit enviado no SGC', 'Postagem concluída com protocolo.'],
                        ['aguardando', 'Aguardando análise', 'Processo postado e em análise no SGC.'],
                        ['pendencias', 'Pendências', 'Correção e novo envio quando necessário.'],
                        ['cracha', 'Status Crachá Liberado', 'Condição para liberar início operacional.'],
                    ],
                },
                liberacao: {
                    checks: [
                        ['orientado', 'Colaborador orientado', 'Orientações finais repassadas no mesmo dia.'],
                        ['epi', 'Uniforme e EPI entregues', 'Entrega documentada pelo RH/ADM.'],
                        ['rota', 'Rota e início confirmados', 'Colaborador apto para iniciar atividades.'],
                    ],
                },
            };

            const loadState = () => {
                try {
                    return JSON.parse(stateInput?.value || '{}');
                } catch (e) {
                    return {};
                }
            };

            const saveState = (state) => {
                if (stateInput) {
                    stateInput.value = JSON.stringify(state);
                }
            };

            const todayDate = () => {
                const date = new Date();
                date.setHours(0, 0, 0, 0);

                return date;
            };

            const dateReached = (value) => {
                if (!value) return false;

                const date = new Date(`${value}T00:00:00`);
                if (Number.isNaN(date.getTime())) return false;
                if (date.getFullYear() < 1900) return false;

                return date <= todayDate();
            };

            const parseDate = (value) => {
                if (!value) return null;

                const date = new Date(`${value}T00:00:00`);
                if (Number.isNaN(date.getTime()) || date.getFullYear() < 1900) {
                    return null;
                }

                return date;
            };

            const formatDateInput = (date) => date.toISOString().slice(0, 10);
            const addDays = (date, days) => {
                const nextDate = new Date(date);
                nextDate.setDate(nextDate.getDate() + days);

                return nextDate;
            };

            const daysBetween = (start, end) => Math.max(0, Math.ceil((end - start) / 86400000));

            const trainingFollowUp = (position) => {
                const state = loadState();
                const plannedEnd = parseDate(state[`candidato_${position}_treinamentos_data_fim`] || '');
                const confirmedAt = parseDate(state[`candidato_${position}_treinamentos_data_confirmacao`] || '');

                if (!plannedEnd) {
                    return {
                        label: 'Informe a data fim programada para acompanhar o prazo de 5 dias.',
                        className: 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
                    };
                }

                if (confirmedAt) {
                    const delay = daysBetween(plannedEnd, confirmedAt);

                    if (delay > 0) {
                        return {
                            label: `Finalizado com atraso de ${delay} dia${delay > 1 ? 's' : ''}.`,
                            className: 'border-red-200 bg-red-50 text-red-700',
                        };
                    }

                    return {
                        label: 'Finalizado dentro do prazo.',
                        className: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    };
                }

                const today = todayDate();
                const delay = daysBetween(plannedEnd, today);

                if (delay > 0) {
                    return {
                        label: `Treinamentos atrasados ha ${delay} dia${delay > 1 ? 's' : ''}.`,
                        className: 'border-red-200 bg-red-50 text-red-700',
                    };
                }

                return {
                    label: 'Treinamentos dentro do prazo programado.',
                    className: 'border-amber-200 bg-amber-50 text-amber-700',
                };
            };

            const signatureFollowUp = (position) => {
                const state = loadState();
                const trainingConfirmedAt = parseDate(state[`candidato_${position}_treinamentos_data_confirmacao`] || '');
                const signatureScheduledAt = parseDate(state[`candidato_${position}_assinatura_data_programada`] || '');
                const signatureConfirmedAt = parseDate(state[`candidato_${position}_assinatura_data_confirmacao`] || '');

                if (!trainingConfirmedAt) {
                    return {
                        label: 'Aguardando confirmação dos treinamentos para iniciar a contagem.',
                        className: 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
                    };
                }

                if (!signatureConfirmedAt) {
                    return {
                        label: signatureScheduledAt
                            ? 'Assinatura programada. Aguardando confirmação.'
                            : 'Informe a data programada e a confirmação da assinatura.',
                        className: 'border-amber-200 bg-amber-50 text-amber-700',
                    };
                }

                const duration = daysBetween(trainingConfirmedAt, signatureConfirmedAt);

                return {
                    label: `Assinatura concluída em ${duration} dia${duration > 1 ? 's' : ''} após os treinamentos.`,
                    className: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                };
            };

            const recruitmentFollowUp = (position) => {
                const state = loadState();
                const requestedAt = parseDate(state.vaga_data_solicitacao || '');
                const acceptedAt = parseDate(state[`candidato_${position}_data_aceite`] || '');

                if (!requestedAt) {
                    return {
                        label: 'Informe a data da solicitação para calcular o tempo do Passo 01.',
                        className: 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
                    };
                }

                if (!acceptedAt) {
                    return {
                        label: 'Aguardando data de aceite do candidato.',
                        className: 'border-amber-200 bg-amber-50 text-amber-700',
                    };
                }

                const duration = daysBetween(requestedAt, acceptedAt);

                return {
                    label: `Passo 01 concluído em ${duration} dia${duration > 1 ? 's' : ''} após a solicitação.`,
                    className: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                };
            };

            const sgcFollowUp = (position) => {
                const state = loadState();
                const signatureConfirmedAt = parseDate(state[`candidato_${position}_assinatura_data_confirmacao`] || '');
                const mobilizedAt = parseDate(state[`candidato_${position}_sgc_data_mobilizacao`] || '');
                const postedAt = parseDate(state[`candidato_${position}_sgc_data_postagem`] || '');

                if (!signatureConfirmedAt) {
                    return {
                        label: 'Aguardando confirmação da assinatura para iniciar a contagem.',
                        className: 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
                    };
                }

                if (!mobilizedAt) {
                    return {
                        label: postedAt ? 'Postagem registrada. Aguardando mobilização.' : 'Informe a postagem e a data de mobilização.',
                        className: 'border-amber-200 bg-amber-50 text-amber-700',
                    };
                }

                const duration = daysBetween(signatureConfirmedAt, mobilizedAt);

                return {
                    label: `Mobilização concluída em ${duration} dia${duration > 1 ? 's' : ''} após a assinatura.`,
                    className: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                };
            };

            const applyAutomaticCandidateRules = () => {
                const state = loadState();
                const quantity = Math.max(1, Math.min(50, Number.parseInt(state.vaga_quantidade || quantityField?.value || '1', 10) || 1));

                Array.from({ length: quantity }, (_, index) => index + 1).forEach((position) => {
                    const trainingDateFields = [
                        `candidato_${position}_treinamentos_data_inicio`,
                        `candidato_${position}_treinamentos_data_fim`,
                        `candidato_${position}_treinamentos_data_confirmacao`,
                        `candidato_${position}_assinatura_data_programada`,
                        `candidato_${position}_assinatura_data_confirmacao`,
                        `candidato_${position}_sgc_data_postagem`,
                        `candidato_${position}_sgc_data_nova_postagem`,
                        `candidato_${position}_sgc_data_mobilizacao`,
                        `candidato_${position}_liberacao_orientado_data`,
                        `candidato_${position}_liberacao_epi_data`,
                    ];

                    trainingDateFields.forEach((key) => {
                        if ((state[key] || '').trim() !== '' && !parseDate(state[key])) {
                            state[key] = '';
                        }
                    });

                    const trainingStart = state[`candidato_${position}_treinamentos_data_inicio`] || '';
                    let trainingEnd = state[`candidato_${position}_treinamentos_data_fim`] || '';
                    const trainingConfirmedAt = state[`candidato_${position}_treinamentos_data_confirmacao`] || '';

                    if (trainingStart.trim() !== '' && trainingEnd.trim() === '') {
                        const startDate = parseDate(trainingStart);

                        if (startDate) {
                            trainingEnd = formatDateInput(addDays(startDate, 5));
                            state[`candidato_${position}_treinamentos_data_fim`] = trainingEnd;
                        }
                    }

                    const trainingConfirmed = parseDate(trainingConfirmedAt) !== null;

                    state[`candidato_${position}_treinamentos_matriz`] = trainingStart.trim() !== '';
                    state[`candidato_${position}_treinamentos_realizados`] = trainingConfirmed;
                    state[`candidato_${position}_treinamentos_certificados`] = trainingConfirmed;

                    const signatureConfirmed = parseDate(state[`candidato_${position}_assinatura_data_confirmacao`] || '') !== null;
                    state[`candidato_${position}_assinatura_pendencias`] = signatureConfirmed;
                    state[`candidato_${position}_assinatura_contrato`] = signatureConfirmed;
                    state[`candidato_${position}_assinatura_kit`] = signatureConfirmed;

                    const sgcPosted = parseDate(state[`candidato_${position}_sgc_data_postagem`] || '') !== null;
                    const sgcPendencyDescription = (state[`candidato_${position}_sgc_pendencia_descricao`] || '').trim();
                    const sgcReposted = parseDate(state[`candidato_${position}_sgc_data_nova_postagem`] || '') !== null;
                    const sgcMobilized = parseDate(state[`candidato_${position}_sgc_data_mobilizacao`] || '') !== null;
                    state[`candidato_${position}_sgc_postagem`] = sgcPosted;
                    state[`candidato_${position}_sgc_aguardando`] = sgcPosted;
                    state[`candidato_${position}_sgc_pendencias`] = sgcPendencyDescription === '' ? sgcMobilized : sgcReposted;
                    state[`candidato_${position}_sgc_cracha`] = sgcMobilized;

                    state[`candidato_${position}_liberacao_orientado`] = parseDate(state[`candidato_${position}_liberacao_orientado_data`] || '') !== null;
                    state[`candidato_${position}_liberacao_epi`] = parseDate(state[`candidato_${position}_liberacao_epi_data`] || '') !== null;
                    state[`candidato_${position}_liberacao_rota`] = (state[`candidato_${position}_liberacao_rota_endereco`] || '').trim() !== '';
                });

                saveState(state);
            };

            const stepIds = ['step-recrutamento', 'step-treinamentos', 'step-assinatura', 'step-sgc', 'step-liberacao'];
            const stepChecks = (stepId) => checks.filter((check) => check.dataset.rhCheck === stepId);
            const stepDone = (stepId) => {
                const candidateStep = stepId.replace('step-', '');

                if (candidateStepConfig[candidateStep]) {
                    const approved = approvedCandidatePositions();

                    return approved.length > 0 && approved.every((candidate) => candidateStepDone(candidate.position, candidateStep));
                }

                const items = stepChecks(stepId);
                return items.length > 0 && items.every((item) => item.checked);
            };

            const approvedCandidatePositions = () => {
                const state = loadState();
                const quantity = Math.max(1, Math.min(50, Number.parseInt(state.vaga_quantidade || quantityField?.value || '1', 10) || 1));

                return Array.from({ length: quantity }, (_, index) => index + 1)
                    .map((position) => ({
                        position,
                        name: state[`candidato_${position}_nome_completo`] || '',
                        phone: state[`candidato_${position}_celular`] || '',
                        acceptedAt: state[`candidato_${position}_data_aceite`] || '',
                        status: state[`candidato_${position}_status`] || 'pendente',
                    }))
                    .filter((candidate) => candidate.status === 'aprovado' && candidate.name.trim() !== '');
            };

            const candidateStepDone = (position, step) => {
                applyAutomaticCandidateRules();
                const state = loadState();
                const checks = candidateStepConfig[step]?.checks ?? [];

                return checks.every(([key]) => Boolean(state[`candidato_${position}_${step}_${key}`]));
            };

            const goStep = (stepId) => {
                const trigger = wrapper.querySelector(`#${stepId}-trigger`);
                if (trigger) trigger.click();
            };

            const updateProgress = () => {
                const quantity = Math.max(1, Math.min(50, Number.parseInt(quantityField?.value || loadState().vaga_quantidade || '1', 10) || 1));
                const approved = approvedCandidatePositions();
                const approvedCount = Math.min(approved.length, quantity);
                let done = stepDone('step-recrutamento') ? approvedCount / quantity : 0;
                const total = stepIds.length;

                Object.keys(candidateStepConfig).forEach((step) => {
                    const doneInStep = approved.filter((candidate) => candidateStepDone(candidate.position, step)).length;
                    done += Math.min(doneInStep, quantity) / quantity;
                });

                const percent = Math.round((done / total) * 100);
                if (progressBar) progressBar.style.width = `${percent}%`;
                if (progressLabel) progressLabel.textContent = `${percent}%`;
            };

            const updateApprovedFields = () => {
                if (!approvedFields || !approvedToggle) return;
                approvedFields.classList.toggle('hidden', !approvedToggle.checked);

                if (approvedToggle.checked) {
                    renderApprovedCandidates();
                }
            };

            const bindFieldPersistence = () => {
                fields.forEach((field) => {
                    if (field.dataset.rhBound === '1') return;

                    field.dataset.rhBound = '1';
                    field.addEventListener('input', () => {
                        if (field.type === 'date') {
                            return;
                        }

                        const state = loadState();
                        state[field.dataset.rhField] = field.type === 'checkbox' ? field.checked : field.value;
                        saveState(state);

                        if (field === quantityField && approvedToggle?.checked) {
                            renderApprovedCandidates();
                        }

                        if (field.dataset.rhField?.startsWith('candidato_') && field.type === 'checkbox') {
                            applyAutomaticCandidateRules();
                            renderCandidateWorkflows();
                        }
                    });
                    field.addEventListener('change', () => {
                        if (field.type === 'date' && field.value !== '' && !parseDate(field.value)) {
                            return;
                        }

                        const state = loadState();
                        state[field.dataset.rhField] = field.type === 'checkbox' ? field.checked : field.value;
                        saveState(state);

                        if (field === quantityField && approvedToggle?.checked) {
                            renderApprovedCandidates();
                        }

                        if (field.dataset.rhField?.startsWith('candidato_')) {
                            applyAutomaticCandidateRules();
                            renderCandidateWorkflows();
                        }
                    });
                });
            };

            const applyFieldValues = () => {
                const state = loadState();
                fields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(state, field.dataset.rhField)) {
                        if (field.type === 'checkbox') {
                            field.checked = Boolean(state[field.dataset.rhField]);
                        } else {
                            field.value = state[field.dataset.rhField];
                        }
                    }
                });
            };

            const renderApprovedCandidates = () => {
                if (!approvedCandidates) return;

                const quantity = Math.max(1, Math.min(50, Number.parseInt(quantityField?.value || '1', 10) || 1));
                const positionLabel = quantity === 1 ? '1 posição' : `${quantity} posições`;

                if (approvedCount) {
                    approvedCount.textContent = positionLabel;
                }

                approvedCandidates.innerHTML = Array.from({ length: quantity }, (_, index) => {
                    const position = index + 1;
                    const recruitmentStatus = recruitmentFollowUp(position);

                    return `
                        <section class="rounded-2xl border border-zinc-200 bg-white p-4">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Posicao ${position}</p>
                                    <h4 class="text-base font-black text-brand-black">Candidato da vaga ${position}</h4>
                                </div>
                                <select data-rh-field="candidato_${position}_status" class="h-10 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    <option value="pendente">Pendente</option>
                                    <option value="aprovado">Aprovado</option>
                                    <option value="desistente">Desistente</option>
                                    <option value="substituido">Substituído</option>
                                </select>
                            </div>
                            <div class="grid gap-4 lg:grid-cols-2">
                                <label class="space-y-2 lg:col-span-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Nome completo</span>
                                    <input data-rh-field="candidato_${position}_nome_completo" placeholder="Nome do candidato aprovado" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Celular</span>
                                    <input data-rh-field="candidato_${position}_celular" placeholder="(00) 00000-0000" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de aceite</span>
                                    <input type="date" data-rh-field="candidato_${position}_data_aceite" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <div class="rounded-xl border px-4 py-3 text-sm font-semibold lg:col-span-2 ${recruitmentStatus.className}">
                                    ${recruitmentStatus.label}
                                </div>
                            </div>
                        </section>
                    `;
                }).join('');

                fields = Array.from(wrapper.querySelectorAll('[data-rh-field]'));
                bindFieldPersistence();
                applyFieldValues();
                renderCandidateWorkflows();
            };

            const renderCandidateWorkflows = () => {
                applyAutomaticCandidateRules();
                const approved = approvedCandidatePositions();

                candidateStepContainers.forEach((container) => {
                    const step = container.dataset.rhCandidateStep;
                    const config = candidateStepConfig[step];

                    if (!config) {
                        return;
                    }

                    if (approved.length === 0) {
                        container.innerHTML = `
                            <div class="rounded-2xl border border-zinc-200 bg-brand-gray-soft/60 p-5 text-sm text-brand-gray">
                                Nenhum candidato aprovado ainda. Aprove um candidato no Passo 01 para iniciar esta etapa.
                            </div>
                        `;
                        return;
                    }

                    container.innerHTML = approved.map((candidate) => {
                        const done = candidateStepDone(candidate.position, step);
                        const trainingStatus = step === 'treinamentos' ? trainingFollowUp(candidate.position) : null;
                        const signatureStatus = step === 'assinatura' ? signatureFollowUp(candidate.position) : null;
                        const sgcStatus = step === 'sgc' ? sgcFollowUp(candidate.position) : null;
                        const state = loadState();
                        const hasSgcPendency = step === 'sgc' && (state[`candidato_${candidate.position}_sgc_pendencia_descricao`] || '').trim() !== '';
                        const sgcMobilized = step === 'sgc' && parseDate(state[`candidato_${candidate.position}_sgc_data_mobilizacao`] || '') !== null;

                        return `
                            <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Posicao ${candidate.position}</p>
                                        <h3 class="text-lg font-black text-brand-black">${candidate.name}</h3>
                                        <p class="text-xs text-brand-gray">${candidate.phone || 'Celular não informado'}${candidate.acceptedAt ? ` · Aceite em ${candidate.acceptedAt.split('-').reverse().join('/')}` : ''}</p>
                                    </div>
                                    <span class="rounded-full ${done ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-brand-gray-soft text-brand-gray'} border px-3 py-1 text-xs font-bold">
                                        ${done ? 'Concluído' : 'Em andamento'}
                                    </span>
                                </div>
                                ${step === 'treinamentos' ? `
                                    <div class="mb-4 grid gap-4 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-4 sm:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de início dos treinamentos</span>
                                            <input type="date" data-rh-field="candidato_${candidate.position}_treinamentos_data_inicio" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data fim programada</span>
                                            <input type="date" data-rh-field="candidato_${candidate.position}_treinamentos_data_fim" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2 sm:col-span-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de confirmação da finalização</span>
                                            <input type="date" data-rh-field="candidato_${candidate.position}_treinamentos_data_confirmacao" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <div class="sm:col-span-2 rounded-xl border px-4 py-3 text-sm font-semibold ${trainingStatus.className}">
                                            ${trainingStatus.label}
                                        </div>
                                    </div>
                                ` : ''}
                                ${step === 'assinatura' ? `
                                    <div class="mb-4 grid gap-4 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-4 sm:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data programada da assinatura contratual</span>
                                            <input type="date" data-rh-field="candidato_${candidate.position}_assinatura_data_programada" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de confirmação da assinatura</span>
                                            <input type="date" data-rh-field="candidato_${candidate.position}_assinatura_data_confirmacao" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <div class="sm:col-span-2 rounded-xl border px-4 py-3 text-sm font-semibold ${signatureStatus.className}">
                                            ${signatureStatus.label}
                                        </div>
                                    </div>
                                ` : ''}
                                ${step === 'sgc' ? `
                                    <div class="mb-4 grid gap-4 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-4 sm:grid-cols-3">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da postagem</span>
                                            <input type="date" data-rh-field="candidato_${candidate.position}_sgc_data_postagem" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Número da postagem</span>
                                            <input data-rh-field="candidato_${candidate.position}_sgc_numero_postagem" placeholder="Número/protocolo" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da mobilização</span>
                                            <input type="date" data-rh-field="candidato_${candidate.position}_sgc_data_mobilizacao" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <div class="sm:col-span-3 rounded-xl border px-4 py-3 text-sm font-semibold ${sgcStatus.className}">
                                            ${sgcStatus.label}
                                        </div>
                                    </div>
                                ` : ''}
                                <div class="grid gap-3">
                                    ${config.checks.map(([key, title, help]) => `
                                        <label class="flex gap-3 rounded-xl border border-zinc-200 p-4">
                                            <input type="checkbox" ${step === 'treinamentos' || step === 'assinatura' || step === 'sgc' || step === 'liberacao' ? 'disabled' : ''} class="mt-1 h-5 w-5 rounded border-zinc-300 accent-[#6f1731] disabled:cursor-not-allowed disabled:opacity-80" data-rh-field="candidato_${candidate.position}_${step}_${key}">
                                            <span class="flex-1 min-w-0">
                                                <strong class="block">${title}</strong>
                                                <small class="text-brand-gray">${help}</small>
                                                ${step === 'liberacao' && key !== 'rota' ? `
                                                    <span class="mt-4 block max-w-xs space-y-2">
                                                        <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Data</span>
                                                        <input type="date" data-rh-field="candidato_${candidate.position}_liberacao_${key}_data" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm font-normal outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                                    </span>
                                                ` : ''}
                                                ${step === 'liberacao' && key === 'rota' ? `
                                                    <span class="mt-4 block space-y-2">
                                                        <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Endereço do colaborador</span>
                                                        <input data-rh-field="candidato_${candidate.position}_liberacao_rota_endereco" placeholder="Digite o endereço do colaborador" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm font-normal outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                                    </span>
                                                ` : ''}
                                                ${step === 'sgc' && key === 'pendencias' ? `
                                                     <span style="display:grid;grid-template-columns:1fr 220px;gap:1.25rem;margin-top:1rem;align-items:start;width:100%;">
                                                        ${!sgcMobilized || hasSgcPendency ? `
                                                            <span class="block space-y-2">
                                                                <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Pendência identificada</span>
                                                                <textarea data-rh-field="candidato_${candidate.position}_sgc_pendencia_descricao" rows="2" placeholder="Descreva a pendência quando houver..." class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm font-normal outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"></textarea>
                                                            </span>
                                                            <span class="block space-y-2">
                                                                <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Data de nova postagem</span>
                                                                <input type="date" data-rh-field="candidato_${candidate.position}_sgc_data_nova_postagem" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm font-normal outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                                            </span>
                                                        ` : `
                                                            <span class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 sm:col-span-2">
                                                                Sem pendência registrada. Item tratado automaticamente pela mobilização.
                                                            </span>
                                                        `}
                                                    </span>
                                                ` : ''}
                                            </span>
                                        </label>
                                    `).join('')}
                                </div>
                            </section>
                        `;
                    }).join('');
                });

                fields = Array.from(wrapper.querySelectorAll('[data-rh-field]'));
                bindFieldPersistence();
                applyFieldValues();
                updateProgress();
            };

            const applyState = () => {
                const state = loadState();
                checks.forEach((check) => {
                    check.checked = Boolean(state[check.id]);
                });
                applyFieldValues();
                const urlStep = new URLSearchParams(window.location.search).get('step');
                const step = urlStep || state.currentStep || 'step-recrutamento';
                goStep(step);
                updateApprovedFields();
                renderCandidateWorkflows();
                updateProgress();
            };

            checks.forEach((check, index) => {
                if (!check.id) check.id = `rh-check-${index + 1}`;
                check.addEventListener('change', () => {
                    const state = loadState();
                    state[check.id] = check.checked;
                    saveState(state);
                    updateProgress();
                    updateApprovedFields();
                });
            });

            bindFieldPersistence();

            triggers.forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const target = trigger.getAttribute('aria-controls');
                    const state = loadState();
                    state.currentStep = target;
                    saveState(state);
                });
            });

            wrapper.querySelectorAll('[data-rh-next-step]').forEach((button) => {
                button.addEventListener('click', () => {
                    const currentPanel = button.closest('[data-rh-step]');
                    if (!currentPanel) return;
                    const currentStep = currentPanel.getAttribute('data-rh-step');
                    const targetStep = button.getAttribute('data-rh-next-step');

                    if (!stepDone(currentStep)) {
                        alert('Conclua todos os itens deste passo antes de avançar.');
                        return;
                    }

                    const state = loadState();
                    state.currentStep = targetStep;
                    saveState(state);

                    if (button.hasAttribute('data-rh-save-next')) {
                        wrapper.closest('form')?.requestSubmit();
                        return;
                    }

                    goStep(targetStep);
                });
            });

            wrapper.querySelectorAll('[data-rh-prev-step]').forEach((button) => {
                button.addEventListener('click', () => goStep(button.getAttribute('data-rh-prev-step')));
            });

            applyState();
        });
    </script>
@endpush
