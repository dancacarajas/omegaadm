@extends('layouts.ponto-mobile')

@section('title', 'Presença na obra')

@section('content')
    <header class="ponto-header" style="padding-bottom: 2rem;">
        <div class="ponto-header-top" style="justify-content: center;">
            @include('ponto._brand', ['class' => 'ponto-logo'])
        </div>

        <div class="ponto-identify-hero">
            <span class="ponto-identify-badge">
                <i data-lucide="hard-hat" class="h-3.5 w-3.5"></i>
                Medição · Obra
            </span>
            <h1 class="ponto-identify-title">Presença na obra</h1>
            <p class="ponto-identify-sub">
                Informe matrícula e CPF para confirmar quem subiu para trabalhar. Não substitui o ponto do RH.
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

        <div id="presenca-obra-login-pending" hidden class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="status">
            <p class="font-bold">Registros aguardando envio</p>
            <p class="mt-1 leading-relaxed" data-login-pending-text></p>
        </div>

        <div id="presenca-obra-login-offline" hidden class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-950" role="alert">
            <p class="font-bold">Sem internet</p>
            <p class="mt-1 leading-relaxed" data-login-offline-text">Conecte-se à internet para fazer o primeiro acesso neste aparelho.</p>
        </div>

        <div id="presenca-obra-offline-ready" hidden class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950" role="status">
            <p class="font-bold">Este aparelho já tem acesso salvo</p>
            <p class="mt-1 leading-relaxed">Você pode entrar sem internet com a mesma matrícula e CPF do último acesso, ou ir direto para a confirmação de presença.</p>
            <button type="button" id="presenca-offline-continuar" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-bold text-white">
                <i data-lucide="hard-hat" class="h-4 w-4"></i>
                Continuar sem internet
            </button>
        </div>

        <form method="POST" action="{{ route('presenca-obra.identificar.store') }}" class="flex flex-1 flex-col gap-4">
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
            Acesso liberado apenas para supervisores autorizados no cadastro do colaborador.
            O primeiro acesso em cada aparelho precisa de internet; depois disso, login e confirmações funcionam sem internet.
        </p>
    </main>
@endsection

@push('scripts')
    @include('presenca-obra.partials.offline-core-script')
    @include('presenca-obra.partials.offline-login-script')
@endpush
