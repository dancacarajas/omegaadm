@extends('layouts.app')

@section('title', 'Editar tipo de justificativa')
@section('page-title', 'Editar tipo de justificativa')

@section('content')
    <form method="POST" action="{{ route('rh.frequencia.justificativa-tipos.update', $tipo) }}">
        @csrf
        @method('PUT')
        @include('rh.frequencia.justificativa-tipos._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('rh.frequencia.justificativa-tipos.index') }}" class="inline-flex h-11 items-center rounded-lg border border-zinc-200 px-5 text-sm font-semibold">Cancelar</a>
            <button type="submit" class="inline-flex h-11 items-center rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white">Atualizar</button>
        </div>
    </form>
@endsection
