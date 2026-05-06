@php
    $totalFuncoes = (int) ($totalFuncoes ?? 0);
    $funcoes100 = is_array($funcoes100 ?? null) ? array_values($funcoes100) : [];
    $qtdFuncoes100 = (int) ($qtdFuncoes100 ?? count($funcoes100));
    $qtdDemaisFuncoes = (int) ($qtdDemaisFuncoes ?? max(0, $totalFuncoes - $qtdFuncoes100));
    $percentual100 = (float) ($percentual100 ?? ($totalFuncoes > 0 ? ($qtdFuncoes100 / $totalFuncoes) * 100 : 0));
    $percentualDemais = (float) ($percentualDemais ?? ($totalFuncoes > 0 ? ($qtdDemaisFuncoes / $totalFuncoes) * 100 : 0));

    $formatPercent = fn ($value) => number_format((float) $value, 1, ',', '').'%';

    $radius = 173;
    $circumference = 2 * pi() * $radius;
    $progressDash = $circumference * ($percentual100 / 100);
    $gapDash = max(0, $circumference - $progressDash);

    $percentual100Label = $formatPercent($percentual100);
    $percentualDemaisLabel = $formatPercent($percentualDemais);
@endphp

<section class="pgu2-stage">
    <div class="pgu2-slide-shell">
        <div class="pgu2-slide-scale">
            <div class="pgu2-slide">
                <div class="pgu2-slide-logo">
                    <img src="{{ asset('logo.png') }}" alt="Omega Service" class="pgu2-slide-logo-img">
                </div>

                <div class="pgu2-left">
                    <div class="pgu2-eyebrow">
                        <span class="pgu2-eyebrow-line"></span>
                        <span class="pgu2-eyebrow-text">SLIDE 2 • FUNÇÕES COM PGU 100%</span>
                    </div>

                    <h1 class="pgu2-title">Funções sem pendência PGU</h1>
                    <div class="pgu2-title-rule"></div>

                    <p class="pgu2-subtitle">Áreas integralmente liberadas, sem pendências para o PGU.</p>

                    <div class="pgu2-cards-grid">
                        @forelse ($funcoes100 as $index => $funcao)
                            <div class="pgu2-card">
                                <div class="pgu2-card-icon" aria-hidden="true">
                                    <svg viewBox="0 0 80 80" fill="none">
                                        <circle cx="40" cy="40" r="31" stroke="currentColor" stroke-width="3"/>
                                        <circle cx="40" cy="40" r="39" stroke="currentColor" stroke-width="2.4" stroke-dasharray="6 5" opacity="0.9"/>
                                        <path d="M27 41.5l8.5 8.5L54 30.8" stroke="currentColor" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="pgu2-card-body">
                                    <div class="pgu2-card-title">{{ ($index + 1).'. '.$funcao }}</div>
                                    <div class="pgu2-card-subtitle">PGU 100%</div>
                                </div>
                            </div>
                        @empty
                            <div class="pgu2-card pgu2-card-empty">
                                <div class="pgu2-card-body">
                                    <div class="pgu2-card-title">Nenhuma função com PGU 100% neste recorte.</div>
                                    <div class="pgu2-card-subtitle">Acompanhar evolução mensal</div>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="pgu2-insight-box">
                        <div class="pgu2-insight-accent"></div>
                        <div class="pgu2-insight-icon-wrap" aria-hidden="true">
                            <svg class="pgu2-insight-icon" viewBox="0 0 72 72" fill="none">
                                <circle cx="36" cy="36" r="25" stroke="currentColor" stroke-width="2.4"/>
                                <circle cx="36" cy="36" r="11" stroke="currentColor" stroke-width="2.4"/>
                                <circle cx="36" cy="36" r="3.5" fill="currentColor"/>
                                <path d="M36 11V4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <path d="M11 36H4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <path d="M36 61v7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <path d="M61 36h7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <path d="M51 21l12-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <p class="pgu2-insight-text">{{ $pgu2InsightText ?? '' }}</p>
                    </div>

                    <div class="pgu2-bottom-metrics">
                        <div class="pgu2-bottom-metric">
                            <div class="pgu2-bottom-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 80 80" fill="none">
                                    <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="34" cy="28" r="8" stroke="currentColor" stroke-width="2.4"/>
                                    <path d="M22 48c2.8-6.8 8.2-10.2 16-10.2 6.2 0 11 2.1 14.4 6.2" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    <circle cx="53" cy="53" r="10" stroke="currentColor" stroke-width="2.4"/>
                                    <path d="M48 53.5l3.2 3.1L58 49.8" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <div class="pgu2-bottom-number">{{ $qtdFuncoes100 }}</div>
                                <div class="pgu2-bottom-label">funções totalmente concluídas</div>
                            </div>
                        </div>

                        <div class="pgu2-bottom-separator"></div>

                        <div class="pgu2-bottom-metric">
                            <div class="pgu2-bottom-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 80 80" fill="none">
                                    <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/>
                                    <path d="M24 54V38" stroke="currentColor" stroke-width="3.6" stroke-linecap="round"/>
                                    <path d="M35 54V27" stroke="currentColor" stroke-width="3.6" stroke-linecap="round"/>
                                    <path d="M46 54V20" stroke="currentColor" stroke-width="3.6" stroke-linecap="round"/>
                                    <path d="M18 54h36" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div>
                                <div class="pgu2-bottom-number">{{ $qtdDemaisFuncoes }}</div>
                                <div class="pgu2-bottom-label">funções ainda em acompanhamento</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pgu2-right">
                    <div class="pgu2-donut-area">
                        <svg class="pgu2-donut-svg" viewBox="0 0 620 620" role="img" aria-label="Funções com PGU 100%">
                            <defs>
                                <linearGradient id="pgu2WineGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#780018"/>
                                    <stop offset="50%" stop-color="#9C002B"/>
                                    <stop offset="100%" stop-color="#B1002E"/>
                                </linearGradient>
                                <filter id="pgu2SoftShadow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="8" stdDeviation="8" flood-color="#000000" flood-opacity="0.07"/>
                                </filter>
                            </defs>

                            <g class="pgu2-radial-marks" transform="translate(310 310)">
                                @for ($i = 0; $i < 64; $i++)
                                    <line x1="0" y1="-236" x2="0" y2="-248" transform="rotate({{ $i * 5.625 }})"/>
                                @endfor
                            </g>

                            <circle cx="310" cy="310" r="248" fill="none" stroke="#ECECEC" stroke-width="1" stroke-dasharray="4 5" opacity="0.55"/>
                            <circle cx="310" cy="310" r="{{ $radius }}" fill="none" stroke="#EFEFEF" stroke-width="66" filter="url(#pgu2SoftShadow)"/>
                            <circle cx="310" cy="310" r="{{ $radius }}" fill="none" stroke="url(#pgu2WineGradient)" stroke-width="66" stroke-linecap="butt" stroke-dasharray="{{ $progressDash }} {{ $gapDash }}" transform="rotate(-90 310 310)"/>
                            <circle cx="310" cy="310" r="{{ $radius }}" fill="none" stroke="#FFFFFF" stroke-width="70" stroke-dasharray="7 {{ $circumference - 7 }}" stroke-dashoffset="-{{ $progressDash - 2 }}" transform="rotate(-90 310 310)" opacity="0.98"/>
                            <circle cx="310" cy="310" r="118" fill="#FAFAFA" opacity="0.98" filter="url(#pgu2SoftShadow)"/>

                            <path class="pgu2-callout-line-solid" d="M 440 163 L 486 118 L 502 118"/>
                            <circle cx="510" cy="118" r="4.8" fill="#8B0B24"/>
                            <path class="pgu2-callout-line-dashed" d="M 212 456 L 182 490 L 160 490 L 160 518"/>
                            <circle cx="160" cy="518" r="4.5" fill="#D2D2D2"/>

                            <text x="310" y="300" text-anchor="middle" class="pgu2-donut-center-main">{{ $qtdFuncoes100 }} de {{ $totalFuncoes }}</text>
                            <text x="310" y="350" text-anchor="middle" class="pgu2-donut-center-sub">funções</text>
                            <rect x="292" y="370" width="36" height="4" rx="2" fill="#8B0B24"/>
                            <text x="310" y="398" text-anchor="middle" class="pgu2-donut-center-foot">100% concluídas</text>

                            <text x="522" y="124" class="pgu2-callout-top-number">{{ $percentual100Label }}</text>
                            <text x="522" y="146" class="pgu2-callout-top-label">funções em 100%</text>
                            <text x="118" y="544" class="pgu2-callout-bottom-number">{{ $percentualDemaisLabel }}</text>
                            <text x="118" y="567" class="pgu2-callout-bottom-label">demais funções</text>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
