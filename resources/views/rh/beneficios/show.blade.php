@extends('layouts.app')

@section('title', 'Gestão do benefício - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Gestão do benefício')

@section('actions')
    @if ($beneficio->requer_controle_adesao)
        <a href="{{ route('rh.beneficios.adesoes.index', ['beneficio_id' => $beneficio->id]) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
            <i data-lucide="clipboard-list" class="h-4 w-4 text-brand-burgundy"></i>
            Solicitações à Matriz
        </a>
    @endif
    <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
        <i data-lucide="calculator" class="h-4 w-4"></i>
        Extrato
    </a>
    <a href="{{ route('rh.beneficios.edit', $beneficio) }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="pencil" class="h-4 w-4"></i>
        Editar
    </a>
    <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $urlGestaoBeneficio = route('rh.beneficios.show', $beneficio);
        $total = $beneficio->colaboradores->count();
        $comDireito = $beneficio->colaboradores->where('tem_direito', true)->count();
        $cartoesPendentes = $beneficio->colaboradores->where('tem_direito', true)->where('cartao_entregue', false)->count();
        $ativos = $beneficio->colaboradores->where('beneficio_ativo', true)->count();
        $requerAdesao = (bool) $beneficio->requer_controle_adesao;
        $adesaoPendentes = $requerAdesao
            ? $beneficio->colaboradores->filter(fn ($v) => $v->adesaoEmAndamento())->count()
            : 0;
        $filtrosAtivos = $busca !== '' || $ordenacao !== 'alfabetica' || $cartao !== 'todos';
        $fluxoAdesao = $beneficio->adesao_automatica_admissao
            ? 'Adesão automática na admissão.'
            : 'Formulário → pedido à Matriz → aviso de coleta (sem previsão) → você retira e entrega o cartão ao colaborador.';
    @endphp

    @include('rh.beneficios.partials._alerts')

    @if ($requerAdesao && is_array($emailMatrizDiagnostico ?? null) && ! ($emailMatrizDiagnostico['pode_enviar'] ?? true))
        <div class="mb-6 rounded-2xl border border-amber-200/80 bg-amber-50 px-5 py-4 text-sm text-amber-950 shadow-sm">
            <p class="flex items-center gap-2 font-bold">
                <i data-lucide="mail-warning" class="h-5 w-5 shrink-0"></i>
                Envio de e-mail à Matriz indisponível
            </p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-amber-900">
                @foreach ($emailMatrizDiagnostico['problemas'] as $problema)
                    <li>{{ $problema }}</li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs">
                <a href="{{ route('configuracoes.email.edit') }}" class="font-bold text-brand-burgundy underline">Abrir Configurações → E-mail</a>
                e use <strong>Enviar teste</strong> para validar o SMTP antes de solicitar à Matriz.
            </p>
        </div>
    @endif

    @include('rh.beneficios.partials._hero', [
        'badgeIcon' => 'hand-heart',
        'badgeText' => $beneficio->tipo ?: 'Benefício',
        'title' => $beneficio->nome,
        'description' => 'Gestão de vínculos, direito ao benefício, cartão e' . ($requerAdesao ? ' fluxo de adesão à Matriz.' : ' ativação.'),
        'heroSlot' => '<span class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white/90"><i data-lucide="building-2" class="h-3.5 w-3.5"></i>' . e($beneficio->fornecedor ?: 'Fornecedor não informado') . '</span><span class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white/90"><i data-lucide="banknote" class="h-3.5 w-3.5"></i>' . ($beneficio->valor ? 'R$ ' . number_format((float) $beneficio->valor, 2, ',', '.') : 'Valor no extrato / regra') . '</span>',
        'stats' => [
            ['label' => 'Vinculados', 'value' => $total],
            ['label' => 'Cartão pendente', 'value' => $cartoesPendentes],
        ],
    ])

    @if ($requerAdesao)
        <p class="mb-6 flex items-start gap-2 rounded-2xl border border-zinc-200/80 bg-zinc-50 px-5 py-4 text-sm text-brand-gray">
            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
            <span>
                <strong class="text-brand-black">Adesão à Matriz:</strong> {{ $fluxoAdesao }}
                @if ($adesaoPendentes > 0)
                    <span class="font-bold text-brand-burgundy"> · {{ $adesaoPendentes }} em andamento neste benefício.</span>
                @endif
            </span>
        </p>
    @endif

    <section class="mb-6 grid grid-cols-2 gap-4 {{ $requerAdesao ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }}">
        <article class="rounded-2xl border border-brand-burgundy/15 bg-white p-5 shadow-sm ring-1 ring-brand-burgundy/5 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="users" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Vinculados</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $total }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-600">
                <i data-lucide="user-check" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Com direito</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $comDireito }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="credit-card" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Cartão pendente</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-burgundy">{{ $cartoesPendentes }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Benefício ativo</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $ativos }}</p>
        </article>
        @if ($requerAdesao)
            <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                </span>
                <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Adesão em andamento</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $adesaoPendentes }}</p>
            </article>
        @endif
    </section>

    {{-- Vincular --}}
    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
            @include('rh.beneficios.partials._section_head', [
                'icon' => 'user-plus',
                'title' => 'Vincular colaborador',
                'subtitle' => 'Adicione quem tem direito a este benefício',
            ])
        </div>
        <form method="POST" action="{{ $urlGestaoBeneficio }}" class="grid gap-4 p-6 lg:grid-cols-12 lg:items-end">
            @csrf
            <label class="space-y-2 lg:col-span-5">
                <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <i data-lucide="user" class="h-3.5 w-3.5"></i>
                    Colaborador
                </span>
                <select name="colaborador_id" required @disabled($colaboradoresDisponiveis->isEmpty()) class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10 disabled:cursor-not-allowed disabled:opacity-60">
                    <option value="">{{ $colaboradoresDisponiveis->isEmpty() ? 'Nenhum colaborador elegível' : 'Selecione...' }}</option>
                    @foreach ($colaboradoresDisponiveis as $colaborador)
                        <option value="{{ $colaborador->id }}">{{ $colaborador->nome }}{{ $colaborador->cargo ? ' — ' . $colaborador->cargo : '' }}</option>
                    @endforeach
                </select>
                @if ($colaboradoresDisponiveis->isEmpty())
                    <p class="mt-1.5 text-[10px] leading-relaxed text-brand-gray">Colaboradores desligados não aparecem nesta lista. Quem já está vinculado também não é exibido.</p>
                @endif
            </label>
            <label class="flex h-12 items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-semibold text-brand-black lg:col-span-2">
                <input type="hidden" name="tem_direito" value="0">
                <input type="checkbox" name="tem_direito" value="1" checked class="h-4 w-4 accent-brand-burgundy">
                Tem direito
            </label>
            <label class="flex h-12 items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-semibold text-brand-black lg:col-span-2">
                <input type="hidden" name="cartao_entregue" value="0">
                <input type="checkbox" name="cartao_entregue" value="1" class="h-4 w-4 accent-brand-burgundy">
                Cartão entregue
            </label>
            <div class="lg:col-span-3">
                <button type="submit" @disabled($colaboradoresDisponiveis->isEmpty()) class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:opacity-50">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Vincular
                </button>
            </div>
        </form>
    </section>

    {{-- Listagem --}}
    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-5">
            @include('rh.beneficios.partials._section_head', [
                'icon' => 'users',
                'title' => 'Acompanhamento por colaborador',
                'subtitle' => $colaboradoresVinculados->total() . ' vinculado(s)' . ($filtrosAtivos ? ' com filtro' : '') . ' · 25 por página',
            ])

            <form method="GET" action="{{ route('rh.beneficios.show', $beneficio) }}" class="mt-5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-2 lg:col-span-4">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="search" class="h-3.5 w-3.5"></i>
                            Buscar
                        </span>
                        <input name="busca" value="{{ $busca }}" placeholder="Nome, matrícula ou cargo…" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="credit-card" class="h-3.5 w-3.5"></i>
                            Cartão
                        </span>
                        <select name="cartao" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="todos" @selected($cartao === 'todos')>Todos</option>
                            <option value="entregue" @selected($cartao === 'entregue')>Entregue</option>
                            <option value="pendente" @selected($cartao === 'pendente')>Pendente</option>
                        </select>
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="arrow-up-down" class="h-3.5 w-3.5"></i>
                            Ordenar
                        </span>
                        <select name="ordenacao" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="alfabetica" @selected($ordenacao === 'alfabetica')>A–Z</option>
                            <option value="recentes" @selected($ordenacao === 'recentes')>Mais recentes</option>
                        </select>
                    </label>
                    <div class="flex gap-2 lg:col-span-2">
                        <button type="submit" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                            Filtrar
                        </button>
                        @if ($filtrosAtivos)
                            <a href="{{ route('rh.beneficios.show', $beneficio) }}" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-brand-gray transition hover:border-zinc-300" title="Limpar">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </a>
                        @endif
                    </div>
                </div>
                @if ($filtrosAtivos)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Filtros:</span>
                        @if ($busca !== '')
                            <span class="rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-semibold text-brand-burgundy">{{ $busca }}</span>
                        @endif
                        @if ($cartao === 'pendente')
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">Cartão pendente</span>
                        @elseif ($cartao === 'entregue')
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">Cartão entregue</span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        @if ($colaboradoresVinculados->isNotEmpty())
            <div class="beneficio-vinculo-list-grid hidden border-b border-zinc-200 bg-zinc-50/80 px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-brand-gray sm:grid">
                <span>Colaborador</span>
                <span class="text-center">Vínculo</span>
                <span class="text-right">{{ $requerAdesao ? 'Adesão / prazo' : 'Resumo' }}</span>
                <span class="text-right">Entrega</span>
                <span></span>
            </div>
            <p class="border-b border-zinc-100 px-4 py-2 text-[10px] text-brand-gray sm:hidden">
                <span class="font-semibold text-brand-black">Legenda vínculo:</span>
                escudo = direito · cartão = entregue · selo = ativo
            </p>
            <div class="divide-y divide-zinc-100">
                @foreach ($colaboradoresVinculados as $vinculo)
                    @include('rh.beneficios.partials._vinculo_colaborador_row', [
                        'vinculo' => $vinculo,
                        'urlGestaoBeneficio' => $urlGestaoBeneficio,
                        'requerAdesao' => $requerAdesao,
                        'adesaoService' => $adesaoService,
                        'statusAdesaoOpcoes' => $statusAdesaoOpcoes,
                        'emailMatrizDiagnostico' => $emailMatrizDiagnostico,
                    ])
                @endforeach
            </div>
            @if ($colaboradoresVinculados->hasPages())
                <div class="border-t border-zinc-100 bg-zinc-50/50 px-5 py-4 sm:px-6">
                    <p class="mb-3 text-xs text-brand-gray">
                        Exibindo {{ $colaboradoresVinculados->firstItem() }}–{{ $colaboradoresVinculados->lastItem() }} de {{ $colaboradoresVinculados->total() }}
                    </p>
                    {{ $colaboradoresVinculados->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="users" class="h-8 w-8"></i>
                </div>
                <p class="mt-5 text-lg font-bold text-brand-black">Nenhum colaborador vinculado</p>
                <p class="mt-2 text-sm text-brand-gray">
                    @if ($filtrosAtivos)
                        Nenhum resultado com os filtros atuais.
                    @else
                        Use o formulário acima para vincular o primeiro colaborador.
                    @endif
                </p>
            </div>
        @endif
    </section>

    @include('rh.beneficios.partials._modal_confirmar_email_matriz')
    @include('rh.beneficios.partials._upload_formulario_adesao_script')
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('details[id^="vinculo-"]').forEach((item) => {
        item.removeAttribute('open');
    });

    const alvoHash = window.location.hash?.match(/^#vinculo-\d+$/);
    if (alvoHash) {
        document.querySelector(alvoHash[0])?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function sincronizarCartaoEntregue(input) {
        if (!input.value) {
            return;
        }
        const form = input.closest('form');
        const checkbox = form?.querySelector('[data-cartao-entregue-target]');
        if (checkbox && !checkbox.checked) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    document.querySelectorAll('[data-sync-cartao-entregue]').forEach((input) => {
        input.addEventListener('change', () => sincronizarCartaoEntregue(input));
        input.addEventListener('input', () => sincronizarCartaoEntregue(input));
        if (input.value) {
            sincronizarCartaoEntregue(input);
        }
    });
})();
</script>
@endpush
