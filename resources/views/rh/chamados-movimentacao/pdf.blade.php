<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $chamado->protocolo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f4f4f5; font-size: 10px; }
        .meta { color: #555; font-size: 10px; margin-bottom: 12px; }
        .ok { color: #166534; }
        .pend { color: #b45309; }
    </style>
</head>
<body>
    <h1>Chamado {{ $chamado->protocolo }}</h1>
    <p class="meta">
        {{ $chamado->tipoLabel() }} · {{ $chamado->statusLabel() }} ·
        Gerado em {{ $geradoEm->format('d/m/Y H:i') }}
    </p>

    <h2>Colaborador</h2>
    <table>
        <tr><th>Nome</th><td>{{ $chamado->colaborador->nome }}</td><th>Matrícula</th><td>{{ $chamado->colaborador->matricula ?? '—' }}</td></tr>
        <tr><th>Função</th><td>{{ $dados['colaborador_funcao'] ?? $chamado->colaborador->cargo ?? '—' }}</td><th>Contrato</th><td>{{ $dados['colaborador_contrato'] ?? '—' }}</td></tr>
        @if ($chamado->tipo === 'desligamento')
            <tr><th>Tipo rescisão</th><td>{{ $tiposRescisao[$dados['tipo_rescisao'] ?? ''] ?? ($dados['tipo_rescisao'] ?? '—') }}</td><th>Último dia</th><td>{{ isset($dados['ultimo_dia_trabalhado']) ? \Carbon\Carbon::parse($dados['ultimo_dia_trabalhado'])->format('d/m/Y') : '—' }}</td></tr>
            <tr><th>Gestor</th><td colspan="3">{{ $dados['gestor_responsavel'] ?? '—' }} · Motivo: {{ $dados['motivo_texto'] ?? $chamado->motivo ?? '—' }}</td></tr>
        @endif
    </table>

    @if (! empty($dados['sigo']))
        <h2>SIGO</h2>
        <table>
            <tr><th>Cadastrado</th><td>{{ ! empty($dados['sigo']['cadastrado']) ? 'Sim' : 'Não' }}</td><th>Data</th><td>{{ $dados['sigo']['data_cadastro'] ?? '—' }}</td></tr>
            <tr><th>Responsável</th><td>{{ $dados['sigo']['responsavel_cadastro'] ?? '—' }}</td><th>Protocolo</th><td>{{ $dados['sigo']['protocolo_sigo'] ?? '—' }}</td></tr>
        </table>
    @endif

    <h2>Etapas do fluxo</h2>
    <table>
        <thead><tr><th>#</th><th>Etapa</th><th>Status</th><th>Concluída em</th></tr></thead>
        <tbody>
            @foreach ($chamado->etapas as $etapa)
                <tr>
                    <td>{{ $etapa->ordem }}</td>
                    <td>{{ $etapa->nome }}</td>
                    <td class="{{ $etapa->isConcluida() ? 'ok' : 'pend' }}">{{ $etapa->status }}</td>
                    <td>{{ $etapa->concluido_em?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($chamado->nadaConsta)
        <h2>Nada Consta Demissional — {{ $chamado->nadaConsta->statusLabel() }}</h2>
        <p class="meta">Validado RH: {{ $chamado->nadaConsta->validado_rh ? 'Sim ('.$chamado->nadaConsta->validado_rh_em?->format('d/m/Y H:i').')' : 'Não' }}</p>
        @foreach ($areasCatalogo as $area => $defItens)
            <p><strong>{{ $labelsAreas[$area] ?? $area }}</strong></p>
            <table>
                <thead><tr><th>Item</th><th>Débito</th><th>Tratativa</th><th>Pendência</th></tr></thead>
                <tbody>
                    @foreach ($chamado->nadaConsta->itens->where('area', $area) as $item)
                        @php $nome = collect($defItens)->firstWhere('slug', $item->item)['nome'] ?? $item->item; @endphp
                        <tr>
                            <td>{{ $nome }}</td>
                            <td>{{ $item->tem_debito === null ? '—' : ($item->tem_debito ? 'Sim' : 'Não') }}</td>
                            <td>{{ $item->statusTratativaLabel() }}</td>
                            <td>{{ $item->descricao_pendencia ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <h2>Anexos</h2>
    <table>
        <thead><tr><th>Tipo</th><th>Arquivo</th></tr></thead>
        <tbody>
            @forelse ($chamado->anexos as $anexo)
                <tr>
                    <td>{{ $labelsAnexos[$anexo->tipo_documento] ?? $anexo->tipo_documento }}</td>
                    <td>{{ $anexo->nome_arquivo }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Nenhum anexo.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($chamado->logs->isNotEmpty())
        <h2>Histórico (últimos registros)</h2>
        <table>
            <thead><tr><th>Data</th><th>Ação</th><th>Usuário</th></tr></thead>
            <tbody>
                @foreach ($chamado->logs->take(30) as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->acao }}</td>
                        <td>{{ $log->usuario->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
