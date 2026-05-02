<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório RDO</title>
    <style>
        @page { margin: 22px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "DejaVu Sans", Arial, sans-serif; color: #171717; font-size: 11px; }
        .header { border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; background: #f8fafc; }
        .brand { width: 160px; max-height: 58px; object-fit: contain; }
        .eyebrow { color: #7a1234; font-size: 9px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 6px 0 0; font-size: 23px; color: #3b0014; }
        .meta { color: #64748b; margin-top: 4px; }
        .kpis { width: 100%; border-spacing: 8px; margin-top: 10px; }
        .kpi { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; background: #ffffff; }
        .kpi-label { color: #64748b; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .kpi-value { margin-top: 4px; font-size: 22px; font-weight: 900; color: #7a1234; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .data th { background: #7a1234; color: #fff; padding: 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        .data td { border-bottom: 1px solid #e5e7eb; padding: 7px; vertical-align: top; }
        .muted { color: #64748b; }
        .pill { display: inline-block; border-radius: 999px; background: #f3f4f6; color: #374151; padding: 4px 8px; font-weight: 800; font-size: 9px; }
        .footer { position: fixed; bottom: -8px; left: 0; right: 0; color: #94a3b8; font-size: 9px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table style="margin:0; width:100%; border-collapse:collapse;">
            <tr>
                <td style="border:0; padding:0; width:190px;">
                    @if ($logo)
                        <img src="{{ $logo }}" class="brand" alt="Omega Service">
                    @endif
                </td>
                <td style="border:0; padding:0;">
                    <div class="eyebrow">Relatório executivo</div>
                    <h1>Consolidado de RDO</h1>
                    <div class="meta">Período: {{ $periodo }} · Gerado em {{ $geradoEm->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="kpis">
        <tr>
            <td class="kpi">
                <div class="kpi-label">RDOs encontrados</div>
                <div class="kpi-value">{{ $relatorios->count() }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">Com evidência</div>
                <div class="kpi-value">{{ $relatorios->whereNotNull('evidencia_path')->count() }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">Atividades registradas</div>
                <div class="kpi-value">{{ $relatorios->sum(fn ($rdo) => count($rdo->atividades ?? [])) }}</div>
            </td>
            <td class="kpi">
                <div class="kpi-label">Pessoas envolvidas</div>
                <div class="kpi-value">{{ $relatorios->sum(fn ($rdo) => count($rdo->equipe ?? [])) }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:68px;">Data</th>
                <th>RDO</th>
                <th style="width:115px;">Contrato</th>
                <th style="width:140px;">Frente / área</th>
                <th style="width:160px;">Responsáveis</th>
                <th style="width:70px;">Equipe</th>
                <th style="width:80px;">Atividades</th>
                <th style="width:70px;">Evidência</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($relatorios as $relatorio)
                <tr>
                    <td>{{ $relatorio->data?->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $relatorio->titulo ?: 'Relatório diário de obra' }}</strong>
                        <div class="muted">{{ $relatorio->disciplina ?: '-' }}</div>
                    </td>
                    <td>{{ $relatorio->contrato ?: '-' }}</td>
                    <td>
                        <strong>{{ $relatorio->frente ?: '-' }}</strong>
                        <div class="muted">{{ $relatorio->area ?: '-' }}</div>
                    </td>
                    <td>
                        <strong>{{ $relatorio->supervisor_nome ?: '-' }}</strong>
                        <div class="muted">{{ $relatorio->encarregado_nome ?: '-' }}</div>
                    </td>
                    <td><span class="pill">{{ count($relatorio->equipe ?? []) }}</span></td>
                    <td><span class="pill">{{ count($relatorio->atividades ?? []) }}</span></td>
                    <td>{{ $relatorio->evidencia_path ? 'Sim' : 'Não' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum RDO encontrado para os filtros informados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Omega286 · Relatório gerado automaticamente</div>
</body>
</html>
