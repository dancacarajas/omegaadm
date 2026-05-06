@extends('layouts.app')

@section('title', 'Editar prazo SLA — SSMA')
@section('eyebrow', 'SSMA')
@section('page-title', 'Editar prazo (SLA)')

@section('actions')
    <a href="{{ route('sesmt.registros.prazos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('sesmt.registros.prazos.update', $prazo) }}" class="max-w-3xl space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('sesmt.registros.prazos._form', ['prazo' => $prazo])

        <div class="flex justify-end gap-3 border-t border-zinc-100 pt-6">
            <a href="{{ route('sesmt.registros.prazos.index') }}" class="inline-flex h-11 items-center rounded-xl border border-zinc-200 bg-white px-5 text-sm font-semibold text-brand-black">Cancelar</a>
            <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">Atualizar</button>
        </div>
    </form>
@endsection
