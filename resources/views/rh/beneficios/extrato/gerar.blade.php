@extends('layouts.app')

@section('title', 'Gerar extrato de benefícios - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Extrato de benefícios')

@section('actions')
    <a href="{{ route('rh.beneficios.extrato.regras') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="settings-2" class="h-4 w-4 text-brand-burgundy"></i>
        Regras
    </a>
    <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Benefícios
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-900 shadow-sm">
            <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Hero --}}
    <section class="relative mb-6 overflow-hidden rounded-3xl border border-zinc-800/10 bg-gradient-to-br from-brand-gray via-[#3d3d45] to-brand-burgundy shadow-xl shadow-brand-burgundy/10">
        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-1/3 h-48 w-48 rounded-full bg-brand-burgundy/30 blur-3xl"></div>
        <div class="relative p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-white backdrop-blur-sm">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-burgundy text-[10px]">3</span>
                    Passo 3 de 3
                </span>
                <span class="hidden h-4 w-px bg-white/20 sm:block"></span>
                <span class="text-xs font-medium text-white/70">{{ $regras->count() }} benefício(s) no extrato</span>
            </div>
            <h2 class="mt-5 text-2xl font-bold tracking-tight text-white sm:text-3xl">Gerar extrato do colaborador</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/80">
                {{ $regras->map(fn ($r) => $r->beneficio?->nome)->filter()->join(' · ') ?: 'Configure os benefícios no passo anterior.' }}
            </p>
        </div>
    </section>

    {{-- Filtros --}}
    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-bold text-brand-black">Parâmetros da apuração</p>
                    <p class="text-xs text-brand-gray">Selecione colaborador e período, depois calcule</p>
                </div>
            </div>
        </div>
        <form method="GET" action="{{ route('rh.beneficios.extrato.gerar') }}" class="p-6">
            <div class="grid gap-5 lg:grid-cols-12 lg:items-end">
                <label class="space-y-2 lg:col-span-5">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                        <i data-lucide="user" class="h-3.5 w-3.5"></i>
                        Colaborador
                    </span>
                    <select name="colaborador_id" required class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium text-brand-black outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                        <option value="">Selecione...</option>
                        @foreach ($colaboradores as $c)
                            <option value="{{ $c->id }}" @selected($colaboradorId === $c->id)>
                                {{ $c->nome }}@if ($c->matricula) ({{ $c->matricula }})@endif
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2 lg:col-span-3">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                        <i data-lucide="calendar-range" class="h-3.5 w-3.5"></i>
                        Período inicial
                    </span>
                    <input
                        type="text"
                        name="periodo_inicio"
                        value="{{ $periodoInicio->format('d/m/Y') }}"
                        data-mask="data-br"
                        placeholder="dd/mm/aaaa"
                        maxlength="10"
                        class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10"
                    >
                </label>
                <label class="space-y-2 lg:col-span-3">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                        <i data-lucide="calendar-check" class="h-3.5 w-3.5"></i>
                        Período final
                    </span>
                    <input
                        type="text"
                        name="periodo_fim"
                        value="{{ $periodoFim->format('d/m/Y') }}"
                        data-mask="data-br"
                        placeholder="dd/mm/aaaa"
                        maxlength="10"
                        class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10"
                    >
                </label>
                <div class="lg:col-span-1">
                    <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-burgundy to-[#8b2942] px-4 text-sm font-bold text-white shadow-lg shadow-brand-burgundy/25 transition hover:shadow-xl hover:brightness-105 lg:w-auto lg:min-w-[3.25rem]" title="Calcular extrato">
                        <i data-lucide="calculator" class="h-5 w-5"></i>
                        <span class="lg:hidden">Calcular</span>
                    </button>
                </div>
            </div>
            <p class="mt-4 flex items-start gap-2 rounded-xl bg-zinc-50 px-4 py-3 text-xs leading-relaxed text-brand-gray">
                <i data-lucide="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-burgundy"></i>
                Período de apuração das faltas (vale). O mês de pagamento proporcional segue o mês do período final.
            </p>
        </form>
    </section>

    @if ($extrato !== null && $colaborador !== null)
        @php
            $totalLiquido = (float) $extrato['total'];
            $totalDescontos = (float) ($extrato['total_descontos'] ?? 0);
            $totalBruto = $totalLiquido + $totalDescontos;
        @endphp

        {{-- Cabeçalho do resultado + KPIs --}}
        <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/40 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 bg-gradient-to-br from-white via-zinc-50/50 to-brand-gray-soft/30 px-6 py-6 sm:px-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-burgundy to-[#6b1f35] text-white shadow-lg shadow-brand-burgundy/20">
                            <i data-lucide="user-round" class="h-7 w-7"></i>
                        </span>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Colaborador</p>
                            <h3 class="text-xl font-bold tracking-tight text-brand-black sm:text-2xl">{{ $colaborador->nome }}</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-semibold text-brand-black shadow-sm">
                                    <i data-lucide="calendar" class="h-3.5 w-3.5 text-brand-burgundy"></i>
                                    {{ $extrato['periodo_inicio']->format('d/m/Y') }} – {{ $extrato['periodo_fim']->format('d/m/Y') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-burgundy/20 bg-brand-burgundy/5 px-3 py-1 text-xs font-semibold text-brand-burgundy">
                                    <i data-lucide="wallet" class="h-3.5 w-3.5"></i>
                                    Pagamento {{ $extrato['mes_pagamento']->format('m/Y') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-100 bg-white p-4 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-gray">Valor líquido</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-brand-burgundy">R$ {{ number_format($totalLiquido, 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50/80 to-white p-4 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-800/80">Descontos</p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-rose-700">
                            @if ($totalDescontos > 0)
                                − R$ {{ number_format($totalDescontos, 2, ',', '.') }}
                            @else
                                <span class="text-lg font-semibold text-brand-gray">—</span>
                            @endif
                        </p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50/80 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-gray">Referência bruta</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums text-brand-black">R$ {{ number_format($totalBruto, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Linhas de benefício --}}
            <div class="divide-y divide-zinc-100">
                @foreach ($extrato['linhas'] as $linha)
                    @php
                        $calc = $linha['calculo'];
                        $valorDesconto = (float) ($calc['valor_descontado'] ?? 0);
                        $valorBrutoLinha = array_key_exists('dias_trabalhados', $calc)
                            ? (float) ($calc['valor_bruto_apuracao'] ?? $calc['valor_proporcional'] ?? 0)
                            : (float) ($calc['valor_base'] ?? 0);
                        $valorFinal = (float) ($calc['valor_final'] ?? 0);
                        $icone = match ($linha['regra']->tipo_regra) {
                            \App\Models\BeneficioExtratoRegra::TIPO_CAFE_MANHA => 'coffee',
                            \App\Models\BeneficioExtratoRegra::TIPO_ASSIDUIDADE => 'utensils',
                            default => 'badge-check',
                        };
                    @endphp
                    <article class="p-6 sm:p-8">
                        <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                            {{-- Coluna benefício --}}
                            <div class="lg:col-span-5">
                                <div class="flex items-start gap-4">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-zinc-100 text-brand-burgundy ring-1 ring-zinc-200/80">
                                        <i data-lucide="{{ $icone }}" class="h-6 w-6"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-base font-bold text-brand-black">{{ $linha['beneficio']->nome }}</h4>
                                        <p class="mt-1 inline-flex items-center gap-1 rounded-lg bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-brand-gray">
                                            {{ \App\Models\BeneficioExtratoRegra::rotuloTipo($linha['regra']->tipo_regra) }}
                                        </p>
                                        @if (! empty($calc['detalhe']))
                                            <p class="mt-3 text-xs leading-relaxed text-brand-gray">{{ $calc['detalhe'] }}</p>
                                        @endif
                                        @if (! empty($calc['dias_apuracao']))
                                            <details class="extrato-dias-detalhe mt-4 group">
                                                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-xl border border-brand-burgundy/20 bg-brand-burgundy/5 px-3.5 py-2 text-xs font-semibold text-brand-burgundy transition hover:bg-brand-burgundy/10 marker:content-none [&::-webkit-details-marker]:hidden">
                                                    <i data-lucide="calendar-days" class="h-4 w-4"></i>
                                                    Ver detalhe dos dias
                                                    <span class="rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-bold tabular-nums">{{ count($calc['dias_apuracao']) }}</span>
                                                    <i data-lucide="chevron-down" class="h-4 w-4 transition group-open:rotate-180"></i>
                                                </summary>
                                                <div class="mt-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/50 p-4 shadow-inner">
                                                    @include('rh.beneficios.extrato._detalhe_dias', ['dias' => $calc['dias_apuracao']])
                                                </div>
                                            </details>
                                        @endif
                                        @if (($linha['vinculo'] ?? null) === null && $linha['beneficio'])
                                            <a href="{{ route('rh.beneficios.show', $linha['beneficio']) }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand-burgundy hover:underline">
                                                Vincular colaborador
                                                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Métricas --}}
                            <div class="grid grid-cols-3 gap-3 lg:col-span-7 lg:grid-cols-3">
                                <div class="rounded-2xl border border-zinc-100 bg-zinc-50/50 p-4 text-center">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-gray">Apuração</p>
                                    <div class="mt-2">
                                        @if (array_key_exists('dias_trabalhados', $calc))
                                            <span class="inline-flex items-center justify-center rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-950">
                                                {{ $calc['dias_trabalhados'] }} dia(s)
                                            </span>
                                            @if (($calc['dias_com_justificativa_sem_trabalho'] ?? 0) + ($calc['dias_sem_trabalho'] ?? 0) > 0)
                                                <p class="mt-2 text-[10px] leading-snug text-brand-gray">
                                                    {{ ($calc['dias_com_justificativa_sem_trabalho'] ?? 0) + ($calc['dias_sem_trabalho'] ?? 0) }} s/ pagamento
                                                </p>
                                            @endif
                                        @elseif (array_key_exists('faltas_injustificadas', $calc))
                                            <span class="inline-flex items-center justify-center rounded-full px-3 py-1 text-sm font-bold {{ ($calc['faltas_injustificadas'] ?? 0) > 0 ? 'bg-amber-100 text-amber-950' : 'bg-emerald-100 text-emerald-900' }}">
                                                {{ $calc['faltas_injustificadas'] }} falta(s)
                                            </span>
                                        @else
                                            <span class="text-sm text-brand-gray">—</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-rose-100/80 bg-gradient-to-b from-rose-50/60 to-white p-4 text-center">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-800/70">Desconto</p>
                                    <div class="mt-2">
                                        @if ($valorDesconto > 0)
                                            <p class="text-lg font-black tabular-nums text-rose-700">− {{ number_format($valorDesconto, 2, ',', '.') }}</p>
                                            @if (array_key_exists('valor_descontado_assiduidade', $calc) && ($calc['valor_descontado_proporcional'] ?? 0) > 0 && ($calc['valor_descontado_assiduidade'] ?? 0) > 0)
                                                <p class="mt-1 text-[10px] text-brand-gray">Prop. + assid.</p>
                                            @elseif (array_key_exists('dias_trabalhados', $calc) && ($calc['dias_com_justificativa_sem_trabalho'] ?? 0) + ($calc['dias_sem_trabalho'] ?? 0) > 0)
                                                <p class="mt-1 text-[10px] text-brand-gray">{{ ($calc['dias_com_justificativa_sem_trabalho'] ?? 0) + ($calc['dias_sem_trabalho'] ?? 0) }} dia(s) úteis</p>
                                            @endif
                                        @else
                                            <p class="text-sm font-medium text-brand-gray">—</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-brand-burgundy/15 bg-gradient-to-b from-brand-burgundy/5 to-white p-4 text-center ring-1 ring-brand-burgundy/10">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy/80">Valor</p>
                                    <div class="mt-2">
                                        @if ($valorBrutoLinha > 0 && $valorDesconto > 0)
                                            <p class="text-xs tabular-nums text-brand-gray line-through decoration-rose-300/80">
                                                R$ {{ number_format($valorBrutoLinha, 2, ',', '.') }}
                                            </p>
                                        @endif
                                        <p class="text-xl font-black tabular-nums text-brand-burgundy">R$ {{ number_format($valorFinal, 2, ',', '.') }}</p>
                                        @if (($calc['valor_recarga_natal'] ?? 0) > 0)
                                            <p class="mt-1 text-[10px] font-semibold text-amber-800">+ Natal R$ {{ number_format((float) $calc['valor_recarga_natal'], 2, ',', '.') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Total --}}
            <div class="border-t border-zinc-200 bg-gradient-to-r from-brand-gray via-[#3a3a42] to-brand-burgundy px-6 py-6 sm:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 text-white">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 backdrop-blur-sm">
                            <i data-lucide="receipt" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-white/70">Total do extrato</p>
                            <p class="text-sm text-white/85">{{ count($extrato['linhas']) }} benefício(s) no período</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-end gap-6 sm:gap-10">
                        @if ($totalDescontos > 0)
                            <div class="text-right">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-rose-200">Descontos</p>
                                <p class="text-lg font-bold tabular-nums text-rose-200">− R$ {{ number_format($totalDescontos, 2, ',', '.') }}</p>
                            </div>
                        @endif
                        <div class="text-right">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">Líquido a pagar</p>
                            <p class="text-3xl font-black tabular-nums tracking-tight text-white">R$ {{ number_format($totalLiquido, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @elseif ($colaboradorId > 0)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0"></i>
            Colaborador não encontrado.
        </div>
    @else
        <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50/50 px-8 py-16 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-brand-gray shadow-sm ring-1 ring-zinc-200">
                <i data-lucide="file-search" class="h-8 w-8"></i>
            </span>
            <p class="mt-4 text-sm font-semibold text-brand-black">Nenhum extrato gerado</p>
            <p class="mt-1 text-xs text-brand-gray">Selecione um colaborador e clique em calcular para ver o resultado.</p>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.extrato-dias-detalhe').forEach((el) => {
            el.addEventListener('toggle', () => {
                if (el.open && typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        });
    </script>
@endpush
