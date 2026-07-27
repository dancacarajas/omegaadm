@extends('layouts.public-presenca-obra')

@section('title', 'Gestão de Presenças - Omega286')
@section('eyebrow', 'Medição')
@section('page-title', 'Gestão de Presenças')

@section('content')
    @include('medicao.presenca-obra._login-supervisor', [
        'redirectAfterLogin' => route('medicao.presenca-obra.index', [], false),
        'loginTitulo' => 'Acesso à gestão de presenças',
        'loginDescricao' => 'Informe matrícula e CPF para acessar a consulta e as confirmações de presença na obra.',
        'loginBotao' => 'Entrar',
    ])
@endsection

@push('scripts')
    @include('presenca-obra.partials.offline-core-script')
    @include('presenca-obra.partials.offline-login-script')
@endpush
