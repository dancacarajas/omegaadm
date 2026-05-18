@extends('layouts.ponto-mobile')

@section('title', 'Identificar')

@section('content')
    <header class="bg-gradient-to-br from-brand-burgundy to-brand-burgundy-dark px-6 pb-10 pt-[max(1.5rem,env(safe-area-inset-top))] text-white">
        <div class="flex justify-center">
            <img src="{{ asset('logo.png') }}" alt="Omega" class="h-10 w-auto brightness-0 invert">
        </div>
        <h1 class="mt-6 text-center text-2xl font-black tracking-tight">Marcação de ponto</h1>
        <p class="mt-2 text-center text-sm text-white/80">Identifique-se para registrar suas batidas do dia.</p>
    </header>

    <main class="relative -mt-6 flex flex-1 flex-col rounded-t-3xl bg-white px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-6 shadow-[0_-8px_30px_rgba(0,0,0,0.08)]">
        @if ($errors->has('identificacao'))
            <div class="mb-4 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0"></i>
                <p class="font-semibold">{{ $errors->first('identificacao') }}</p>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('ponto.identificar.store') }}" class="flex flex-1 flex-col gap-5">
            @csrf
            <div>
                <label for="matricula" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-brand-gray">Matrícula</label>
                <input
                    type="text"
                    name="matricula"
                    id="matricula"
                    value="{{ old('matricula') }}"
                    required
                    autocomplete="username"
                    inputmode="numeric"
                    class="h-14 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 text-lg font-semibold outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/15"
                    placeholder="Ex.: 12345"
                >
            </div>
            <div>
                <label for="cpf" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-brand-gray">CPF</label>
                <input
                    type="text"
                    name="cpf"
                    id="cpf"
                    value="{{ old('cpf') }}"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    class="h-14 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 text-lg font-semibold outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/15"
                    placeholder="Somente números"
                >
            </div>
            <button type="submit" class="mt-auto flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-brand-burgundy text-base font-bold text-white shadow-lg shadow-brand-burgundy/30 active:scale-[0.98]">
                <i data-lucide="fingerprint" class="h-5 w-5"></i>
                Entrar
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-brand-gray">
            Em caso de divergência, procure o RH.
        </p>
    </main>
@endsection
