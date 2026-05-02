@extends('layouts.app')

@section('title', 'Novo benefício - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Novo benefício')

@section('actions')
    <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('rh.beneficios.store') }}">
        @csrf

        <div class="mb-6 overflow-hidden rounded-2xl border border-zinc-200 bg-brand-gray text-white shadow-sm">
            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white">
                        <i data-lucide="hand-heart" class="h-3.5 w-3.5"></i>
                        Cadastro de benefício
                    </div>
                    <h2 class="mt-4 text-2xl font-bold">Novo benefício</h2>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-white/85">
                        Defina o benefício, regras de elegibilidade, fornecedor e valor para uso nos próximos fluxos de RH.
                    </p>
                </div>
                <div class="hidden rounded-2xl border border-white/20 bg-white/10 p-4 lg:block">
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-brand-burgundy text-white">
                        <i data-lucide="wallet-cards" class="h-8 w-8"></i>
                    </div>
                </div>
            </div>
        </div>

        @include('rh.beneficios._form')

        <div class="sticky bottom-0 z-10 mt-6 rounded-t-2xl border border-b-0 border-zinc-200 bg-white/95 px-4 py-4 shadow-[0_-12px_30px_rgba(17,17,17,0.06)] backdrop-blur">
            <div class="flex justify-end gap-3">
                <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-5 py-3 text-sm font-semibold text-brand-black">
                    <i data-lucide="x" class="h-4 w-4"></i>
                    Cancelar
                </a>
                <button class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar benefício
                </button>
            </div>
        </div>
    </form>
@endsection
