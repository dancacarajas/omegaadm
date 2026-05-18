@extends('layouts.app')

@section('title', 'Novo registro TST — Omega286')
@section('eyebrow', 'SSMA / Registros TST')
@section('page-title', 'Registros TST CT-286')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="mb-6 text-center">
            <img src="{{ asset('logo.png') }}" alt="Omega Service" class="mx-auto mb-4 object-contain" style="max-height: 48px;">
            <p class="text-sm text-brand-gray">Preencha os dados do registro de segurança do trabalho, como no formulário de campo.</p>
        </div>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="POST" action="{{ route('sesmt.registros-tst.registros.store') }}" enctype="multipart/form-data">
                @csrf
                @include('sesmt.registros-tst.registros._form', ['registro' => $registro])

                <div class="mt-8 flex flex-col gap-3">
                    <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-burgundy text-base font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="send" class="h-5 w-5"></i>
                        Enviar
                    </button>
                    <a href="{{ route('sesmt.registros-tst.registros.index') }}" class="inline-flex h-11 w-full items-center justify-center text-sm font-semibold text-brand-gray transition hover:text-brand-burgundy">
                        Cancelar
                    </a>
                </div>
            </form>
        </section>
    </div>
@endsection
