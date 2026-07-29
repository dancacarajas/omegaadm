@php
    $i = $indicadores;
    $periodoLabel = \Carbon\Carbon::parse($dataInicio)->format('d/m/Y').' — '.\Carbon\Carbon::parse($dataFim)->format('d/m/Y');
    $chartBase = [
        'fontFamily' => 'Instrument Sans, sans-serif',
        'toolbar' => ['show' => false],
        'zoom' => ['enabled' => false],
    ];
    $labelsEvolucao = collect($evolucaoPeriodo)->pluck('label')->all();
    $presentesEvolucao = collect($evolucaoPeriodo)->pluck('presentes')->all();
    $ausentesEvolucao = collect($evolucaoPeriodo)->pluck('ausentes')->all();
    $labelsCentro = collect($porCentroCusto)->pluck('centro_custo')->take(8)->all();
    $faltasCentro = collect($porCentroCusto)->pluck('ausentes')->take(8)->all();
    $presentesCentro = collect($porCentroCusto)->pluck('presentes')->take(8)->all();
    $charts = [
        'distribuicao' => [
            'chart' => $chartBase + ['type' => 'donut', 'height' => 300],
            'series' => [(int) $i['total_presentes'], (int) $i['total_ausentes']],
            'labels' => ['Presenças confirmadas', 'Faltas confirmadas'],
            'colors' => ['#059669', '#dc2626'],
            'legend' => ['position' => 'bottom', 'fontWeight' => 700],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '68%',
                        'labels' => ['show' => true, 'total' => ['show' => true, 'label' => 'Marcações']],
                    ],
                ],
            ],
            'dataLabels' => ['enabled' => true],
            'stroke' => ['width' => 0],
        ],
        'evolucao' => [
            'chart' => $chartBase + ['type' => 'line', 'height' => 320],
            'series' => [
                ['name' => 'Presenças', 'data' => $presentesEvolucao],
                ['name' => 'Faltas', 'data' => $ausentesEvolucao],
            ],
            'colors' => ['#059669', '#dc2626'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'markers' => ['size' => 4, 'strokeWidth' => 2, 'hover' => ['size' => 6]],
            'xaxis' => ['categories' => $labelsEvolucao, 'labels' => ['rotate' => -45, 'style' => ['fontSize' => '10px']]],
            'legend' => ['position' => 'top', 'horizontalAlign' => 'left', 'fontWeight' => 800],
            'grid' => ['borderColor' => '#f0f0f0'],
            'dataLabels' => ['enabled' => false],
            'tooltip' => ['shared' => true, 'intersect' => false],
        ],
        'centro_faltas' => [
            'chart' => $chartBase + ['type' => 'bar', 'height' => 320, 'stacked' => true],
            'series' => [
                ['name' => 'Presenças', 'data' => $presentesCentro],
                ['name' => 'Faltas', 'data' => $faltasCentro],
            ],
            'colors' => ['#059669', '#dc2626'],
            'plotOptions' => ['bar' => ['horizontal' => true, 'barHeight' => '62%', 'borderRadius' => 4]],
            'xaxis' => ['categories' => $labelsCentro],
            'legend' => ['position' => 'top', 'horizontalAlign' => 'left', 'fontWeight' => 800],
            'grid' => ['borderColor' => '#f0f0f0'],
            'dataLabels' => ['enabled' => false],
        ],
    ];
@endphp

<section class="mb-5 overflow-hidden rounded-2xl border border-brand-burgundy/15 bg-gradient-to-br from-brand-burgundy via-[#7a1a36] to-brand-burgundy-dark p-5 text-white shadow-lg shadow-brand-burgundy/20">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-white/70">Painel de gerenciamento</p>
            <h2 class="mt-1 text-xl font-bold lg:text-2xl">Indicadores de presença na obra</h2>
            <p class="mt-2 text-sm text-white/85">Período analisado: <strong>{{ $periodoLabel }}</strong> ({{ $i['dias_periodo'] }} dias)</p>
        </div>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                <p class="text-[10px] font-bold uppercase text-white/70">Taxa presença</p>
                <p class="text-lg font-bold">{{ number_format($i['taxa_presenca'], 1, ',', '.') }}%</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                <p class="text-[10px] font-bold uppercase text-white/70">Absenteísmo</p>
                <p class="text-lg font-bold">{{ number_format($i['taxa_absenteismo'], 1, ',', '.') }}%</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                <p class="text-[10px] font-bold uppercase text-white/70">Faltas</p>
                <p class="text-lg font-bold">{{ $i['total_ausentes'] }}</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                <p class="text-[10px] font-bold uppercase text-white/70">Com falta</p>
                <p class="text-lg font-bold">{{ $i['colaboradores_com_falta'] }}</p>
            </div>
        </div>
    </div>
</section>

<form method="GET" action="{{ $urlFiltro }}" class="presenca-dashboard-filters mb-5 grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm lg:grid-cols-5">
    @if ($errors->any())
        <div class="lg:col-span-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif
    <div>
        <label for="dashboard-inicio" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Período inicial</label>
        <input type="date" name="data_inicio" id="dashboard-inicio" value="{{ $dataInicio }}" required class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
    </div>
    <div>
        <label for="dashboard-fim" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Período final</label>
        <input type="date" name="data_fim" id="dashboard-fim" value="{{ $dataFim }}" required class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
    </div>
    <div>
        <label for="dashboard-centro" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Centro de custo</label>
        <select name="centro_custo" id="dashboard-centro" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
            <option value="">Todos</option>
            @foreach ($centrosCusto as $cc)
                <option value="{{ $cc }}" @selected($centroCusto === $cc)>{{ $cc }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end lg:col-span-2">
        <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-bold text-white shadow-sm hover:bg-brand-burgundy-dark">
            <i data-lucide="filter" class="h-4 w-4"></i>
            Aplicar filtros
        </button>
    </div>
</form>

<div class="presenca-dashboard-kpis mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4 xl:grid-cols-6">
    @foreach ([
        ['icon' => 'calendar-range', 'label' => 'Dias no período', 'valor' => $i['dias_periodo'], 'tone' => 'burgundy'],
        ['icon' => 'calendar-check', 'label' => 'Dias com registro', 'valor' => $i['dias_com_registro'], 'tone' => 'zinc'],
        ['icon' => 'users', 'label' => 'Efetivo ativo', 'valor' => $i['efetivo_ativo'], 'tone' => 'zinc'],
        ['icon' => 'user-check', 'label' => 'Presenças', 'valor' => $i['total_presentes'], 'tone' => 'emerald'],
        ['icon' => 'user-x', 'label' => 'Faltas', 'valor' => $i['total_ausentes'], 'tone' => 'red'],
        ['icon' => 'file-warning', 'label' => 'Faltas s/ justificativa', 'valor' => $i['faltas_sem_justificativa'], 'tone' => 'amber'],
    ] as $card)
        @php
            $toneClasses = match ($card['tone']) {
                'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
                'red' => 'border-red-200 bg-red-50 text-red-900',
                'burgundy' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
                default => 'border-zinc-200 bg-white text-brand-black',
            };
        @endphp
        <article class="presenca-dashboard-kpi rounded-2xl border p-4 shadow-sm {{ $toneClasses }}">
            <div class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/70">
                    <i data-lucide="{{ $card['icon'] }}" class="h-4 w-4"></i>
                </span>
                <p class="text-[10px] font-bold uppercase tracking-wide opacity-80">{{ $card['label'] }}</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight">{{ $card['valor'] }}</p>
        </article>
    @endforeach
</div>

<div class="presenca-dashboard-rankings mb-5 grid gap-4 lg:grid-cols-2">
    @include('presenca-obra.partials._dashboard-ranking-card', [
        'titulo' => 'Top 5 — Mais faltas',
        'subtitulo' => 'Colaboradores com maior número de ausências confirmadas no período.',
        'icone' => 'user-x',
        'tema' => 'red',
        'campo' => 'faltas',
        'labelValor' => 'Faltas',
        'itens' => $rankingMaisFaltas,
        'vazio' => 'Nenhuma falta registrada no período.',
    ])

    @include('presenca-obra.partials._dashboard-ranking-card', [
        'titulo' => 'Top 5 — Atestados',
        'subtitulo' => 'Colaboradores com mais faltas documentadas por anexo (atestado médico).',
        'icone' => 'file-badge',
        'tema' => 'sky',
        'campo' => 'atestados',
        'labelValor' => 'Atestados',
        'itens' => $rankingMaisAtestados,
        'vazio' => 'Nenhum atestado anexado no período.',
    ])
</div>

<div class="presenca-dashboard-charts mb-5 grid gap-4 lg:grid-cols-2">
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-brand-black">Composição do período</h2>
        <p class="mt-1 text-xs text-brand-gray">Total de presenças e faltas confirmadas no intervalo.</p>
        <div class="mt-4" data-apex-chart="#chart-presenca-distribuicao"></div>
        <script type="application/json" id="chart-presenca-distribuicao">@json($charts['distribuicao'])</script>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-brand-black">Evolução diária no período</h2>
        <p class="mt-1 text-xs text-brand-gray">Tendência de presenças e faltas dia a dia.</p>
        <div class="mt-4" data-apex-chart="#chart-presenca-evolucao"></div>
        <script type="application/json" id="chart-presenca-evolucao">@json($charts['evolucao'])</script>
    </section>
</div>

<div class="presenca-dashboard-bottom mb-5 grid gap-4 lg:grid-cols-2">
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-brand-black">Faltas por centro de custo</h2>
        <p class="mt-1 text-xs text-brand-gray">Centros com maior volume de ausências no período.</p>
        @if ($porCentroCusto === [])
            <p class="mt-6 text-center text-sm text-brand-gray">Sem dados para o filtro aplicado.</p>
        @else
            <div class="mt-4" data-apex-chart="#chart-presenca-centro"></div>
            <script type="application/json" id="chart-presenca-centro">@json($charts['centro_faltas'])</script>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] to-white px-5 py-4">
            <h2 class="text-sm font-bold text-brand-black">Supervisores com mais confirmações</h2>
            <p class="mt-1 text-xs text-brand-gray">Ranking no período (presenças + faltas lançadas).</p>
        </div>
        <ul class="divide-y divide-zinc-100">
            @forelse ($rankingSupervisores as $index => $item)
                <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-xs font-bold text-brand-burgundy">{{ $index + 1 }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-brand-black">{{ $item['nome'] }}</p>
                            @if ($item['matricula'])
                                <p class="text-xs text-brand-gray">Mat. {{ $item['matricula'] }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold text-brand-black">{{ $item['total'] }} marcações</span>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-brand-gray">Nenhuma confirmação no período.</li>
            @endforelse
        </ul>
    </section>
</div>

<section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-b border-zinc-100 px-5 py-4">
        <h2 class="text-sm font-bold text-brand-black">Resumo de justificativas de faltas</h2>
        <p class="mt-1 text-xs text-brand-gray">Acompanhamento de faltas documentadas no período.</p>
    </div>
    <div class="grid gap-3 p-5 sm:grid-cols-3">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-[10px] font-bold uppercase text-red-800">Total de faltas</p>
            <p class="mt-2 text-2xl font-bold text-red-900">{{ $i['total_ausentes'] }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
            <p class="text-[10px] font-bold uppercase text-sky-900">Com texto justificado</p>
            <p class="mt-2 text-2xl font-bold text-sky-950">{{ $i['faltas_com_justificativa'] }}</p>
        </div>
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
            <p class="text-[10px] font-bold uppercase text-orange-900">Com anexo (atestado)</p>
            <p class="mt-2 text-2xl font-bold text-orange-950">{{ $i['total_atestados'] ?? $i['faltas_com_anexo'] }}</p>
        </div>
    </div>
</section>
