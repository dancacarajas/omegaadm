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
                Confirme quem subiu para trabalhar ou acesse a consulta da Medição.
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

        <section class="rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft/40 p-4">
            <h2 class="text-sm font-bold text-brand-burgundy">Confirmar presença (supervisor)</h2>
            <p class="mt-1 text-xs leading-relaxed text-brand-burgundy/90">
                Use matrícula e CPF para marcar quem está presente ou ausente na obra. Funciona sem internet após o primeiro acesso no aparelho.
            </p>
        </section>

        <form method="POST" action="{{ route('presenca-obra.identificar.store') }}" class="flex flex-col gap-4">
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
            <button type="submit" class="ponto-btn-primary">
                <span class="ponto-btn-primary-row">
                    <i data-lucide="log-in" class="h-5 w-5"></i>
                    Entrar para confirmar
                </span>
            </button>
        </form>

        <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-bold text-brand-black">Consulta Medição</h2>
            <p class="mt-1 text-xs leading-relaxed text-brand-gray">
                Para planejadores e RH: visualize confirmações do dia e exporte a folha de ponto em Excel.
            </p>
            <div class="mt-4 flex flex-col gap-2">
                @if ($usuarioLogado)
                    <a href="{{ $urlConsulta }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-bold text-white shadow-sm">
                        <i data-lucide="table-2" class="h-4 w-4"></i>
                        Abrir consulta
                    </a>
                @else
                    <a href="{{ $urlLoginConsulta }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-bold text-brand-black shadow-sm">
                        <i data-lucide="mail" class="h-4 w-4"></i>
                        Entrar com e-mail do sistema
                    </a>
                @endif
                <a href="{{ $urlConfirmacao }}" class="inline-flex h-10 items-center justify-center gap-2 text-xs font-semibold text-brand-gray underline-offset-2 hover:text-brand-burgundy hover:underline">
                    Ir para confirmação em tela cheia
                </a>
            </div>
        </section>

        <p class="text-center text-xs leading-relaxed text-brand-gray">
            Acesso de confirmação liberado apenas para supervisores autorizados no cadastro do colaborador.
        </p>
    </main>
@endsection

@push('scripts')
    @include('presenca-obra.partials.offline-core-script')
    @include('presenca-obra.partials.offline-login-script')
@endpush
