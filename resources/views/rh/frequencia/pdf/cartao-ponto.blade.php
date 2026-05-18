<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Cartão de Ponto</title>
    <style>
        @page {
            size: letter portrait;
            margin: 28px 32px 32px 32px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 7px;
        }
        .cartao { page-break-after: always; }
        .cartao:last-child { page-break-after: auto; }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .hdr td { border: 0; padding: 0; vertical-align: top; }
        .hdr-logo-omega { width: 28%; }
        .hdr-logo-omega img { height: 42px; width: auto; max-width: 150px; }
        .hdr-titulo { width: 44%; text-align: center; padding-top: 4px; }
        .hdr-titulo h1 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #6b6b6b;
            letter-spacing: 0.01em;
        }
        .hdr-titulo .periodo {
            margin-top: 3px;
            font-size: 10px;
            font-weight: 700;
            color: #c41230;
        }
        .hdr-controlid { width: 28%; text-align: right; }
        .hdr-controlid img { height: 22px; width: auto; max-width: 130px; }
        .hdr-controlid .meta {
            margin-top: 4px;
            font-size: 6.5px;
            color: #555;
            line-height: 1.35;
        }
        .hdr-linha {
            border: none;
            border-top: 1px solid #b8b8b8;
            margin: 8px 0 10px;
            height: 0;
            padding: 0;
        }

        .bloco-info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .bloco-info > tbody > tr > td { border: 0; padding: 0; vertical-align: top; }
        .col-info { width: 36%; padding-right: 8px; font-size: 7px; line-height: 1.45; }
        .col-info-meio { width: 22%; padding-right: 8px; font-size: 7px; line-height: 1.45; }
        .col-horario { width: 42%; }
        .lbl { font-weight: 700; text-transform: uppercase; }
        .campo { margin-bottom: 5px; }

        .tbl-horario { width: 100%; border-collapse: collapse; font-size: 6.5px; }
        .tbl-horario th,
        .tbl-horario td {
            border: 1px solid #b0b0b0;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
        }
        .tbl-horario th { background: #efefef; font-weight: 700; }
        .tbl-horario .th-titulo { font-size: 7px; }
        .tbl-horario .dia-lbl { text-align: left; font-weight: 700; width: 26px; }

        .tbl-ponto { width: 100%; border-collapse: collapse; font-size: 6.2px; table-layout: fixed; }
        .tbl-ponto th,
        .tbl-ponto td {
            border: 1px solid #b0b0b0;
            padding: 2px 1px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
        }
        .tbl-ponto th {
            background: #efefef;
            font-weight: 700;
            font-size: 5.8px;
            padding: 3px 1px;
        }
        .tbl-ponto tbody tr:nth-child(odd) td { background: #fafafa; }
        .tbl-ponto tbody tr:nth-child(even) td { background: #fff; }
        .tbl-ponto .c-dia {
            text-align: left;
            padding-left: 3px;
            font-size: 6.2px;
            white-space: nowrap;
            width: 78px;
        }
        .tbl-ponto .c-rotulo {
            text-align: center;
            font-size: 6px;
        }
        .tbl-ponto tr.totais td {
            font-weight: 700;
            background: #f5f5f5 !important;
        }
        .tbl-ponto tr.totais .c-dia { text-align: left; font-weight: 700; }

        .legenda {
            margin-top: 6px;
            font-size: 6.5px;
            color: #333;
        }

        .assinaturas { width: 100%; border-collapse: collapse; margin-top: 22px; }
        .assinaturas td { width: 50%; border: 0; text-align: center; vertical-align: top; padding: 0 16px; }
        .assinaturas .traco {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
@php
    $logoOmega = $logoOmega ?? (is_file(public_path('cartao-ponto-omega.png'))
        ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('cartao-ponto-omega.png')))
        : ($logo ?? null));
    $logoControlId = $logoControlId ?? (is_file(public_path('cartao-ponto-controlid.png'))
        ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('cartao-ponto-controlid.png')))
        : null);
@endphp
@foreach ($cartoes as $idx => $cartao)
    @php
        $c = $cartao['colaborador'];
        $pagina = str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);
        $total = str_pad((string) $totalPaginas, 2, '0', STR_PAD_LEFT);
        $cpf = preg_replace('/\D+/', '', (string) $c->cpf);
        $cpfFmt = strlen($cpf) === 11
            ? substr($cpf, 0, 3).'.'.substr($cpf, 3, 3).'.'.substr($cpf, 6, 3).'-'.substr($cpf, 9, 2)
            : ($c->cpf ?: '');
    @endphp
    <div class="cartao">
        <table class="hdr">
            <tr>
                <td class="hdr-logo-omega">
                    @if ($logoOmega)
                        <img src="{{ $logoOmega }}" alt="{{ $empresaFantasia }}">
                    @endif
                </td>
                <td class="hdr-titulo">
                    <h1>Cartão de Ponto</h1>
                    <div class="periodo">{{ $periodoTitulo }}</div>
                </td>
                <td class="hdr-controlid">
                    @if ($logoControlId)
                        <img src="{{ $logoControlId }}" alt="Control iD">
                    @else
                        <strong style="font-size: 10px;">Control iD</strong>
                    @endif
                    <div class="meta">
                        Página {{ $pagina }} de {{ $total }}<br>
                        Emitido em {{ $geradoEm->format('d/m/Y') }} às {{ $geradoEm->format('H:i') }}
                    </div>
                </td>
            </tr>
        </table>
        <hr class="hdr-linha">

        <table class="bloco-info">
            <tr>
                <td class="col-info">
                    <div class="campo"><span class="lbl">Nome da empresa:</span> {{ $empresaRazao }}</div>
                    <div class="campo"><span class="lbl">Nome do funcionário:</span> {{ $c->nome }}</div>
                    <div class="campo"><span class="lbl">Nome do cargo:</span> {{ $c->cargo ?: '' }}</div>
                </td>
                <td class="col-info-meio">
                    <div class="campo"><span class="lbl">Número de matrícula:</span> {{ $c->matricula ?: '' }}</div>
                    <div class="campo"><span class="lbl">CPF do funcionário:</span> {{ $cpfFmt }}</div>
                </td>
                <td class="col-horario">
                    <table class="tbl-horario">
                        <thead>
                            <tr>
                                <th colspan="5" class="th-titulo">Horário de trabalho</th>
                            </tr>
                            <tr>
                                <th class="dia-lbl"></th>
                                <th>Ent. 1</th>
                                <th>Saí. 1</th>
                                <th>Ent. 2</th>
                                <th>Saí. 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cartao['horario_semana'] as $h)
                                <tr>
                                    <td class="dia-lbl">{{ $h['label'] }}</td>
                                    <td>{{ $h['entrada_1'] }}</td>
                                    <td>{{ $h['saida_1'] }}</td>
                                    <td>{{ $h['entrada_2'] }}</td>
                                    <td>{{ $h['saida_2'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <table class="tbl-ponto">
            <thead>
                <tr>
                    <th class="c-dia">Dia</th>
                    <th>Ent. 1</th>
                    <th>Saí. 1</th>
                    <th>Ent. 2</th>
                    <th>Saí. 2</th>
                    <th>Total<br>normais</th>
                    <th>Total<br>trabalhado</th>
                    <th>Adicional<br>noturno</th>
                    <th>Horas<br>previstas</th>
                    <th>Dia<br>falta</th>
                    <th>Horas<br>falta</th>
                    <th>Horas<br>atraso</th>
                    <th>Falta e<br>atraso</th>
                    <th>Atestado</th>
                    <th>Extras<br>total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cartao['linhas'] as $linha)
                    @php
                        $rotulo = strlen((string) $linha['entrada_1']) > 9;
                    @endphp
                    <tr>
                        <td class="c-dia">{{ $linha['dia'] }}</td>
                        <td class="{{ $rotulo ? 'c-rotulo' : '' }}">{{ $linha['entrada_1'] }}</td>
                        <td>{{ $linha['saida_1'] }}</td>
                        <td>{{ $linha['entrada_2'] }}</td>
                        <td>{{ $linha['saida_2'] }}</td>
                        <td>{{ $linha['total_normais'] }}</td>
                        <td>{{ $linha['total_trabalhado'] }}</td>
                        <td>{{ $linha['adicional_noturno'] }}</td>
                        <td>{{ $linha['horas_previstas'] }}</td>
                        <td>{{ $linha['dia_falta'] }}</td>
                        <td>{{ $linha['horas_falta'] }}</td>
                        <td>{{ $linha['horas_atraso'] }}</td>
                        <td>{{ $linha['falta_atraso'] }}</td>
                        <td>{{ $linha['atestado'] }}</td>
                        <td>{{ $linha['extras_total'] }}</td>
                    </tr>
                @endforeach
                <tr class="totais">
                    <td class="c-dia">TOTAIS</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ $cartao['totais']['normais'] }}</td>
                    <td>{{ $cartao['totais']['trabalhado'] }}</td>
                    <td>{{ $cartao['totais']['noturno'] }}</td>
                    <td>{{ $cartao['totais']['previstas'] }}</td>
                    <td>{{ $cartao['totais']['dia_falta'] }}</td>
                    <td>{{ $cartao['totais']['horas_falta'] }}</td>
                    <td>{{ $cartao['totais']['horas_atraso'] }}</td>
                    <td>{{ $cartao['totais']['falta_atraso'] }}</td>
                    <td>{{ $cartao['totais']['atestado'] }}</td>
                    <td>{{ $cartao['totais']['extras'] }}</td>
                </tr>
            </tbody>
        </table>

        <p class="legenda">(I)=Incluído, (P)=Pré-assinalado, (M)=Coletor REP-P Mobile/Web, (C)=Coletor REP-P (iDFace/iDFlex)</p>

        <table class="assinaturas">
            <tr>
                <td><div class="traco">{{ $c->nome }}</div></td>
                <td><div class="traco">{{ $empresaRazao }}</div></td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
