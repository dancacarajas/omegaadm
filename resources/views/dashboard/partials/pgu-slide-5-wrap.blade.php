@php
    $acoes = collect($acoes ?? [])
        ->filter(fn ($row) => is_array($row) && trim((string) ($row['funcao'] ?? '')) !== '')
        ->values()
        ->all();
    $totalPendenciasPriorizadas = (int) ($totalPendenciasPriorizadas ?? collect($acoes)->sum('pendencias'));
    $totalFuncoesCriticas = (int) ($totalFuncoesCriticas ?? count($acoes));
    $ritmoAcompanhamento = (string) ($ritmoAcompanhamento ?? '24h');
    $pgu5FocusText = (string) ($pgu5FocusText ?? 'Sem funções críticas com pendências para o recorte selecionado.');
@endphp

<section class="pgu5-stage">
    <div class="pgu5-slide-shell">
        <div class="pgu5-slide-scale">
            <div class="pgu5-slide">
                <div class="pgu5-slide-logo">
                    <img src="{{ asset('logo.png') }}" alt="Omega Service" class="pgu5-slide-logo-img">
                </div>

                <div class="pgu5-content">
                    <div class="pgu5-eyebrow">
                        <span class="pgu5-eyebrow-line"></span>
                        <span class="pgu5-eyebrow-text">SLIDE 5 • PLANO DE AÇÃO EXECUTIVO</span>
                    </div>

                    <h1 class="pgu5-title">Prioridades para destravar o PGU</h1>
                    <p class="pgu5-subtitle">Ações prioritárias para reduzir pendências nas funções críticas.</p>

                    <div class="pgu5-table-card">
                        <div class="pgu5-table-topline"></div>

                        <div class="pgu5-table-header pgu5-table-grid">
                            <div class="pgu5-header-empty"></div>
                            <div>Função</div>
                            <div>Pendências</div>
                            <div>Risco</div>
                            <div>Ação Recomendada</div>
                            <div>Responsável</div>
                        </div>

                        @foreach ($acoes as $item)
                            <div class="pgu5-table-row pgu5-table-grid">
                                <div class="pgu5-function-icon" aria-hidden="true">
                                    <svg viewBox="0 0 64 64" fill="none">
                                        <circle cx="22" cy="23" r="7" stroke="currentColor" stroke-width="2.4"/>
                                        <circle cx="35" cy="22" r="7" stroke="currentColor" stroke-width="2.4"/>
                                        <circle cx="44" cy="30" r="6" stroke="currentColor" stroke-width="2.4"/>
                                        <path d="M10 47c1.8-8 8-12 17.8-12 3.4 0 6.4.5 8.8 1.6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                        <path d="M27 47c1.8-7.5 7.4-11.2 16-11.2 7.2 0 11.6 3 13.2 9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                                    </svg>
                                </div>

                                <div class="pgu5-function-name">{{ $item['funcao'] ?? '' }}</div>
                                <div class="pgu5-pendency-value">{{ (int) ($item['pendencias'] ?? 0) }}</div>
                                <div>
                                    <div class="pgu5-risk-pill is-{{ $item['risco_tipo'] ?? 'attention' }}">
                                        <span>{{ $item['risco'] ?? 'Atenção' }}</span>
                                    </div>
                                </div>
                                <div class="pgu5-action-text">{{ $item['acao'] ?? '' }}</div>
                                <div class="pgu5-owner-text">{{ $item['responsavel'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pgu5-focus-box">
                        <div class="pgu5-focus-icon" aria-hidden="true">
                            <svg viewBox="0 0 84 84" fill="none"><circle cx="38" cy="43" r="28" stroke="currentColor" stroke-width="2.8"/><circle cx="38" cy="43" r="16" stroke="currentColor" stroke-width="2.8"/><circle cx="38" cy="43" r="5" fill="currentColor"/><path d="M38 15V6" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><path d="M10 43H2" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><path d="M38 71v9" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><path d="M66 43h9" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><path d="M55 26l18-15" stroke="currentColor" stroke-width="3.4" stroke-linecap="round"/><path d="M69 10l4 1 1 4" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="pgu5-focus-divider"></div>
                        <p class="pgu5-focus-text">{{ $pgu5FocusText }}</p>
                    </div>

                    <div class="pgu5-bottom-metrics">
                        <div class="pgu5-bottom-metric"><div class="pgu5-bottom-icon-circle" aria-hidden="true"><svg viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/><path d="M28 20h19l8 8v28H28V20z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M47 20v9h8" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M35 38h13" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M35 47h10" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></div><div><div class="pgu5-bottom-number">{{ $totalPendenciasPriorizadas }}</div><div class="pgu5-bottom-label">pendências priorizadas</div></div></div>
                        <div class="pgu5-bottom-separator"></div>
                        <div class="pgu5-bottom-metric"><div class="pgu5-bottom-icon-circle" aria-hidden="true"><svg viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/><circle cx="28" cy="31" r="7" stroke="currentColor" stroke-width="2.2"/><circle cx="44" cy="29" r="7" stroke="currentColor" stroke-width="2.2"/><circle cx="55" cy="37" r="6" stroke="currentColor" stroke-width="2.2"/><path d="M17 56c1.8-8 7.6-12 17.2-12 3.5 0 6.5.5 9 1.6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M35 56c1.8-7.5 7.2-11.2 16-11.2 7 0 11.4 3 13 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg></div><div><div class="pgu5-bottom-number">{{ $totalFuncoesCriticas }}</div><div class="pgu5-bottom-label">funções críticas</div></div></div>
                        <div class="pgu5-bottom-separator"></div>
                        <div class="pgu5-bottom-metric"><div class="pgu5-bottom-icon-circle" aria-hidden="true"><svg viewBox="0 0 80 80" fill="none"><circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="2"/><circle cx="40" cy="40" r="20" stroke="currentColor" stroke-width="2.5"/><path d="M40 28v13l9 6" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div><div class="pgu5-bottom-number">{{ $ritmoAcompanhamento }}</div><div class="pgu5-bottom-label">ritmo de acompanhamento</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
