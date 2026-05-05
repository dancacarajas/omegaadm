@extends('layouts.app')

@section('title', 'Nova manutenção - Veículos - Omega286')
@section('eyebrow', 'Operação / Veículos / Frota')
@section('page-title', 'Nova manutenção')

@section('content')
    <form method="POST" action="{{ route('veiculos.manutencoes.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('veiculos.manutencoes._form')

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('veiculos.frota.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                Voltar
            </a>
            <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="save" class="h-4 w-4"></i>
                Salvar manutenção
            </button>
        </div>
    </form>
@endsection
