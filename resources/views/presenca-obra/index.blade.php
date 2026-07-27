@extends('layouts.ponto-mobile')

@section('title', 'Confirmar presença')

@section('content')
    <header class="ponto-header" style="padding-bottom: 1.25rem;">
        <div class="ponto-header-top">
            @include('ponto._brand', ['class' => 'ponto-logo'])
            <form method="POST" action="{{ route('presenca-obra.sair') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white">
                    Sair
                </button>
            </form>
        </div>
        <div class="mt-4">
            <p class="text-xs font-bold uppercase tracking-wide text-white/70">Confirmando como</p>
            <h1 class="mt-1 text-lg font-bold text-white">{{ $confirmador->nome }}</h1>
            <p class="mt-1 text-xs text-white/80">{{ $confirmador->matricula ?: 'Sem matrícula' }} · presença na obra (não é ponto RH)</p>
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
                    <p class="mt-1 text-sm leading-relaxed text-amber-900" data-pending-alert-text>
                        Você tem confirmações salvas neste aparelho aguardando envio ao servidor.
                    </p>
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

        @if ($errors->any())
            <div class="ponto-card" role="alert">
                <span class="ponto-card-icon ponto-card-icon--error">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-text text-red-950">{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <form method="GET" action="{{ route('presenca-obra.index') }}" class="ponto-field-card space-y-3">
            <div>
                <label for="data" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Data</label>
                <input type="date" name="data" id="data" value="{{ $data }}" class="ponto-input" onchange="this.form.submit()">
            </div>
            <div>
                <label for="centro_custo" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Centro de custo</label>
                <select name="centro_custo" id="centro_custo" class="ponto-input" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach ($centrosCusto as $cc)
                        <option value="{{ $cc }}" @selected($centroCusto === $cc)>{{ $cc }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="busca" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-gray">Buscar</label>
                <div class="flex gap-2">
                    <input type="search" name="busca" id="busca" value="{{ $busca }}" class="ponto-input" placeholder="Nome ou matrícula">
                    <button type="submit" class="shrink-0 rounded-lg bg-brand-burgundy px-3 text-sm font-bold text-white">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="grid grid-cols-3 gap-2 text-center text-xs">
            <div class="rounded-xl border border-zinc-200 bg-white px-2 py-3">
                <p class="font-bold text-brand-black">{{ $totais['lista'] }}</p>
                <p class="text-brand-gray">Na lista</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-2 py-3">
                <p class="font-bold text-emerald-800">{{ $totais['presentes'] }}</p>
                <p class="text-emerald-700">Presentes</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-2 py-3">
                <p class="font-bold text-amber-900">{{ $totais['ausentes'] }}</p>
                <p class="text-amber-800">Ausentes</p>
            </div>
        </div>

        <form method="POST" action="{{ route('presenca-obra.salvar') }}" id="form-presenca-obra">
            @csrf
            <input type="hidden" name="data" value="{{ $data }}">
            <input type="hidden" name="busca" value="{{ $busca }}">
            <input type="hidden" name="centro_custo" value="{{ $centroCusto }}">

            <div class="mb-3 flex gap-2">
                <button type="button" data-marcar-todos="presente" class="flex-1 rounded-lg border border-emerald-300 bg-emerald-50 px-2 py-2 text-xs font-bold text-emerald-800">
                    Todos presentes
                </button>
                <button type="button" data-marcar-todos="ausente" class="flex-1 rounded-lg border border-amber-300 bg-amber-50 px-2 py-2 text-xs font-bold text-amber-900">
                    Todos ausentes
                </button>
            </div>

            <div class="space-y-2">
                @forelse ($colaboradores as $colab)
                    @php
                        $atual = $statusDia[$colab->id] ?? old("itens.{$colab->id}.status", '');
                    @endphp
                    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-sm" data-presenca-row>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-brand-black">{{ $colab->nome }}</p>
                                <p class="mt-0.5 text-[11px] text-brand-gray">
                                    {{ $colab->matricula ?: 'Sem matrícula' }}
                                    @if ($colab->centro_custo)
                                        · {{ $colab->centro_custo }}
                                    @endif
                                    @if ($colab->cargo)
                                        · {{ $colab->cargo }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-2 py-2 text-xs font-bold transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-900">
                                <input type="radio" name="itens[{{ $colab->id }}][status]" value="presente" class="sr-only" @checked($atual === 'presente') data-presenca-status>
                                Presente
                            </label>
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-2 py-2 text-xs font-bold transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 has-[:checked]:text-amber-950">
                                <input type="radio" name="itens[{{ $colab->id }}][status]" value="ausente" class="sr-only" @checked($atual === 'ausente') data-presenca-status>
                                Ausente
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-6 text-center text-sm text-brand-gray">
                        Nenhum colaborador ativo encontrado com os filtros atuais.
                    </div>
                @endforelse
            </div>

            @if ($colaboradores->isNotEmpty())
                <button type="submit" class="ponto-btn-primary mt-4">
                    <span class="ponto-btn-primary-row">
                        <i data-lucide="save" class="h-5 w-5"></i>
                        Salvar confirmação
                    </span>
                </button>
                <p class="mt-2 text-center text-[11px] leading-relaxed text-brand-gray">
                    Sem internet? A confirmação fica salva no aparelho e pode ser enviada depois pelo alerta acima.
                </p>
            @endif
        </form>
    </main>
@endsection

@push('scripts')
    @include('presenca-obra.partials.offline-core-script')
    @include('presenca-obra.partials.offline-sync-script')
    <script>
        document.querySelectorAll('[data-marcar-todos]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const status = btn.getAttribute('data-marcar-todos');
                document.querySelectorAll('[data-presenca-row]').forEach((row) => {
                    const radio = row.querySelector('input[value="' + status + '"]');
                    if (radio) radio.checked = true;
                });
            });
        });
    </script>
@endpush
