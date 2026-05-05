@extends('layouts.app')

@section('title', 'Status / Cronograma - Omega286')
@section('eyebrow', 'Contrato')
@section('page-title', 'Status / Cronograma')

@section('content')
    @php
        $fmtQtd = static fn ($valor) => rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
        $fmtPct = static fn ($valor) => number_format((float) $valor, 1, ',', '.') . '%';
    @endphp

    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">STATUS / CRONOGRAMA</h2>
            <p class="mt-1 text-sm text-brand-gray">Acompanhamento executivo da transição das funções da etapa 1 (Pré-PGU) para etapa 2 (PGU).</p>
        </div>

        <form method="GET" class="grid gap-3 p-5 md:grid-cols-[1fr_170px_180px_auto] md:items-end">
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                <select name="contrato" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Selecionar...</option>
                    @foreach ($contratos as $contrato)
                        <option value="{{ $contrato }}" @selected($contratoSelecionado === $contrato)>{{ $contrato }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência</span>
                <input type="month" name="competencia" value="{{ $competenciaMes }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data limite etapa 2</span>
                <input type="date" name="data_limite_etapa_2" value="{{ $dataLimiteInput }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <button class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                Atualizar visão
            </button>
        </form>
    </section>

    <section class="mb-5 grid gap-3 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-gray">Total de funções</p>
            <p class="mt-1 text-2xl font-bold text-brand-black">{{ $indicadores['total_funcoes'] }}</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Concluídas na etapa 2</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $indicadores['concluidas'] }}</p>
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pendentes para etapa 2</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $indicadores['pendentes'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-gray">Avanço médio</p>
            <p class="mt-1 text-2xl font-bold text-brand-black">{{ $fmtPct($indicadores['media_avanco'] ?? 0) }}</p>
        </article>
    </section>

    <section class="mb-5 grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-brand-black">Top pendências para PGU</h3>
                <span class="text-xs text-brand-gray">Prioridade de atuação</span>
            </div>
            <div data-apex-chart="#chart-gap-config"></div>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-brand-black">Progresso por função</h3>
                <span class="text-xs text-brand-gray">100% empilhado (PGU x pendente)</span>
            </div>
            <div data-apex-chart="#chart-progresso-config"></div>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-brand-black">Status geral</h3>
                <span class="text-xs text-brand-gray">Concluídas x Pendentes</span>
            </div>
            <div data-apex-chart="#chart-status-config"></div>
        </article>

        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-brand-black">Termômetro de avanço</h3>
                <span class="text-xs text-brand-gray">Média executiva</span>
            </div>
            <div data-apex-chart="#chart-gauge-config"></div>
            <div class="mt-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-brand-gray">
                @if ($chartData['prazo']['data_limite'])
                    Data limite da etapa 2: <span class="font-semibold text-brand-black">{{ $chartData['prazo']['data_limite'] }}</span>
                    ·
                    @if (($chartData['prazo']['dias_restantes'] ?? 0) >= 0)
                        <span class="font-semibold text-emerald-700">{{ $chartData['prazo']['dias_restantes'] }} dias restantes</span>
                    @else
                        <span class="font-semibold text-red-700">{{ abs((int) $chartData['prazo']['dias_restantes']) }} dias de atraso</span>
                    @endif
                @else
                    Defina a data limite para exibir o monitor de prazo.
                @endif
            </div>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-3 py-3">Funções</th>
                        <th class="px-3 py-3">Etapa 1 - Pré PGU</th>
                        <th class="px-3 py-3">Etapa 2 - PGU</th>
                        <th class="px-3 py-3">Avanço</th>
                        <th class="px-3 py-3">Data limite etapa 2</th>
                        <th class="px-3 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($funcoes as $funcao)
                        <tr>
                            <td class="px-3 py-3">
                                <p class="font-semibold text-brand-black">{{ $funcao['item_codigo'] ? $funcao['item_codigo'] . ' - ' : '' }}{{ $funcao['descricao'] }}</p>
                            </td>
                            <td class="px-3 py-3 text-brand-black">{{ $fmtQtd($funcao['etapa_1']) }}</td>
                            <td class="px-3 py-3 text-brand-black">{{ $fmtQtd($funcao['etapa_2']) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-32 overflow-hidden rounded-full bg-zinc-100">
                                        <div class="h-full rounded-full {{ $funcao['concluido'] ? 'bg-emerald-500' : 'bg-brand-burgundy' }}" style="width: {{ max(0, min(100, $funcao['percentual_avanco'])) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-brand-black">{{ $fmtPct($funcao['percentual_avanco']) }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-brand-black">{{ $funcao['data_limite'] ?: '-' }}</td>
                            <td class="px-3 py-3">
                                @if ($funcao['concluido'])
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Concluído</span>
                                @else
                                    <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pendente (faltam {{ $fmtQtd($funcao['faltante']) }})</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-brand-gray">Sem dados de histograma para o contrato e competência selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @php
        $comparativoCategorias = $chartData['comparativo']['categorias'] ?? [];
        $comparativoEtapa1 = $chartData['comparativo']['etapa_1'] ?? [];
        $comparativoEtapa2 = $chartData['comparativo']['etapa_2'] ?? [];
        $statusSeries = $chartData['status']['series'] ?? [0, 0];
        $statusLabels = $chartData['status']['labels'] ?? ['Concluídas', 'Pendentes'];
        $gapCategorias = $chartData['gaps']['categorias'] ?? [];
        $gapSeries = $chartData['gaps']['series'] ?? [];
        $progressoCategorias = $chartData['progresso']['categorias'] ?? [];
        $progressoConcluido = $chartData['progresso']['concluido_pct'] ?? [];
        $progressoPendente = $chartData['progresso']['pendente_pct'] ?? [];
        $avancoMedio = $chartData['avanco_medio'] ?? 0;

        $chartComparativoConfig = [
            'chart' => ['type' => 'bar', 'height' => 340, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Etapa 1 - Pré PGU', 'data' => $comparativoEtapa1],
                ['name' => 'Etapa 2 - PGU', 'data' => $comparativoEtapa2],
            ],
            'xaxis' => ['categories' => $comparativoCategorias, 'labels' => ['rotate' => -35]],
            'colors' => ['#9ca3af', '#6f1731'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '48%']],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'top'],
            'yaxis' => ['title' => ['text' => 'Quantidade']],
        ];

        $chartProgressoConfig = [
            'chart' => ['type' => 'bar', 'height' => 340, 'stacked' => true, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Concluído PGU (%)', 'data' => $progressoConcluido],
                ['name' => 'Pendente (%)', 'data' => $progressoPendente],
            ],
            'xaxis' => ['categories' => $progressoCategorias],
            'colors' => ['#059669', '#b45309'],
            'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 3, 'barHeight' => '70%']],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'top'],
            'tooltip' => ['shared' => true, 'intersect' => false],
        ];

        $chartStatusConfig = [
            'chart' => ['type' => 'donut', 'height' => 320],
            'series' => $statusSeries,
            'labels' => $statusLabels,
            'colors' => ['#059669', '#b45309'],
            'legend' => ['position' => 'bottom'],
            'dataLabels' => ['enabled' => true],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '62%',
                        'labels' => [
                            'show' => true,
                            'total' => ['show' => true, 'label' => 'Funções'],
                        ],
                    ],
                ],
            ],
        ];

        $chartGapConfig = [
            'chart' => ['type' => 'bar', 'height' => 340, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Faltante para PGU', 'data' => $gapSeries],
            ],
            'xaxis' => ['categories' => $gapCategorias],
            'colors' => ['#dc2626'],
            'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4]],
            'dataLabels' => ['enabled' => false],
            'yaxis' => ['title' => ['text' => 'Funções']],
        ];

        $chartGaugeConfig = [
            'chart' => ['type' => 'radialBar', 'height' => 320],
            'series' => [round((float) $avancoMedio, 1)],
            'labels' => ['Avanço médio'],
            'colors' => ['#6f1731'],
            'plotOptions' => [
                'radialBar' => [
                    'hollow' => ['size' => '62%'],
                    'dataLabels' => [
                        'name' => ['fontSize' => '14px'],
                        'value' => ['fontSize' => '28px'],
                    ],
                ],
            ],
        ];
    @endphp

    <script type="application/json" id="chart-comparativo-config">
        {!! json_encode($chartComparativoConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script type="application/json" id="chart-status-config">
        {!! json_encode($chartStatusConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script type="application/json" id="chart-gap-config">
        {!! json_encode($chartGapConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script type="application/json" id="chart-progresso-config">
        {!! json_encode($chartProgressoConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script type="application/json" id="chart-gauge-config">
        {!! json_encode($chartGaugeConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection
