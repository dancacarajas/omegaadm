@extends('layouts.app')

@section('title', 'Regras do extrato - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Configurar regras do extrato')

@section('actions')
    @php $todosOk = $regras->every(fn ($r) => $r->configurado); @endphp
    @if ($todosOk)
        <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm">
            <i data-lucide="file-text" class="h-4 w-4"></i>
            <span class="whitespace-nowrap">Gerar extrato</span>
        </a>
    @endif
    <a href="{{ route('rh.beneficios.extrato.config') }}" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        <span class="whitespace-nowrap">Seleção</span>
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mb-5 overflow-hidden rounded-2xl border border-zinc-200 bg-brand-gray text-white shadow-sm">
        <div class="p-6">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-bold">Passo 2 de 3</div>
            <h2 class="mt-4 text-2xl font-bold">Regras por benefício</h2>
            <p class="mt-2 text-sm text-white/85">Abra a configuração de cada benefício. As regras ficam salvas por vigência (ano) e alimentam o cálculo do extrato.</p>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        @foreach ($regras as $regra)
            @php $b = $regra->beneficio; @endphp
            <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-brand-black">{{ $b?->nome }}</h3>
                        <p class="mt-1 text-xs text-brand-gray">
                            {{ \App\Models\BeneficioExtratoRegra::rotuloTipo($regra->tipo_regra) }}
                            @if ($regra->ano_vigencia) · Vigência {{ $regra->ano_vigencia }} @endif
                        </p>
                    </div>
                    @if ($regra->configurado)
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800">OK</span>
                    @else
                        <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-800">Pendente</span>
                    @endif
                </div>
                <div class="mt-4">
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
                    @else
                        <p class="text-sm text-brand-gray">Valor fixo do cadastro — sem parâmetros adicionais.</p>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <div class="mt-6 flex flex-wrap justify-end gap-3">
        @if ($todosOk)
            <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white">
                <span class="whitespace-nowrap">Ir para geração do extrato</span>
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        @else
            <p class="flex-1 text-sm text-brand-gray">Configure todos os benefícios marcados como pendentes para liberar a geração.</p>
        @endif
    </div>
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
    document.querySelectorAll('[data-fechar-modal-va], [data-fechar-modal-cafe]').forEach((btn) => {
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
