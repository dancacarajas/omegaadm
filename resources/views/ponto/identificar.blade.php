@extends('layouts.ponto-mobile')

@section('title', 'Identificar')

@push('head')
    <style>
        .ponto-header-glow {
            background:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(255, 255, 255, 0.18), transparent),
                linear-gradient(145deg, #6f1731 0%, #4a0f21 55%, #3a0c1a 100%);
        }
    </style>
@endpush

@section('content')
    <header class="ponto-header-glow relative overflow-hidden px-6 pb-8 pt-[max(1rem,env(safe-area-inset-top))] text-white">
        <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-white/5" aria-hidden="true"></div>

        <div class="relative flex justify-center">
            @include('ponto._brand', ['class' => 'h-11 w-auto max-w-[165px] object-contain brightness-0 invert'])
        </div>

        <div class="relative mt-8 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white/80">
                <i data-lucide="fingerprint" class="h-3.5 w-3.5"></i>
                Ponto eletrônico
            </span>
            <h1 class="mt-4 text-2xl font-black tracking-tight">Marcação de ponto</h1>
            <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-white/75">
                Informe matrícula e CPF para registrar suas batidas do dia.
            </p>
        </div>
    </header>

    <main class="relative -mt-5 flex flex-1 flex-col rounded-t-[1.75rem] bg-gradient-to-b from-zinc-50 to-zinc-100 px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-6 shadow-[0_-12px_40px_rgba(17,17,17,0.08)]">
        @if ($errors->has('identificacao'))
            <div class="mb-4 flex gap-3 rounded-2xl border border-red-200/80 bg-white px-4 py-3.5 text-sm shadow-sm" role="alert">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                </span>
                <p class="font-semibold leading-snug text-red-950">{{ $errors->first('identificacao') }}</p>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 flex gap-3 rounded-2xl border border-emerald-200/80 bg-white px-4 py-3.5 text-sm shadow-sm" role="status">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i data-lucide="circle-check" class="h-5 w-5"></i>
                </span>
                <p class="font-semibold leading-snug text-emerald-950">{{ session('success') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('ponto.identificar.store') }}" class="flex flex-1 flex-col gap-4">
            @csrf
            <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
                <label for="matricula" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Matrícula</label>
                <input
                    type="text"
                    name="matricula"
                    id="matricula"
                    value="{{ old('matricula') }}"
                    required
                    autocomplete="username"
                    inputmode="numeric"
                    class="h-14 w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 text-lg font-semibold outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/15"
                    placeholder="Ex.: 22541"
                >
            </div>
            <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
                <label for="cpf" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">CPF</label>
                <input
                    type="text"
                    name="cpf"
                    id="cpf"
                    value="{{ old('cpf') }}"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    class="h-14 w-full rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 text-lg font-semibold outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/15"
                    placeholder="Somente números"
                >
            </div>
            <button
                type="submit"
                class="mt-auto flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-brand-burgundy text-base font-bold text-white shadow-lg shadow-brand-burgundy/25 transition active:scale-[0.98] hover:bg-brand-burgundy-dark"
            >
                <i data-lucide="log-in" class="h-5 w-5"></i>
                Entrar
            </button>
        </form>

        <p class="mt-6 text-center text-xs leading-relaxed text-brand-gray">
            Dúvidas ou divergências? Procure o setor de RH.
        </p>
    </main>
@endsection
