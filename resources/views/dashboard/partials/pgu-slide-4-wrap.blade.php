@php
    $paretoItems = collect($paretoItems ?? [])
        ->filter(fn ($item) => is_array($item) && trim((string) ($item['funcao'] ?? '')) !== '')
        ->map(function ($item) {
            $item['pendencias'] = (int) ($item['pendencias'] ?? 0);
            return $item;
        })
        ->values()
        ->all();

    $totalPendencias = (int) ($totalPendencias ?? collect($paretoItems)->sum('pendencias'));
    $totalTop5 = (int) collect($paretoItems)->take(5)->sum('pendencias');
    $maiorItem = collect($paretoItems)->sortByDesc('pendencias')->first();
    $maiorItemPendencias = (int) ($maiorItem['pendencias'] ?? 0);
    $concentracaoTop5 = $totalPendencias > 0 ? ($totalTop5 / $totalPendencias) * 100 : 0;
    $pgu4InsightText = (string) ($pgu4InsightText ?? '');

    $formatPercent = fn ($value) => number_format((float) $value, 1, ',', '').'%';
    $maxVolume = max(100, (int) collect($paretoItems)->max('pendencias'));

    $chart = [
        'viewW' => 1080,
        'viewH' => 430,
        'x0' => 72,
        'y0' => 340,
        'plotW' => 890,
        'plotH' => 280,
        'rightAxisX' => 998,
        'maxVolume' => $maxVolume,
        'barW' => 76,
    ];

    $count = max(1, count($paretoItems));
    $slot = $chart['plotW'] / $count;
    $running = 0;
    $plotPoints = [];
    foreach ($paretoItems as $i => $item) {
        $cx = $chart['x0'] + ($slot * $i) + ($slot / 2);
        $barH = ($item['pendencias'] / $chart['maxVolume']) * $chart['plotH'];
        $barX = $cx - ($chart['barW'] / 2);
        $barY = $chart['y0'] - $barH;

        $running += (int) $item['pendencias'];
        $accumulated = isset($item['accumulated'])
            ? (float) $item['accumulated']
            : ($totalPendencias > 0 ? ($running / $totalPendencias) * 100 : 0);
        $lineY = $chart['y0'] - (($accumulated / 100) * $chart['plotH']);

        $plotPoints[] = [
            'index' => $i,
            'cx' => $cx,
            'barX' => $barX,
            'barY' => $barY,
            'barH' => $barH,
            'value' => (int) ($item['pendencias'] ?? 0),
            'accumulated' => $accumulated,
            'lineY' => $lineY,
            'item' => $item,
        ];
    }

    $barRows = array_map(function (array $point) use ($chart) {
        $barType = $point['item']['type'] ?? 'wine-light';
        $barFill = match ($barType) {
            'main' => 'url(#pgu4WineBar)',
            'wine-soft' => 'url(#pgu4WineSoftBar)',
            'wine-light' => 'url(#pgu4WineLightBar)',
            'gray' => 'url(#pgu4GrayBar)',
            default => 'url(#pgu4WineLightBar)',
        };

        return [
            'bar_x' => $point['barX'],
            'bar_y' => $point['barY'],
            'bar_h' => $point['barH'],
            'bar_fill' => $barFill,
            'cx' => $point['cx'],
            'value' => $point['value'],
            'value_y' => $barType === 'main' ? $point['barY'] - 10 : $point['barY'] + 24,
            'is_main' => $barType === 'main',
            'label_lines' => $point['item']['label_lines'] ?? [''],
            'bar_w' => $chart['barW'],
        ];
    }, $plotPoints);

    $lineRows = array_map(function (array $point, int $idx) use ($formatPercent, $plotPoints) {
        $label = $formatPercent($point['accumulated']);
        if (round($point['accumulated'], 1) === 100.0) {
            $label = '100%';
        }

        $labelYOffset = 22;
        if ($idx === 0) {
            $labelYOffset = 28;
        } elseif ($idx >= (count($plotPoints) - 2)) {
            $labelYOffset = 18;
        }

        return [
            'cx' => $point['cx'],
            'cy' => $point['lineY'],
            'label_y' => $point['lineY'] - $labelYOffset,
            'label' => $label,
        ];
    }, $plotPoints, array_keys($plotPoints));

    $polylinePoints = collect($plotPoints)->map(fn ($point) => $point['cx'].','.$point['lineY'])->implode(' ');
    $yTicks = [0, 20, 40, 60, 80, 100];
    $percentTicks = [0, 20, 40, 60, 80, 100];
    $reference80Y = $chart['y0'] - (0.8 * $chart['plotH']);
@endphp

<section class="pgu4-stage">
    <div class="pgu4-slide-shell">
        <div class="pgu4-slide-scale">
            <div class="pgu4-slide">
                <div class="pgu4-slide-logo">
                    <img src="{{ asset('logo.png') }}" alt="Omega Service" class="pgu4-slide-logo-img">
                </div>

                <div class="pgu4-content">
                    <div class="pgu4-eyebrow">
                        <span class="pgu4-eyebrow-line"></span>
                        <span class="pgu4-eyebrow-text">SLIDE 4 • CONCENTRAÇÃO DO PROBLEMA</span>
                    </div>
                    <h1 class="pgu4-title">Poucas funções concentram a maior parte das pendências.</h1>
                    <div class="pgu4-title-rule"></div>
                    <p class="pgu4-subtitle">Gráfico de Pareto com volume absoluto de pendências e linha acumulada de concentração.</p>

                    @if (count($paretoItems) > 0)
                    <div class="pgu4-chart-wrap">
                        <svg class="pgu4-chart" viewBox="0 0 {{ $chart['viewW'] }} {{ $chart['viewH'] }}" role="img" aria-label="Gráfico de Pareto de pendências PGU">
                            <defs>
                                <linearGradient id="pgu4WineBar" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#B1002E"/>
                                    <stop offset="100%" stop-color="#8B0B24"/>
                                </linearGradient>
                                <linearGradient id="pgu4WineSoftBar" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(139,11,36,0.62)"/>
                                    <stop offset="100%" stop-color="rgba(139,11,36,0.38)"/>
                                </linearGradient>
                                <linearGradient id="pgu4WineLightBar" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="rgba(139,11,36,0.32)"/>
                                    <stop offset="100%" stop-color="rgba(139,11,36,0.20)"/>
                                </linearGradient>
                                <linearGradient id="pgu4GrayBar" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#CFCFCF"/>
                                    <stop offset="100%" stop-color="#AFAFAF"/>
                                </linearGradient>
                            </defs>

                            <text x="0" y="24" class="pgu4-axis-title-left">Pendências (volume absoluto)</text>
                            <text x="882" y="24" class="pgu4-axis-title-right">Percentual acumulado (%)</text>

                            <line x1="{{ $chart['x0'] }}" y1="{{ $chart['y0'] - $chart['plotH'] }}" x2="{{ $chart['x0'] }}" y2="{{ $chart['y0'] }}" class="pgu4-axis-line"/>
                            <line x1="{{ $chart['x0'] - 6 }}" y1="{{ $chart['y0'] }}" x2="{{ $chart['x0'] + $chart['plotW'] + 8 }}" y2="{{ $chart['y0'] }}" class="pgu4-axis-line-strong"/>
                            <line x1="{{ $chart['rightAxisX'] }}" y1="{{ $chart['y0'] - $chart['plotH'] }}" x2="{{ $chart['rightAxisX'] }}" y2="{{ $chart['y0'] }}" class="pgu4-axis-line"/>

                            @foreach ($yTicks as $tick)
                                @php($tickY = $chart['y0'] - (($tick / $chart['maxVolume']) * $chart['plotH']))
                                <line x1="{{ $chart['x0'] - 6 }}" y1="{{ $tickY }}" x2="{{ $chart['x0'] }}" y2="{{ $tickY }}" class="pgu4-tick-line"/>
                                <text x="{{ $chart['x0'] - 16 }}" y="{{ $tickY + 5 }}" text-anchor="end" class="pgu4-axis-number">{{ $tick }}</text>
                            @endforeach

                            @foreach ($percentTicks as $tick)
                                @php($tickY = $chart['y0'] - (($tick / 100) * $chart['plotH']))
                                <line x1="{{ $chart['rightAxisX'] }}" y1="{{ $tickY }}" x2="{{ $chart['rightAxisX'] + 6 }}" y2="{{ $tickY }}" class="pgu4-tick-line"/>
                                <text x="{{ $chart['rightAxisX'] + 14 }}" y="{{ $tickY + 5 }}" class="pgu4-axis-number">{{ $tick }}%</text>
                            @endforeach

                            <line x1="{{ $chart['x0'] }}" y1="{{ $reference80Y }}" x2="{{ $chart['rightAxisX'] }}" y2="{{ $reference80Y }}" class="pgu4-reference-line"/>
                            <text x="{{ $chart['rightAxisX'] - 10 }}" y="{{ $reference80Y - 8 }}" text-anchor="end" class="pgu4-reference-label">Linha de referência 80%</text>

                            @foreach ($barRows as $bar)
                                <rect x="{{ $bar['bar_x'] }}" y="{{ $bar['bar_y'] }}" width="{{ $bar['bar_w'] }}" height="{{ $bar['bar_h'] }}" fill="{{ $bar['bar_fill'] }}"/>
                                <text x="{{ $bar['cx'] }}" y="{{ $bar['value_y'] }}" text-anchor="middle" class="{{ $bar['is_main'] ? 'pgu4-bar-value is-main' : 'pgu4-bar-value' }}">{{ $bar['value'] }}</text>

                                <text x="{{ $bar['cx'] }}" y="365" text-anchor="middle" class="pgu4-x-label">
                                    @foreach ($bar['label_lines'] as $lineIndex => $line)
                                        <tspan x="{{ $bar['cx'] }}" dy="{{ $lineIndex === 0 ? 0 : 17 }}">{{ $line }}</tspan>
                                    @endforeach
                                </text>
                            @endforeach

                            <polyline points="{{ $polylinePoints }}" class="pgu4-pareto-line"/>

                            @foreach ($lineRows as $linePoint)
                                <circle cx="{{ $linePoint['cx'] }}" cy="{{ $linePoint['cy'] }}" r="7" class="pgu4-pareto-dot"/>
                                <text x="{{ $linePoint['cx'] }}" y="{{ $linePoint['label_y'] }}" text-anchor="middle" class="pgu4-pareto-label">{{ $linePoint['label'] }}</text>
                            @endforeach
                        </svg>
                    </div>
                    @else
                    <div class="pgu4-chart-wrap flex items-center justify-center">
                        <p class="text-sm font-medium text-slate-500">Sem dados reais de Pareto para o recorte selecionado.</p>
                    </div>
                    @endif

                    <div class="pgu4-footer-row">
                        <div class="pgu4-insight-box">
                            <div class="pgu4-insight-accent"></div>
                            <div class="pgu4-insight-icon-wrap" aria-hidden="true">
                                <svg class="pgu4-insight-icon" viewBox="0 0 72 72" fill="none">
                                    <circle cx="33" cy="35" r="25" stroke="currentColor" stroke-width="2.2"/>
                                    <path d="M22 46V34" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    <path d="M31 46V25" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    <path d="M40 46V18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    <path d="M18 46h28" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                    <path d="M54 37l12 22H42l12-22z" fill="#FAFAFA" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                                    <path d="M54 45v6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    <circle cx="54" cy="55" r="1.8" fill="currentColor"/>
                                </svg>
                            </div>
                            <p class="pgu4-insight-text">{{ $pgu4InsightText }}</p>
                        </div>

                        <div class="pgu4-bottom-metrics">
                            <div class="pgu4-bottom-metric">
                                <div class="pgu4-bottom-icon-circle" aria-hidden="true">
                                    <svg viewBox="0 0 80 80" fill="none">
                                        <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="28" cy="31" r="7" stroke="currentColor" stroke-width="2.2"/>
                                        <circle cx="44" cy="29" r="7" stroke="currentColor" stroke-width="2.2"/>
                                        <circle cx="55" cy="37" r="6" stroke="currentColor" stroke-width="2.2"/>
                                        <path d="M17 56c1.8-8 7.6-12 17.2-12 3.5 0 6.5.5 9 1.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                        <path d="M35 56c1.8-7.5 7.2-11.2 16-11.2 7 0 11.4 3 13 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="pgu4-bottom-number">{{ $totalPendencias }}</div>
                                    <div class="pgu4-bottom-label">Pendências mapeadas</div>
                                </div>
                            </div>

                            <div class="pgu4-bottom-separator"></div>

                            <div class="pgu4-bottom-metric">
                                <div class="pgu4-bottom-icon-circle" aria-hidden="true">
                                    <svg viewBox="0 0 80 80" fill="none">
                                        <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="40" cy="40" r="13" stroke="currentColor" stroke-width="2.2"/>
                                        <circle cx="40" cy="40" r="4" fill="currentColor"/>
                                        <path d="M40 6v13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                        <path d="M40 61v13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                        <path d="M6 40h13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                        <path d="M61 40h13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="pgu4-bottom-number">{{ $formatPercent($concentracaoTop5) }}</div>
                                    <div class="pgu4-bottom-label">Concentração nas Top 5</div>
                                </div>
                            </div>

                            <div class="pgu4-bottom-separator"></div>

                            <div class="pgu4-bottom-metric">
                                <div class="pgu4-bottom-icon-circle" aria-hidden="true">
                                    <svg viewBox="0 0 80 80" fill="none">
                                        <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/>
                                        <path d="M25 56V42" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                        <path d="M38 56V32" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                        <path d="M51 56V23" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                        <path d="M20 56h38" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="pgu4-bottom-number">{{ $maiorItemPendencias }}</div>
                                    <div class="pgu4-bottom-label">Maior volume individual</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pgu4-right-visual" aria-hidden="true">
                    <svg class="pgu4-network-lines" viewBox="0 0 520 520" fill="none">
                        <path d="M50 365L112 304L168 340L236 236L298 256L370 142L442 168L500 40" stroke="white" stroke-width="2" opacity="0.72"/>
                        <path d="M100 220L155 228L205 176L260 250L330 210L410 222" stroke="#BFC2C5" stroke-width="1.4" opacity="0.48"/>
                        <line x1="112" y1="304" x2="112" y2="406" stroke="white" stroke-width="1" opacity="0.45"/>
                        <line x1="236" y1="236" x2="236" y2="406" stroke="white" stroke-width="1" opacity="0.45"/>
                        <line x1="370" y1="142" x2="370" y2="406" stroke="white" stroke-width="1" opacity="0.45"/>
                        <circle cx="50" cy="365" r="5" fill="white"/>
                        <circle cx="112" cy="304" r="5" fill="white"/>
                        <circle cx="168" cy="340" r="5" fill="white"/>
                        <circle cx="236" cy="236" r="5" fill="white"/>
                        <circle cx="298" cy="256" r="5" fill="#C8C8C8"/>
                        <circle cx="370" cy="142" r="5" fill="white"/>
                        <circle cx="442" cy="168" r="5" fill="white"/>
                        <circle cx="500" cy="40" r="5" fill="white"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>
