@extends('layouts.app')

@section('title', 'Recrutamento - Omega286')
@section('eyebrow', 'RH')
@section('page-title', 'Recrutamento')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('rh.recrutamento.painel-preenchimento', request()->only(['contrato', 'busca', 'ordem_nome'])) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="layout-list" class="h-4 w-4"></i>
            Painel de vagas e candidatos
        </a>
        <a href="{{ route('rh.recrutamento.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Nova vaga
        </a>
    </div>
@endsection

@section('content')
    @php
        $statusClass = [
            'Em abertura' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
            'Aprovada' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
            'Em divulgacao' => 'border-blue-200 bg-blue-50 text-blue-700',
            'Em selecao' => 'border-amber-200 bg-amber-50 text-amber-700',
            'Congelada' => 'border-red-200 bg-red-50 text-red-700',
        ];

        $candidateSteps = [
            'exame_medico' => [],
            'treinamentos' => [],
            'assinatura' => ['pendencias', 'contrato', 'kit'],
            'sgc' => ['postagem', 'aguardando', 'pendencias', 'cracha'],
            'liberacao' => ['orientado', 'epi', 'rota'],
        ];
        $approvedCandidates = function ($vaga): \Illuminate\Support\Collection {
            $state = $vaga->form_state ?? [];
            $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));

            return collect(range(1, $quantity))
                ->map(fn ($position) => [
                    'position' => $position,
                    'name' => $state["candidato_{$position}_nome_completo"] ?? '',
                    'status' => $state["candidato_{$position}_status"] ?? 'pendente',
                ])
                ->filter(fn ($candidate) => $candidate['status'] === 'aprovado' && filled($candidate['name']))
                ->values();
        };
        $candidateStepDone = function ($vaga, int $position, string $step) use ($candidateSteps): bool {
            $state = $vaga->form_state ?? [];

            if ($step === 'exame_medico') {
                $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
                $trainingEnd = $state["candidato_{$position}_treinamentos_data_fim"] ?? null;
                $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;
                $scheduledAt = $state["candidato_{$position}_treinamentos_data_agendamento"] ?? null;

                if (blank($trainingEnd) && filled($trainingStart)) {
                    try {
                        $trainingEnd = \Carbon\Carbon::parse($trainingStart)->addDays(5)->toDateString();
                    } catch (\Throwable $e) {
                        $trainingEnd = null;
                    }
                }

                return filled($trainingStart) && filled($trainingConfirmedAt)
                    && (filled($scheduledAt) || filled($trainingEnd));
            }

            if ($step === 'treinamentos') {
                if (! empty($state["candidato_{$position}_treinamentos_capacitacao"])) {
                    return true;
                }
                $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
                $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;

                return filled($trainingStart) && filled($trainingConfirmedAt);
            }

            if ($step === 'assinatura') {
                return filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
            }

            if ($step === 'sgc') {
                $hasPendency = filled($state["candidato_{$position}_sgc_pendencia_descricao"] ?? null);
                $pendencyDone = $hasPendency
                    ? filled($state["candidato_{$position}_sgc_data_nova_postagem"] ?? null)
                    : filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);

                return filled($state["candidato_{$position}_sgc_data_postagem"] ?? null)
                    && filled($state["candidato_{$position}_sgc_numero_postagem"] ?? null)
                    && $pendencyDone
                    && filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);
            }

            if ($step === 'liberacao') {
                return filled($state["candidato_{$position}_liberacao_orientado_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_epi_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_rota_endereco"] ?? null);
            }

            return collect($candidateSteps[$step] ?? [])
                ->every(fn ($key) => (bool) ($state["candidato_{$position}_{$step}_{$key}"] ?? false));
        };
        $trainingFollowUp = function ($vaga, int $position): array {
            $state = $vaga->form_state ?? [];
            $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
            $trainingEnd = $state["candidato_{$position}_treinamentos_data_fim"] ?? null;
            $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;

            if (blank($trainingEnd) && filled($trainingStart)) {
                try {
                    $trainingEnd = \Carbon\Carbon::parse($trainingStart)->addDays(5)->toDateString();
                } catch (\Throwable $e) {
                    $trainingEnd = null;
                }
            }

            if (blank($trainingEnd)) {
                return ['label' => 'Pendente', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            try {
                $plannedEnd = \Carbon\Carbon::parse($trainingEnd)->startOfDay();
                $referenceDate = filled($trainingConfirmedAt)
                    ? \Carbon\Carbon::parse($trainingConfirmedAt)->startOfDay()
                    : today();
                $delay = max(0, $plannedEnd->diffInDays($referenceDate, false));
            } catch (\Throwable $e) {
                return ['label' => 'Pendente', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            if ($delay > 0) {
                return ['label' => "{$delay} dias atraso", 'class' => 'border-red-200 bg-red-50 text-red-700'];
            }

            if (filled($trainingConfirmedAt)) {
                return ['label' => 'OK', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'];
            }

            return ['label' => 'No prazo', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'];
        };
        $recruitmentFollowUp = function ($vaga, int $position): array {
            $state = $vaga->form_state ?? [];
            $requestedAt = $state['vaga_data_solicitacao'] ?? null;
            $acceptedAt = $state["candidato_{$position}_data_aceite"] ?? null;

            if (blank($requestedAt)) {
                return ['label' => 'Pendente', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            if (blank($acceptedAt)) {
                return ['label' => 'Aguardando', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'];
            }

            try {
                $duration = max(0, \Carbon\Carbon::parse($requestedAt)->startOfDay()->diffInDays(\Carbon\Carbon::parse($acceptedAt)->startOfDay(), false));
            } catch (\Throwable $e) {
                return ['label' => 'Pendente', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            return ['label' => "{$duration} dias", 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'];
        };
        $signatureFollowUp = function ($vaga, int $position): array {
            $state = $vaga->form_state ?? [];
            $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;
            $signatureConfirmedAt = $state["candidato_{$position}_assinatura_data_confirmacao"] ?? null;
            $signatureScheduledAt = $state["candidato_{$position}_assinatura_data_programada"] ?? null;

            if (blank($trainingConfirmedAt)) {
                return ['label' => 'Aguardando', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            if (blank($signatureConfirmedAt)) {
                return [
                    'label' => filled($signatureScheduledAt) ? 'Programada' : 'Pendente',
                    'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                ];
            }

            try {
                $duration = max(0, \Carbon\Carbon::parse($trainingConfirmedAt)->startOfDay()->diffInDays(\Carbon\Carbon::parse($signatureConfirmedAt)->startOfDay(), false));
            } catch (\Throwable $e) {
                return ['label' => 'Pendente', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            return ['label' => "{$duration} dias", 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'];
        };
        $sgcFollowUp = function ($vaga, int $position): array {
            $state = $vaga->form_state ?? [];
            $signatureConfirmedAt = $state["candidato_{$position}_assinatura_data_confirmacao"] ?? null;
            $postedAt = $state["candidato_{$position}_sgc_data_postagem"] ?? null;
            $mobilizedAt = $state["candidato_{$position}_sgc_data_mobilizacao"] ?? null;

            if (blank($signatureConfirmedAt)) {
                return ['label' => 'Aguardando', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            if (blank($mobilizedAt)) {
                return [
                    'label' => filled($postedAt) ? 'Postado' : 'Pendente',
                    'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                ];
            }

            try {
                $duration = max(0, \Carbon\Carbon::parse($signatureConfirmedAt)->startOfDay()->diffInDays(\Carbon\Carbon::parse($mobilizedAt)->startOfDay(), false));
            } catch (\Throwable $e) {
                return ['label' => 'Pendente', 'class' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray'];
            }

            return ['label' => "{$duration} dias", 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'];
        };
        $stepOneDone = function ($vaga): bool {
            $state = $vaga->form_state ?? [];
            $checks = collect($state)
                ->filter(fn ($value, $key) => str_starts_with((string) $key, 'rh-check-'))
                ->values();

            $chunk = $checks->slice(0, 3);

            return $chunk->count() > 0 && $chunk->every(fn ($value) => (bool) $value);
        };
        $timelineTone = function (array $status, bool $done = false): array {
            $class = $status['class'] ?? '';

            if (str_contains($class, 'red-')) {
                return [
                    'dot' => 'bg-red-600 ring-red-100 text-white',
                    'line' => 'bg-red-200',
                    'lineColor' => '#fecaca',
                    'dotColor' => '#dc2626',
                    'ringColor' => '#fee2e2',
                    'card' => 'border-red-200 bg-red-50',
                    'text' => 'text-red-700',
                    'icon' => 'triangle-alert',
                    'label' => 'Atrasado',
                ];
            }

            if (str_contains($class, 'amber-')) {
                return [
                    'dot' => 'bg-amber-500 ring-amber-100 text-white',
                    'line' => 'bg-amber-200',
                    'lineColor' => '#fde68a',
                    'dotColor' => '#f59e0b',
                    'ringColor' => '#fef3c7',
                    'card' => 'border-amber-200 bg-amber-50',
                    'text' => 'text-amber-700',
                    'icon' => 'clock-3',
                    'label' => 'Atenção',
                ];
            }

            if (str_contains($class, 'emerald-') || $done) {
                return [
                    'dot' => 'bg-emerald-600 ring-emerald-100 text-white',
                    'line' => 'bg-emerald-200',
                    'lineColor' => '#a7f3d0',
                    'dotColor' => '#059669',
                    'ringColor' => '#d1fae5',
                    'card' => 'border-emerald-200 bg-emerald-50',
                    'text' => 'text-emerald-700',
                    'icon' => 'check',
                    'label' => 'No prazo',
                ];
            }

            return [
                'dot' => 'bg-zinc-300 ring-zinc-100 text-brand-gray',
                'line' => 'bg-zinc-200',
                'lineColor' => '#e4e4e7',
                'dotColor' => '#f8fafc',
                'ringColor' => '#f4f4f5',
                'card' => 'border-zinc-200 bg-white',
                'text' => 'text-brand-gray',
                'icon' => 'circle',
                'label' => 'Pendente',
            ];
        };
    @endphp

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Vagas e onboarding RH</h2>
                <p class="mt-1 text-sm text-brand-gray">Controle as vagas abertas, candidatos aprovados e o andamento de cada fluxo.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por vaga, contrato, gestor..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-96">
                </label>
                <label>
                    <select name="contrato" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-72">
                        <option value="">Selecione o centro de custo</option>
                        @foreach (($centrosDeCusto ?? collect()) as $centroDeCusto)
                            <option value="{{ $centroDeCusto }}" @selected(($contratoSelecionado ?? '') === $centroDeCusto)>{{ $centroDeCusto }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="border-b border-zinc-200 bg-white px-5 py-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Fichas de vaga</p>
                    <p class="mt-1 text-2xl font-black text-brand-black">{{ $indicadores['fichas'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Vagas previstas</p>
                    <p class="mt-1 text-2xl font-black text-brand-black">{{ $indicadores['total_vagas'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-amber-50 px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Em abertura</p>
                    <p class="mt-1 text-2xl font-black text-amber-800">{{ $indicadores['em_abertura'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-blue-700">Candidatos aprovados</p>
                    <p class="mt-1 text-2xl font-black text-blue-800">{{ $indicadores['aprovados'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">Liberados</p>
                    <p class="mt-1 text-2xl font-black text-emerald-800">{{ $indicadores['liberados'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="w-[280px] px-5 py-4">Vaga</th>
                        <th class="w-[110px] px-4 py-4">Qtd.</th>
                        <th class="w-[180px] px-4 py-4">Contrato</th>
                        <th class="w-[180px] px-4 py-4">Gestor</th>
                        <th class="w-[150px] px-4 py-4">Inicio</th>
                        <th class="w-[140px] px-4 py-4">Status</th>
                        <th class="w-[190px] px-4 py-4">Progresso</th>
                        <th class="w-[250px] px-4 py-4 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($vagas as $vaga)
                        @php
                            $approved = $approvedCandidates($vaga);
                            $quantity = max(1, (int) $vaga->quantidade);
                            $approvedCount = $approved->count();
                            $progressUnits = $stepOneDone($vaga) ? 1 : 0;

                            foreach (array_keys($candidateSteps) as $step) {
                                if ($approvedCount === 0) {
                                    continue;
                                }
                                $doneInStep = $approved
                                    ->filter(fn ($candidate) => $candidateStepDone($vaga, $candidate['position'], $step))
                                    ->count();

                                $progressUnits += $doneInStep / $approvedCount;
                            }

                            $percent = (int) round(($progressUnits / (1 + count($candidateSteps))) * 100);
                        @endphp
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="briefcase-business" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $vaga->titulo ?: 'Vaga sem titulo' }}</p>
                                        <p class="text-xs text-brand-gray">{{ $vaga->tipo ?: 'Tipo nao informado' }} · {{ $vaga->prioridade ?: 'Sem prioridade' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $vaga->quantidade }}</td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $vaga->contrato ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ $vaga->local ?: 'Local nao informado' }}</p>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">{{ $vaga->gestor ?: '-' }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ optional($vaga->previsao_inicio)->format('d/m/Y') ?: '-' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass[$vaga->status] ?? $statusClass['Em abertura'] }}">
                                    {{ $vaga->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-28 overflow-hidden rounded-full bg-zinc-200">
                                            <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-brand-gray">{{ $percent }}%</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1">
                                        <span class="rounded-full bg-brand-gray-soft px-2 py-0.5 text-[11px] font-bold text-brand-gray">{{ $approved->count() }}/{{ $vaga->quantidade }} aprov.</span>
                                        @if ($approved->isNotEmpty())
                                            @php
                                                $avgCandidateProgress = (int) round($approved->avg(function ($candidate) use ($candidateSteps, $candidateStepDone, $recruitmentFollowUp, $vaga) {
                                                    $done = str_contains($recruitmentFollowUp($vaga, $candidate['position'])['class'] ?? '', 'emerald-') ? 1 : 0;
                                                    $done += collect(array_keys($candidateSteps))
                                                        ->filter(fn ($step) => $candidateStepDone($vaga, $candidate['position'], $step))
                                                        ->count();

                                                    return ($done / (count($candidateSteps) + 1)) * 100;
                                                }));
                                            @endphp
                                            <span class="rounded-full bg-brand-burgundy-soft px-2 py-0.5 text-[11px] font-bold text-brand-burgundy">{{ $avgCandidateProgress }}% ind.</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button" data-toggle-candidates="{{ $vaga->id }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-gray shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="users-round" class="h-4 w-4"></i>
                                        Candidatos
                                    </button>
                                    <a href="{{ route('rh.recrutamento.edit', $vaga) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('rh.recrutamento.destroy', $vaga) }}" onsubmit="return confirm('Deseja realmente excluir esta vaga?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-100">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="hidden bg-brand-gray-soft/30" data-candidate-row="{{ $vaga->id }}">
                            <td colspan="8" class="px-5 py-3">
                                <div class="rounded-xl border border-zinc-200 bg-white p-4">
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Andamento individual</p>
                                            <p class="text-sm font-semibold text-brand-black">Candidatos aprovados desta vaga</p>
                                        </div>
                                        <span class="rounded-full bg-brand-gray-soft px-3 py-1 text-xs font-bold text-brand-gray">{{ $approved->count() }} aprovado(s) / {{ $vaga->quantidade }} posicoes</span>
                                    </div>

                                    @if ($approved->isEmpty())
                                        <div class="rounded-lg border border-zinc-200 bg-brand-gray-soft/60 px-4 py-3 text-sm text-brand-gray">
                                            Nenhum candidato aprovado ainda para iniciar fluxo individual.
                                        </div>
                                    @else
                                        <div class="grid gap-4">
                                            @foreach ($approved as $candidate)
                                                    @php
                                                        $recruitmentStatus = $recruitmentFollowUp($vaga, $candidate['position']);
                                                        $timelineItems = collect([
                                                            [
                                                                'key' => 'aceite',
                                                                'title' => 'Aceite',
                                                                'status' => $recruitmentStatus,
                                                                'done' => str_contains($recruitmentStatus['class'], 'emerald-'),
                                                            ],
                                                            [
                                                                'key' => 'exame_medico',
                                                                'title' => 'Exame Médico',
                                                                'status' => $trainingFollowUp($vaga, $candidate['position']),
                                                                'done' => $candidateStepDone($vaga, $candidate['position'], 'exame_medico'),
                                                            ],
                                                            [
                                                                'key' => 'treinamentos',
                                                                'title' => 'Treinamentos',
                                                                'status' => $trainingFollowUp($vaga, $candidate['position']),
                                                                'done' => $candidateStepDone($vaga, $candidate['position'], 'treinamentos'),
                                                            ],
                                                            [
                                                                'key' => 'assinatura',
                                                                'title' => 'Assinatura',
                                                                'status' => $signatureFollowUp($vaga, $candidate['position']),
                                                                'done' => $candidateStepDone($vaga, $candidate['position'], 'assinatura'),
                                                            ],
                                                            [
                                                                'key' => 'sgc',
                                                                'title' => 'SGC',
                                                                'status' => $sgcFollowUp($vaga, $candidate['position']),
                                                                'done' => $candidateStepDone($vaga, $candidate['position'], 'sgc'),
                                                            ],
                                                            [
                                                                'key' => 'liberacao',
                                                                'title' => 'Liberação',
                                                                'status' => [
                                                                    'label' => $candidateStepDone($vaga, $candidate['position'], 'liberacao') ? 'OK' : 'Pendente',
                                                                    'class' => $candidateStepDone($vaga, $candidate['position'], 'liberacao') ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
                                                                ],
                                                                'done' => $candidateStepDone($vaga, $candidate['position'], 'liberacao'),
                                                            ],
                                                        ])->map(function ($item) use ($timelineTone) {
                                                            $item['tone'] = $timelineTone($item['status'], $item['done']);
                                                            return $item;
                                                        });
                                                        $candidateCompleted = $timelineItems->filter(fn ($item) => $item['done'])->count();
                                                        $candidatePercent = (int) round(($candidateCompleted / max(1, $timelineItems->count())) * 100);
                                                    @endphp
                                                <div class="rounded-xl border border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/40 p-4 shadow-sm">
                                                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                                        <div>
                                                            <p class="text-[11px] font-black uppercase tracking-wide text-brand-burgundy">Posição {{ $candidate['position'] }}</p>
                                                            <p class="text-base font-bold text-brand-black">{{ $candidate['name'] }}</p>
                                                        </div>
                                                        <div class="flex items-center gap-3">
                                                            <div class="h-2 w-28 overflow-hidden rounded-full bg-zinc-200">
                                                                <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ $candidatePercent }}%"></div>
                                                            </div>
                                                            <span class="rounded-full bg-brand-gray-soft px-3 py-1 text-xs font-bold text-brand-gray">{{ $candidatePercent }}%</span>
                                                        </div>
                                                    </div>

                                                    <div style="overflow-x:auto;padding:10px 0 4px;">
                                                        <div style="position:relative;display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));min-width:1100px;padding:8px 28px 0;align-items:start;">
                                                            @foreach ($timelineItems as $index => $item)
                                                                <div style="position:relative;z-index:1;display:flex;min-width:0;flex-direction:column;align-items:center;text-align:center;padding:0 8px;">
                                                                    @if ($index < $timelineItems->count() - 1)
                                                                        <span style="position:absolute;left:50%;top:22px;width:100%;height:4px;border-radius:999px;background:{{ $item['tone']['lineColor'] }};z-index:0;"></span>
                                                                    @endif
                                                                    <div class="flex items-center justify-center rounded-full border-[5px] border-white shadow-md" style="position:relative;z-index:2;width:44px;height:44px;flex:0 0 44px;background:{{ $item['tone']['dotColor'] }};color:{{ $item['tone']['dotColor'] === '#f8fafc' ? '#71717a' : '#ffffff' }};box-shadow:0 0 0 6px {{ $item['tone']['ringColor'] }},0 10px 18px rgba(15,23,42,.12);">
                                                                        <i data-lucide="{{ $item['tone']['icon'] }}" class="h-4 w-4"></i>
                                                                    </div>
                                                                    <p class="mt-3 w-full truncate text-sm font-black text-brand-black">{{ $item['title'] }}</p>
                                                                    <p class="mt-1 min-h-8 max-w-[150px] text-xs font-bold leading-snug {{ $item['tone']['text'] }}">{{ $item['status']['label'] }}</p>
                                                                    <span class="mt-2 rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wide shadow-sm {{ $item['tone']['card'] }} {{ $item['tone']['text'] }}">
                                                                        {{ $item['tone']['label'] }}
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="user-round-search" class="h-7 w-7"></i>
                                </div>
                                @if (blank($contratoSelecionado ?? ''))
                                    <p class="mt-4 text-base font-bold text-brand-black">Selecione um centro de custo para visualizar as vagas.</p>
                                    <p class="mt-1 text-sm text-brand-gray">A listagem só é exibida após selecionar o contrato no filtro.</p>
                                @else
                                    <p class="mt-4 text-base font-bold text-brand-black">Nenhuma vaga cadastrada.</p>
                                    <p class="mt-1 text-sm text-brand-gray">Crie uma vaga para iniciar o fluxo de recrutamento.</p>
                                    <a href="{{ route('rh.recrutamento.create') }}" class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                        Nova vaga
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $vagas->links() }}
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-toggle-candidates]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = document.querySelector(`[data-candidate-row="${button.dataset.toggleCandidates}"]`);

                if (!row) {
                    return;
                }

                row.classList.toggle('hidden');
                button.classList.toggle('border-brand-burgundy');
                button.classList.toggle('text-brand-burgundy');
            });
        });
    </script>
@endpush
