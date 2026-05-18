@extends('layouts.ponto-mobile')

@section('title', 'Registros TST')

@section('content')
    <header class="ponto-header" style="padding-bottom: 2rem;">
        <div class="ponto-header-top" style="justify-content: center;">
            @include('ponto._brand', ['class' => 'ponto-logo'])
        </div>

        <div class="ponto-identify-hero">
            <span class="ponto-identify-badge">
                <i data-lucide="hard-hat" class="h-3.5 w-3.5"></i>
                SSMA · Registros TST
            </span>
            <h1 class="ponto-identify-title">Registros TST CT-286</h1>
            <p class="ponto-identify-sub">
                Informe matrícula e CPF para registrar atividades de segurança do trabalho em campo.
            </p>
        </div>
    </header>

    <main class="ponto-main">
        @if ($errors->has('identificacao'))
            <div class="ponto-card" role="alert">
                <span class="ponto-card-icon ponto-card-icon--error">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-text text-red-950">{{ $errors->first('identificacao') }}</p>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="ponto-card" role="status">
                <span class="ponto-card-icon ponto-card-icon--success">
                    <i data-lucide="circle-check" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-text text-emerald-950">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('tst-campo.identificar.store') }}" class="flex flex-1 flex-col gap-4">
            @csrf
            <div class="ponto-field-card">
                <label for="matricula" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Matrícula</label>
                <input
                    type="text"
                    name="matricula"
                    id="matricula"
                    value="{{ old('matricula') }}"
                    required
                    autocomplete="username"
                    inputmode="numeric"
                    class="ponto-input"
                    placeholder="Ex.: 22541"
                >
            </div>
            <div class="ponto-field-card">
                <label for="cpf" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">CPF</label>
                <input
                    type="text"
                    name="cpf"
                    id="cpf"
                    value="{{ old('cpf') }}"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    class="ponto-input"
                    placeholder="Somente números"
                >
            </div>
            <button type="submit" class="ponto-btn-primary" style="margin-top: auto;">
                <span class="ponto-btn-primary-row">
                    <i data-lucide="log-in" class="h-5 w-5"></i>
                    Entrar
                </span>
            </button>
        </form>

        <p class="text-center text-xs leading-relaxed text-brand-gray">
            Em caso de dúvidas, procure o setor de SSMA ou RH.
        </p>
    </main>
@endsection
