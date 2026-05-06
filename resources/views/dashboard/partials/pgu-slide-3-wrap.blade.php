@php
    $gargalos = is_array($gargalos ?? null) ? array_values($gargalos) : [];
    $outrasFuncoes = is_array($outrasFuncoes ?? null) ? $outrasFuncoes : ['ranking' => 6, 'funcao' => '', 'pendencias' => 0];
    $funcoesComPendencia = (int) ($funcoesComPendencia ?? 0);
    $pgu3InsightText = (string) ($pgu3InsightText ?? '');

    $maiorValor = collect($gargalos)->push($outrasFuncoes)->max('pendencias') ?: 0;
    $totalTop5 = (int) collect($gargalos)->sum('pendencias');
    $maiorGargalo = collect($gargalos)->sortByDesc('pendencias')->first() ?? ['funcao' => '', 'pendencias' => 0];
    $barWidth = function ($valor) use ($maiorValor) {
        if ($maiorValor <= 0) {
            return 0;
        }
        return round((((int) $valor) / $maiorValor) * 100, 2);
    };
@endphp

<section class="pgu3-stage">
    <div class="pgu3-slide-shell">
        <div class="pgu3-slide-scale">
            <div class="pgu3-slide">
                <div class="pgu3-slide-logo">
                    <img src="{{ asset('logo.png') }}" alt="Omega Service" class="pgu3-slide-logo-img">
                </div>

                <div class="pgu3-content">
                    <div class="pgu3-eyebrow">
                        <span class="pgu3-eyebrow-line"></span>
                        <span class="pgu3-eyebrow-text">SLIDE 3 • PRINCIPAIS GARGALOS</span>
                    </div>

                    <h1 class="pgu3-title">Onde estão concentradas as pendências?</h1>
                    <div class="pgu3-title-rule"></div>
                    <p class="pgu3-subtitle">Ranking executivo das funções com maior volume de pendências.</p>

                    <div class="pgu3-ranking">
                        @foreach ($gargalos as $item)
                            @php($width = $barWidth($item['pendencias'] ?? 0))
                            <div class="pgu3-ranking-row {{ !empty($item['destaque']) ? 'is-highlight' : '' }}">
                                <div class="pgu3-rank-number">{{ (int) ($item['ranking'] ?? 0) }}.</div>
                                <div class="pgu3-rank-label">{{ $item['funcao'] ?? '' }}</div>
                                <div class="pgu3-axis-line"></div>
                                <div class="pgu3-bar-track">
                                    <div class="pgu3-bar" style="width: {{ $width }}%;"></div>
                                </div>
                                <div class="pgu3-bar-value">{{ (int) ($item['pendencias'] ?? 0) }}</div>
                            </div>
                        @endforeach

                        <div class="pgu3-ranking-divider"></div>

                        @php($outrasWidth = $barWidth($outrasFuncoes['pendencias'] ?? 0))
                        <div class="pgu3-ranking-row is-other">
                            <div class="pgu3-rank-number">{{ (int) ($outrasFuncoes['ranking'] ?? 6) }}.</div>
                            <div class="pgu3-rank-label">{{ $outrasFuncoes['funcao'] ?? '' }}</div>
                            <div class="pgu3-axis-line"></div>
                            <div class="pgu3-bar-track">
                                <div class="pgu3-bar" style="width: {{ $outrasWidth }}%;"></div>
                            </div>
                            <div class="pgu3-bar-value">{{ (int) ($outrasFuncoes['pendencias'] ?? 0) }}</div>
                        </div>
                    </div>

                    <div class="pgu3-insight-box">
                        <div class="pgu3-insight-accent"></div>
                        <div class="pgu3-insight-icon-wrap" aria-hidden="true">
                            <svg class="pgu3-insight-icon" viewBox="0 0 72 72" fill="none">
                                <circle cx="33" cy="35" r="25" stroke="currentColor" stroke-width="2.2"/>
                                <path d="M33 8v7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M33 55v7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M6 35h7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M53 35h7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M22 46V34" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <path d="M31 46V25" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <path d="M40 46V18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <path d="M18 46h28" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                <path d="M54 37l12 22H42l12-22z" fill="#FAFAFA" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                                <path d="M54 45v6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                <circle cx="54" cy="55" r="1.8" fill="currentColor"/>
                            </svg>
                        </div>
                        <p class="pgu3-insight-text">{{ $pgu3InsightText }}</p>
                    </div>

                    <div class="pgu3-bottom-metrics">
                        <div class="pgu3-bottom-metric">
                            <div class="pgu3-bottom-icon-circle" aria-hidden="true">
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
                                <div class="pgu3-bottom-number">{{ $totalTop5 }}</div>
                                <div class="pgu3-bottom-label">Top 5 funções</div>
                            </div>
                        </div>

                        <div class="pgu3-bottom-separator"></div>

                        <div class="pgu3-bottom-metric">
                            <div class="pgu3-bottom-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 80 80" fill="none">
                                    <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/>
                                    <path d="M40 19l21 38H19l21-38z" stroke="currentColor" stroke-width="2.6" stroke-linejoin="round"/>
                                    <path d="M40 32v12" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                    <circle cx="40" cy="51" r="2.1" fill="currentColor"/>
                                </svg>
                            </div>
                            <div>
                                <div class="pgu3-bottom-number">{{ (int) ($maiorGargalo['pendencias'] ?? 0) }}</div>
                                <div class="pgu3-bottom-label">Maior gargalo</div>
                            </div>
                        </div>

                        <div class="pgu3-bottom-separator"></div>

                        <div class="pgu3-bottom-metric">
                            <div class="pgu3-bottom-icon-circle" aria-hidden="true">
                                <svg viewBox="0 0 80 80" fill="none">
                                    <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/>
                                    <path d="M25 56V42" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M38 56V32" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M51 56V23" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                    <path d="M20 56h38" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div>
                                <div class="pgu3-bottom-number">{{ $funcoesComPendencia }}</div>
                                <div class="pgu3-bottom-label">Funções com pendência</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
