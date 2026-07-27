@extends('layouts.presenca-obra')

@section('title', 'Presença na obra · sem internet')

@section('content')
    <header class="ponto-header" style="padding-bottom: 1.25rem;">
        <div class="ponto-header-top">
            @include('ponto._brand', ['class' => 'ponto-logo'])
            <button type="button" id="presenca-offline-sair" class="rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white">
                Sair
            </button>
        </div>
        <div class="mt-4">
            <p class="text-xs font-bold uppercase tracking-wide text-white/70">Sem internet</p>
            <h1 class="mt-1 text-lg font-bold text-white" id="presenca-offline-nome">Carregando...</h1>
            <p class="mt-1 text-xs text-white/80" id="presenca-offline-meta">Presença na obra · usando dados salvos neste aparelho</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span data-presenca-online-state class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[11px] font-bold text-white">Verificando...</span>
                <span data-presenca-pending-count class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[11px] font-bold text-white">0 pendentes</span>
            </div>
        </div>
    </header>

    <main class="ponto-main" style="gap: 0.75rem;">
        <div id="presenca-obra-pending-alert" hidden class="rounded-xl border border-amber-300 bg-amber-50 p-4 shadow-sm" role="alert">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800">
                    <i data-lucide="wifi-off" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-amber-950">Registros pendentes de envio</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-900" data-pending-alert-text></p>
                    <button
                        type="button"
                        data-presenca-sync
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-800"
                    >
                        <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                        Enviar registros pendentes
                    </button>
                </div>
            </div>
        </div>

        <div id="presenca-obra-feedback" hidden class="ponto-card" role="status">
            <span class="ponto-card-icon ponto-card-icon--success">
                <i data-lucide="circle-check" class="h-5 w-5"></i>
            </span>
            <div class="ponto-card-body">
                <p class="ponto-card-text" data-feedback-text></p>
            </div>
        </div>

        <div id="presenca-offline-empty" hidden class="rounded-xl border border-dashed border-zinc-300 bg-white p-6 text-center text-sm text-brand-gray">
            Não há dados salvos neste aparelho. Conecte-se à internet, entre com matrícula e CPF uma vez e, depois disso, poderá confirmar presença sem internet.
        </div>

        <div id="presenca-offline-app" hidden class="space-y-3">
            <div class="ponto-field-card space-y-3">
                <div>
                    <label for="data" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Data</label>
                    <input type="date" id="data" class="ponto-input">
                </div>
                <div>
                    <label for="centro_custo" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Centro de custo</label>
                    <select id="centro_custo" class="ponto-input"></select>
                </div>
                <div>
                    <label for="busca" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Buscar</label>
                    <input type="search" id="busca" class="ponto-input" placeholder="Nome ou matrícula">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs" id="presenca-offline-totais"></div>

            <div class="mb-3 flex gap-2">
                <button type="button" data-marcar-todos="presente" class="flex-1 rounded-lg border border-emerald-300 bg-emerald-50 px-2 py-2 text-xs font-bold text-emerald-800">
                    Todos presentes
                </button>
                <button type="button" data-marcar-todos="ausente" class="flex-1 rounded-lg border border-amber-300 bg-amber-50 px-2 py-2 text-xs font-bold text-amber-900">
                    Todos ausentes
                </button>
            </div>

            <div id="presenca-offline-lista" class="space-y-2"></div>

            <button type="button" id="presenca-offline-salvar" class="ponto-btn-primary mt-2">
                <span class="ponto-btn-primary-row">
                    <i data-lucide="save" class="h-5 w-5"></i>
                    Salvar confirmação
                </span>
            </button>
        </div>
    </main>
@endsection

@push('scripts')
    @include('presenca-obra.partials.offline-core-script')
    @include('presenca-obra.partials.offline-app-script')
@endpush
