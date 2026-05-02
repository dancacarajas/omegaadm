<html>
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 12px; }
        th { background: #7a1234; color: #ffffff; font-weight: 700; text-align: left; }
        th, td { border: 1px solid #d7dce2; padding: 8px; vertical-align: top; }
        .title { font-size: 18px; font-weight: 700; color: #7a1234; margin-bottom: 8px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <div class="title">Relatório de RDO - Omega Service</div>
    <p class="muted">Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Título</th>
                <th>Contrato</th>
                <th>Frente</th>
                <th>Área</th>
                <th>Disciplina</th>
                <th>Supervisor</th>
                <th>Encarregado</th>
                <th>Condição climática</th>
                <th>Atividades</th>
                <th>Equipe</th>
                <th>Observações</th>
                <th>Ocorrências</th>
                <th>Evidência</th>
                <th>Transmitido em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($relatorios as $relatorio)
                <tr>
                    <td>{{ $relatorio->id }}</td>
                    <td>{{ $relatorio->data?->format('d/m/Y') }}</td>
                    <td>{{ $relatorio->titulo ?: 'Relatório diário de obra' }}</td>
                    <td>{{ $relatorio->contrato }}</td>
                    <td>{{ $relatorio->frente }}</td>
                    <td>{{ $relatorio->area }}</td>
                    <td>{{ $relatorio->disciplina }}</td>
                    <td>{{ $relatorio->supervisor_nome }} {{ $relatorio->supervisor_matricula ? '('.$relatorio->supervisor_matricula.')' : '' }}</td>
                    <td>{{ $relatorio->encarregado_nome }} {{ $relatorio->encarregado_matricula ? '('.$relatorio->encarregado_matricula.')' : '' }}</td>
                    <td>{{ $relatorio->condicao_climatica }}</td>
                    <td>
                        @foreach ($relatorio->atividades ?? [] as $atividade)
                            {{ $atividade['inicio'] ?? '--' }} até {{ $atividade['fim'] ?? '--' }} - {{ $atividade['descricao'] ?? '-' }}<br>
                        @endforeach
                    </td>
                    <td>
                        @foreach ($relatorio->equipe ?? [] as $pessoa)
                            {{ $pessoa['matricula'] ?? '-' }} - {{ $pessoa['nome'] ?? '-' }} - {{ $pessoa['funcao'] ?? '-' }}<br>
                        @endforeach
                    </td>
                    <td>{{ $relatorio->observacoes }}</td>
                    <td>{{ $relatorio->ocorrencias }}</td>
                    <td>{{ $relatorio->evidencia_path ? asset('storage/'.$relatorio->evidencia_path) : '-' }}</td>
                    <td>{{ $relatorio->transmitido_em?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16">Nenhum RDO encontrado para os filtros informados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
