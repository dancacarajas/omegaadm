@extends('emails.layout')

@section('preheader', 'Solicitação de adesão de benefício à Matriz, com formulário assinado em anexo.')

@section('titulo', 'Solicitação de adesão à Matriz')

@section('conteudo')
    @php
        $gray = '#6b6f76';
        $black = '#111111';
        $burgundy = '#6f1731';
        $colab = $vinculo->colaborador;
        $termos = $termosColaborador ?? ['substantivo' => 'colaborador', 'substantivo_titulo' => 'Colaborador', 'assinado' => 'assinado', 'pela' => 'pelo colaborador'];
        $tituloDados = 'Dados da '.$termos['substantivo'];
        $labelColab = $termos['substantivo_titulo'].':';
    @endphp

    <p style="margin:16px 0 0;font-size:15px;line-height:1.65;color:{{ $black }};">
        Prezada <strong>{{ $responsavelMatriz ?? 'Celiamara' }}</strong>, {{ $saudacaoHorario ?? 'bom dia' }}.
    </p>

    <p style="margin:16px 0 0;font-size:15px;line-height:1.65;color:{{ $black }};">
        Encaminho, em anexo, o formulário de adesão {{ $termos['assinado'] }} {{ $termos['pela'] }} abaixo, para solicitação do benefício
        <strong>{{ $nomeBeneficio ?? ($vinculo->beneficio?->nome ?? 'Benefício') }}</strong> junto à Matriz.
    </p>

    <p style="margin:16px 0 0;font-size:15px;line-height:1.65;color:{{ $black }};">
        Solicito, por gentileza, o registro do pedido.
    </p>

    <p style="margin:28px 0 10px;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:{{ $burgundy }};">
        {{ $tituloDados }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 20px;font-size:14px;line-height:1.65;color:{{ $black }};">
        <tr>
            <td style="padding:4px 0;color:{{ $gray }};width:42%;vertical-align:top;">{{ $labelColab }}</td>
            <td style="padding:4px 0;font-weight:600;">{{ $colab?->nome ?? '—' }}</td>
        </tr>
        @if (filled($colab?->matricula))
            <tr>
                <td style="padding:4px 0;color:{{ $gray }};vertical-align:top;">Matrícula:</td>
                <td style="padding:4px 0;">{{ $colab->matricula }}</td>
            </tr>
        @endif
        @if (filled($colab?->cargo))
            <tr>
                <td style="padding:4px 0;color:{{ $gray }};vertical-align:top;">Cargo:</td>
                <td style="padding:4px 0;">{{ $colab->cargo }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:4px 0;color:{{ $gray }};vertical-align:top;">Benefício:</td>
            <td style="padding:4px 0;">{{ $nomeBeneficio ?? ($vinculo->beneficio?->nome ?? '—') }}</td>
        </tr>
        @if ($colab?->data_admissao)
            <tr>
                <td style="padding:4px 0;color:{{ $gray }};vertical-align:top;">Data de admissão:</td>
                <td style="padding:4px 0;">{{ $colab->data_admissao->format('d/m/Y') }}</td>
            </tr>
        @endif
        @if ($vinculo->data_formulario_recebido)
            <tr>
                <td style="padding:4px 0;color:{{ $gray }};vertical-align:top;">Formulário recebido em:</td>
                <td style="padding:4px 0;">{{ $vinculo->data_formulario_recebido->format('d/m/Y') }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:4px 0;color:{{ $gray }};vertical-align:top;">Enviado por:</td>
            <td style="padding:4px 0;">{{ $enviadoPor ?? '—' }}</td>
        </tr>
    </table>

    @if (! empty($urlFormularioVisualizar))
        @include('emails.partials.botao', [
            'url' => $urlFormularioVisualizar,
            'texto' => 'Abrir formulário de adesão (PDF)',
        ])
    @endif
@endsection
