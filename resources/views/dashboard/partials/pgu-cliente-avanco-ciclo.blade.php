<section id="cardClienteCiclo" class="pgu-client-cycle-card" x-show="!loading && !error && data" x-cloak>
    <style>
        .pgu-client-cycle-card {
            --cc-accent-900: var(--color-brand-burgundy-dark, var(--brand-burgundy-dark));
            --cc-accent-800: var(--color-brand-burgundy, var(--brand-burgundy));
            --cc-accent-100: var(--color-brand-burgundy-soft, var(--brand-burgundy-soft));
            --cc-ink: var(--color-pgu-ink);
            --cc-muted: var(--color-pgu-muted);
            --cc-subtle: var(--color-pgu-subtle);
            --cc-border: var(--color-pgu-border);
            --cc-card: var(--color-pgu-card);
            --cc-soft: var(--color-pgu-bg);
            --cc-warning: var(--color-pgu-warning);
            --cc-danger: var(--color-pgu-danger);

            overflow: hidden;
            border: 1px solid var(--cc-border);
            border-radius: 28px;
            background: var(--cc-card);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            color: var(--cc-ink);
        }

        .pgu-client-cycle-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 28px 32px 24px;
            border-bottom: 1px solid var(--cc-border);
        }

        .pgu-client-cycle-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .pgu-client-cycle-icon {
            display: inline-flex;
            width: 48px;
            height: 48px;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: var(--cc-accent-800);
            color: white;
            flex-shrink: 0;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.08);
            border: 1px solid color-mix(in srgb, var(--cc-accent-800) 18%, white);
        }

        .pgu-client-cycle-title {
            margin: 0;
            font-size: 44px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: var(--cc-ink);
        }

        .pgu-client-cycle-subtitle {
            margin: 10px 0 0;
            max-width: 920px;
            color: var(--cc-muted);
            font-size: clamp(15px, 1.2vw, 19px);
            line-height: 1.45;
        }

        .pgu-client-cycle-contract-box {
            min-width: 350px;
            border: 1px solid var(--cc-border);
            border-radius: 18px;
            background: color-mix(in srgb, var(--cc-card) 92%, var(--cc-soft));
            padding: 18px 20px;
        }

        .pgu-client-cycle-contract-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 18px;
        }

        .pgu-client-cycle-contract-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .pgu-client-cycle-contract-item.full {
            grid-column: 1 / -1;
            padding-top: 14px;
            border-top: 1px solid var(--cc-border);
        }

        .pgu-client-cycle-contract-label {
            display: block;
            color: var(--cc-muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .pgu-client-cycle-contract-value {
            display: block;
            margin-top: 2px;
            color: var(--cc-accent-800);
            font-size: 20px;
            font-weight: 900;
            line-height: 1;
        }

        .pgu-client-cycle-kpis {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0;
            margin: 0 32px;
            border: 1px solid var(--cc-border);
            border-radius: 22px;
            background: var(--cc-card);
            overflow: hidden;
        }

        .pgu-client-cycle-kpi {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 22px 24px;
            border-right: 1px solid var(--cc-border);
        }

        .pgu-client-cycle-kpi:last-child {
            border-right: 0;
        }

        .pgu-client-cycle-kpi-icon {
            display: inline-flex;
            width: 62px;
            height: 62px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: var(--cc-accent-100);
            color: var(--cc-accent-800);
            flex-shrink: 0;
        }

        .pgu-client-cycle-kpi-icon.warning {
            background: color-mix(in srgb, var(--cc-warning) 14%, var(--cc-card));
            color: var(--cc-warning);
        }

        .pgu-client-cycle-kpi-label {
            display: block;
            color: var(--cc-ink);
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.045em;
        }

        .pgu-client-cycle-kpi-value {
            display: block;
            margin-top: 6px;
            color: var(--cc-accent-800);
            font-size: clamp(28px, 2.6vw, 42px);
            font-weight: 950;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .pgu-client-cycle-kpi-value.warning {
            color: var(--cc-warning);
        }

        .pgu-client-cycle-kpi-note {
            display: block;
            margin-top: 8px;
            color: var(--cc-muted);
            font-size: 13px;
            line-height: 1.25;
        }

        .pgu-client-cycle-timeline {
            margin: 18px 32px 0;
            padding: 26px 28px 24px;
            border: 1px solid var(--cc-border);
            border-radius: 24px;
            background: var(--cc-card);
        }

        .pgu-client-cycle-section-title {
            margin: 0;
            color: var(--cc-accent-900);
            font-size: 18px;
            font-weight: 950;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .pgu-client-cycle-section-subtitle {
            margin: 6px 0 0;
            color: var(--cc-ink);
            font-size: 14px;
            line-height: 1.45;
        }

        .pgu-client-cycle-sla-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid color-mix(in srgb, var(--cc-accent-800) 28%, var(--cc-border));
            border-radius: 14px;
            background: linear-gradient(180deg, var(--cc-accent-100) 0%, var(--cc-card) 100%);
            padding: 12px 16px;
            color: var(--cc-accent-900);
            font-size: 15px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pgu-client-cycle-timeline-top {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 28px;
        }

        /* Linha do tempo: conteúdo acima do trilho (título → ícone → haste → nó na linha) */
        .pgu-client-cycle-lane {
            --cycle-rail-h: 6px;
            --cycle-node-d: 12px;
            /* diâmetro visual incl. borda 2px de cada lado */
            --cycle-node-outer: calc(var(--cycle-node-d) + 4px);

            position: relative;
            margin-top: 12px;
            padding: 8px 8px 28px;
        }

        .pgu-client-cycle-track-top {
            display: flex;
            align-items: stretch;
            gap: 10px;
            min-height: 220px;
            padding: 0 4px;
        }

        .pgu-client-cycle-track-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 0;
            flex: 1 1 0;
        }

        .pgu-client-cycle-track-col--sla {
            flex: 1.35 1 0;
            justify-content: flex-start;
            padding-top: 4px;
        }

        .pgu-client-cycle-track-col--narrow {
            flex: 0.85 1 100px;
            max-width: 140px;
        }

        .pgu-client-cycle-track-col--deadline {
            flex: 0.85 1 108px;
            max-width: 150px;
        }

        .pgu-client-cycle-milestone-head {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
        }

        .pgu-client-cycle-marker-title {
            margin: 0 0 10px;
            color: var(--cc-accent-900);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.25;
        }

        .pgu-client-cycle-marker-date {
            margin: 6px 0 0;
            color: var(--cc-muted);
            font-size: 12px;
            font-weight: 600;
            line-height: 1.3;
        }

        .pgu-client-cycle-marker-date--below-line {
            margin-top: 10px;
            color: var(--cc-ink);
            font-weight: 700;
        }

        .pgu-client-cycle-dot {
            display: inline-flex;
            width: 56px;
            height: 56px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--cc-accent-800), var(--cc-accent-900));
            color: white;
            flex-shrink: 0;
        }

        .pgu-client-cycle-milestone--deadline .pgu-client-cycle-dot {
            background: white;
            color: var(--cc-accent-800);
            border: 3px solid var(--cc-accent-800);
        }

        .pgu-client-cycle-stem {
            flex: 1 1 auto;
            width: 2px;
            min-height: 28px;
            margin-top: 10px;
            background: var(--cc-accent-800);
            border-radius: 1px;
        }

        .pgu-client-cycle-stem-spacer {
            flex: 1 1 auto;
            min-height: 32px;
            margin-top: 10px;
        }

        .pgu-client-cycle-track-under {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 2px 4px 0;
            /* Espaço para o rótulo "Hoje" abaixo do ponto (trilho tem altura pequena) */
            margin-top: 2.25rem;
        }

        .pgu-client-cycle-track-under .pgu-client-cycle-track-col--deadline {
            text-align: center;
        }

        .pgu-client-cycle-track-under .pgu-client-cycle-marker-date--below-line {
            margin-top: 0;
        }

        .pgu-client-cycle-sla-box {
            width: 100%;
            max-width: 280px;
            padding: 14px 16px;
            border: 1px solid color-mix(in srgb, var(--cc-accent-800) 22%, var(--cc-border));
            border-radius: 14px;
            background: linear-gradient(180deg, var(--cc-accent-100) 0%, var(--cc-soft) 100%);
            text-align: center;
        }

        .pgu-client-cycle-sla-main {
            color: var(--cc-accent-900);
            font-size: 16px;
            font-weight: 950;
        }

        .pgu-client-cycle-sla-desc {
            margin-top: 6px;
            color: var(--cc-ink);
            font-size: 12px;
            line-height: 1.35;
        }

        .pgu-client-cycle-hline-wrap {
            position: relative;
            height: var(--cycle-rail-h);
            margin: 0 12px;
            /* Centro do trilho = centro vertical dos nós (sobreposição ao fim das colunas) */
            margin-top: calc(-0.5 * var(--cycle-node-outer) - 0.5 * var(--cycle-rail-h));
            overflow: visible;
            z-index: 1;
        }

        .pgu-client-cycle-hline-rail {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: var(--cycle-rail-h);
            border-radius: 999px;
            background: var(--cc-border);
            overflow: hidden;
        }

        /* Só o trilho neutro até o marco SGC; o avanço real vem em `.pgu-client-cycle-hline-progress-fill`. */
        .pgu-client-cycle-hline-solid {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 43%;
            border-radius: 999px 0 0 999px;
            background: transparent;
            overflow: hidden;
        }

        .pgu-client-cycle-hline-dash {
            position: absolute;
            left: 43%;
            right: 0;
            top: 0;
            bottom: 0;
            border-radius: 0 999px 999px 0;
            background: repeating-linear-gradient(
                90deg,
                color-mix(in srgb, var(--cc-accent-800) 72%, var(--cc-border)) 0,
                color-mix(in srgb, var(--cc-accent-800) 72%, var(--cc-border)) 10px,
                transparent 10px,
                transparent 18px
            );
        }

        .pgu-client-cycle-hline-progress-fill {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            max-width: 100%;
            width: 0;
            border-radius: 999px 0 0 999px;
            background: linear-gradient(90deg, var(--cc-accent-900), var(--cc-accent-800));
            pointer-events: none;
            transition: width 0.35s ease;
        }

        .pgu-client-cycle-node-inline {
            width: var(--cycle-node-d);
            height: var(--cycle-node-d);
            border-radius: 999px;
            background: var(--cc-accent-800);
            border: 2px solid var(--cc-card);
            flex-shrink: 0;
            margin-top: 0;
            position: relative;
            z-index: 3;
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--cc-accent-800) 40%, var(--cc-border));
        }

        .pgu-client-cycle-node-inline--accent {
            background: var(--cc-accent-800);
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--cc-accent-800) 35%, var(--cc-border));
        }

        .pgu-client-cycle-node-inline--deadline {
            background: white;
            border-color: var(--cc-accent-800);
            box-shadow: 0 0 0 1px var(--cc-accent-100);
        }

        .pgu-client-cycle-today {
            position: absolute;
            top: calc(0.5 * var(--cycle-rail-h));
            transform: translateX(-50%);
            z-index: 5;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-width: 88px;
            pointer-events: none;
        }

        .pgu-client-cycle-today-dot {
            order: 1;
            width: var(--cycle-node-d);
            height: var(--cycle-node-d);
            flex-shrink: 0;
            border-radius: 999px;
            background: var(--cc-danger);
            border: 2px solid var(--cc-card);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--cc-danger) 22%, transparent);
            transform: translateY(-50%);
        }

        .pgu-client-cycle-today-label {
            order: 2;
            margin-top: 2px;
            color: var(--cc-danger);
            font-size: 12px;
            font-weight: 950;
            line-height: 1.2;
        }

        .pgu-client-cycle-today-date {
            order: 3;
            margin-top: 2px;
            color: var(--cc-ink);
            font-size: 11px;
            font-weight: 700;
        }

        .pgu-client-cycle-info-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            max-width: 1180px;
            margin: 0 auto;
            padding: 12px 18px;
            border-radius: 14px;
            background: var(--cc-accent-100);
            color: var(--cc-accent-900);
            font-size: 14px;
            line-height: 1.45;
        }

        .pgu-client-cycle-bottom-grid {
            display: grid;
            grid-template-columns: 1.05fr 1.1fr 1fr;
            gap: 18px;
            margin: 18px 32px 0;
        }

        .pgu-client-cycle-panel {
            border: 1px solid var(--cc-border);
            border-radius: 22px;
            background: var(--cc-card);
            padding: 22px;
            min-height: 210px;
        }

        .pgu-client-cycle-sla-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 16px;
            border: 1px solid var(--cc-border);
            border-radius: 14px;
            background: var(--cc-card);
            margin-top: 12px;
        }

        .pgu-client-cycle-sla-row:first-of-type {
            margin-top: 18px;
        }

        .pgu-client-cycle-sla-row-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--cc-ink);
            font-size: 15px;
            font-weight: 700;
        }

        .pgu-client-cycle-sla-row-value {
            color: var(--cc-accent-800);
            font-size: 28px;
            font-weight: 950;
            white-space: nowrap;
        }

        .pgu-client-cycle-stepper {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
            position: relative;
        }

        .pgu-client-cycle-step {
            position: relative;
            text-align: center;
            padding: 12px 8px;
        }

        .pgu-client-cycle-step-number {
            display: inline-flex;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--cc-accent-800), var(--cc-accent-900));
            color: white;
            font-size: 20px;
            font-weight: 950;
        }

        .pgu-client-cycle-step-title {
            margin-top: 10px;
            color: var(--cc-accent-900);
            font-size: 14px;
            font-weight: 950;
        }

        .pgu-client-cycle-step-desc {
            margin-top: 4px;
            color: var(--cc-muted);
            font-size: 13px;
            line-height: 1.25;
        }

        .pgu-client-cycle-status-box {
            margin-top: 18px;
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 18px;
            border-radius: 16px;
            border: 1px solid var(--cc-border);
            background: linear-gradient(90deg, var(--cc-accent-100), var(--cc-card));
        }

        .pgu-client-cycle-status-box.is-tone-success {
            border-color: color-mix(in srgb, var(--cc-accent-800) 32%, var(--cc-border));
            background: linear-gradient(90deg, var(--cc-accent-100), var(--cc-card));
        }

        .pgu-client-cycle-status-box.is-tone-warning {
            border-color: color-mix(in srgb, var(--cc-warning) 45%, var(--cc-border));
            background: linear-gradient(90deg, color-mix(in srgb, var(--cc-warning) 10%, var(--cc-card)), var(--cc-card));
        }

        .pgu-client-cycle-status-box.is-tone-danger {
            border-color: color-mix(in srgb, var(--cc-danger) 40%, var(--cc-border));
            background: linear-gradient(90deg, color-mix(in srgb, var(--cc-danger) 8%, var(--cc-card)), var(--cc-card));
        }

        .pgu-client-cycle-status-box.is-tone-neutral {
            border-color: var(--cc-border);
            background: linear-gradient(90deg, var(--cc-soft), var(--cc-card));
        }

        .pgu-client-cycle-status-icon {
            display: inline-flex;
            width: 68px;
            height: 68px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: white;
            flex-shrink: 0;
        }

        .pgu-client-cycle-status-icon.is-tone-success {
            background: linear-gradient(135deg, var(--cc-accent-800), var(--cc-accent-900));
        }

        .pgu-client-cycle-status-icon.is-tone-warning {
            background: linear-gradient(135deg, var(--cc-warning), color-mix(in srgb, var(--cc-warning) 78%, var(--cc-ink)));
        }

        .pgu-client-cycle-status-icon.is-tone-danger {
            background: linear-gradient(135deg, var(--cc-danger), color-mix(in srgb, var(--cc-danger) 72%, var(--cc-ink)));
        }

        .pgu-client-cycle-status-icon.is-tone-neutral {
            background: linear-gradient(135deg, var(--cc-muted), var(--cc-subtle));
        }

        .pgu-client-cycle-status-title {
            margin: 0;
            color: var(--cc-accent-900);
            font-size: 24px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .pgu-client-cycle-status-text {
            margin: 4px 0 0;
            color: var(--cc-ink);
            font-size: 14px;
            line-height: 1.35;
        }

        .pgu-client-cycle-check-list {
            margin: 18px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }

        .pgu-client-cycle-check-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--cc-ink);
            font-size: 15px;
            font-weight: 700;
        }

        .pgu-client-cycle-check-list .check {
            display: inline-flex;
            width: 22px;
            height: 22px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: var(--cc-accent-800);
            color: white;
            flex-shrink: 0;
        }

        .pgu-client-cycle-footer {
            margin-top: 18px;
            padding: 22px 32px 28px;
        }

        .pgu-client-cycle-footer-message {
            display: flex;
            align-items: center;
            gap: 18px;
            width: 100%;
            border-radius: 18px;
            background: linear-gradient(90deg, var(--cc-accent-100), var(--cc-card));
            border: 1px solid var(--cc-border);
            padding: 18px 22px;
            color: var(--cc-ink);
            font-size: 16px;
            line-height: 1.45;
        }

        .pgu-client-cycle-footer-icon {
            display: inline-flex;
            width: 58px;
            height: 58px;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            color: white;
            background: linear-gradient(135deg, var(--cc-accent-800), var(--cc-accent-900));
            flex-shrink: 0;
        }

        @media (max-width: 1280px) {
            .pgu-client-cycle-header {
                flex-direction: column;
            }

            .pgu-client-cycle-contract-box {
                width: 100%;
                min-width: 0;
            }

            .pgu-client-cycle-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pgu-client-cycle-kpi {
                border-right: 0;
                border-bottom: 1px solid var(--cc-border);
            }

            .pgu-client-cycle-bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .pgu-client-cycle-header,
            .pgu-client-cycle-footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .pgu-client-cycle-kpis,
            .pgu-client-cycle-timeline,
            .pgu-client-cycle-bottom-grid {
                margin-left: 20px;
                margin-right: 20px;
            }

            .pgu-client-cycle-kpis {
                grid-template-columns: 1fr;
            }

            .pgu-client-cycle-lane {
                overflow-x: auto;
                min-width: 900px;
            }

        }
    </style>

    <div class="pgu-client-cycle-header">
        <div class="pgu-client-cycle-title-wrap">
            <span class="pgu-client-cycle-icon">
                <i data-lucide="line-chart" class="h-6 w-6"></i>
            </span>

            <div>
                <h2 class="pgu-client-cycle-title">2. Avanço do Ciclo até a Data Limite</h2>
                <p class="pgu-client-cycle-subtitle">
                    Acompanhamento do avanço da mobilização do contrato em relação ao prazo final acordado.
                    Visão clara do progresso atual, dos prazos operacionais e do tempo restante até a conclusão das fases.
                </p>
            </div>
        </div>

        <div class="pgu-client-cycle-contract-box">
            <div class="pgu-client-cycle-contract-grid">
                <div class="pgu-client-cycle-contract-item">
                    <i data-lucide="briefcase-business" class="h-6 w-6 text-brand-burgundy"></i>
                    <div>
                        <span class="pgu-client-cycle-contract-label">Contrato</span>
                        <span class="pgu-client-cycle-contract-value" x-text="clienteCicloSlaResumo().contrato"></span>
                    </div>
                </div>

                <div class="pgu-client-cycle-contract-item">
                    <i data-lucide="calendar-days" class="h-6 w-6 text-brand-burgundy"></i>
                    <div>
                        <span class="pgu-client-cycle-contract-label">Competência</span>
                        <span class="pgu-client-cycle-contract-value" x-text="clienteCicloSlaResumo().competencia"></span>
                    </div>
                </div>

                <div class="pgu-client-cycle-contract-item full">
                    <i data-lucide="calendar-check-2" class="h-6 w-6 text-brand-burgundy"></i>
                    <div>
                        <span class="pgu-client-cycle-contract-label">Data limite para conclusão</span>
                        <span class="pgu-client-cycle-contract-value" x-text="clienteCicloSlaResumo().dataLimiteLabel"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pgu-client-cycle-kpis">
        <div class="pgu-client-cycle-kpi">
            <span class="pgu-client-cycle-kpi-icon">
                <i data-lucide="target" class="h-7 w-7"></i>
            </span>
            <div>
                <span class="pgu-client-cycle-kpi-label">% consolidado atual</span>
                <span class="pgu-client-cycle-kpi-value" x-text="clienteCicloSlaResumo().progressoLabel"></span>
                <span class="pgu-client-cycle-kpi-note">Índice de consolidação do ciclo</span>
            </div>
        </div>

        <div class="pgu-client-cycle-kpi">
            <span class="pgu-client-cycle-kpi-icon">
                <i data-lucide="check" class="h-7 w-7"></i>
            </span>
            <div>
                <span class="pgu-client-cycle-kpi-label">Vagas consolidadas</span>
                <span class="pgu-client-cycle-kpi-value" x-text="formatQtyPtBr(clienteCicloSlaResumo().vagasConsolidadas)"></span>
                <span class="pgu-client-cycle-kpi-note">Vagas já consolidadas</span>
            </div>
        </div>

        <div class="pgu-client-cycle-kpi">
            <span class="pgu-client-cycle-kpi-icon">
                <i data-lucide="refresh-cw" class="h-7 w-7"></i>
            </span>
            <div>
                <span class="pgu-client-cycle-kpi-label">Vagas em evolução</span>
                <span class="pgu-client-cycle-kpi-value" x-text="formatQtyPtBr(clienteCicloSlaResumo().vagasEmEvolucao)"></span>
                <span class="pgu-client-cycle-kpi-note">Vagas em andamento</span>
            </div>
        </div>

        <div class="pgu-client-cycle-kpi">
            <span class="pgu-client-cycle-kpi-icon">
                <i data-lucide="users-round" class="h-7 w-7"></i>
            </span>
            <div>
                <span class="pgu-client-cycle-kpi-label">Vagas mapeadas</span>
                <span class="pgu-client-cycle-kpi-value" x-text="formatQtyPtBr(clienteCicloSlaResumo().vagasMapeadas)"></span>
                <span class="pgu-client-cycle-kpi-note">Total de vagas no PGU</span>
            </div>
        </div>

        <div class="pgu-client-cycle-kpi">
            <span class="pgu-client-cycle-kpi-icon">
                <i data-lucide="calendar-clock" class="h-7 w-7"></i>
            </span>
            <div>
                <span class="pgu-client-cycle-kpi-label">Prazo (dias)</span>
                <span class="pgu-client-cycle-kpi-value" x-text="clienteCicloSlaResumo().diasRestantesKpi"></span>
                <span class="pgu-client-cycle-kpi-note" x-text="clienteCicloSlaResumo().diasRestantesKpiNote"></span>
            </div>
        </div>
    </div>

    <div class="pgu-client-cycle-timeline">
        <div class="pgu-client-cycle-timeline-top">
            <div>
                <h3 class="pgu-client-cycle-section-title">Linha do tempo do ciclo de mobilização</h3>
                <p class="pgu-client-cycle-section-subtitle">
                    Referência operacional do ciclo: proposta aceita → postagem no SGC → avaliação para liberação.
                </p>
            </div>

            <div class="pgu-client-cycle-sla-pill">
                <i data-lucide="calendar-range" class="h-5 w-5"></i>
                <span>Janela estimada até liberação:</span>
                <strong x-text="clienteCicloSlaResumo().janelaTotalSla"></strong>
            </div>
        </div>

        <div class="pgu-client-cycle-lane">
            <div class="pgu-client-cycle-track-top">
                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--narrow">
                    <div class="pgu-client-cycle-milestone-head">
                        <div class="pgu-client-cycle-marker-title">Aceite da proposta</div>
                        <span class="pgu-client-cycle-dot">
                            <i data-lucide="file-check-2" class="h-6 w-6"></i>
                        </span>
                        <div class="pgu-client-cycle-marker-date">Início do fluxo</div>
                    </div>
                    <div class="pgu-client-cycle-stem"></div>
                    <span class="pgu-client-cycle-node-inline pgu-client-cycle-node-inline--accent" aria-hidden="true"></span>
                </div>

                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--sla">
                    <div class="pgu-client-cycle-sla-box">
                        <div class="pgu-client-cycle-sla-main">
                            Prazo padrão: <span x-text="clienteCicloSlaResumo().slaAceiteSgc"></span>
                        </div>
                        <div class="pgu-client-cycle-sla-desc">
                            Da data de aceite da proposta até a postagem no SGC
                        </div>
                    </div>
                    <div class="pgu-client-cycle-stem-spacer"></div>
                </div>

                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--narrow">
                    <div class="pgu-client-cycle-milestone-head">
                        <div class="pgu-client-cycle-marker-title">Postagem no SGC</div>
                        <span class="pgu-client-cycle-dot">
                            <i data-lucide="upload" class="h-6 w-6"></i>
                        </span>
                        <div class="pgu-client-cycle-marker-date">Marco operacional</div>
                    </div>
                    <div class="pgu-client-cycle-stem"></div>
                    <span class="pgu-client-cycle-node-inline pgu-client-cycle-node-inline--accent" aria-hidden="true"></span>
                </div>

                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--sla">
                    <div class="pgu-client-cycle-sla-box">
                        <div class="pgu-client-cycle-sla-main">
                            Prazo médio: <span x-text="clienteCicloSlaResumo().slaSgcLiberacao"></span>
                        </div>
                        <div class="pgu-client-cycle-sla-desc">
                            Da postagem no SGC até a avaliação para liberação
                        </div>
                    </div>
                    <div class="pgu-client-cycle-stem-spacer"></div>
                </div>

                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--narrow">
                    <div class="pgu-client-cycle-milestone-head">
                        <div class="pgu-client-cycle-marker-title">Liberação</div>
                        <span class="pgu-client-cycle-dot">
                            <i data-lucide="shield-check" class="h-6 w-6"></i>
                        </span>
                        <div class="pgu-client-cycle-marker-date">Início das atividades</div>
                    </div>
                    <div class="pgu-client-cycle-stem"></div>
                    <span class="pgu-client-cycle-node-inline" aria-hidden="true"></span>
                </div>

                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--deadline pgu-client-cycle-milestone--deadline">
                    <div class="pgu-client-cycle-milestone-head">
                        <div class="pgu-client-cycle-marker-title">Data limite</div>
                        <span class="pgu-client-cycle-dot">
                            <i data-lucide="calendar-check-2" class="h-6 w-6"></i>
                        </span>
                    </div>
                    <div class="pgu-client-cycle-stem"></div>
                    <span class="pgu-client-cycle-node-inline pgu-client-cycle-node-inline--deadline" aria-hidden="true"></span>
                </div>
            </div>

            <div class="pgu-client-cycle-hline-wrap">
                <div class="pgu-client-cycle-hline-rail">
                    <div class="pgu-client-cycle-hline-solid">
                        <div class="pgu-client-cycle-hline-progress-fill" :style="clienteCicloSlaResumo().progressoAceiteSgcStyle"></div>
                    </div>
                    <div class="pgu-client-cycle-hline-dash"></div>
                </div>
                <div class="pgu-client-cycle-today" :style="clienteCicloSlaResumo().hojeStyle">
                    <div class="pgu-client-cycle-today-dot"></div>
                    <div class="pgu-client-cycle-today-label">Hoje</div>
                    <div class="pgu-client-cycle-today-date" x-text="clienteCicloSlaResumo().hojeLabel"></div>
                </div>
            </div>

            <div class="pgu-client-cycle-track-under">
                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--narrow"></div>
                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--sla"></div>
                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--narrow"></div>
                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--sla"></div>
                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--narrow"></div>
                <div class="pgu-client-cycle-track-col pgu-client-cycle-track-col--deadline">
                    <div class="pgu-client-cycle-marker-date pgu-client-cycle-marker-date--below-line" x-text="'Data limite ' + clienteCicloSlaResumo().dataLimiteLabel"></div>
                </div>
            </div>
        </div>

        <div class="pgu-client-cycle-info-strip">
            <i data-lucide="info" class="h-5 w-5"></i>
            <span>
                O acompanhamento do ciclo considera o prazo operacional de
                <strong x-text="clienteCicloSlaReferencia().diasAceiteAteSgc"></strong> dias até a postagem no SGC, seguido de
                <strong><span x-text="clienteCicloSlaReferencia().diasSgcAteLiberacaoMin"></span> a <span x-text="clienteCicloSlaReferencia().diasSgcAteLiberacaoMax"></span> dias</strong>
                para avaliação e liberação.
            </span>
        </div>
    </div>

    <div class="pgu-client-cycle-bottom-grid">
        <div class="pgu-client-cycle-panel">
            <h3 class="pgu-client-cycle-section-title">Composição dos Prazos</h3>

            <div class="pgu-client-cycle-sla-row">
                <div class="pgu-client-cycle-sla-row-label">
                    <i data-lucide="file-check-2" class="h-5 w-5 text-brand-burgundy"></i>
                    <span>Processo Omega Finalizado</span>
                </div>
                <div class="pgu-client-cycle-sla-row-value" x-text="clienteCicloSlaResumo().slaAceiteSgc"></div>
            </div>

            <div class="pgu-client-cycle-sla-row">
                <div class="pgu-client-cycle-sla-row-label">
                    <i data-lucide="upload" class="h-5 w-5 text-brand-burgundy"></i>
                    <span>SGC → Liberação</span>
                </div>
                <div class="pgu-client-cycle-sla-row-value" x-text="clienteCicloSlaResumo().slaSgcLiberacao"></div>
            </div>

            <div class="pgu-client-cycle-sla-row">
                <div class="pgu-client-cycle-sla-row-label">
                    <i data-lucide="calendar-range" class="h-5 w-5 text-brand-burgundy"></i>
                    <span>Janela total estimada</span>
                </div>
                <div class="pgu-client-cycle-sla-row-value" x-text="clienteCicloSlaResumo().janelaTotalSla"></div>
            </div>
        </div>

        <div class="pgu-client-cycle-panel">
            <h3 class="pgu-client-cycle-section-title">Leitura do ciclo</h3>

            <div class="pgu-client-cycle-stepper">
                <div class="pgu-client-cycle-step">
                    <span class="pgu-client-cycle-step-number">1</span>
                    <div class="pgu-client-cycle-step-title">Etapa 1</div>
                    <div class="pgu-client-cycle-step-desc">Proposta aceita</div>
                </div>

                <div class="pgu-client-cycle-step">
                    <span class="pgu-client-cycle-step-number">2</span>
                    <div class="pgu-client-cycle-step-title">Etapa 2</div>
                    <div class="pgu-client-cycle-step-desc">Postagem no SGC</div>
                </div>

                <div class="pgu-client-cycle-step">
                    <span class="pgu-client-cycle-step-number">3</span>
                    <div class="pgu-client-cycle-step-title">Etapa 3</div>
                    <div class="pgu-client-cycle-step-desc">Avaliação e liberação</div>
                </div>
            </div>

            <div class="pgu-client-cycle-info-strip" style="margin-top: 16px;">
                <i data-lucide="trending-up" class="h-5 w-5"></i>
                <span>
                    A mobilização evolui conforme o avanço das vagas pelas etapas dos prazos operacionais.
                </span>
            </div>
        </div>

        <div class="pgu-client-cycle-panel">
            <h3 class="pgu-client-cycle-section-title">Situação do ciclo</h3>

            <div
                class="pgu-client-cycle-status-box"
                :class="{
                    'is-tone-success': clienteCicloSlaResumo().statusTone === 'success',
                    'is-tone-warning': clienteCicloSlaResumo().statusTone === 'warning',
                    'is-tone-danger': clienteCicloSlaResumo().statusTone === 'danger',
                    'is-tone-neutral': clienteCicloSlaResumo().statusTone === 'neutral',
                }"
            >
                <span
                    class="pgu-client-cycle-status-icon"
                    :class="{
                        'is-tone-success': clienteCicloSlaResumo().statusTone === 'success',
                        'is-tone-warning': clienteCicloSlaResumo().statusTone === 'warning',
                        'is-tone-danger': clienteCicloSlaResumo().statusTone === 'danger',
                        'is-tone-neutral': clienteCicloSlaResumo().statusTone === 'neutral',
                    }"
                >
                    <i data-lucide="check" class="h-8 w-8"></i>
                </span>

                <div>
                    <h4 class="pgu-client-cycle-status-title" x-text="clienteCicloSlaResumo().statusLabel"></h4>
                    <p class="pgu-client-cycle-status-text" x-text="clienteCicloSlaResumo().statusText"></p>
                </div>
            </div>

            <ul class="pgu-client-cycle-check-list">
                <li>
                    <span class="check"><i data-lucide="check" class="h-4 w-4"></i></span>
                    <span>
                        <span x-text="clienteCicloSlaResumo().slaAceiteSgc"></span> para postagem no SGC
                    </span>
                </li>
                <li>
                    <span class="check"><i data-lucide="check" class="h-4 w-4"></i></span>
                    <span>
                        <span x-text="clienteCicloSlaResumo().slaSgcLiberacao"></span> para avaliação de liberação
                    </span>
                </li>
                <li>
                    <span class="check"><i data-lucide="check" class="h-4 w-4"></i></span>
                    <span>
                        Monitoramento contínuo até
                        <strong x-text="clienteCicloSlaResumo().dataLimiteLabel"></strong>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <div class="pgu-client-cycle-footer">
        <div class="pgu-client-cycle-footer-message">
            <span class="pgu-client-cycle-footer-icon">
                <i data-lucide="target" class="h-7 w-7"></i>
            </span>
            <span>
                Nosso compromisso é conduzir a mobilização com previsibilidade, respeitando os prazos operacionais
                e a conclusão do ciclo até
                <strong x-text="clienteCicloSlaResumo().dataLimiteLabel"></strong>.
            </span>
        </div>
    </div>
</section>
