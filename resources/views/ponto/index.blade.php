@extends('layouts.ponto-mobile')

@section('title', 'Registrar ponto')

@section('content')
    <header class="ponto-header">
        <div class="ponto-header-top">
            @include('ponto._brand', ['class' => 'ponto-logo'])
            <form method="POST" action="{{ route('ponto.sair') }}">
                @csrf
                <button type="submit" class="ponto-btn-sair" title="Sair" aria-label="Sair do ponto">
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
            <p class="ponto-clock-label">Horário atual</p>
            <p class="ponto-clock-time" id="ponto-relogio" aria-live="polite">--:--</p>
            <p class="ponto-clock-date">{{ $registro->data?->format('d/m/Y') }}</p>
            <p class="ponto-clock-tz">Fuso de Brasília</p>
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

        @if ($errors->has('ponto'))
            <div class="ponto-card" role="alert">
                <span class="ponto-card-icon ponto-card-icon--error">
                    <i data-lucide="ban" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-text text-red-950">{{ $errors->first('ponto') }}</p>
                </div>
            </div>
        @endif

        @if ($bloqueioMotivo)
            <div class="ponto-card" role="alert">
                <span class="ponto-card-icon ponto-card-icon--warn">
                    <i data-lucide="calendar-off" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-title ponto-card-title--warn">Marcação indisponível</p>
                    <p class="ponto-card-text text-amber-950">{{ $bloqueioMotivo }}</p>
                </div>
            </div>
        @endif

        @if ($colaborador->horarioEscala)
            <div class="ponto-card">
                <span class="ponto-card-icon ponto-card-icon--ok">
                    <i data-lucide="calendar-clock" class="h-5 w-5"></i>
                </span>
                <div class="ponto-card-body">
                    <p class="ponto-card-title text-brand-gray">Escala</p>
                    <p class="font-bold text-brand-black">{{ $colaborador->horarioEscala->nome }}</p>
                    <p class="ponto-card-text text-brand-gray">
                        Previsto hoje:
                        <span class="{{ $diaEscala ? 'ponto-text-burgundy' : '' }}">
                            {{ $diaEscala?->textoGrade() ?? 'Folga / sem jornada' }}
                        </span>
                    </p>
                    @if ($diaEscala && (! empty($diaEscala->saida_1) || ! empty($diaEscala->entrada_2)))
                        <p class="mt-2 text-[11px] leading-relaxed text-brand-gray">
                            Intervalo preenchido automaticamente conforme o cadastro de horários.
                        </p>
                    @endif
                </div>
            </div>
        @endif

        <section class="ponto-section">
            <div class="ponto-section-head">
                <h2 class="ponto-section-title">Batidas de hoje</h2>
                @php
                    $totalRegistradas = collect($batidas)->where('registrada', true)->count();
                @endphp
                <span class="ponto-badge">{{ $totalRegistradas }}/{{ count($batidas) }}</span>
            </div>
            <ul class="ponto-batida-list">
                @foreach ($batidas as $batida)
                    <li class="ponto-batida {{ $batida['registrada'] ? 'ponto-batida--done' : '' }}">
                        <div class="ponto-batida-left">
                            <span class="ponto-batida-icon {{ $batida['registrada'] ? 'ponto-batida-icon--done' : '' }}">
                                @if ($batida['registrada'])
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                @else
                                    <i data-lucide="clock" class="h-4 w-4"></i>
                                @endif
                            </span>
                            <span class="ponto-batida-label">
                                {{ $batida['label'] }}
                                @if (! $batida['registrada'] && ($batida['automatica_escala'] ?? false))
                                    <span class="ponto-batida-sublabel">Horário da escala</span>
                                @endif
                            </span>
                        </div>
                        <span class="ponto-batida-hora {{ $batida['registrada'] ? 'ponto-batida-hora--done' : '' }}">
                            {{ $batida['hora'] ?? '—' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="ponto-actions">
            @if ($podeRegistrar && $proximaBatida)
                <form method="POST" action="{{ route('ponto.registrar') }}" id="form-registrar-ponto">
                    @csrf
                    <button type="submit" class="ponto-btn-primary">
                        <span class="ponto-btn-primary-row">
                            <span class="ponto-btn-primary-icon">
                                <i data-lucide="fingerprint" class="h-5 w-5"></i>
                            </span>
                            Registrar {{ $proximaBatida['label'] }}
                        </span>
                        <span class="ponto-btn-primary-hint">Toque para marcar agora</span>
                    </button>
                </form>
            @elseif ($proximaBatida === null && ! $bloqueioMotivo)
                <div class="ponto-card" style="flex-direction: column; align-items: center; text-align: center;">
                    <span class="ponto-card-icon ponto-card-icon--success" style="height: 3.5rem; width: 3.5rem;">
                        <i data-lucide="badge-check" class="h-7 w-7"></i>
                    </span>
                    <p class="mt-3 text-base font-bold text-emerald-950">Jornada concluída</p>
                    <p class="ponto-card-text text-emerald-800">Todas as batidas de hoje foram registradas.</p>
                </div>
            @else
                <div class="ponto-btn-disabled">
                    <i data-lucide="lock" class="h-6 w-6"></i>
                    <span class="text-sm font-bold">Marcação indisponível</span>
                </div>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        (function () {
            const el = document.getElementById('ponto-relogio');
            if (!el) return;

            const tick = () => {
                const now = new Date();
                el.textContent = now.toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });
            };

            tick();
            setInterval(tick, 1000);

            const form = document.getElementById('form-registrar-ponto');
            form?.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                }
            });
        })();
    </script>
@endpush
