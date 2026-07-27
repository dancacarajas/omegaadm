@extends('layouts.public-presenca-obra')

@section('title', 'Gestão de Presenças - Omega286')
@section('eyebrow', 'Medição')
@section('page-title', 'Gestão de Presenças')

@section('content')
    @include('medicao.presenca-obra._consulta')
@endsection
