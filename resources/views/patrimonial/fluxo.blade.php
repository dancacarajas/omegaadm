@extends('layouts.app')

@section('title', 'Fluxo de equipamento - Omega286')
@section('eyebrow', 'Patrimonial / Equipamentos')
@section('page-title', 'Fluxo do equipamento')

@section('actions')
    <a href="{{ route('patrimonial.show', $patrimonio) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-cadastro', 'number' => '01', 'label' => 'Cadastro e Planejamento'],
            ['id' => 'step-inspecao', 'number' => '02', 'label' => 'Inspeção Inicial'],
            ['id' => 'step-documentacao', 'number' => '03', 'label' => 'Documentação'],
            ['id' => 'step-liberacao', 'number' => '04', 'label' => 'Liberação Operacional'],
            ['id' => 'step-manutencao', 'number' => '05', 'label' => 'Manutenção e Controle'],
            ['id' => 'step-encerramento', 'number' => '06', 'label' => 'Encerramento / Baixa'],
        ];
        $state = old('fluxo_state', json_encode($patrimonio->fluxo_state ?? [], JSON_UNESCAPED_UNICODE));
    @endphp

    <form method="POST" action="{{ route('patrimonial.fluxo.update', $patrimonio) }}" class="space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="fluxo_state" value="{{ $state }}" data-eqp-state>
        <input type="hidden" name="fluxo_step" value="{{ $patrimonio->fluxo_step ?? 'step-cadastro' }}" data-eqp-step>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Equipamento</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">{{ $patrimonio->nome }}</h2>
            <p class="mt-1 text-sm text-brand-gray">
                TAG {{ $patrimonio->tag_patrimonial }} · Contrato {{ $patrimonio->contrato ?: 'não informado' }}
            </p>
        </section>

        <section class="bs-stepper js-stepper rounded-xl border border-zinc-200 bg-white shadow-sm" data-eqp-stepper>
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
                    <span>Progresso do fluxo do equipamento</span>
                    <span data-eqp-progress-label>0%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                    <div data-eqp-progress-bar class="h-full w-0 rounded-full bg-brand-burgundy transition-all duration-300 ease-out"></div>
                </div>
            </div>

            <div class="bs-stepper-content p-5 lg:p-6">
                @foreach ($steps as $step)
                    <div id="{{ $step['id'] }}" class="content {{ $loop->first ? 'active dstepper-block step-visible' : '' }}" role="tabpanel" aria-labelledby="{{ $step['id'] }}-trigger">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                            <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                                <h2 class="text-3xl font-black text-[#3f0812]">{{ $step['label'] }}</h2>
                                <p class="mt-2 text-sm text-brand-gray">Estrutura base criada. Me passe os campos deste passo e eu completo o conteúdo detalhado.</p>

                                <div class="mt-6 space-y-3">
                                    <label class="flex gap-3 rounded-xl border border-zinc-200 p-4">
                                        <input type="checkbox" class="mt-1 h-5 w-5 rounded border-zinc-300 accent-[#6f1731]" data-eqp-check="{{ $step['id'] }}-1">
                                        <span><strong class="block">Checklist 1</strong><small class="text-brand-gray">Placeholder para regra da etapa.</small></span>
                                    </label>
                                    <label class="flex gap-3 rounded-xl border border-zinc-200 p-4">
                                        <input type="checkbox" class="mt-1 h-5 w-5 rounded border-zinc-300 accent-[#6f1731]" data-eqp-check="{{ $step['id'] }}-2">
                                        <span><strong class="block">Checklist 2</strong><small class="text-brand-gray">Placeholder para validação operacional.</small></span>
                                    </label>
                                    <label class="flex gap-3 rounded-xl border border-zinc-200 p-4">
                                        <input type="checkbox" class="mt-1 h-5 w-5 rounded border-zinc-300 accent-[#6f1731]" data-eqp-check="{{ $step['id'] }}-3">
                                        <span><strong class="block">Checklist 3</strong><small class="text-brand-gray">Placeholder para aprovação final da etapa.</small></span>
                                    </label>
                                </div>
                            </section>

                            <aside class="h-fit min-w-0 self-start rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm lg:sticky lg:top-28">
                                <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar fluxo
                                </button>
                            </aside>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.querySelector('[data-eqp-stepper]');
            if (!wrapper) return;

            const stateInput = document.querySelector('[data-eqp-state]');
            const stepInput = document.querySelector('[data-eqp-step]');
            const progressBar = wrapper.querySelector('[data-eqp-progress-bar]');
            const progressLabel = wrapper.querySelector('[data-eqp-progress-label]');
            const triggers = Array.from(wrapper.querySelectorAll('.step-trigger'));
            const checks = Array.from(wrapper.querySelectorAll('[data-eqp-check]'));

            const loadState = () => {
                try {
                    return JSON.parse(stateInput?.value || '{}');
                } catch (_e) {
                    return {};
                }
            };
            const saveState = (state) => {
                if (stateInput) stateInput.value = JSON.stringify(state);
            };

            const updateProgress = () => {
                const total = checks.length || 1;
                const done = checks.filter((c) => c.checked).length;
                const percent = Math.round((done / total) * 100);
                if (progressBar) progressBar.style.width = `${percent}%`;
                if (progressLabel) progressLabel.textContent = `${percent}%`;
            };

            checks.forEach((check) => {
                const key = check.dataset.eqpCheck;
                if (!key) return;
                const state = loadState();
                check.checked = Boolean(state[key]);
                check.addEventListener('change', () => {
                    const current = loadState();
                    current[key] = check.checked;
                    saveState(current);
                    updateProgress();
                });
            });

            triggers.forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const target = trigger.getAttribute('aria-controls');
                    if (stepInput) stepInput.value = target || 'step-cadastro';
                });
            });

            const initialStep = stepInput?.value || 'step-cadastro';
            const initialTrigger = wrapper.querySelector(`#${initialStep}-trigger`);
            if (initialTrigger) initialTrigger.click();
            updateProgress();
        });
    </script>
@endpush

