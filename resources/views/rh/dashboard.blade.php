@extends('layouts.app')

@section('title', 'Painel RH - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Painel executivo de RH')

@section('content')
    @php
        $toneClass = [
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
            'red' => 'border-red-200 bg-red-50 text-red-700',
            'burgundy' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
        ];
        $chartBase = [
            'fontFamily' => 'Instrument Sans, sans-serif',
            'toolbar' => ['show' => false],
            'zoom' => ['enabled' => false],
        ];
        $funilLabels = collect($funil)->pluck('label')->all();
        $funilValues = collect($funil)->pluck('value')->all();
        $gargaloLabels = array_keys($gargalos);
        $gargaloPendentes = collect($gargalos)->pluck('pendentes')->values()->all();
        $gargaloAtrasados = collect($gargalos)->pluck('atrasados')->values()->all();
        $moduloLabels = collect($submodulos)->pluck('label')->all();
        $moduloPendentes = collect($submodulos)->pluck('pendentes')->values()->all();
        $moduloAtrasados = collect($submodulos)->pluck('atrasados')->values()->all();
        $totalPendencias = max(1, collect($submodulos)->sum('pendentes'));
        $saudeValues = collect($submodulos)->map(fn ($item) => max(0, (int) $item['pendentes']))->values()->all();
        $saudeColors = ['#6f1731', '#9f2449', '#d97706', '#dc2626'];
        $riskMatrix = [
            [
                'name' => 'Pendentes',
                'data' => collect($gargalos)->map(fn ($item, $label) => ['x' => $label, 'y' => $item['pendentes']])->values()->all(),
            ],
            [
                'name' => 'Atrasados',
                'data' => collect($gargalos)->map(fn ($item, $label) => ['x' => $label, 'y' => $item['atrasados']])->values()->all(),
            ],
        ];
        $charts = [
            'funil' => [
                'chart' => $chartBase + ['type' => 'bar', 'height' => 390],
                'series' => [['name' => 'Quantidade', 'data' => $funilValues]],
                'colors' => ['#6f1731'],
                'plotOptions' => [
                    'bar' => [
                        'horizontal' => true,
                        'barHeight' => '72%',
                        'isFunnel' => true,
                        'borderRadius' => 6,
                    ],
                ],
                'dataLabels' => [
                    'enabled' => true,
                    'style' => ['fontSize' => '12px', 'fontWeight' => 800],
                ],
                'xaxis' => ['categories' => $funilLabels],
                'grid' => ['borderColor' => '#f0f0f0'],
                'tooltip' => ['theme' => 'light'],
            ],
            'saude' => [
                'chart' => $chartBase + ['type' => 'donut', 'height' => 335],
                'series' => $saudeValues,
                'labels' => $moduloLabels,
                'colors' => $saudeColors,
                'legend' => ['position' => 'bottom', 'fontWeight' => 700],
                'plotOptions' => [
                    'pie' => [
                        'donut' => [
                            'size' => '66%',
                            'labels' => [
                                'show' => true,
                                'total' => [
                                    'show' => true,
                                    'label' => 'Pendências',
                                ],
                            ],
                        ],
                    ],
                ],
                'stroke' => ['width' => 0],
            ],
            'gargalos' => [
                'chart' => $chartBase + ['type' => 'bar', 'height' => 390, 'stacked' => true],
                'series' => [
                    ['name' => 'Pendentes', 'data' => $gargaloPendentes],
                    ['name' => 'Atrasados', 'data' => $gargaloAtrasados],
                ],
                'colors' => ['#6f1731', '#dc2626'],
                'plotOptions' => [
                    'bar' => [
                        'horizontal' => true,
                        'barHeight' => '58%',
                        'borderRadius' => 5,
                    ],
                ],
                'xaxis' => ['categories' => $gargaloLabels],
                'legend' => ['position' => 'top', 'horizontalAlign' => 'left', 'fontWeight' => 800],
                'dataLabels' => ['enabled' => false],
                'grid' => ['borderColor' => '#f0f0f0'],
                'tooltip' => ['theme' => 'light'],
            ],
            'modulos' => [
                'chart' => $chartBase + ['type' => 'radar', 'height' => 335],
                'series' => [
                    ['name' => 'Pendentes', 'data' => $moduloPendentes],
                    ['name' => 'Atrasados', 'data' => $moduloAtrasados],
                ],
                'labels' => $moduloLabels,
                'colors' => ['#6f1731', '#dc2626'],
                'markers' => ['size' => 4],
                'stroke' => ['width' => 3],
                'fill' => ['opacity' => 0.18],
                'legend' => ['position' => 'bottom', 'fontWeight' => 800],
                'yaxis' => ['show' => false],
            ],
            'risco' => [
                'chart' => $chartBase + ['type' => 'heatmap', 'height' => 320],
                'series' => $riskMatrix,
                'colors' => ['#6f1731'],
                'plotOptions' => [
                    'heatmap' => [
                        'shadeIntensity' => 0.45,
                        'radius' => 8,
                        'colorScale' => [
                            'ranges' => [
                                ['from' => 0, 'to' => 0, 'name' => 'Sem volume', 'color' => '#f4f4f5'],
                                ['from' => 1, 'to' => 2, 'name' => 'Baixo', 'color' => '#f9d7e1'],
                                ['from' => 3, 'to' => 5, 'name' => 'Médio', 'color' => '#b63a60'],
                                ['from' => 6, 'to' => 999, 'name' => 'Crítico', 'color' => '#6f1731'],
                            ],
                        ],
                    ],
                ],
                'dataLabels' => ['enabled' => true, 'style' => ['colors' => ['#111111'], 'fontWeight' => 900]],
                'legend' => ['position' => 'bottom'],
            ],
        ];
        $json = fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp

    <section class="mb-5 grid gap-4 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg {{ $toneClass[$kpi['tone']] }}">
                        <i data-lucide="{{ $kpi['icon'] }}" class="h-5 w-5"></i>
                    </div>
                    <span class="rounded-full border px-2.5 py-1 text-xs font-bold {{ $toneClass[$kpi['tone']] }}">RH</span>
                </div>
                <p class="mt-5 text-sm font-semibold text-brand-gray">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-4xl font-black text-brand-black">{{ $kpi['value'] }}</p>
                <p class="mt-2 text-xs font-medium leading-5 text-brand-gray">{{ $kpi['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mb-5 grid gap-5 2xl:grid-cols-[1.2fr_.8fr]">
        <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Fluxo ponta a ponta</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Funil do recrutamento até a liberação</h2>
                <p class="mt-1 text-sm text-brand-gray">Mostra onde o processo perde velocidade desde a vaga até o colaborador liberado.</p>
            </div>
            <div class="p-5">
                <div data-apex-chart="#chart-funil"></div>
            </div>
        </article>

        <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Saúde operacional</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Distribuição das pendências</h2>
                <p class="mt-1 text-sm text-brand-gray">Donut executivo para enxergar qual submódulo concentra mais cobrança.</p>
            </div>
            <div class="p-5">
                <div data-apex-chart="#chart-saude"></div>
            </div>
        </article>
    </section>

    <section class="mb-5 grid gap-5 2xl:grid-cols-[1fr_.85fr]">
        <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Gargalos por etapa</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Onde está travando</h2>
                <p class="mt-1 text-sm text-brand-gray">Barras empilhadas separam volume pendente de atraso real para facilitar cobrança.</p>
            </div>
            <div class="p-5">
                <div data-apex-chart="#chart-gargalos"></div>
            </div>
        </article>

        <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Comparativo estratégico</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Pressão por submódulo</h2>
                <p class="mt-1 text-sm text-brand-gray">Radar compara pendências e atrasos para destacar a frente mais crítica.</p>
            </div>
            <div class="p-5">
                <div data-apex-chart="#chart-modulos"></div>
            </div>
        </article>
    </section>

    <section class="mb-5 grid gap-5 2xl:grid-cols-[1fr_.9fr]">
        <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Mapa de risco</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Intensidade por etapa</h2>
                <p class="mt-1 text-sm text-brand-gray">Heatmap para bater o olho e identificar concentração de pendências e atrasos.</p>
            </div>
            <div class="p-5">
                <div data-apex-chart="#chart-risco"></div>
            </div>
        </article>

        <article class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Lista de cobrança</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Prioridades para a diretoria</h2>
                <p class="mt-1 text-sm text-brand-gray">Exibe primeiro o que tem atraso e maior volume pendente.</p>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($alertas as $alerta)
                    <div class="grid gap-3 p-5 sm:grid-cols-[1fr_auto] sm:items-center">
                        <div>
                            <p class="text-sm font-black text-brand-black">{{ $alerta['label'] }}</p>
                            <p class="mt-1 text-xs font-semibold text-brand-gray">{{ $alerta['tipo'] }} • {{ $alerta['sla'] }}</p>
                        </div>
                        <div class="flex gap-2 sm:justify-end">
                            <span class="rounded-full bg-brand-gray-soft px-2.5 py-1 text-xs font-bold text-brand-gray">{{ $alerta['pendentes'] }} pend.</span>
                            <span class="rounded-full {{ $alerta['atrasados'] > 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-xs font-bold">{{ $alerta['atrasados'] }} atr.</span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-sm font-bold text-brand-black">Nenhum gargalo crítico no momento.</p>
                        <p class="mt-1 text-sm text-brand-gray">Os principais fluxos estão sem atrasos registrados.</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    <script type="application/json" id="chart-funil">{!! $json($charts['funil']) !!}</script>
    <script type="application/json" id="chart-saude">{!! $json($charts['saude']) !!}</script>
    <script type="application/json" id="chart-gargalos">{!! $json($charts['gargalos']) !!}</script>
    <script type="application/json" id="chart-modulos">{!! $json($charts['modulos']) !!}</script>
    <script type="application/json" id="chart-risco">{!! $json($charts['risco']) !!}</script>
@endsection
