@extends('layouts.app')

@section('title', 'Editar feriado - Omega286')
@section('eyebrow', 'Frequência / Feriados')
@section('page-title', 'Editar feriado')

@section('actions')
    <a href="{{ route('rh.frequencia.feriados.index', ['ano' => $feriado->data->year]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('rh.frequencia.feriados.update', $feriado) }}">
        @csrf
        @method('PUT')
        @include('rh.frequencia.feriados._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('rh.frequencia.feriados.index', ['ano' => $feriado->data->year]) }}" class="inline-flex h-11 items-center rounded-lg border border-zinc-200 px-5 text-sm font-semibold text-brand-black">Cancelar</a>
            <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm">
                <i data-lucide="save" class="h-4 w-4"></i>
                Atualizar feriado
            </button>
        </div>
    </form>
@endsection
