<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>RDO {{ $rdo->id }}</title>
    <style>
        @page { margin: 24px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "DejaVu Sans", Arial, sans-serif; color: #171717; background: #ffffff; font-size: 12px; }
        .header { border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px; background: #f8fafc; }
        .brand { width: 180px; max-height: 62px; object-fit: contain; }
        .eyebrow { color: #7a1234; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 8px 0 0; font-size: 25px; line-height: 1.15; color: #3b0014; }
        .meta { color: #64748b; margin-top: 6px; }
        .badge { display: inline-block; border-radius: 999px; padding: 6px 10px; background: #ecfdf5; color: #047857; font-weight: 800; font-size: 10px; }
        .grid { width: 100%; border-spacing: 10px; margin-top: 12px; }
        .card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; vertical-align: top; background: #ffffff; }
        .card-title { color: #7a1234; font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 8px; }
        .label { color: #64748b; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .value { margin-top: 3px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #7a1234; color: #fff; padding: 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
        .section { margin-top: 12px; }
        .text-block { white-space: pre-line; line-height: 1.55; color: #334155; }
        .evidence { width: 100%; max-height: 360px; object-fit: cover; border-radius: 12px; border: 1px solid #e5e7eb; margin-top: 8px; }
        .footer { position: fixed; bottom: -8px; left: 0; right: 0; color: #94a3b8; font-size: 9px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table style="margin:0; border-collapse:collapse;">
            <tr>
                <td style="border:0; padding:0; width:210px;">
                    @if ($logo)
                        <img src="{{ $logo }}" class="brand" alt="Omega Service">
                    @endif
                </td>
                <td style="border:0; padding:0;">
                    <div class="eyebrow">Relatório diário de obra</div>
                    <h1>{{ $rdo->titulo ?: 'RDO' }}</h1>
                    <div class="meta">{{ $rdo->data?->format('d/m/Y') }} · {{ $rdo->frente ?: 'Frente não informada' }} · {{ $rdo->area ?: 'Área não informada' }}</div>
                </td>
                <td style="border:0; padding:0; text-align:right; width:120px;">
                    <span class="badge">Transmitido</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="grid">
        <tr>
            <td class="card">
                <div class="card-title">Contrato</div>
                <div class="label">Contrato</div>
                <div class="value">{{ $rdo->contrato ?: '-' }}</div>
                <br>
                <div class="label">Disciplina</div>
                <div class="value">{{ $rdo->disciplina ?: '-' }}</div>
            </td>
            <td class="card">
                <div class="card-title">Responsáveis</div>
                <div class="label">Supervisor</div>
                <div class="value">{{ $rdo->supervisor_nome ?: '-' }} {{ $rdo->supervisor_matricula ? '· '.$rdo->supervisor_matricula : '' }}</div>
                <br>
                <div class="label">Encarregado</div>
                <div class="value">{{ $rdo->encarregado_nome ?: '-' }} {{ $rdo->encarregado_matricula ? '· '.$rdo->encarregado_matricula : '' }}</div>
            </td>
            <td class="card">
                <div class="card-title">Condição</div>
                <div class="label">Clima</div>
                <div class="value">{{ $rdo->condicao_climatica ?: '-' }}</div>
                <br>
                <div class="label">Transmitido em</div>
                <div class="value">{{ $rdo->transmitido_em?->format('d/m/Y H:i') ?: '-' }}</div>
            </td>
        </tr>
    </table>

    @if ($evidencia)
        <div class="section card">
            <div class="card-title">Evidência fotográfica</div>
            <img src="{{ $evidencia }}" class="evidence" alt="Evidência do RDO">
        </div>
    @endif

    <div class="section card">
        <div class="card-title">Linha do tempo das atividades</div>
        <table>
            <thead>
                <tr>
                    <th style="width:90px;">Início</th>
                    <th style="width:90px;">Fim</th>
                    <th>Atividade executada</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rdo->atividades ?? [] as $atividade)
                    <tr>
                        <td>{{ $atividade['inicio'] ?? '--' }}</td>
                        <td>{{ $atividade['fim'] ?? '--' }}</td>
                        <td>{{ $atividade['descricao'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Nenhuma atividade registrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section card">
        <div class="card-title">Efetivo envolvido</div>
        <table>
            <thead>
                <tr>
                    <th style="width:110px;">Matrícula</th>
                    <th>Nome</th>
                    <th style="width:180px;">Função</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rdo->equipe ?? [] as $pessoa)
                    <tr>
                        <td>{{ $pessoa['matricula'] ?? '-' }}</td>
                        <td>{{ $pessoa['nome'] ?? '-' }}</td>
                        <td>{{ $pessoa['funcao'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Nenhuma pessoa registrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <table class="grid">
        <tr>
            <td class="card">
                <div class="card-title">Observações</div>
                <div class="text-block">{{ $rdo->observacoes ?: '-' }}</div>
            </td>
            <td class="card">
                <div class="card-title">Ocorrências</div>
                <div class="text-block">{{ $rdo->ocorrencias ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">Gerado em {{ $geradoEm->format('d/m/Y H:i') }} pelo Omega286</div>
</body>
</html>
