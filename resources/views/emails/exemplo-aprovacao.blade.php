@extends('emails.layout')

@section('preheader', 'Exemplo de notificação do Omega Administrativo — layout para aprovação.')

@section('titulo', 'Chamado de movimentação atualizado')

@section('subtitulo')
    O fluxo de RH registrou uma alteração que pode exigir sua atenção.
@endsection

@section('conteudo')
    @php
        $gray = '#6b6f76';
        $black = '#111111';
        $urlExemplo = ($appUrl ?? config('app.url')).'/rh/chamados-movimentacao';
    @endphp

    <p style="margin:16px 0 0;font-size:15px;line-height:1.6;color:{{ $black }};">
        Olá, <strong>Jarbas Alves</strong>,
    </p>
    <p style="margin:12px 0 0;font-size:15px;line-height:1.6;color:{{ $gray }};">
        O chamado <strong style="color:{{ $black }};">#1042</strong> foi atualizado para o status
        <strong style="color:#6f1731;">Em análise</strong>. Confira os dados abaixo e acesse o sistema para validar pendências ou anexos.
    </p>

    @include('emails.partials.tabela-detalhes', [
        'linhas' => [
            'Colaborador' => 'Maria da Silva Santos',
            'Matrícula' => '0001847',
            'Tipo' => 'Alteração de função',
            'Contrato' => '312 — Omega Service',
            'Atualizado em' => now()->format('d/m/Y H:i'),
        ],
    ])

    @component('emails.partials.caixa-info', ['titulo' => 'Observação do RH'])
        Documentação complementar anexada ao chamado. Prazo sugerido para retorno: <strong>3 dias úteis</strong>.
    @endcomponent

    @include('emails.partials.botao', [
        'url' => $urlExemplo,
        'texto' => 'Abrir chamado no sistema',
    ])

    <p style="margin:8px 0 0;font-size:13px;line-height:1.5;color:{{ $gray }};">
        Se o botão não funcionar, copie e cole no navegador:<br>
        <a href="{{ $urlExemplo }}" style="color:#6f1731;word-break:break-all;">{{ $urlExemplo }}</a>
    </p>
@endsection
