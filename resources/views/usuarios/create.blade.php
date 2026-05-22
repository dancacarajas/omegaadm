@extends('layouts.app')

@section('title', 'Novo usuário - Omega286')
@section('eyebrow', 'Controle de acesso')
@section('page-title', 'Novo usuário')

@section('actions')
    <a href="{{ route('usuarios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('usuarios.store') }}" enctype="multipart/form-data">
        @include('usuarios._form')
    </form>
@endsection
