@extends('layouts.app')

@section('title', 'Editar contrato - Omega286')
@section('eyebrow', 'Gestão contratual')
@section('page-title', 'Editar contrato')

@section('actions')
    <a href="{{ route('contratos.show', $contrato) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="eye" class="h-4 w-4"></i>
        Visualizar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('contratos.update', $contrato) }}">
        @method('PUT')
        @include('contratos._form')
    </form>
@endsection
