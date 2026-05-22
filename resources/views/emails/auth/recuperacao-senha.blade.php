@extends('emails.layout')

@section('preheader', 'Link para redefinir sua senha de acesso ao portal.')

@section('titulo', 'Recuperação de senha')

@section('subtitulo')
    Recebemos uma solicitação para redefinir a senha da sua conta.
@endsection

@section('conteudo')
    @php
        $gray = '#6b6f76';
        $black = '#111111';
        $nome = $usuario->name ?? 'Usuário';
        $minutos = $expiraEmMinutos ?? 60;
    @endphp

    <p style="margin:16px 0 0;font-size:15px;line-height:1.6;color:{{ $black }};">
        Olá, <strong>{{ $nome }}</strong>,
    </p>
    <p style="margin:12px 0 0;font-size:15px;line-height:1.6;color:{{ $gray }};">
        Clique no botão abaixo para criar uma nova senha. O link é válido por
        <strong>{{ $minutos }} minutos</strong> e só pode ser usado uma vez.
    </p>

    @include('emails.partials.tabela-detalhes', [
        'linhas' => [
            'E-mail da conta' => $usuario->email ?? '—',
            'Validade do link' => $minutos.' minutos',
        ],
    ])

    @include('emails.partials.botao', [
        'url' => $resetUrl,
        'texto' => 'Redefinir minha senha',
    ])

    @component('emails.partials.caixa-info', ['titulo' => 'Não foi você?'])
        Se você não solicitou esta alteração, ignore este e-mail. Sua senha atual permanece a mesma.
    @endcomponent

    <p style="margin:8px 0 0;font-size:13px;line-height:1.5;color:{{ $gray }};">
        Link alternativo:<br>
        <a href="{{ $resetUrl }}" style="color:#6f1731;word-break:break-all;">{{ $resetUrl }}</a>
    </p>
@endsection
