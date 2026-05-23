<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Nada Consta Demissional — {{ $colaborador->nome }}</title>
    @include('rh.chamados-movimentacao._pdf-arial-fonts')
    <style>
        @page { margin: 16px 20px 14px 20px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 7px;
            color: #000;
            line-height: 1.2;
        }
        table { border-collapse: collapse; width: 100%; }
        .hdr-box {
            margin-bottom: 4px;
            border: 1px solid #000;
        }
        .hdr-box td {
            border: 1px solid #000;
            vertical-align: middle;
        }
        .hdr-logo {
            width: 20%;
            padding: 6px 8px;
            text-align: center;
        }
        .hdr-logo img { height: 34px; width: auto; max-width: 120px; }
        .hdr-titulo {
            width: 52%;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 4px;
            letter-spacing: 0.02em;
        }
        .hdr-rg {
            text-align: center;
            font-size: 7px;
            font-weight: 700;
            padding: 4px 3px;
        }
        .hdr-rev,
        .hdr-data {
            text-align: center;
            font-size: 6.5px;
            font-weight: 700;
            padding: 3px 2px;
        }
        .dados-func {
            margin: 0 0 4px;
            border: 1px solid #000;
            font-size: 7.5px;
        }
        .dados-func td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
        }
        .dados-func .lbl { font-weight: 700; white-space: nowrap; }
        .tbl-nc { margin-top: 0; table-layout: fixed; }
        .tbl-nc + .tbl-nc { margin-top: 0; }
        .tbl-itens { margin-top: 4px; margin-bottom: 0; }
        .tbl-nc td,
        .tbl-nc th {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }
        .secao-titulo {
            background: #6f1731;
            color: #fff;
            font-weight: 700;
            font-size: 8.5px;
            text-align: center;
            padding: 2px 4px;
            text-transform: uppercase;
        }
        .th-principal {
            background: #e8e8e8;
            font-size: 6.5px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            padding: 3px 2px;
        }
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
    <table class="hdr-box">
        <tr>
            <td rowspan="2" class="hdr-logo">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Omega Service">
                @else
                    <strong style="font-size: 10px; color: #6f1731;">OMEGA SERVICE</strong>
                @endif
            </td>
            <td rowspan="2" class="hdr-titulo">NADA CONSTA DEMISSIONAL</td>
            <td colspan="2" class="hdr-rg">{{ \App\Support\Rh\MovimentacaoDesligamentoCatalog::NADA_CONSTA_DOC_CODIGO }}</td>
        </tr>
        <tr>
            <td class="hdr-rev">REV {{ \App\Support\Rh\MovimentacaoDesligamentoCatalog::NADA_CONSTA_DOC_REV }}</td>
            <td class="hdr-data">DATA:{{ $dataEmissao->format('d/m/Y') }}</td>
        </tr>
    </table>

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
