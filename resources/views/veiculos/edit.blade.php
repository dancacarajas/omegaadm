@extends('layouts.app')

@section('title', 'Editar veiculo - Omega286')
@section('eyebrow', 'Veiculos')
@section('page-title', 'Editar veiculo')

@section('content')
    <form method="POST" action="{{ route('veiculos.update', $veiculo) }}">
        @method('PUT')
        @include('veiculos._form')
    </form>
@endsection
