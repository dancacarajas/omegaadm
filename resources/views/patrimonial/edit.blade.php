@extends('layouts.app')

@section('title', 'Editar patrimônio - Omega286')
@section('eyebrow', 'Gestão patrimonial')
@section('page-title', 'Editar patrimônio')

@section('actions')
    <a href="{{ route('patrimonial.show', $patrimonio) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="eye" class="h-4 w-4"></i>
        Visualizar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('patrimonial.update', $patrimonio) }}">
        @method('PUT')
        @include('patrimonial._form')
    </form>
@endsection
