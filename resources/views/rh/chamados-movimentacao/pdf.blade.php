<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $chamado->protocolo }}</title>
    @include('rh.chamados-movimentacao._pdf-arial-fonts')
    @include('rh.chamados-movimentacao._pdf-estilos-omega')
</head>
<body>
    @include('rh.chamados-movimentacao._pdf-cabecalho', [
        'pdfTitulo' => 'Resumo do Chamado de Movimentação',
        'pdfCodigo' => $chamado->protocolo,
        'pdfRev' => \App\Support\Rh\MovimentacaoDesligamentoCatalog::CHAMADO_RESUMO_DOC_REV,
        'pdfData' => $geradoEm,
        'logoBase64' => $logoBase64,
    ])

    <table class="dados-func">
        <tr>
            <td class="lbl">Tipo:</td>
            <td>{{ $chamado->tipoLabel() }}</td>
            <td class="lbl">Status:</td>
            <td>{{ $chamado->statusLabel() }}</td>
        </tr>
        <tr>
            <td class="lbl">Solicitante:</td>
            <td>{{ $chamado->solicitante->name ?? '—' }}</td>
            <td class="lbl">Gerado em:</td>
            <td>{{ $geradoEm->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="tbl-nc">
        <tr>
            <td colspan="4" class="secao-titulo">Colaborador</td>
        </tr>
        <tr>
            <td class="lbl" style="width: 18%;">Nome:</td>
            <td style="width: 32%;">{{ $chamado->colaborador->nome }}</td>
            <td class="lbl" style="width: 18%;">Matrícula:</td>
            <td>{{ $chamado->colaborador->matricula ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Função:</td>
            <td>{{ $dados['colaborador_funcao'] ?? $chamado->colaborador->cargo ?? '—' }}</td>
            <td class="lbl">Setor de Trabalho:</td>
            <td>{{ $setorTrabalho }}</td>
        </tr>
        @if ($chamado->tipo === 'desligamento')
            <tr>
                <td class="lbl">Tipo rescisão:</td>
                <td>{{ $tiposRescisao[$dados['tipo_rescisao'] ?? ''] ?? ($dados['tipo_rescisao'] ?? '—') }}</td>
                <td class="lbl">Último dia:</td>
                <td>{{ isset($dados['ultimo_dia_trabalhado']) ? \Carbon\Carbon::parse($dados['ultimo_dia_trabalhado'])->format('d/m/Y') : '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Gestor:</td>
                <td colspan="3">{{ $dados['gestor_responsavel'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Motivo:</td>
                <td colspan="3">{{ $dados['motivo_texto'] ?? $chamado->motivo ?? '—' }}</td>
            </tr>
        @else
            <tr>
                <td class="lbl">Contrato:</td>
                <td colspan="3">{{ $dados['colaborador_contrato'] ?? '—' }}</td>
            </tr>
        @endif
    </table>

    @if (! empty($dados['sigo']))
        <table class="tbl-nc">
            <tr>
                <td colspan="4" class="secao-titulo">SIGO</td>
            </tr>
            <tr>
                <td class="lbl" style="width: 22%;">Cadastrado:</td>
                <td>{{ ! empty($dados['sigo']['cadastrado']) ? 'Sim' : 'Não' }}</td>
                <td class="lbl" style="width: 22%;">Data:</td>
                <td>{{ $dados['sigo']['data_cadastro'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Responsável:</td>
                <td>{{ $dados['sigo']['responsavel_cadastro'] ?? '—' }}</td>
                <td class="lbl">Protocolo:</td>
                <td>{{ $dados['sigo']['protocolo_sigo'] ?? '—' }}</td>
            </tr>
        </table>
    @endif

    <table class="tbl-nc">
        <tr>
            <td colspan="4" class="secao-titulo">Etapas do fluxo</td>
        </tr>
        <tr>
            <th class="th-principal" style="width: 8%;">#</th>
            <th class="th-principal" style="width: 42%;">Etapa</th>
            <th class="th-principal" style="width: 22%;">Status</th>
            <th class="th-principal" style="width: 28%;">Concluída em</th>
        </tr>
        @foreach ($chamado->etapas as $etapa)
            <tr class="linha-dado{{ $loop->even ? '-alt' : '' }}">
                <td class="text-center">{{ $etapa->ordem }}</td>
                <td>{{ $etapa->nome }}</td>
                <td class="{{ $etapa->isConcluida() ? 'ok' : 'pend' }}">{{ $etapa->status }}</td>
                <td>{{ $etapa->concluido_em?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        @endforeach
    </table>

    @if ($chamado->nadaConsta)
        <p class="meta-rodape">
            Nada Consta Demissional — {{ $chamado->nadaConsta->statusLabel() }}
            · Validado RH: {{ $chamado->nadaConsta->validado_rh ? 'Sim ('.$chamado->nadaConsta->validado_rh_em?->format('d/m/Y H:i').')' : 'Não' }}
        </p>
        @foreach ($areasCatalogo as $area => $defItens)
            @php
                $itensArea = $chamado->nadaConsta->itens->where('area', $area);
            @endphp
            @if ($itensArea->isNotEmpty())
                <table class="tbl-nc">
                    <tr>
                        <td colspan="4" class="secao-titulo">{{ strtoupper($labelsAreas[$area] ?? $area) }}</td>
                    </tr>
                    <tr>
                        <th class="th-principal" style="width: 36%;">Item</th>
                        <th class="th-principal" style="width: 14%;">Débito</th>
                        <th class="th-principal" style="width: 22%;">Tratativa</th>
                        <th class="th-principal" style="width: 28%;">Pendência</th>
                    </tr>
                    @foreach ($itensArea as $item)
                        @php $nome = collect($defItens)->firstWhere('slug', $item->item)['nome'] ?? $item->item; @endphp
                        <tr class="linha-dado{{ $loop->even ? '-alt' : '' }}">
                            <td>{{ $nome }}</td>
                            <td class="text-center">{{ $item->tem_debito === null ? '—' : ($item->tem_debito ? 'Sim' : 'Não') }}</td>
                            <td>{{ $item->statusTratativaLabel() }}</td>
                            <td>{{ $item->descricao_pendencia ?? '—' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        @endforeach
    @endif

    <table class="tbl-nc">
        <tr>
            <td colspan="2" class="secao-titulo">Anexos</td>
        </tr>
        <tr>
            <th class="th-principal" style="width: 38%;">Tipo</th>
            <th class="th-principal">Arquivo</th>
        </tr>
        @forelse ($chamado->anexos as $anexo)
            <tr class="linha-dado{{ $loop->even ? '-alt' : '' }}">
                <td>{{ $labelsAnexos[$anexo->tipo_documento] ?? $anexo->tipo_documento }}</td>
                <td>{{ $anexo->nome_arquivo }}</td>
            </tr>
        @empty
            <tr class="linha-dado">
                <td colspan="2" class="text-center">Nenhum anexo.</td>
            </tr>
        @endforelse
    </table>

    @if ($chamado->logs->isNotEmpty())
        <table class="tbl-nc">
            <tr>
                <td colspan="3" class="secao-titulo">Histórico (últimos registros)</td>
            </tr>
            <tr>
                <th class="th-principal" style="width: 22%;">Data</th>
                <th class="th-principal" style="width: 38%;">Ação</th>
                <th class="th-principal">Usuário</th>
            </tr>
            @foreach ($chamado->logs->take(30) as $log)
                <tr class="linha-dado{{ $loop->even ? '-alt' : '' }}">
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->acao }}</td>
                    <td>{{ $log->usuario->name ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
