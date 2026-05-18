@extends('layouts.ponto-mobile')

@section('title', 'Registrar ponto')

@push('head')
    <style>
        .ponto-header-glow {
            background:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(255, 255, 255, 0.18), transparent),
                linear-gradient(145deg, #6f1731 0%, #4a0f21 55%, #3a0c1a 100%);
        }
        .ponto-clock-glass {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
        }
    </style>
@endpush

@section('content')
    <header class="ponto-header-glow relative overflow-hidden px-5 pb-6 pt-[max(0.75rem,env(safe-area-inset-top))] text-white">
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/5" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-12 h-40 w-40 rounded-full bg-black/10" aria-hidden="true"></div>

        <div class="relative flex items-center justify-between gap-3">
            @include('ponto._brand')
            <form method="POST" action="{{ route('ponto.sair') }}">
                @csrf
                <button
                    type="submit"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white transition active:scale-95 hover:bg-white/20"
                    title="Sair"
                    aria-label="Sair do ponto"
                >
                    <i data-lucide="log-out" class="h-5 w-5"></i>
                </button>
            </form>
        </div>

        <div class="relative mt-5 flex items-center gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-lg font-black ring-2 ring-white/20">
                {{ mb_substr($colaborador->nome, 0, 1) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/60">Colaborador</p>
                <h1 class="truncate text-base font-bold leading-tight">{{ $colaborador->nome }}</h1>
                <p class="mt-0.5 text-xs font-medium text-white/75">Matrícula {{ $colaborador->matricula ?: '—' }}</p>
            </div>
        </div>

        <div class="ponto-clock-glass relative mt-5 rounded-2xl px-4 py-4 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white/55">Horário atual</p>
            <p class="mt-1 text-[2.75rem] font-black leading-none tabular-nums tracking-tight" id="ponto-relogio" aria-live="polite">--:--</p>
            <p class="mt-2 text-sm font-semibold text-white/90">{{ $registro->data?->format('d/m/Y') }}</p>
            <p class="mt-0.5 text-[10px] font-medium text-white/50">Fuso de Brasília</p>
        </div>
    </header>

    <main class="relative -mt-4 flex flex-1 flex-col gap-3.5 rounded-t-[1.75rem] bg-gradient-to-b from-zinc-50 to-zinc-100 px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-5 shadow-[0_-12px_40px_rgba(17,17,17,0.08)]">
        @if (session('success'))
            <div class="flex gap-3 rounded-2xl border border-emerald-200/80 bg-white px-4 py-3.5 text-sm shadow-sm" role="status">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <i data-lucide="circle-check" class="h-5 w-5"></i>
                </span>
                <span class="font-semibold leading-snug text-emerald-950">{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->has('ponto'))
            <div class="flex gap-3 rounded-2xl border border-red-200/80 bg-white px-4 py-3.5 text-sm shadow-sm" role="alert">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700">
                    <i data-lucide="ban" class="h-5 w-5"></i>
                </span>
                <span class="font-semibold leading-snug text-red-950">{{ $errors->first('ponto') }}</span>
            </div>
        @endif

        @if ($bloqueioMotivo)
            <div class="flex gap-3 rounded-2xl border border-amber-200/90 bg-white px-4 py-4 shadow-sm" role="alert">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800">
                    <i data-lucide="calendar-off" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-900">Marcação indisponível</p>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-amber-950">{{ $bloqueioMotivo }}</p>
                </div>
            </div>
        @endif

        @if ($colaborador->horarioEscala)
            <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                        <i data-lucide="calendar-clock" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gray">Escala</p>
                        <p class="font-bold text-brand-black">{{ $colaborador->horarioEscala->nome }}</p>
                        <p class="mt-1.5 text-sm text-brand-gray">
                            Previsto hoje:
                            <span class="font-semibold {{ $diaEscala ? 'text-brand-burgundy' : 'text-brand-gray' }}">
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
            </div>
        @endif

        <section class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-xs font-bold uppercase tracking-wide text-brand-gray">Batidas de hoje</h2>
                @php
                    $totalRegistradas = collect($batidas)->where('registrada', true)->count();
                @endphp
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-[10px] font-bold text-brand-gray">{{ $totalRegistradas }}/{{ count($batidas) }}</span>
            </div>
            <ul class="mt-3 space-y-2">
                @foreach ($batidas as $batida)
                    <li class="flex items-center justify-between gap-3 rounded-xl border px-3 py-3 {{ $batida['registrada'] ? 'border-emerald-200/80 bg-emerald-50/80' : 'border-zinc-100 bg-zinc-50/80' }}">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $batida['registrada'] ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30' : 'bg-white text-zinc-400 ring-1 ring-zinc-200' }}">
                                @if ($batida['registrada'])
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                @else
                                    <i data-lucide="clock" class="h-4 w-4"></i>
                                @endif
                            </span>
                            <span class="min-w-0 text-sm font-semibold text-brand-black">
                                {{ $batida['label'] }}
                                @if (! $batida['registrada'] && ($batida['automatica_escala'] ?? false))
                                    <span class="block text-[10px] font-medium text-brand-gray">Horário da escala</span>
                                @endif
                            </span>
                        </div>
                        <span class="shrink-0 text-sm font-bold tabular-nums {{ $batida['registrada'] ? 'text-emerald-800' : 'text-zinc-400' }}">
                            {{ $batida['hora'] ?? '—' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="mt-auto pt-1">
            @if ($podeRegistrar && $proximaBatida)
                <form method="POST" action="{{ route('ponto.registrar') }}" id="form-registrar-ponto">
                    @csrf
                    <button
                        type="submit"
                        class="group flex min-h-[4.25rem] w-full flex-col items-center justify-center gap-0.5 rounded-2xl bg-brand-burgundy px-6 py-4 text-white shadow-lg shadow-brand-burgundy/30 transition active:scale-[0.98] hover:bg-brand-burgundy-dark disabled:opacity-60"
                    >
                        <span class="flex items-center gap-2 text-lg font-black">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15">
                                <i data-lucide="fingerprint" class="h-5 w-5"></i>
                            </span>
                            Registrar {{ $proximaBatida['label'] }}
                        </span>
                        <span class="text-xs font-medium text-white/75">Toque para marcar agora</span>
                    </button>
                </form>
            @elseif ($proximaBatida === null && ! $bloqueioMotivo)
                <div class="rounded-2xl border border-emerald-200/90 bg-white px-4 py-6 text-center shadow-sm">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <i data-lucide="badge-check" class="h-7 w-7"></i>
                    </span>
                    <p class="mt-3 text-base font-bold text-emerald-950">Jornada concluída</p>
                    <p class="mt-1 text-sm text-emerald-800">Todas as batidas de hoje foram registradas.</p>
                </div>
            @else
                <div class="flex min-h-[4.25rem] flex-col items-center justify-center gap-1 rounded-2xl border border-zinc-200 bg-white px-6 py-4 text-center shadow-sm">
                    <i data-lucide="lock" class="h-6 w-6 text-zinc-400"></i>
                    <span class="text-sm font-bold text-zinc-500">Marcação indisponível</span>
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
                    btn.classList.add('opacity-70');
                }
            });
        })();
    </script>
@endpush
