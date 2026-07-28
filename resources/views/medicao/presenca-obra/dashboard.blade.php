@extends('layouts.public-presenca-obra')

@section('title', 'Dashboard — Gestão de Presenças')
@section('eyebrow', 'Medição')
@section('page-title', 'Painel de Gerenciamento')

@section('content')
    @include('presenca-obra.partials._dashboard-conteudo')
@endsection
