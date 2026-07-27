@extends('layouts.public-presenca-obra')

@section('title', 'Presença na obra - Omega286')
@section('eyebrow', 'Medição')
@section('page-title', 'Presença na obra')

@section('actions')
    @auth
        <a href="{{ route('medicao.presenca-obra.consulta') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="table-2" class="h-4 w-4"></i>
            Consulta interna
        </a>
    @else
        <a href="{{ route('login', ['redirect' => route('medicao.presenca-obra.consulta', [], false)]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="mail" class="h-4 w-4"></i>
            Entrar com e-mail
        </a>
    @endauth
@endsection

@section('content')
    @include('medicao.presenca-obra._login-supervisor')
    @include('medicao.presenca-obra._consulta')
@endsection

@push('scripts')
    @include('presenca-obra.partials.offline-core-script')
    @include('presenca-obra.partials.offline-login-script')
@endpush
