@extends('emails.layout')

@section('preheader', 'Seu acesso ao portal foi criado. Utilize o e-mail e a senha informados para entrar.')

@section('titulo', 'Bem-vindo ao portal')

@section('subtitulo')
    Seu usuário foi cadastrado no {{ $emailBrandName ?? 'Omega Adm CT 286' }}.
@endsection

@section('conteudo')
    @php
        $gray = '#6b6f76';
        $black = '#111111';
        $nome = $usuario->name ?? 'Colaborador';
    @endphp

    <p style="margin:16px 0 0;font-size:15px;line-height:1.6;color:{{ $black }};">
        Olá, <strong>{{ $nome }}</strong>,
    </p>
    <p style="margin:12px 0 0;font-size:15px;line-height:1.6;color:{{ $gray }};">
        Um administrador criou seu acesso ao sistema. Use as credenciais abaixo para entrar pela primeira vez.
        Recomendamos alterar a senha após o primeiro login, em <strong>Esqueci minha senha</strong> ou com apoio do ADM.
    </p>

    @include('emails.partials.tabela-detalhes', [
        'linhas' => array_filter([
            'E-mail de acesso' => $usuario->email ?? '—',
            'Senha inicial' => ! empty($senhaTemporaria) ? $senhaTemporaria : null,
            'Cargo' => $usuario->cargo ?? null,
            'Cadastrado por' => $cadastradoPor ?? null,
        ]),
    ])

    @component('emails.partials.caixa-info', ['titulo' => 'Segurança'])
        Não compartilhe sua senha. O acesso é pessoal e intransferível. Em caso de dúvida, contate o administrador do seu contrato.
    @endcomponent

    @include('emails.partials.botao', [
        'url' => $loginUrl ?? route('login'),
        'texto' => 'Entrar no sistema',
    ])
@endsection
