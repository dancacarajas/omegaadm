@extends('layouts.app')

@section('title', 'Regras do extrato - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Configurar regras do extrato')

@section('actions')
    @php $todosOk = $regras->every(fn ($r) => $r->configurado); @endphp
    @if ($todosOk)
        <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="calculator" class="h-4 w-4"></i>
            <span class="whitespace-nowrap">Gerar extrato</span>
        </a>
    @endif
    <a href="{{ route('rh.beneficios.extrato.config') }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        <span class="whitespace-nowrap">Seleção</span>
    </a>
@endsection

@section('content')
    @include('rh.beneficios.partials._alerts')

    @php
        $pendentes = $regras->filter(fn ($r) => ! $r->configurado)->count();
        $todosOk = $regras->every(fn ($r) => $r->configurado);
    @endphp

    @include('rh.beneficios.partials._hero', [
        'badgeIcon' => 'settings-2',
        'badgeText' => 'Extrato · Passo 2 de 3',
        'title' => 'Regras por benefício',
        'description' => 'Configure parâmetros de cada benefício (vigência, valores, percentuais). Tudo alimenta o cálculo do extrato no passo seguinte.',
        'stats' => [
            ['label' => 'Benefícios', 'value' => $regras->count()],
            ['label' => 'Pendentes', 'value' => $pendentes],
        ],
    ])

    <section class="grid gap-4 md:grid-cols-2">
        @foreach ($regras as $regra)
            @php $b = $regra->beneficio; @endphp
            <article class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/60 to-white px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="hand-heart" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate text-lg font-bold text-brand-black">{{ $b?->nome }}</h3>
                                <p class="mt-1 text-xs text-brand-gray">
                                    {{ \App\Models\BeneficioExtratoRegra::rotuloTipo($regra->tipo_regra) }}
                                    @if ($regra->ano_vigencia) · {{ $regra->ano_vigencia }} @endif
                                </p>
                            </div>
                        </div>
                        @if ($regra->configurado)
                            <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">OK</span>
                        @else
                            <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-800 ring-1 ring-amber-200">Pendente</span>
                        @endif
                    </div>
                </div>
                <div class="p-5">
                    @if ($regra->tipo_regra === \App\Models\BeneficioExtratoRegra::TIPO_ASSIDUIDADE)
                        <button
                            type="button"
                            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-brand-burgundy/30 bg-brand-burgundy-soft px-4 text-sm font-semibold text-brand-burgundy"
                            data-abrir-modal-va="{{ $b->id }}"
                        >
                            <i data-lucide="settings-2" class="h-4 w-4"></i>
                            Configurar vale / auxílio alimentação
                        </button>
                    @elseif ($regra->tipo_regra === \App\Models\BeneficioExtratoRegra::TIPO_CAFE_MANHA)
                        <button
                            type="button"
                            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-950"
                            data-abrir-modal-cafe="{{ $b->id }}"
                        >
                            <i data-lucide="coffee" class="h-4 w-4"></i>
                            Configurar café da manhã
                        </button>
                    @elseif ($regra->tipo_regra === \App\Models\BeneficioExtratoRegra::TIPO_WEBCARD)
                        <button
                            type="button"
                            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 text-sm font-semibold text-violet-950"
                            data-abrir-modal-webcard="{{ $b->id }}"
                        >
                            <i data-lucide="credit-card" class="h-4 w-4"></i>
                            Configurar WebCard
                        </button>
                    @else
                        <p class="text-sm text-brand-gray">Valor fixo do cadastro — sem parâmetros adicionais.</p>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-sm ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-5">
            @if ($todosOk)
                <p class="text-sm text-brand-gray">Todas as regras configuradas — você pode gerar o extrato.</p>
                <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-12 items-center gap-2 rounded-2xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    Ir para geração do extrato
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            @else
                <p class="flex items-center gap-2 text-sm text-amber-900">
                    <i data-lucide="alert-circle" class="h-4 w-4"></i>
                    Configure {{ $pendentes }} benefício(s) pendente(s) para liberar a geração.
                </p>
            @endif
        </div>
    </section>
@endsection

@push('modals')
    @foreach ($regras as $regra)
        @php $b = $regra->beneficio; @endphp
        @if ($regra->tipo_regra === \App\Models\BeneficioExtratoRegra::TIPO_ASSIDUIDADE && $b)
            @include('rh.beneficios.extrato._modal_vale_alimentacao', [
                'beneficio' => $b,
                'regra' => $regra,
                'config' => $regra->configValeAlimentacao(),
            ])
        @endif
        @if ($regra->tipo_regra === \App\Models\BeneficioExtratoRegra::TIPO_CAFE_MANHA && $b)
            @include('rh.beneficios.extrato._modal_cafe_manha', [
                'beneficio' => $b,
                'regra' => $regra,
                'config' => $regra->configCafeDaManha(),
            ])
        @endif
        @if ($regra->tipo_regra === \App\Models\BeneficioExtratoRegra::TIPO_WEBCARD && $b)
            @include('rh.beneficios.extrato._modal_webcard', [
                'beneficio' => $b,
                'regra' => $regra,
                'config' => $regra->configWebcard(),
            ])
        @endif
    @endforeach
@endpush

@push('scripts')
<script>
    function abrirModalExtrato(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('extrato-modal--open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('extrato-modal-open');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    function fecharModalExtrato(el) {
        const modal = el.closest('[role="dialog"]');
        if (!modal) return;
        modal.classList.remove('extrato-modal--open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.extrato-modal.extrato-modal--open')) {
            document.body.classList.remove('extrato-modal-open');
        }
    }
    document.querySelectorAll('[data-abrir-modal-va]').forEach((btn) => {
        btn.addEventListener('click', () => abrirModalExtrato('modal-va-' + btn.getAttribute('data-abrir-modal-va')));
    });
    document.querySelectorAll('[data-abrir-modal-cafe]').forEach((btn) => {
        btn.addEventListener('click', () => abrirModalExtrato('modal-cafe-' + btn.getAttribute('data-abrir-modal-cafe')));
    });
    document.querySelectorAll('[data-abrir-modal-webcard]').forEach((btn) => {
        btn.addEventListener('click', () => abrirModalExtrato('modal-webcard-' + btn.getAttribute('data-abrir-modal-webcard')));
    });
    document.querySelectorAll('[data-fechar-modal-va], [data-fechar-modal-cafe], [data-fechar-modal-webcard]').forEach((btn) => {
        btn.addEventListener('click', () => fecharModalExtrato(btn));
    });
    document.querySelectorAll('.extrato-modal').forEach((modal) => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) fecharModalExtrato(modal);
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.extrato-modal.extrato-modal--open').forEach((m) => fecharModalExtrato(m));
    });
    document.querySelectorAll('[data-add-faixa-falta]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const beneficioId = btn.getAttribute('data-add-faixa-falta');
            const tbody = document.querySelector('[data-faixas-falta="' + beneficioId + '"] tbody');
            const idx = tbody.querySelectorAll('tr').length;
            const tpl = document.getElementById('tpl-faixa-falta-' + beneficioId);
            if (!tbody || !tpl) return;
            const row = tpl.content.cloneNode(true);
            row.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace('__INDEX__', idx);
            });
            tbody.appendChild(row);
        });
    });
    document.querySelectorAll('[data-add-faixa-atestado]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const beneficioId = btn.getAttribute('data-add-faixa-atestado');
            const tbody = document.querySelector('[data-faixas-atestado="' + beneficioId + '"] tbody');
            const idx = tbody.querySelectorAll('tr').length;
            const tpl = document.getElementById('tpl-faixa-atestado-' + beneficioId);
            if (!tbody || !tpl) return;
            const row = tpl.content.cloneNode(true);
            row.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace('__INDEX__', idx);
            });
            tbody.appendChild(row);
        });
    });
</script>
@endpush
