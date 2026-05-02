@extends('layouts.app')

@section('title', 'Novo contrato - Omega286')
@section('eyebrow', 'Gestão contratual')
@section('page-title', 'Novo contrato')

@section('actions')
    <a href="{{ route('contratos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('contratos.store') }}">
        @include('contratos._form')
    </form>
@endsection
