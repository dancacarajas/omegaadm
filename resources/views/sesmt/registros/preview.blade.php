<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prévia — Registro mensal SSMA {{ $registro->competencia?->format('m/Y') }}</title>
    <style>
        :root { --burgundy: #6f1731; --ink: #111; --muted: #52525b; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; padding: 2rem; color: var(--ink); background: #fafafa; line-height: 1.5; }
        .sheet { max-width: 900px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { margin: 0 0 0.25rem; font-size: 1.5rem; color: var(--burgundy); }
        .meta { color: var(--muted); font-size: 0.875rem; margin-bottom: 1.5rem; }
        h2 { font-size: 1rem; margin: 1.5rem 0 0.5rem; border-bottom: 2px solid var(--burgundy); padding-bottom: 0.25rem; }
        ul { margin: 0.5rem 0 0; padding-left: 1.25rem; }
        li { margin-bottom: 0.35rem; font-size: 0.9rem; }
        .box { background: #f4f4f5; padding: 1rem; border-radius: 8px; margin-top: 1rem; white-space: pre-wrap; font-size: 0.9rem; }
        .actions { margin-top: 2rem; text-align: center; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <h1>Registro mensal SSMA — prévia</h1>
        <p class="meta">
            Competência: <strong>{{ $registro->competencia?->format('m/Y') }}</strong>
            @if (filled($registro->contrato)) · Contrato: {{ $registro->contrato }} @endif
            @if (filled($registro->local_base)) · Local: {{ $registro->local_base }} @endif
            · Status: <strong>{{ $registro->rotuloStatus() }}</strong>
        </p>

        @if (filled($registro->comentario_executivo))
            <h2>Comentário executivo</h2>
            <div class="box">{{ $registro->comentario_executivo }}</div>
        @endif

        <h2>Resumo das etapas</h2>
        <ul>
            @foreach ($resumoLinhas as $linha)
                <li>{{ $linha }}</li>
            @endforeach
        </ul>

        @if (filled($registro->observacoes_gerais_mes))
            <h2>Observações gerais do mês</h2>
            <div class="box">{{ $registro->observacoes_gerais_mes }}</div>
        @endif

        <p class="meta" style="margin-top: 2rem;">Gerado em {{ now()->format('d/m/Y H:i') }} — Omega286</p>

        <div class="actions no-print">
            <button type="button" onclick="window.print()" style="padding: 0.6rem 1.25rem; background: var(--burgundy); color: #fff; border: 0; border-radius: 8px; font-weight: 600; cursor: pointer;">Imprimir / PDF</button>
        </div>
    </div>
</body>
</html>
