@extends('layouts.ponto-mobile')

@section('title', 'Novo registro TST')

@section('content')
    <header class="ponto-header">
        <div class="ponto-header-top">
            @include('ponto._brand', ['class' => 'ponto-logo'])
            <form method="POST" action="{{ route('tst-campo.sair') }}">
                @csrf
                <button type="submit" class="ponto-btn-sair" title="Sair" aria-label="Sair">
                    <i data-lucide="log-out" class="h-5 w-5"></i>
                </button>
            </form>
        </div>

        <div class="ponto-user">
            <div class="ponto-user-avatar">{{ mb_substr($colaborador->nome, 0, 1) }}</div>
            <div class="ponto-user-meta">
                <p class="ponto-user-label">Colaborador</p>
                <h1 class="ponto-user-name">{{ $colaborador->nome }}</h1>
                <p class="ponto-user-matricula">Matrícula {{ $colaborador->matricula ?: '—' }}</p>
            </div>
        </div>

        <div class="ponto-clock-card">
            <p class="ponto-clock-label">Registro de campo</p>
            <p class="ponto-clock-date">{{ now()->format('d/m/Y') }}</p>
            <p class="ponto-clock-tz">Segurança do trabalho · CT-286</p>
        </div>
    </header>

    <main class="ponto-main">
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
                    <p class="ponto-card-title ponto-card-title--warn">Verifique os campos</p>
                    <ul class="mt-2 list-inside list-disc text-sm text-red-950">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="ponto-section">
            <div class="ponto-section-head">
                <h2 class="ponto-section-title">Novo registro</h2>
            </div>
            <form method="POST" action="{{ route('tst-campo.store') }}" enctype="multipart/form-data" id="form-tst-campo" class="flex flex-col gap-3">
                @csrf

                <div class="ponto-field-card">
                    <label for="ssma_tst_atividade_id" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">
                        Atividade <span class="font-normal normal-case text-brand-gray">(opcional)</span>
                    </label>
                    <select name="ssma_tst_atividade_id" id="ssma_tst_atividade_id" class="ponto-input">
                        <option value="">Escolher</option>
                        @foreach ($atividades as $atv)
                            <option value="{{ $atv->id }}" @selected(old('ssma_tst_atividade_id') == $atv->id)>{{ $atv->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ponto-field-card">
                    <label for="data" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Data <span class="text-red-600">*</span></label>
                    <input type="date" name="data" id="data" value="{{ old('data', $dataHoje) }}" required class="ponto-input">
                </div>

                <div class="ponto-field-card">
                    <label for="descricao" class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição da atividade <span class="text-red-600">*</span></label>
                    <textarea name="descricao" id="descricao" rows="4" required placeholder="Descreva o que foi realizado..." class="ponto-input min-h-[6rem] resize-y py-3">{{ old('descricao') }}</textarea>
                </div>

                <x-tst-fotos-upload variant="mobile" :obrigatorio="true" />

                <button type="submit" class="ponto-btn-primary">
                    <span class="ponto-btn-primary-row">
                        <i data-lucide="send" class="h-5 w-5"></i>
                        Enviar registro
                    </span>
                    <span class="ponto-btn-primary-hint">Os dados serão enviados ao SSMA</span>
                </button>
            </form>
        </section>

        @if ($recentes->isNotEmpty())
            <section class="ponto-section">
                <div class="ponto-section-head">
                    <h2 class="ponto-section-title">Seus registros recentes</h2>
                    <span class="ponto-badge">{{ $recentes->count() }}</span>
                </div>
                <ul class="ponto-batida-list">
                    @foreach ($recentes as $item)
                        <li class="ponto-batida ponto-batida--done">
                            <div class="ponto-batida-left min-w-0">
                                <span class="ponto-batida-icon ponto-batida-icon--done">
                                    <i data-lucide="camera" class="h-4 w-4"></i>
                                </span>
                                <span class="ponto-batida-label min-w-0">
                                    <span class="block truncate font-semibold">{{ $item->atividade?->nome ?? 'Sem atividade' }}</span>
                                    <span class="ponto-batida-sublabel line-clamp-2">{{ Str::limit($item->descricao, 80) }}@if (($item->fotos_count ?? 0) > 0) · {{ $item->fotos_count }} foto(s)@endif</span>
                                </span>
                            </div>
                            <span class="ponto-batida-hora ponto-batida-hora--done shrink-0 text-right">
                                {{ $item->data->format('d/m/Y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </main>
@endsection
