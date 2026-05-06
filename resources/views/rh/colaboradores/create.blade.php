@extends('layouts.app')

@section('title', 'Novo colaborador - Omega286')
@section('eyebrow', 'RH / Efetivo')
@section('page-title', 'Novo colaborador')

@section('actions')
    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ route('rh.efetivo.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-6 overflow-hidden rounded-2xl border border-zinc-200 bg-brand-gray text-white shadow-sm">
            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white">
                        <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                        Admissão de colaborador
                    </div>
                    <h2 class="mt-4 text-2xl font-bold">Nova ficha do efetivo</h2>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-white/85">
                        Preencha os dados essenciais da ficha de registro. As seções seguem a ordem natural de admissão:
                        identificação, documentos, contrato e entrada.
                    </p>
                </div>
                <div class="hidden rounded-2xl border border-white/20 bg-white/10 p-4 lg:block">
                    <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-brand-burgundy text-white">
                        <i data-lucide="clipboard-list" class="h-8 w-8"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[260px_1fr]">
            <aside class="hidden xl:block">
                <div class="sticky top-28 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <p class="px-2 text-xs font-bold uppercase tracking-wide text-brand-gray">Etapas</p>
                    <div class="mt-3 space-y-1">
                        <a href="#dados-pessoais" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-brand-black transition hover:bg-brand-gray-soft">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-burgundy text-xs text-white">01</span>
                            Dados pessoais
                        </a>
                        <a href="#documentos" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-brand-gray transition hover:bg-brand-gray-soft hover:text-brand-black">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-gray-soft text-xs text-brand-gray">02</span>
                            Documentos
                        </a>
                        <a href="#contrato" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-brand-gray transition hover:bg-brand-gray-soft hover:text-brand-black">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-gray-soft text-xs text-brand-gray">03</span>
                            Contrato
                        </a>
                        <a href="#admissao" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-brand-gray transition hover:bg-brand-gray-soft hover:text-brand-black">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-gray-soft text-xs text-brand-gray">04</span>
                            Admissão
                        </a>
                        <a href="#mobilizacao-sgc" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-brand-gray transition hover:bg-brand-gray-soft hover:text-brand-black">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-gray-soft text-xs text-brand-gray">05</span>
                            SGC Vale
                        </a>
                    </div>
                </div>
            </aside>

            <div>
                @include('rh.colaboradores._form')
            </div>
        </div>

        <div class="sticky bottom-0 z-10 mt-6 rounded-t-2xl border border-b-0 border-zinc-200 bg-white/95 px-4 py-4 shadow-[0_-12px_30px_rgba(17,17,17,0.06)] backdrop-blur">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-brand-gray">Os dados podem ser editados depois na ficha do colaborador.</p>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-5 py-3 text-sm font-semibold text-brand-black">
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Cancelar
                    </a>
                    <button class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Salvar colaborador
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
