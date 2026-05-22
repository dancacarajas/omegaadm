@extends('emails.layout')

@section('preheader', 'Sua senha de acesso foi alterada com sucesso.')

@section('titulo', 'Senha redefinida')

@section('subtitulo')
    A alteração foi concluída. Você já pode entrar com a nova senha.
@endsection

@section('conteudo')
    @php
        $gray = '#6b6f76';
        $black = '#111111';
        $nome = $usuario->name ?? 'Usuário';
    @endphp

    <p style="margin:16px 0 0;font-size:15px;line-height:1.6;color:{{ $black }};">
        Olá, <strong>{{ $nome }}</strong>,
    </p>
    <p style="margin:12px 0 0;font-size:15px;line-height:1.6;color:{{ $gray }};">
        Confirmamos que a senha da conta <strong>{{ $usuario->email }}</strong> foi redefinida com sucesso.
    </p>

    @include('emails.partials.tabela-detalhes', [
        'linhas' => [
            'Data/hora' => $redefinidoEm ?? now()->format('d/m/Y H:i'),
            'E-mail' => $usuario->email ?? '—',
        ],
    ])

    @component('emails.partials.caixa-info', ['titulo' => 'Não reconhece esta alteração?'])
        Entre em contato imediatamente com o administrador do sistema. Não compartilhe sua nova senha.
    @endcomponent

    @include('emails.partials.botao', [
        'url' => $loginUrl ?? route('login'),
        'texto' => 'Ir para o login',
    ])
@endsection
