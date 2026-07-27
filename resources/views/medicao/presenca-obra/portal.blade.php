@extends('layouts.public-presenca-obra')

@section('title', 'Presença na obra - Omega286')
@section('eyebrow', 'Medição')
@section('page-title', 'Presença na obra')

@section('content')
    @include('medicao.presenca-obra._login-supervisor')
    @include('medicao.presenca-obra._consulta')
@endsection

@push('scripts')
    @include('presenca-obra.partials.offline-core-script')
    @include('presenca-obra.partials.offline-login-script')
@endpush
