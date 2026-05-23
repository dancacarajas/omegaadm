<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Nada Consta Demissional — {{ $colaborador->nome }}</title>
    @include('rh.chamados-movimentacao._pdf-arial-fonts')
    @include('rh.chamados-movimentacao._pdf-estilos-omega')
    <style>
        body { font-size: 7px; line-height: 1.2; }
        .hdr-titulo { font-size: 12px; letter-spacing: 0.02em; text-transform: none; }
        .dados-func { margin: 0 0 4px; }
        .tbl-nc { margin-top: 0; table-layout: fixed; }
        .tbl-nc + .tbl-nc { margin-top: 0; }
        .tbl-itens { margin-top: 4px; margin-bottom: 0; }
        .tbl-nc td,
        .tbl-nc th {
            padding: 2px 4px;
            vertical-align: middle;
            font-size: 6.5px;
        }
        .th-principal { font-size: 6.5px; padding: 3px 2px; }
        .col-item { width: 38%; text-align: left; }
        .col-sim { width: 11%; text-align: center; }
        .col-nao { width: 11%; text-align: center; }
        .col-qual { width: 14%; text-align: left; vertical-align: top; }
        .col-ass { width: 26%; }
        .linha-dado td {
            background: #e8e8e8;
            font-size: 6.5px;
            padding: 2px 3px;
        }
        .linha-dado .nome-item {
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
            padding: 3px 4px;
        }
        .linha-dado .chk {
            text-align: center;
            font-size: 6.5px;
            white-space: nowrap;
        }
        .linha-dado .qual-valor {
            font-size: 6.5px;
            vertical-align: top;
            text-align: left;
        }
        .linha-dado .qual-valor .qual-titulo {
            font-weight: 700;
            display: block;
            line-height: 1.1;
        }
        .linha-dado .qual-valor .qual-texto {
            display: block;
            margin-top: 2px;
            line-height: 1.2;
        }
        .linha-dado .ass-cell {
            background: #e8e8e8;
            min-height: 14px;
        }
        .tbl-obs {
            margin-top: 0;
            margin-bottom: 0;
        }
        .obs-conteudo {
            min-height: 38px;
            padding: 3px 5px 4px;
            vertical-align: top;
            font-size: 6.5px;
            background: #fff;
        }
        .obs-label {
            font-weight: 700;
            font-size: 7px;
            text-align: left;
            margin: 0 0 4px;
        }
        .obs-texto-usuario {
            font-size: 7px;
            text-align: left;
            margin: 0 0 6px;
            min-height: 14px;
        }
        .obs-notas-fixas {
            text-align: center;
            font-size: 6px;
            line-height: 1.45;
            margin: 0;
            padding: 0 8px;
        }
        .obs-notas-fixas p {
            margin: 0 0 3px;
        }
        .linha-local-data td {
            font-size: 7.5px;
            font-weight: 700;
            padding: 5px 6px;
            vertical-align: middle;
            background: #fff;
        }
        .linha-local-data .col-local { text-align: left; }
        .linha-local-data .col-data { text-align: right; }
        .rodape-assinaturas {
            margin-top: 12px;
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .rodape-assinaturas > tbody > tr > td {
            border: 0;
            padding: 0;
            vertical-align: bottom;
            height: 198px;
        }
        .assinaturas {
            width: 100%;
            border-collapse: collapse;
        }
        .assinaturas td {
            border: 0;
            text-align: center;
            vertical-align: bottom;
            font-size: 6.5px;
            font-weight: 700;
            padding: 0 10px;
        }
        .assinaturas .ass-rh-func {
            width: 50%;
            padding-top: 32px;
        }
        .assinaturas .ass-spacer td {
            height: 72px;
            padding: 0;
            border: 0;
            line-height: 0;
            font-size: 0;
        }
        .assinaturas .ass-gestor {
            padding-top: 6px;
            padding-bottom: 10px;
        }
        .assinaturas .linha-ass {
            border-top: 1px solid #000;
            height: 1px;
            margin: 0 14px 3px;
        }
        .assinaturas .linha-ass-gestor {
            border-top: 1px solid #000;
            height: 1px;
            width: 46%;
            margin: 0 auto 3px;
        }
        .assinaturas .gestor-nome {
            margin-top: 3px;
            font-weight: 400;
            font-size: 6px;
        }
    </style>
</head>
<body>
    @include('rh.chamados-movimentacao._pdf-cabecalho', [
        'pdfTitulo' => 'Nada Consta Demissional',
        'pdfCodigo' => \App\Support\Rh\MovimentacaoDesligamentoCatalog::NADA_CONSTA_DOC_CODIGO,
        'pdfRev' => \App\Support\Rh\MovimentacaoDesligamentoCatalog::NADA_CONSTA_DOC_REV,
        'pdfData' => $dataEmissao,
        'logoBase64' => $logoBase64,
    ])

    <table class="dados-func">
        <tr>
            <td class="lbl">Nome:</td>
            <td style="width: 42%;">{{ $colaborador->nome }}</td>
            <td class="lbl">MATRÍCULA:</td>
            <td>{{ $colaborador->matricula ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Função:</td>
            <td>{{ $dados['colaborador_funcao'] ?? $colaborador->cargo ?? '—' }}</td>
            <td class="lbl">Setor de Trabalho:</td>
            <td>{{ $setorTrabalho }}</td>
        </tr>
    </table>

    <table class="tbl-nc tbl-itens">
        <colgroup>
            <col class="col-item">
            <col class="col-sim">
            <col class="col-nao">
            <col class="col-qual">
            <col class="col-ass">
        </colgroup>
        @foreach ($itensPorArea as $area => $linhas)
            <tr>
                <td colspan="5" class="secao-titulo">{{ strtoupper($labelsAreas[$area] ?? $area) }}</td>
            </tr>
            <tr>
                <th class="th-principal col-item">ITENS</th>
                <th class="th-principal" colspan="3">TEM DÉBITO?</th>
                <th class="th-principal col-ass">ASSINATURA RESPONSÁVEL/CARIMBO</th>
            </tr>
            @foreach ($linhas as $linha)
                <tr class="linha-dado">
                    <td class="nome-item">{{ $linha['nome'] }}</td>
                    <td class="chk">SIM ( {{ $linha['sim_padded'] }} )</td>
                    <td class="chk">NÃO ( {{ $linha['nao_padded'] }} )</td>
                    <td class="qual-valor">
                        <span class="qual-titulo">Qual?</span>
                        @if (filled($linha['qual']))
                            <span class="qual-texto">{{ $linha['qual'] }}</span>
                        @endif
                    </td>
                    <td class="ass-cell">&nbsp;</td>
                </tr>
            @endforeach
        @endforeach
    </table>

    <table class="tbl-nc tbl-obs" style="margin-top: 0;">
        <colgroup>
            <col class="col-item">
            <col class="col-sim">
            <col class="col-nao">
            <col class="col-qual">
            <col class="col-ass">
        </colgroup>
        <tr>
            <td colspan="5" class="obs-conteudo">
                <div class="obs-label">Observações:</div>
                @if (filled($nada->observacao))
                    <div class="obs-texto-usuario">{{ $nada->observacao }}</div>
                @endif
                <div class="obs-notas-fixas">
                    <p>*Para itens com pendência, deverá ser enviado as evidências acompanhadas com seus respectivos termos de baixa ou autorização de desconto.</p>
                    <p>*Este documento deve ser emitido em duas cópias idênticas, uma destinada ao trabalhador e outra à empresa.</p>
                </div>
            </td>
        </tr>
        <tr class="linha-local-data">
            <td colspan="3" class="col-local">LOCAL: {{ $local }}</td>
            <td colspan="2" class="col-data">DATA: &nbsp;&nbsp;{{ $dataEmissao->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="rodape-assinaturas">
        <tr>
            <td>
                <table class="assinaturas">
                    <tr>
                        <td class="ass-rh-func">
                            <div class="linha-ass"></div>
                            ASSINATURA/CARIMBO RH
                        </td>
                        <td class="ass-rh-func">
                            <div class="linha-ass"></div>
                            ASSINATURA DO FUNCIONÁRIO (LEGÍVEL)
                        </td>
                    </tr>
                    <tr class="ass-spacer">
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="ass-gestor">
                            <div class="linha-ass-gestor"></div>
                            ASSINATURA/CARIMBO DO GESTOR DO CONTRATO
                            @if (filled($nada->gestor_contrato))
                                <div class="gestor-nome">{{ $nada->gestor_contrato }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
