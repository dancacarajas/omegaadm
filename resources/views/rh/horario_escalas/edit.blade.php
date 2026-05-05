@extends('layouts.app')

@section('title', 'Editar cadastro de horários - Omega286')
@section('eyebrow', 'Recursos Humanos / Frequência')
@section('page-title', 'Editar cadastro de horários')

@section('content')
    <form method="POST" action="{{ route('rh.horarios.update', $escala) }}" class="space-y-6" data-horario-escala-form>
        @csrf
        @method('PUT')
        @include('rh.horario_escalas._form', ['escala' => $escala, 'diasPorSemana' => $diasPorSemana])

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('rh.horarios.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Voltar
            </a>
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="save" class="h-4 w-4"></i>
                Atualizar cadastro
            </button>
        </div>
    </form>
@endsection
