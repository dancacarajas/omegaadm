@extends('layouts.ponto-mobile')

@section('title', 'Registrar ponto')

@section('content')
    <header class="bg-gradient-to-br from-brand-burgundy to-brand-burgundy-dark px-5 pb-8 pt-[max(1rem,env(safe-area-inset-top))] text-white">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-wide text-white/70">Colaborador</p>
                <h1 class="truncate text-lg font-black">{{ $colaborador->nome }}</h1>
                <p class="mt-0.5 text-xs text-white/75">{{ $colaborador->matricula ?: 'Sem matrícula' }}</p>
            </div>
            <form method="POST" action="{{ route('ponto.sair') }}">
                @csrf
                <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/25 bg-white/10 text-white" title="Sair">
                    <i data-lucide="log-out" class="h-5 w-5"></i>
                </button>
            </form>
        </div>
        <p class="mt-4 text-center text-4xl font-black tabular-nums tracking-tight" id="ponto-relogio" aria-live="polite">--:--</p>
        <p class="mt-1 text-center text-sm text-white/80">{{ $registro->data?->format('d/m/Y') }}</p>
        <p class="mt-0.5 text-center text-[10px] font-medium text-white/60">Registro no horário de Brasília</p>
    </header>

    <main class="relative -mt-5 flex flex-1 flex-col gap-4 rounded-t-3xl bg-zinc-100 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-5">
        @if (session('success'))
            <div class="flex gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900">
                <i data-lucide="circle-check" class="h-5 w-5 shrink-0 text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->has('ponto'))
            <div class="flex gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-900">
                <i data-lucide="ban" class="h-5 w-5 shrink-0 text-red-600"></i>
                <span>{{ $errors->first('ponto') }}</span>
            </div>
        @endif

        @if ($colaborador->horarioEscala)
            <div class="rounded-2xl border border-zinc-200/80 bg-white px-4 py-3 text-xs text-brand-gray shadow-sm">
                <p class="font-bold text-brand-black">{{ $colaborador->horarioEscala->nome }}</p>
                <p class="mt-1">Previsto hoje: <span class="font-semibold text-brand-burgundy">{{ $diaEscala?->textoGrade() ?? '—' }}</span></p>
                @if ($diaEscala && (! empty($diaEscala->saida_1) || ! empty($diaEscala->entrada_2)))
                    <p class="mt-1 text-[10px] text-brand-gray">Saída e retorno do intervalo usam o horário da escala (automático ao registrar a entrada).</p>
                @endif
            </div>
        @endif

        @if ($bloqueioMotivo)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-950">
                {{ $bloqueioMotivo }}
            </div>
        @endif

        <section class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wide text-brand-gray">Batidas de hoje</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($batidas as $batida)
                    <li class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ $batida['registrada'] ? 'bg-emerald-50' : 'bg-zinc-50' }}">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $batida['registrada'] ? 'bg-emerald-600 text-white' : 'bg-zinc-200 text-zinc-500' }}">
                                @if ($batida['registrada'])
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                @else
                                    <i data-lucide="circle" class="h-4 w-4"></i>
                                @endif
                            </span>
                            <span class="text-sm font-semibold text-brand-black">
                                {{ $batida['label'] }}
                                @if (! $batida['registrada'] && ($batida['automatica_escala'] ?? false))
                                    <span class="ml-1 text-[10px] font-normal text-brand-gray">(escala)</span>
                                @endif
                            </span>
                        </div>
                        <span class="text-sm font-bold tabular-nums {{ $batida['registrada'] ? 'text-emerald-800' : 'text-brand-gray' }}">
                            {{ $batida['hora'] ?? '—' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="mt-auto pt-2">
            @if ($podeRegistrar && $proximaBatida)
                <form method="POST" action="{{ route('ponto.registrar') }}" id="form-registrar-ponto">
                    @csrf
                    <button
                        type="submit"
                        class="flex min-h-[4.5rem] w-full flex-col items-center justify-center gap-1 rounded-3xl bg-brand-burgundy px-6 py-5 text-white shadow-xl shadow-brand-burgundy/35 active:scale-[0.98] disabled:opacity-60"
                    >
                        <span class="flex items-center gap-2 text-lg font-black">
                            <i data-lucide="clock" class="h-6 w-6"></i>
                            Registrar {{ $proximaBatida['label'] }}
                        </span>
                        <span class="text-xs font-medium text-white/80">Toque para marcar agora</span>
                    </button>
                </form>
            @elseif ($proximaBatida === null && ! $bloqueioMotivo)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-5 text-center">
                    <i data-lucide="party-popper" class="mx-auto h-8 w-8 text-emerald-600"></i>
                    <p class="mt-2 text-sm font-bold text-emerald-900">Jornada de hoje concluída</p>
                    <p class="mt-1 text-xs text-emerald-800">Todas as batidas foram registradas.</p>
                </div>
            @else
                <button type="button" disabled class="flex min-h-[4.5rem] w-full flex-col items-center justify-center rounded-3xl bg-zinc-300 px-6 py-5 text-zinc-600">
                    <span class="text-base font-bold">Marcação indisponível</span>
                </button>
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
