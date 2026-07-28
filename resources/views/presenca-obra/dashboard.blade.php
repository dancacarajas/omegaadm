@extends('layouts.presenca-obra')

@section('title', 'Dashboard — Presença na obra')

@section('content')
    <header class="ponto-header" style="padding-bottom: 1.25rem;">
        <div class="ponto-header-top">
            @include('ponto._brand', ['class' => 'ponto-logo'])
            <form method="POST" action="{{ route('presenca-obra.sair') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white">
                    Sair
                </button>
            </form>
        </div>
        <div class="mt-4">
            <p class="text-xs font-bold uppercase tracking-wide text-white/70">Painel de gerenciamento</p>
            <h1 class="mt-1 text-lg font-bold text-white lg:text-2xl">Dashboard</h1>
            <p class="mt-1 text-xs text-white/80 lg:text-sm">{{ $confirmador->nome }} · {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</p>
        </div>
    </header>

    <main class="ponto-main presenca-dashboard-main">
        @include('presenca-obra.partials._dashboard-conteudo')
    </main>
@endsection
