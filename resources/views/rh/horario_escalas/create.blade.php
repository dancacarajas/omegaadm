@extends('layouts.app')

@section('title', 'Novo cadastro de horários - Omega286')
@section('eyebrow', 'Recursos Humanos / Frequência')
@section('page-title', 'Novo cadastro de horários')

@section('content')
    <form method="POST" action="{{ route('rh.horarios.store') }}" class="space-y-6" data-horario-escala-form>
        @csrf
        @include('rh.horario_escalas._form')

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('rh.horarios.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Voltar
            </a>
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="save" class="h-4 w-4"></i>
                Salvar cadastro
            </button>
        </div>
    </form>
@endsection
