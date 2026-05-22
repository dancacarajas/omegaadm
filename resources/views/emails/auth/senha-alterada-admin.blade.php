@extends('emails.layout')

@section('preheader', 'Um administrador atualizou a senha da sua conta.')

@section('titulo', 'Senha atualizada pelo administrador')

@section('subtitulo')
    Sua senha de acesso foi alterada pela equipe responsável pelo sistema.
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
        A senha da conta <strong>{{ $usuario->email }}</strong> foi atualizada por um administrador.
        Utilize a nova senha informada pelo ADM para acessar o portal.
    </p>

    @include('emails.partials.tabela-detalhes', [
        'linhas' => array_filter([
            'E-mail' => $usuario->email ?? '—',
            'Alterado por' => $alteradoPor ?? null,
            'Data/hora' => $alteradoEm ?? now()->format('d/m/Y H:i'),
        ]),
    ])

    @component('emails.partials.caixa-info', ['titulo' => 'Dúvidas sobre a nova senha?'])
        Procure o administrador que realizou a alteração ou o responsável pelo seu contrato. Por segurança, a nova senha não é enviada neste e-mail.
    @endcomponent

    @include('emails.partials.botao', [
        'url' => $loginUrl ?? route('login'),
        'texto' => 'Entrar no sistema',
    ])
@endsection
