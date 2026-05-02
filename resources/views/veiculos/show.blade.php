@extends('layouts.app')

@section('title', 'Mobilizacao do veiculo - Omega286')
@section('eyebrow', 'Veiculos')
@section('page-title', 'Mobilizacao do veiculo')

@section('actions')
    <a href="{{ route('veiculos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $mobilizacoes = $veiculo->mobilizacoes->keyBy('etapa');
        $etapaInicial = $mobilizacoes->get('ETAPA_INICIAL');
        $checklist = $etapaInicial?->checklist_data ?? [];
        $steps = [
            'ETAPA_INICIAL' => ['numero' => '01', 'label' => 'Etapa inicial'],
            'TAG' => ['numero' => '02', 'label' => 'TAG'],
            'SUBCONTRATACAO' => ['numero' => '03', 'label' => 'Subcontratacao'],
            'SVG' => ['numero' => '04', 'label' => 'SVG'],
            'APROVACAO_VALE' => ['numero' => '05', 'label' => 'Aprovacao Vale'],
            'VISTORIA' => ['numero' => '06', 'label' => 'Vistoria'],
            'FINALIZACAO' => ['numero' => '07', 'label' => 'Finalizacao'],
        ];
        $statusLabel = [
            'pendente' => 'Pendente',
            'em_andamento' => 'Em andamento',
            'concluido' => 'Concluido',
            'bloqueado' => 'Bloqueado',
        ];
        $initialChecks = [
            'veiculo_solicitado' => [
                'title' => 'Veiculo solicitado conforme a demanda do contrato',
                'help' => 'Registrar qual veiculo sera usado e para qual finalidade contratual.',
            ],
            'periodo_definido' => [
                'title' => 'Data de inicio e fim da atividade definida',
                'help' => 'O periodo precisa ser claro para evitar conflito de programacao.',
            ],
            'inspecao_prevista' => [
                'title' => 'Data de liberacao para inspecao prevista',
                'help' => 'Nao aguardar a vespera da operacao para tratar vistoria.',
            ],
            'linha_confirmada' => [
                'title' => 'Linha contratual confirmada',
                'help' => 'Validar com o gestor ou responsavel pelo contrato.',
            ],
            'criterios_conferidos' => [
                'title' => 'Checklist Excel de criterios tecnicos conferido',
                'help' => 'O veiculo precisa atender ao que o contrato exige.',
            ],
            'anexo_validado' => [
                'title' => 'Documentacao completa conforme Anexo 6 validada',
                'help' => 'Sem documentacao completa, o processo tende a gerar pendencia.',
            ],
        ];
        $concluidos = collect($initialChecks)->keys()->filter(fn ($key) => ! empty($checklist[$key]))->count();
        $percentual = count($initialChecks) > 0 ? round(($concluidos / count($initialChecks)) * 100) : 0;
    @endphp

    <div class="grid gap-5 xl:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="h-fit rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="rounded-lg bg-brand-gray-soft p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">{{ $veiculo->placa }}</p>
                <h2 class="mt-1 text-base font-bold text-brand-black">{{ trim($veiculo->marca.' '.$veiculo->modelo) ?: 'Modelo nao informado' }}</h2>
                <p class="mt-1 text-xs text-brand-gray">{{ $veiculo->contrato ?: 'Contrato nao informado' }}</p>
            </div>

            <div class="mt-4 space-y-2">
                @foreach ($steps as $key => $step)
                    @php
                        $mobilizacao = $mobilizacoes->get($key);
                        $isActive = $key === 'ETAPA_INICIAL';
                    @endphp
                    <div class="flex items-center gap-3 rounded-lg border px-3 py-3 {{ $isActive ? 'border-brand-burgundy bg-brand-burgundy-soft' : 'border-zinc-200 bg-white' }}">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-black {{ $isActive ? 'bg-brand-burgundy text-white' : 'bg-brand-gray-soft text-brand-gray' }}">
                            {{ $step['numero'] }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold {{ $isActive ? 'text-brand-burgundy' : 'text-brand-black' }}">{{ $step['label'] }}</p>
                            <p class="text-xs text-brand-gray">{{ $statusLabel[$mobilizacao?->status ?? 'pendente'] ?? 'Pendente' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>

        <form method="POST" action="{{ route('veiculos.mobilizacao.update', $etapaInicial) }}" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <input type="hidden" name="status" value="{{ $etapaInicial->status === 'concluido' ? 'concluido' : 'em_andamento' }}">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-black text-brand-burgundy shadow-sm">
                    Etapa inicial
                </span>
                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy">
                    Base do processo
                </span>
            </div>

            <div class="mt-5 max-w-4xl">
                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl">Solicitacao do veiculo conforme a demanda do contrato.</h2>
                <p class="mt-3 text-sm leading-6 text-brand-gray">
                    Antes de abrir qualquer formulario, confirme se o veiculo atende ao contrato, ao periodo de uso, a linha contratual e aos criterios tecnicos exigidos.
                </p>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                    <p class="text-sm font-black text-[#3f0812]">Periodo contratual</p>
                    <p class="mt-2 text-xs leading-5 text-brand-gray">Definir data de inicio da atividade e data de fim.</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <input type="date" value="{{ optional($veiculo->data_inicio_atividade)->format('Y-m-d') }}" disabled class="h-10 rounded-lg border border-zinc-200 bg-brand-gray-soft px-3 text-xs text-brand-gray">
                        <input type="date" value="{{ optional($veiculo->data_fim_atividade)->format('Y-m-d') }}" disabled class="h-10 rounded-lg border border-zinc-200 bg-brand-gray-soft px-3 text-xs text-brand-gray">
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                    <p class="text-sm font-black text-[#3f0812]">Inspecao</p>
                    <p class="mt-2 text-xs leading-5 text-brand-gray">Prever data de liberacao para inspecao com antecedencia.</p>
                    <input type="date" value="{{ optional($veiculo->data_liberacao_inspecao)->format('Y-m-d') }}" disabled class="mt-4 h-10 w-full rounded-lg border border-zinc-200 bg-brand-gray-soft px-3 text-xs text-brand-gray">
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                    <p class="text-sm font-black text-[#3f0812]">Linha contratual</p>
                    <p class="mt-2 text-xs leading-5 text-brand-gray">Confirmar a linha correta antes de vincular o veiculo.</p>
                    <p class="mt-4 rounded-lg bg-brand-gray-soft px-3 py-2 text-xs font-bold text-brand-black">{{ $veiculo->linha_contratual ?: 'Linha nao informada' }}</p>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                    <p class="text-sm font-black text-[#3f0812]">Criterio tecnico</p>
                    <p class="mt-2 text-xs leading-5 text-brand-gray">Conferir o checklist Excel e os requisitos contratuais.</p>
                    <p class="mt-4 rounded-lg bg-brand-gray-soft px-3 py-2 text-xs font-bold text-brand-black">{{ $veiculo->criterio_tecnico ?: 'Criterio nao informado' }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($initialChecks as $key => $item)
                    <label class="flex cursor-pointer gap-4 rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-burgundy/40 hover:bg-brand-gray-soft/40">
                        <input type="hidden" name="checklist_data[{{ $key }}]" value="0">
                        <input type="checkbox" name="checklist_data[{{ $key }}]" value="1" @checked(! empty($checklist[$key])) class="mt-1 h-5 w-5 rounded border-zinc-300 text-brand-burgundy accent-[#6f1731]">
                        <span>
                            <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                            <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 grid gap-4 rounded-2xl border border-zinc-200 bg-brand-gray-soft p-5 lg:grid-cols-4">
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsavel</span>
                    <input name="responsavel" value="{{ $etapaInicial->responsavel }}" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Previsao</span>
                    <input type="date" name="data_prevista" value="{{ optional($etapaInicial->data_prevista)->format('Y-m-d') }}" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Realizacao</span>
                    <input type="date" name="data_realizada" value="{{ optional($etapaInicial->data_realizada)->format('Y-m-d') }}" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Progresso</span>
                    <div class="flex h-10 items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-brand-gray-soft">
                            <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ $percentual }}%"></div>
                        </div>
                        <span class="text-xs font-black text-brand-black">{{ $percentual }}%</span>
                    </div>
                </label>
                <label class="space-y-2 lg:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidencia</span>
                    <input name="link_evidencia" value="{{ $etapaInicial->link_evidencia }}" placeholder="Link da pasta, documento ou protocolo" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="space-y-2 lg:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Solicitacao / protocolo</span>
                    <input name="numero_solicitacao" value="{{ $etapaInicial->numero_solicitacao }}" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="space-y-2 lg:col-span-4">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
                    <textarea name="observacoes" rows="3" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $etapaInicial->observacoes }}</textarea>
                </label>
            </div>

            <div class="mt-6 flex flex-col gap-3 border-t border-zinc-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-brand-gray">{{ $concluidos }} de {{ count($initialChecks) }} itens concluidos nesta etapa.</p>
                <div class="flex gap-2">
                    <a href="{{ route('veiculos.edit', $veiculo) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                        Editar cadastro
                    </a>
                    <button name="status" value="concluido" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-brand-burgundy bg-white px-4 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:bg-brand-burgundy-soft">
                        <i data-lucide="circle-check" class="h-4 w-4"></i>
                        Concluir etapa
                    </button>
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Salvar etapa
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
