@extends('layouts.app')

@section('title', 'Presença na obra - Omega286')
@section('eyebrow', 'Medição')
@section('page-title', 'Presença na obra')

@section('actions')
    <a href="{{ route('medicao.presenca-obra.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="globe" class="h-4 w-4"></i>
        Portal público
    </a>
    <a href="{{ $urlPublica }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="hard-hat" class="h-4 w-4"></i>
        App de confirmação
    </a>
@endsection

@section('content')
    @include('medicao.presenca-obra._consulta')
@endsection
