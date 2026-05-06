@extends('layouts.app')

@section('title', 'Novo registro ambiental — SSMA - Omega286')
@section('eyebrow', 'SSMA / Meio Ambiente')
@section('page-title', 'Novo registro (competência)')

@section('actions')
    <a href="{{ route('sesmt.meio-ambiente.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm md:p-8">
        <form method="POST" action="{{ route('sesmt.meio-ambiente.store') }}" enctype="multipart/form-data" class="max-w-4xl">
            @csrf
            @include('sesmt.meio-ambiente._form', ['registro' => $registro])
            <div class="mt-8 flex flex-wrap gap-3">
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar
                </button>
                <a href="{{ route('sesmt.meio-ambiente.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-6 text-sm font-semibold text-brand-black transition hover:border-brand-burgundy hover:text-brand-burgundy">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
