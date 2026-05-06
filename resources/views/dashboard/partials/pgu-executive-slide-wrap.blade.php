@php
    /*
    |--------------------------------------------------------------------------
    | Dados do slide PGU (sempre vindos do controller — assembleDashboard)
    |--------------------------------------------------------------------------
    */
    $totalFuncoes = (int) ($totalFuncoes ?? 0);
    $concluidas = (int) ($concluidas ?? 0);
    $pendentes = (int) ($pendentes ?? 0);

    $avancoGeral = (float) ($avancoGeral ?? 0);
    $restante = max(0.0, 100 - $avancoGeral);

    $percentualFuncoesConcluidas = isset($percentualFuncoesConcluidas)
        ? (float) $percentualFuncoesConcluidas
        : ($totalFuncoes > 0 ? round(($concluidas / $totalFuncoes) * 100, 1) : 0.0);
    $percentualFuncoesPendentes = isset($percentualFuncoesPendentes)
        ? (float) $percentualFuncoesPendentes
        : ($totalFuncoes > 0 ? round(($pendentes / $totalFuncoes) * 100, 1) : 0.0);

    $formatPercent = function ($value) {
        return number_format((float) $value, 1, ',', '') . '%';
    };

    $radius = 174;
    $circumference = 2 * pi() * $radius;
    $progressDash = $circumference * ($avancoGeral / 100);
    $gapDash = $circumference - $progressDash;

    $avancoLabel = $formatPercent($avancoGeral);
    $restanteLabel = $formatPercent($restante);
    $funcoesConcluidasLabel = $formatPercent($percentualFuncoesConcluidas);
    $funcoesPendentesLabel = $formatPercent($percentualFuncoesPendentes);

    /* Anéis dos KPIs inferiores: arco proporcional ao percentual (antes era path fixo ~25%) */
    $bottomRingR = 29;
    $bottomRingC = 2 * M_PI * $bottomRingR;
    $pctConclRing = max(0.0, min(100.0, (float) $percentualFuncoesConcluidas));
    $pctPendRing = max(0.0, min(100.0, (float) $percentualFuncoesPendentes));
    $bottomRingDashConcl = $bottomRingC * ($pctConclRing / 100);
    $bottomRingGapConcl = max(0.0, $bottomRingC - $bottomRingDashConcl);
    $bottomRingDashPend = $bottomRingC * ($pctPendRing / 100);
    $bottomRingGapPend = max(0.0, $bottomRingC - $bottomRingDashPend);
@endphp

<section class="pgu-stage">
    <div class="pgu-slide-shell">
        <div class="pgu-slide-scale">
        <div class="pgu-slide">

            <div class="pgu-slide-logo">
                <img src="{{ asset('logo.png') }}" alt="Omega Service" class="pgu-slide-logo-img">
            </div>

            {{-- Coluna esquerda --}}
            <div class="pgu-left">

                {{-- Eyebrow --}}
                <div class="pgu-eyebrow">
                    <span class="pgu-eyebrow-line"></span>
                    <span class="pgu-eyebrow-text">SLIDE 1 • VISÃO GERAL PGU</span>
                </div>

                {{-- Título --}}
                <h1 class="pgu-title">
                    Status executivo do avanço PGU
                </h1>

                <div class="pgu-title-rule"></div>

                {{-- Subtítulo --}}
                <p class="pgu-subtitle">
                    Visão geral consolidada do progresso por função
                </p>

                {{-- Indicadores superiores --}}
                <div class="pgu-main-metrics">
                    <div class="pgu-main-metric">
                        <div class="pgu-main-metric-number">{{ $totalFuncoes }}</div>
                        <div class="pgu-main-metric-rule"></div>
                        <div class="pgu-main-metric-label">
                            Funções<br>monitoradas
                        </div>
                    </div>

                    <div class="pgu-main-metric-separator"></div>

                    <div class="pgu-main-metric">
                        <div class="pgu-main-metric-number">{{ $concluidas }}</div>
                        <div class="pgu-main-metric-rule"></div>
                        <div class="pgu-main-metric-label">
                            Concluídas
                        </div>
                    </div>

                    <div class="pgu-main-metric-separator"></div>

                    <div class="pgu-main-metric">
                        <div class="pgu-main-metric-number">{{ $pendentes }}</div>
                        <div class="pgu-main-metric-rule"></div>
                        <div class="pgu-main-metric-label">
                            Pendentes
                        </div>
                    </div>
                </div>

                {{-- Box explicativo --}}
                <div class="pgu-insight-box">
                    <div class="pgu-insight-accent"></div>

                    <div class="pgu-insight-icon-wrap" aria-hidden="true">
                        <svg class="pgu-insight-icon" viewBox="0 0 64 64" fill="none">
                            <circle cx="32" cy="32" r="25" stroke="currentColor" stroke-width="2"/>
                            <path d="M32 5v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M32 49v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M5 32h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M49 32h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M22 42V31" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <path d="M31 42V24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <path d="M40 42V18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <path d="M19 42h26" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <path d="M22 31h6v11h-6z" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M31 24h6v18h-6z" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M40 18h6v24h-6z" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                    </div>

                    <p class="pgu-insight-text">
                        {{ $pguInsightText ?? '' }}
                    </p>
                </div>

                {{-- Indicadores inferiores --}}
                <div class="pgu-bottom-metrics">
                    <div class="pgu-bottom-metric">
                        <div class="pgu-bottom-icon-circle pgu-bottom-icon-check" aria-hidden="true">
                            <svg viewBox="0 0 64 64" fill="none">
                                <circle cx="32" cy="32" r="29" stroke="currentColor" stroke-width="1.8" fill="none" opacity=".18"/>
                                <circle
                                    cx="32"
                                    cy="32"
                                    r="29"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    fill="none"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $bottomRingDashConcl }} {{ $bottomRingGapConcl }}"
                                    stroke-dashoffset="0"
                                    transform="rotate(-90 32 32)"
                                />
                                <path d="M20 33.5l8 8L45 23" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>

                        <div>
                            <div class="pgu-bottom-number">{{ $funcoesConcluidasLabel }}</div>
                            <div class="pgu-bottom-label">funções concluídas</div>
                        </div>
                    </div>

                    <div class="pgu-bottom-separator"></div>

                    <div class="pgu-bottom-metric">
                        <div class="pgu-bottom-icon-circle pgu-bottom-icon-hourglass" aria-hidden="true">
                            <svg viewBox="0 0 64 64" fill="none">
                                <circle cx="32" cy="32" r="29" stroke="currentColor" stroke-width="1.8" fill="none" opacity=".18"/>
                                <circle
                                    cx="32"
                                    cy="32"
                                    r="29"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    fill="none"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $bottomRingDashPend }} {{ $bottomRingGapPend }}"
                                    stroke-dashoffset="0"
                                    transform="rotate(-90 32 32)"
                                />
                                <path d="M22 15h20" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                                <path d="M22 49h20" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                                <path d="M25 15c0 11 14 11 14 22 0 4.5-3 8-7 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M39 15c0 11-14 11-14 22 0 4.5 3 8 7 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <div>
                            <div class="pgu-bottom-number">{{ $funcoesPendentesLabel }}</div>
                            <div class="pgu-bottom-label">funções com pendências</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Coluna direita --}}
            <div class="pgu-right">
                <div class="pgu-donut-area">

                    <svg class="pgu-donut-svg" viewBox="0 0 620 620" role="img" aria-label="Avanço geral PGU">
                        <defs>
                            <linearGradient id="pguWineGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#780018"/>
                                <stop offset="48%" stop-color="#9C002B"/>
                                <stop offset="100%" stop-color="#B1002E"/>
                            </linearGradient>

                            <filter id="pguSoftShadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="8" stdDeviation="8" flood-color="#000000" flood-opacity="0.08"/>
                            </filter>

                            <filter id="pguInnerSoft" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000000" flood-opacity="0.05"/>
                            </filter>
                        </defs>

                        {{-- Marcas radiais externas --}}
                        <g class="pgu-radial-marks" transform="translate(310 310)">
                            @for ($i = 0; $i < 64; $i++)
                                <line
                                    x1="0"
                                    y1="-238"
                                    x2="0"
                                    y2="-250"
                                    transform="rotate({{ $i * 5.625 }})"
                                />
                            @endfor
                        </g>

                        {{-- Círculo externo sutil --}}
                        <circle
                            cx="310"
                            cy="310"
                            r="250"
                            fill="none"
                            stroke="#EAEAEA"
                            stroke-width="1"
                            stroke-dasharray="4 5"
                            opacity="0.6"
                        />

                        {{-- Donut base --}}
                        <circle
                            class="pgu-donut-base"
                            cx="310"
                            cy="310"
                            r="{{ $radius }}"
                            fill="none"
                            stroke="#EFEFEF"
                            stroke-width="66"
                            filter="url(#pguSoftShadow)"
                        />

                        {{-- Donut progresso --}}
                        <circle
                            class="pgu-donut-progress"
                            cx="310"
                            cy="310"
                            r="{{ $radius }}"
                            fill="none"
                            stroke="url(#pguWineGradient)"
                            stroke-width="66"
                            stroke-linecap="butt"
                            stroke-dasharray="{{ $progressDash }} {{ $gapDash }}"
                            stroke-dashoffset="0"
                            transform="rotate(-90 310 310)"
                        />

                        {{-- Pequeno corte branco para refinar separação visual --}}
                        <circle
                            cx="310"
                            cy="310"
                            r="{{ $radius }}"
                            fill="none"
                            stroke="#FFFFFF"
                            stroke-width="70"
                            stroke-dasharray="7 {{ $circumference - 7 }}"
                            stroke-dashoffset="-{{ $progressDash - 2 }}"
                            transform="rotate(-90 310 310)"
                            opacity="0.96"
                        />

                        {{-- Centro --}}
                        <circle
                            cx="310"
                            cy="310"
                            r="117"
                            fill="#FAFAFA"
                            opacity="0.98"
                            filter="url(#pguInnerSoft)"
                        />

                        {{-- Callout superior direito --}}
                        <path
                            class="pgu-callout-line-solid"
                            d="M 444 168 L 488 124 L 548 124"
                        />
                        <circle cx="556" cy="124" r="4.8" fill="#8B0B24"/>

                        {{-- Callout inferior esquerdo --}}
                        <path
                            class="pgu-callout-line-dashed"
                            d="M 173 454 L 122 504 L 84 504 L 84 538"
                        />
                        <circle cx="84" cy="538" r="4.6" fill="#D2D2D2"/>

                        {{-- Textos do callout superior --}}
                        <text x="570" y="133" class="pgu-callout-top-number">{{ $avancoLabel }}</text>
                        <text x="570" y="153" class="pgu-callout-top-label">Avanço consolidado</text>

                        {{-- Textos do callout inferior --}}
                        <text x="54" y="568" class="pgu-callout-bottom-number">{{ $restanteLabel }}</text>
                        <text x="54" y="590" class="pgu-callout-bottom-label">Ainda por avançar</text>

                        {{-- Texto central --}}
                        <text x="310" y="318" text-anchor="middle" class="pgu-donut-center-number">{{ $avancoLabel }}</text>
                        <text x="310" y="358" text-anchor="middle" class="pgu-donut-center-label">Avanço geral</text>
                        <rect x="292" y="383" width="36" height="4" rx="2" fill="#8B0B24"/>
                    </svg>
                </div>
            </div>
        </div>
        </div>
    </div>
</section>
