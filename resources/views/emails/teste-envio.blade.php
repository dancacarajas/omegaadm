@extends('emails.layout')

@section('preheader', 'E-mail de teste — validação da configuração SMTP.')

@section('titulo', 'Teste de envio de e-mail')

@section('subtitulo')
    Se você está lendo esta mensagem, o layout e o SMTP estão operacionais.
@endsection

@section('conteudo')
    @php
        $gray = '#6b6f76';
        $black = '#111111';
        $enviadoEm = $enviadoEm ?? now()->format('d/m/Y H:i:s');
    @endphp

    <p style="margin:16px 0 0;font-size:15px;line-height:1.6;color:{{ $black }};">
        Este é um e-mail de teste enviado por <strong>{{ $appName }}</strong>.
    </p>
    <p style="margin:12px 0 0;font-size:15px;line-height:1.6;color:{{ $gray }};">
        Data/hora do envio: <strong style="color:{{ $black }};">{{ $enviadoEm }}</strong>
    </p>

    @component('emails.partials.caixa-info', ['titulo' => 'Próximo passo'])
        Após validar o recebimento, utilize o layout aprovado nas notificações automáticas do sistema (RH, benefícios, movimentações, etc.).
    @endcomponent

    @if (!empty($appUrl))
        @include('emails.partials.botao', [
            'url' => $appUrl,
            'texto' => 'Acessar o portal',
        ])
    @endif
@endsection
