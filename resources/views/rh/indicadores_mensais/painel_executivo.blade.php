@extends('layouts.app')

@section('title', 'Indicadores mensais · Painel, evolução, turnover e frequência')
@section('eyebrow', 'RH · Indicadores mensais')
@section('page-title', 'Indicadores mensais')

@section('content')
    @php
        use Carbon\Carbon;
        $competenciaRotulo = Carbon::parse($competenciaYm.'-01')->format('m/Y');
        $periodoRotulo = $periodoInicio->format('d/m/Y').' a '.$periodoFim->copy()->startOfDay()->format('d/m/Y');
        $dataLimiteRotulo = $periodoFim->format('d/m/Y');
        $periodoInicioInput = $periodoInicioInput ?? $periodoInicio->toDateString();
        $periodoFimInput = $periodoFimInput ?? $periodoFim->copy()->startOfDay()->toDateString();
        $json = fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $mp = $movimentacoesPainel ?? [];
        $evolucaoTe = $evolucaoTransferencias ?? [
            'entrada' => (int) ($mp['transferencia_entrada'] ?? 0),
            'saida' => (int) ($mp['transferencia_saida'] ?? 0),
        ];
        $evolucaoEntradasTotal = (int) ($resumoEfetivo['admitidos'] ?? 0) + (int) ($evolucaoTe['entrada'] ?? 0);
        $evolucaoSaidasTotal = (int) ($resumoEfetivo['desligados'] ?? 0) + (int) ($evolucaoTe['saida'] ?? 0);
    @endphp

    {{-- Card 01 — Painel Executivo de RH (conteúdo original; filtros aplicam aos dois cards) --}}
    <div id="rh-card-painel-executivo" class="mb-8 overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
        <div class="rh-pe-hero-wrap flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
            <div class="rh-pe-hero min-w-0 flex-1">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                    <i data-lucide="users-round" class="h-8 w-8" stroke-width="1.5"></i>
                </div>
                <div class="rh-pe-hero__text pt-0.5">
                    <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Painel Executivo de RH</h2>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500">Resumo mensal dos principais indicadores de gestão de pessoas</p>
                </div>
            </div>
            @unless ($semContratosAtivos ?? false)
                <div class="grid w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 lg:max-w-[520px] lg:gap-4">
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Contrato</p>
                            <p class="truncate text-sm font-bold text-zinc-900" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Competência</p>
                            <p class="text-sm font-bold text-zinc-900">{{ $competenciaRotulo }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Período</p>
                            <p class="text-sm font-bold text-zinc-900">{{ $periodoRotulo }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Data limite</p>
                            <p class="text-sm font-bold text-zinc-900">{{ $dataLimiteRotulo }}</p>
                        </div>
                    </div>
                </div>
            @endunless
        </div>

        @if ($semContratosAtivos ?? false)
            <div class="px-8 py-14 text-center">
                <p class="text-sm font-semibold text-zinc-900">Não há contratos ativos vinculados ao seu acesso.</p>
                <p class="mt-2 text-sm text-zinc-500">Cadastre contratos ativos ou ajuste permissões para visualizar indicadores.</p>
            </div>
        @else
            <form id="form-painel-indicadores-rh" method="get" action="{{ route('rh.indicadores-mensais.painel-executivo') }}" class="flex flex-col gap-4 border-b border-zinc-100 bg-white px-6 py-5 sm:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                <label class="block min-w-[220px] flex-1">
                    <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-zinc-500">Contrato (centro de custo)</span>
                    <select name="contrato" class="h-12 w-full rounded-xl border border-[#E0E0E0] bg-white px-4 text-sm font-semibold text-zinc-900 shadow-sm focus:border-[#600020] focus:outline-none focus:ring-2 focus:ring-[#600020]/15">
                        @foreach ($contratosAtivos as $c)
                            @php
                                $optVal = trim((string) ($c->centro_custo ?: $c->numero ?: $c->nome));
                            @endphp
                            @if ($optVal !== '')
                                <option value="{{ $optVal }}" @selected($contratoCentro === $optVal)>{{ trim((string) ($c->numero ?: $optVal)) }}</option>
                            @endif
                        @endforeach
                    </select>
                </label>
                <label class="block w-full sm:w-44">
                    <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-zinc-500">Período inicial</span>
                    <input type="date" name="periodo_inicio" value="{{ $periodoInicioInput }}" required class="h-12 w-full rounded-xl border border-[#E0E0E0] bg-white px-4 text-sm font-semibold text-zinc-900 shadow-sm focus:border-[#600020] focus:outline-none focus:ring-2 focus:ring-[#600020]/15">
                </label>
                <label class="block w-full sm:w-44">
                    <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-zinc-500">Período final</span>
                    <input type="date" name="periodo_fim" value="{{ $periodoFimInput }}" required class="h-12 w-full rounded-xl border border-[#E0E0E0] bg-white px-4 text-sm font-semibold text-zinc-900 shadow-sm focus:border-[#600020] focus:outline-none focus:ring-2 focus:ring-[#600020]/15">
                </label>
                <label class="block w-full sm:w-44">
                    <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-zinc-500">Competência (atalho)</span>
                    <input type="month" name="competencia" id="input-competencia-painel-rh" value="{{ $competenciaYm }}" class="h-12 w-full rounded-xl border border-[#E0E0E0] bg-white px-4 text-sm font-semibold text-zinc-900 shadow-sm focus:border-[#600020] focus:outline-none focus:ring-2 focus:ring-[#600020]/15">
                </label>
                <div class="flex flex-wrap gap-2 sm:pb-0.5">
                <button type="submit" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-[#600020] px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4a0018]">
                    <i data-lucide="refresh-cw" class="h-4 w-4" stroke-width="1.5"></i>
                    Atualizar período
                </button>
                <button type="submit" name="usar_mes_competencia" value="1" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-[#600020]/30 bg-white px-5 text-sm font-semibold text-[#600020] shadow-sm transition hover:bg-[#600020]/5">
                    <i data-lucide="calendar" class="h-4 w-4" stroke-width="1.5"></i>
                    Usar mês da competência
                </button>
                </div>
                </div>
                <p class="text-xs leading-relaxed text-zinc-500">
                    Os indicadores de todos os cards abaixo usam o <strong class="text-zinc-700">período inicial e final</strong> informados (efetivo, frequência, absenteísmo, jornada e plano de ação).
                    Use <strong class="text-zinc-700">Usar mês da competência</strong> para o intervalo do 1º ao último dia do mês selecionado.
                </p>
            </form>

            <div class="border-b border-amber-200/90 bg-amber-50/80 px-6 py-3.5 text-xs leading-relaxed text-amber-950 sm:px-8">
                <span class="font-semibold">Metodologia:</span>
                efetivo inicial/final e admissões por data no cadastro; transferências, desligamentos com motivo e demais eventos pelo histórico em <strong>Movimentações de efetivo</strong>. Vínculo ao contrato por <strong>centro de custo</strong>, <strong>tipo de contrato</strong>, <strong>vaga</strong> ou <strong>local de trabalho</strong> (TRIM e equivalência numérica 286 = 0286).
            </div>

            <div class="px-6 pb-10 pt-8 sm:px-8">
                <div class="rh-pe-main">
                    <div class="flex min-w-0 flex-col gap-8">
                        @if ($chartResumoPeriodo)
                            <x-rh.chart-card
                                eyebrow="Movimentação"
                                title="Resumo do período"
                                subtitle="Efetivo inicial, admissões, transferências internas, desligamentos e efetivo final — alinhado ao histórico de Movimentações de efetivo."
                            >
                                <div class="rounded-xl bg-zinc-50/80 p-4">
                                    <div data-apex-chart="#chart-rh-resumo-periodo" class="rh-pe-chart-host min-h-[300px]"></div>
                                </div>
                                @if (! empty($resumoMovimentacoesCard ?? []))
                                    <div class="mt-4 flex flex-wrap gap-2 border-t border-zinc-100 pt-4">
                                        @foreach ($resumoMovimentacoesCard as $chip)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-[#600020]/15 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-800">
                                                <span class="text-zinc-500">{{ $chip['label'] }}</span>
                                                <span class="tabular-nums text-[#600020]">{{ $chip['value'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </x-rh.chart-card>
                        @endif

                        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-8">
                            <div class="flex flex-wrap items-start justify-center gap-y-8 sm:justify-between">
                                @foreach ($indicadoresFaixa ?? [] as $circ)
                                    <x-rh.circle-metric :label="$circ['label']" :value="$circ['value']" :icon="$circ['icon']" />
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-col gap-8">
                        <div class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                            @foreach ($kpisRh ?? [] as $kpi)
                                <x-rh.metric-tile :label="$kpi['title']" :value="$kpi['value']" :icon="$kpi['icon']" />
                            @endforeach
                        </div>

                        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                            <div class="flex gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                    <i data-lucide="lightbulb" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-zinc-900">Leitura executiva</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $leituraExecutiva }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                            <div class="flex gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                    <i data-lucide="flag" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-bold text-zinc-900">Pontos de atenção</h3>
                                    <ul class="mt-3 list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                                        @foreach ($pontosAtencao ?? [] as $ponto)
                                            <li>{{ $ponto }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15">
                    <i data-lucide="shield" class="h-5 w-5" stroke-width="1.5"></i>
                </div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] sm:text-sm">Indicadores de RH consolidados</p>
            </div>
        @endif
    </div>

    @unless ($semContratosAtivos ?? false)
        {{-- Card 02 — Evolução do Efetivo (independente do card 01) --}}
        <div id="rh-card-evolucao-efetivo" class="mb-8 overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
            <div class="rh-pe-hero-wrap flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="rh-pe-hero min-w-0 flex-1">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                        <i data-lucide="users-round" class="h-8 w-8" stroke-width="1.5"></i>
                    </div>
                    <div class="rh-pe-hero__text pt-0.5">
                        <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Evolução do Efetivo</h2>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">Movimentação do quadro de colaboradores no período</p>
                    </div>
                </div>
                <div class="grid w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 lg:max-w-[520px] lg:gap-4">
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Contrato</p>
                            <p class="truncate text-sm font-bold text-zinc-900" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Competência</p>
                            <p class="text-sm font-bold text-zinc-900">{{ $competenciaRotulo }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Período</p>
                            <p class="text-sm font-bold text-zinc-900">{{ $periodoRotulo }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                        <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Data limite</p>
                            <p class="text-sm font-bold text-zinc-900">{{ $dataLimiteRotulo }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <p class="border-b border-zinc-100 px-6 py-2.5 text-[11px] leading-relaxed text-zinc-500 sm:px-8">
                Transferências, desligamentos com motivo e demais eventos vêm do histórico em <strong class="text-zinc-700">Movimentações de efetivo</strong>; admissões e efetivo inicial/final usam datas do cadastro.
            </p>

            <div class="px-6 pb-10 pt-8 sm:px-8">
                <div class="flex w-full min-w-0 flex-col gap-8">
                    <div class="grid w-full min-w-0 gap-8 lg:min-h-0 lg:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)] lg:items-stretch lg:gap-10">
                        <div class="flex min-h-0 min-w-0 flex-col gap-4 lg:h-full">
                            @if ($evolucaoWaterfallLayout ?? null)
                                <x-rh.chart-card
                                    titleIcon="chart-column-increasing"
                                    title="Resumo do período"
                                    subtitle="Efetivo inicial, admissões, transferências, desligamentos e efetivo final (histórico de Movimentações de efetivo)."
                                    class="w-full shrink-0"
                                >
                                    <div class="rounded-xl bg-zinc-50/80 p-4">
                                        <x-rh.waterfall-evolucao-efetivo :layout="$evolucaoWaterfallLayout" />
                                    </div>
                                </x-rh.chart-card>
                            @endif

                            <div class="min-h-0 flex-1 basis-0" aria-hidden="true"></div>

                            <div
                                class="flex w-full shrink-0 flex-col divide-y divide-[#E0E0E0] overflow-hidden rounded-[14px] border border-[#E0E0E0] bg-white shadow-sm sm:flex-row sm:divide-x sm:divide-y-0"
                            >
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="user-plus" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Admissões</p>
                                    <p class="text-2xl font-bold tabular-nums text-[#600020]">{{ (int) ($resumoEfetivo['admitidos'] ?? 0) }}</p>
                                </div>
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="arrow-left-right" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Transferências de entrada</p>
                                    <p class="text-2xl font-bold tabular-nums text-[#600020]">{{ (int) ($evolucaoTe['entrada'] ?? 0) }}</p>
                                </div>
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="user-x" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Desligamentos</p>
                                    <p class="text-2xl font-bold tabular-nums text-[#600020]">{{ (int) ($resumoEfetivo['desligados'] ?? 0) }}</p>
                                </div>
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="arrow-left-right" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Transferências de saída</p>
                                    <p class="text-2xl font-bold tabular-nums text-[#600020]">{{ (int) ($evolucaoTe['saida'] ?? 0) }}</p>
                                </div>
                            </div>

                            @if (! empty($evolucaoMetricasExtras ?? []))
                                <div
                                    class="flex w-full shrink-0 flex-col divide-y divide-[#E0E0E0] overflow-hidden rounded-[14px] border border-dashed border-[#600020]/25 bg-zinc-50/50 shadow-sm sm:flex-row sm:divide-x sm:divide-y-0"
                                >
                                    @foreach ($evolucaoMetricasExtras as $extra)
                                        <div class="flex flex-1 flex-col items-center gap-2 px-3 py-4 text-center sm:px-4">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-100/80 text-[#600020]">
                                                <i data-lucide="{{ $extra['icon'] }}" class="h-4 w-4" stroke-width="1.5"></i>
                                            </div>
                                            <p class="text-[11px] font-bold leading-tight text-zinc-800">{{ $extra['label'] }}</p>
                                            <p class="text-xl font-bold tabular-nums text-[#600020]">{{ $extra['value'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex min-h-0 min-w-0 flex-col gap-6 lg:h-full">
                            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                <x-rh.sidebar-stat-card
                                    label="Efetivo inicial"
                                    :value="(string) ($resumoEfetivo['efetivo_inicial'] ?? 0)"
                                    icon="user"
                                />
                                <x-rh.sidebar-stat-card
                                    label="Entradas"
                                    :value="(string) $evolucaoEntradasTotal"
                                    icon="user-plus"
                                />
                                <x-rh.sidebar-stat-card
                                    label="Saídas"
                                    :value="(string) $evolucaoSaidasTotal"
                                    icon="log-out"
                                />
                                <x-rh.sidebar-stat-card
                                    label="Efetivo final"
                                    :value="(string) ($resumoEfetivo['efetivo_final'] ?? 0)"
                                    icon="users-round"
                                />
                            </div>
                            @isset($variacaoEfetivo)
                                <x-rh.sidebar-stat-card
                                    label="Variação"
                                    :value="$variacaoEfetivo['value']"
                                    :icon="$variacaoEfetivo['icon']"
                                    :compact="str_contains($variacaoEfetivo['value'], '|')"
                                />
                            @endisset

                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="flex gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                        <i data-lucide="lightbulb" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-zinc-900">Leitura executiva</h3>
                                        <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $leituraEvolucaoEfetivo }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="flex gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                        <i data-lucide="triangle-alert" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-sm font-bold text-zinc-900">Pontos de atenção</h3>
                                        <ul class="mt-3 list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                                            @foreach ($pontosAtencaoEvolucao ?? [] as $ponto)
                                                <li>{{ $ponto }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15">
                    <i data-lucide="shield" class="h-5 w-5" stroke-width="1.5"></i>
                </div>
                <p class="text-right text-xs font-bold uppercase tracking-[0.2em] sm:text-sm">Movimentação do efetivo consolidada</p>
            </div>
        </div>
    @endunless

    @unless ($semContratosAtivos ?? false)
        @if ($turnoverMovimentacoes ?? null)
            @php
                $tm = $turnoverMovimentacoes;
            @endphp
            <div id="rh-card-turnover-movimentacoes" class="mb-8 overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                <div class="rh-pe-hero-wrap flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
                    <div class="rh-pe-hero min-w-0 flex-1">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                            <i data-lucide="users-round" class="h-8 w-8" stroke-width="1.5"></i>
                        </div>
                        <div class="rh-pe-hero__text pt-0.5">
                            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Turnover e Movimentações</h2>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-500">Rotatividade e movimentação do quadro de colaboradores no período.</p>
                        </div>
                    </div>
                    <div class="grid w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 lg:max-w-[520px] lg:gap-4">
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Contrato</p>
                                <p class="truncate text-sm font-bold text-zinc-900" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Competência</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $competenciaRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Período</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $periodoRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Data limite</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $dataLimiteRotulo }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="border-b border-zinc-100 px-6 py-2.5 text-[11px] leading-relaxed text-zinc-500 sm:px-8">
                    <strong class="text-zinc-700">Turnover geral</strong> = [(admissões + desligamentos) ÷ 2] ÷ efetivo médio × 100, com efetivo médio = (inicial + final) ÷ 2.
                    Voluntário usa desligamentos com tipo <em>pedido de demissão</em> no histórico. Motivos consolidados vêm de Movimentações de efetivo.
                </p>

                <div class="px-6 pb-10 pt-8 sm:px-8">
                    <div class="grid w-full min-w-0 gap-8 lg:min-h-0 lg:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)] lg:items-stretch lg:gap-10">
                        <div class="flex min-h-0 min-w-0 flex-col gap-4 lg:h-full">
                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="mb-6 flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                        <i data-lucide="chart-column-increasing" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <h3 class="text-lg font-bold tracking-tight text-zinc-900">Movimentações do período</h3>
                                </div>
                                <div class="space-y-5">
                                    @foreach ($tm['movimentacoesBarras'] as $bar)
                                        <div>
                                            <div class="mb-1.5 flex items-center justify-between gap-3 text-xs font-semibold text-zinc-700">
                                                <span>{{ $bar['label'] }}</span>
                                                <span class="tabular-nums text-zinc-900">{{ $bar['value'] }}</span>
                                            </div>
                                            <div class="h-3 w-full overflow-hidden rounded-full bg-rose-100/90">
                                                <div
                                                    class="h-full min-w-[4px] rounded-full transition-all"
                                                    style="width: {{ $bar['pct'] }}%; background-color: {{ $bar['hex'] }}"
                                                ></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-6 flex items-center gap-3 rounded-xl border border-dashed border-[#E0E0E0] bg-zinc-50/80 px-4 py-3 text-sm text-zinc-700">
                                    <i data-lucide="users-round" class="h-5 w-5 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                    <p>
                                        <span class="font-semibold text-zinc-900">Movimentações totais no período</span>
                                        <span class="text-zinc-500"> | </span>
                                        <span class="font-bold text-[#600020]">{{ $tm['totalEventos'] }} eventos</span>
                                    </p>
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 basis-0" aria-hidden="true"></div>

                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="mb-6 flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                        <i data-lucide="bookmark" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <h3 class="text-lg font-bold tracking-tight text-zinc-900">Motivos consolidados</h3>
                                </div>
                                <div class="divide-y divide-[#ECECEC]">
                                    @foreach ($tm['motivos'] as $mot)
                                        <div class="flex items-center gap-4 py-4 first:pt-0 last:pb-0">
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                                <i data-lucide="{{ $mot['icon'] }}" class="h-6 w-6" stroke-width="1.5"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-semibold text-zinc-800">{{ $mot['label'] }}</p>
                                            </div>
                                            <p class="text-2xl font-bold tabular-nums text-[#600020]">{{ $mot['value'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex min-h-0 min-w-0 flex-col gap-6 lg:h-full">
                            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                @foreach ($tm['kpisTurnover'] as $k)
                                    <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-[#E0E0E0] bg-white px-3 py-5 text-center shadow-sm">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                            <i data-lucide="{{ $k['icon'] }}" class="h-5 w-5" stroke-width="1.5"></i>
                                        </div>
                                        <p class="text-[10px] font-bold uppercase leading-tight tracking-wide text-zinc-500">{{ $k['label'] }}</p>
                                        <p class="text-xl font-bold tabular-nums text-zinc-900 sm:text-2xl">{{ $k['value'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="flex gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                        <i data-lucide="lightbulb" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-zinc-900">Leitura executiva</h3>
                                        <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $tm['leitura'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="flex gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                        <i data-lucide="flag" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-sm font-bold text-zinc-900">Pontos de atenção</h3>
                                        <ul class="mt-3 list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                                            @foreach ($tm['pontos'] as $ponto)
                                                <li>{{ $ponto }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-3 border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/15">
                        <i data-lucide="shield" class="h-5 w-5" stroke-width="1.5"></i>
                    </div>
                    <p class="text-center text-xs font-bold uppercase tracking-[0.2em] sm:text-sm">Rotatividade e movimentações monitoradas</p>
                </div>
            </div>
        @endif
    @endunless

    @unless ($semContratosAtivos ?? false)
        @if ($absenteismoFrequencia ?? null)
            @php
                $af = $absenteismoFrequencia;
                $fp = min(100, max(0, (float) $af['freqGeralPct']));
                $ap = min(100, max(0, (float) $af['absMensalPct']));
                $gapStartFreq = 327.0;
                $pinkGapFreqDeg = 360 * (100 - $fp) / 100;
                if ($fp >= 99.999) {
                    $freqDonutBg = 'conic-gradient(#600020 0deg 360deg)';
                } elseif ($fp <= 0.001) {
                    $freqDonutBg = 'conic-gradient(#fce8ef 0deg 360deg)';
                } else {
                    $pinkEndFreq = min(360.0, $gapStartFreq + max(0.2, $pinkGapFreqDeg));
                    $freqDonutBg = sprintf(
                        'conic-gradient(#600020 0deg %.4fdeg, #fce8ef %.4fdeg %.4fdeg, #600020 %.4fdeg 360deg)',
                        $gapStartFreq,
                        $gapStartFreq,
                        $pinkEndFreq,
                        $pinkEndFreq
                    );
                }
                $maroonAbsDeg = 360 * $ap / 100;
                if ($ap <= 0.001) {
                    $absDonutBg = 'conic-gradient(#fce8ef 0deg 360deg)';
                } elseif ($ap >= 99.999) {
                    $absDonutBg = 'conic-gradient(#600020 0deg 360deg)';
                } else {
                    $absDonutBg = sprintf(
                        'conic-gradient(#600020 0deg %.4fdeg, #fce8ef %.4fdeg 360deg)',
                        $maroonAbsDeg,
                        $maroonAbsDeg
                    );
                }
            @endphp
            <div id="rh-card-absenteismo-frequencia" class="mb-8 overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                <div class="rh-pe-hero-wrap flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
                    <div class="rh-pe-hero min-w-0 flex-1">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                            <i data-lucide="calendar-clock" class="h-8 w-8" stroke-width="1.5"></i>
                        </div>
                        <div class="rh-pe-hero__text pt-0.5">
                            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Absenteísmo e Frequência</h2>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-500">Presença da equipe e impacto das ausências no período.</p>
                        </div>
                    </div>
                    <div class="grid w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 lg:max-w-[520px] lg:gap-4">
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Contrato</p>
                                <p class="truncate text-sm font-bold text-zinc-900" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Competência</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $competenciaRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Período</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $periodoRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Data limite</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $dataLimiteRotulo }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="border-b border-zinc-100 px-6 py-2.5 text-[11px] leading-relaxed text-zinc-500 sm:px-8">
                    <strong class="text-zinc-700">Absenteísmo geral</strong> = horas de ausência (justificadas + injustificadas, incluindo atestados) ÷ horas previstas × 100.
                    <strong class="text-zinc-700">Horas previstas</strong> somam a jornada do dia na escala ou padrão de 8 h. Folgas e feriados não entram.
                    Atestados e abonos impactam a operação e entram no indicador; a folha pode abonar sem desconto.
                </p>

                <div class="px-6 pb-10 pt-8 sm:px-8">
                    <div class="grid w-full min-w-0 gap-8 lg:min-h-0 lg:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)] lg:items-stretch lg:gap-10">
                        <div class="flex min-h-0 min-w-0 flex-col gap-4 lg:h-full">
                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="mb-6 flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                        <i data-lucide="chart-column-increasing" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <h3 class="text-lg font-bold tracking-tight text-zinc-900">Ocorrências do período</h3>
                                </div>
                                <div
                                    class="grid items-center gap-x-3 gap-y-3.5 sm:gap-x-4"
                                    style="grid-template-columns: minmax(0, 12.5rem) 1px minmax(0, 1fr); grid-template-rows: repeat(5, minmax(2.5rem, auto));"
                                >
                                    <div class="col-start-2 row-span-5 row-start-1 w-px self-stretch justify-self-center bg-zinc-900" aria-hidden="true"></div>
                                    @foreach ($af['ocorrenciasBarras'] as $bar)
                                        <div
                                            class="col-start-1 self-center text-right text-sm font-medium leading-snug text-zinc-900"
                                            style="grid-row: {{ $loop->iteration }}"
                                        >{{ $bar['label'] }}</div>
                                        <div
                                            class="col-start-3 flex min-w-0 items-center gap-2 self-center"
                                            style="grid-row: {{ $loop->iteration }}"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="h-4 max-w-full rounded-r-full transition-[width] duration-300 sm:h-[1.125rem]"
                                                    style="width: {{ $bar['value'] > 0 ? max(1.5, (float) $bar['pct']) : 0 }}%; min-width: {{ $bar['value'] > 0 ? '4px' : '0' }}; background-color: {{ $bar['hex'] }}"
                                                ></div>
                                            </div>
                                            <span
                                                class="shrink-0 text-base font-bold tabular-nums leading-none"
                                                style="color: {{ $bar['hex'] }}"
                                            >{{ (int) $bar['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 basis-0" aria-hidden="true"></div>

                            <div
                                class="flex w-full shrink-0 flex-col divide-y divide-[#E0E0E0] overflow-hidden rounded-[14px] border border-[#E0E0E0] bg-white shadow-sm sm:flex-row sm:divide-x sm:divide-y-0"
                            >
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="clock" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Horas previstas</p>
                                    <p class="text-[10px] text-zinc-500">Jornada no período</p>
                                    <p class="text-xl font-bold tabular-nums text-[#600020] sm:text-2xl">{{ number_format((float) $af['horasPrevistas'], 1, ',', '.') }}</p>
                                </div>
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="circle-alert" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Horas de ausência</p>
                                    <p class="text-[10px] text-zinc-500">Geral (justif. + injustif.)</p>
                                    <p class="text-xl font-bold tabular-nums text-[#600020] sm:text-2xl">{{ number_format((float) $af['horasAusencia'], 1, ',', '.') }}</p>
                                </div>
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="calendar-x" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Dias falta injustificada</p>
                                    <p class="text-[10px] text-zinc-500">Folha / disciplinar</p>
                                    <p class="text-xl font-bold tabular-nums text-[#600020] sm:text-2xl">{{ (int) $af['diasPerdidos'] }}</p>
                                </div>
                                <div class="flex flex-1 flex-col items-center gap-2 px-3 py-5 text-center sm:px-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-100 text-[#600020]">
                                        <i data-lucide="clipboard-list" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <p class="text-xs font-bold text-zinc-900">Registros de ocorrência</p>
                                    <p class="text-[10px] text-zinc-500">Sem duplicar categorias</p>
                                    <p class="text-xl font-bold tabular-nums text-[#600020] sm:text-2xl">{{ (int) $af['ocorrenciasTotais'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex min-h-0 min-w-0 flex-col gap-6 lg:h-full">
                            <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2">
                                <div class="flex h-full min-h-[11rem] flex-col rounded-xl border border-[#E0E0E0] bg-white p-6 shadow-sm">
                                    <h4 class="text-center text-base font-bold text-zinc-900">Frequência geral</h4>
                                    <div class="mt-5 flex flex-1 items-center justify-center gap-6 sm:gap-8">
                                        <div
                                            class="relative h-[7.25rem] w-[7.25rem] shrink-0 rounded-full p-[2.5px]"
                                            style="background: {{ $freqDonutBg }}"
                                        >
                                            <div class="flex h-full w-full items-center justify-center rounded-full bg-[#F5E6E8]">
                                                <div class="relative flex h-[2.35rem] w-[2.85rem] items-end justify-center text-[#600020]">
                                                    <i data-lucide="users-round" class="h-8 w-8" stroke-width="1.75"></i>
                                                    <span class="absolute -bottom-0.5 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#F5E6E8] ring-[1.5px] ring-[#600020]/25">
                                                        <i data-lucide="circle-check" class="h-2.5 w-2.5" stroke-width="2.5"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-[2.125rem] font-bold leading-none tabular-nums text-[#600020] sm:text-[2.25rem]">{{ $af['freqLabel'] }}</p>
                                    </div>
                                </div>
                                <div class="flex h-full min-h-[11rem] flex-col rounded-xl border border-[#E0E0E0] bg-white p-6 shadow-sm">
                                    <h4 class="text-center text-base font-bold text-zinc-900">Absenteísmo geral</h4>
                                    <p class="mt-1 text-center text-[10px] text-zinc-500">Horas ausência ÷ horas previstas</p>
                                    <div class="mt-5 flex flex-1 items-center justify-center gap-6 sm:gap-8">
                                        <div
                                            class="relative h-[7.25rem] w-[7.25rem] shrink-0 rounded-full p-[2.5px]"
                                            style="background: {{ $absDonutBg }}"
                                        >
                                            <div class="flex h-full w-full items-center justify-center rounded-full bg-[#F5E6E8]">
                                                <div class="relative flex h-9 w-9 items-end justify-center text-[#600020]">
                                                    <i data-lucide="user-round" class="h-8 w-8" stroke-width="1.75"></i>
                                                    <span class="absolute -bottom-0.5 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#F5E6E8] ring-[1.5px] ring-[#600020]/25">
                                                        <i data-lucide="circle-x" class="h-2.5 w-2.5" stroke-width="2.5"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-[2.125rem] font-bold leading-none tabular-nums text-[#600020] sm:text-[2.25rem]">{{ $af['absLabel'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="flex flex-col rounded-xl border border-[#E0E0E0] bg-white px-4 py-6 text-center shadow-sm sm:py-7">
                                    <h4 class="text-center text-sm font-bold text-zinc-900">Absenteísmo justificado</h4>
                                    <div class="mt-4 flex flex-1 flex-col items-center justify-center gap-3">
                                        <p class="text-3xl font-bold tabular-nums leading-none text-[#600020]">{{ $af['absJustificadaLabel'] ?? '—' }}</p>
                                        <p class="text-[10px] text-zinc-500">{{ number_format((float) ($af['horasAusenciaJustificada'] ?? 0), 1, ',', '.') }} h</p>
                                    </div>
                                </div>
                                <div class="flex flex-col rounded-xl border border-[#E0E0E0] bg-white px-4 py-6 text-center shadow-sm sm:py-7">
                                    <h4 class="text-center text-sm font-bold text-zinc-900">Absenteísmo injustificado</h4>
                                    <div class="mt-4 flex flex-1 flex-col items-center justify-center gap-3">
                                        <p class="text-3xl font-bold tabular-nums leading-none text-[#600020]">{{ $af['absInjustificadaLabel'] ?? '—' }}</p>
                                        <p class="text-[10px] text-zinc-500">{{ number_format((float) ($af['horasAusenciaInjustificada'] ?? 0), 1, ',', '.') }} h</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="flex flex-col rounded-xl border border-[#E0E0E0] bg-white px-4 py-6 text-center shadow-sm sm:py-7">
                                    <h4 class="text-center text-sm font-bold text-zinc-900">Presença média</h4>
                                    <div class="mt-4 flex flex-1 flex-col items-center justify-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#F5E6E8] ring-1 ring-rose-200/80 text-[#600020]">
                                            <i data-lucide="users-round" class="h-6 w-6" stroke-width="1.5"></i>
                                        </div>
                                        <p class="text-3xl font-bold tabular-nums leading-none text-[#600020]">{{ $af['presencaMediaLabel'] }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col rounded-xl border border-[#E0E0E0] bg-white px-4 py-6 text-center shadow-sm sm:py-7">
                                    <h4 class="text-center text-sm font-bold text-zinc-900">Impacto operacional</h4>
                                    <div class="mt-4 flex flex-1 flex-col items-center justify-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#F5E6E8] ring-1 ring-rose-200/80 text-[#600020]">
                                            <i data-lucide="shield" class="h-6 w-6" stroke-width="1.5"></i>
                                        </div>
                                        <p class="text-3xl font-bold tabular-nums leading-none text-[#600020]">{{ $af['impactoOperacional'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="flex min-h-0 flex-col rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                            <i data-lucide="lightbulb" class="h-5 w-5" stroke-width="1.5"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-zinc-900">Leitura executiva</h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-zinc-600">{{ $af['leitura'] }}</p>
                                </div>
                                <div class="flex min-h-0 flex-col rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#600020]/20 text-[#600020]">
                                            <i data-lucide="flag" class="h-5 w-5" stroke-width="1.5"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-zinc-900">Pontos de atenção</h3>
                                    </div>
                                    <ul class="list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                                        @foreach ($af['pontos'] as $ponto)
                                            <li>{{ $ponto }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white/20">
                        <i data-lucide="shield" class="h-5 w-5" stroke-width="1.5"></i>
                    </div>
                    <p class="min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.2em] sm:text-sm">Frequência e absenteísmo monitorados</p>
                    <div class="hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
                </div>
            </div>
        @endif
    @endunless

    @unless ($semContratosAtivos ?? false)
        @if ($jornadaPontoHorasExtras ?? null)
            @php $jp = $jornadaPontoHorasExtras; @endphp
            <div id="rh-card-jornada-ponto-horas-extras" class="mb-8 overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                <div class="rh-pe-hero-wrap flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
                    <div class="rh-pe-hero min-w-0 flex-1">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                            <i data-lucide="clock" class="h-7 w-7 shrink-0" stroke-width="1.5"></i>
                            <i data-lucide="user-round" class="-ml-1 h-7 w-7 shrink-0 opacity-95" stroke-width="1.5"></i>
                        </div>
                        <div class="rh-pe-hero__text pt-0.5">
                            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Jornada, Ponto e Horas Extras</h2>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-500">Controle da jornada de trabalho, regularização do ponto e horas extras no período.</p>
                        </div>
                    </div>
                    <div class="grid w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 lg:max-w-[520px] lg:gap-4">
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Contrato</p>
                                <p class="truncate text-sm font-bold text-zinc-900" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Competência</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $competenciaRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Período</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $periodoRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Data limite</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $dataLimiteRotulo }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="border-b border-zinc-100 px-6 py-2.5 text-[11px] leading-relaxed text-zinc-500 sm:px-8">
                    Jornada e horas extras vêm da <strong class="text-zinc-700">apuração de ponto</strong>. <strong class="text-zinc-700">Regularização</strong> = % de dias com jornada prevista tratados (presente, justificado/abono ou falta lançada); incompleto ou dia em branco não conta.
                    <strong class="text-zinc-700">Controle de ponto</strong> usa totais de registos de frequência no período.
                </p>

                <div class="px-6 pb-10 pt-8 sm:px-8">
                    <div class="grid w-full min-w-0 gap-8 lg:min-h-0 lg:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)] lg:items-stretch lg:gap-10">
                        <div class="flex min-h-0 min-w-0 flex-col gap-6 lg:h-full">
                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                            <i data-lucide="chart-column-increasing" class="h-5 w-5" stroke-width="1.5"></i>
                                        </div>
                                        <h3 class="text-lg font-bold tracking-tight text-zinc-900">Horas extras por causa</h3>
                                    </div>
                                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-right shadow-sm">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Total de horas extras</p>
                                        <p class="mt-1 text-2xl font-bold tabular-nums leading-none text-[#600020] sm:text-[1.65rem]">{{ $jp['totalHorasExtrasLabel'] }}</p>
                                    </div>
                                </div>
                                <div
                                    class="grid items-center gap-x-3 gap-y-3.5 sm:gap-x-4"
                                    style="grid-template-columns: minmax(0, 11rem) 1px minmax(0, 1fr); grid-template-rows: repeat(4, minmax(2.5rem, auto));"
                                >
                                    <div class="col-start-2 row-span-4 row-start-1 w-px self-stretch justify-self-center bg-zinc-900" aria-hidden="true"></div>
                                    @foreach ($jp['horasExtrasBarras'] as $bar)
                                        <div
                                            class="col-start-1 self-center text-right text-sm font-medium leading-snug text-zinc-900"
                                            style="grid-row: {{ $loop->iteration }}"
                                        >{{ $bar['label'] }}</div>
                                        <div
                                            class="col-start-3 flex min-w-0 items-center gap-2 self-center"
                                            style="grid-row: {{ $loop->iteration }}"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="h-4 max-w-full rounded-r-full transition-[width] duration-300 sm:h-[1.125rem]"
                                                    style="width: {{ $bar['hours'] > 0 ? max(1.5, (float) $bar['pct']) : 0 }}%; min-width: {{ $bar['hours'] > 0 ? '4px' : '0' }}; background-color: {{ $bar['hex'] }}"
                                                ></div>
                                            </div>
                                            <span
                                                class="shrink-0 text-base font-bold tabular-nums leading-none text-[#600020]"
                                            >{{ $bar['valueLabel'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                <div class="mb-5 flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                        <i data-lucide="clipboard-check" class="h-5 w-5" stroke-width="1.5"></i>
                                    </div>
                                    <h3 class="text-lg font-bold tracking-tight text-zinc-900">Controle de ponto</h3>
                                </div>
                                <div class="flex w-full min-w-0 flex-nowrap items-stretch justify-between gap-0">
                                    @foreach ($jp['pontoFluxo'] as $fluxo)
                                        @if (! $loop->first)
                                            <div class="flex shrink-0 items-center gap-2 px-1 text-zinc-300 sm:px-2" aria-hidden="true">
                                                <span class="h-12 w-px shrink-0 rounded-full bg-zinc-300 sm:h-14"></span>
                                                <i data-lucide="chevron-right" class="h-5 w-5 shrink-0 sm:h-6 sm:w-6" stroke-width="2"></i>
                                            </div>
                                        @endif
                                        <div class="flex min-w-0 flex-1 flex-col items-center justify-center gap-2.5 text-center">
                                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#F5E6E8] text-[#600020] ring-1 ring-rose-200/80">
                                                @switch ($fluxo['kind'])
                                                    @case ('conferidos')
                                                        <i data-lucide="users-round" class="h-7 w-7" stroke-width="1.75"></i>
                                                        @break
                                                    @case ('ocorrencia')
                                                        <div class="relative flex h-[1.85rem] w-[1.35rem] items-end justify-center text-[#600020]">
                                                            <i data-lucide="file-text" class="h-7 w-7" stroke-width="1.75"></i>
                                                            <span class="absolute -bottom-0.5 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#F5E6E8] ring-[1.5px] ring-rose-200/80">
                                                                <i data-lucide="circle-alert" class="h-2.5 w-2.5 text-[#600020]" stroke-width="2.5"></i>
                                                            </span>
                                                        </div>
                                                        @break
                                                    @case ('regularizados')
                                                        <i data-lucide="circle-check" class="h-7 w-7" stroke-width="1.75"></i>
                                                        @break
                                                    @case ('pendentes')
                                                        <div class="relative flex h-[1.85rem] w-[1.35rem] items-end justify-center text-[#600020]">
                                                            <i data-lucide="file-text" class="h-7 w-7" stroke-width="1.75"></i>
                                                            <span class="absolute -bottom-0.5 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#F5E6E8] ring-[1.5px] ring-rose-200/80">
                                                                <i data-lucide="clock" class="h-2.5 w-2.5 text-[#600020]" stroke-width="2.5"></i>
                                                            </span>
                                                        </div>
                                                        @break
                                                    @default
                                                        <i data-lucide="circle" class="h-7 w-7" stroke-width="1.75"></i>
                                                @endswitch
                                            </div>
                                            <p class="text-3xl font-bold tabular-nums leading-none text-[#600020] sm:text-[2rem]">{{ (int) $fluxo['value'] }}</p>
                                            <p class="max-w-[6.5rem] px-0.5 text-xs font-medium leading-snug text-zinc-800 sm:max-w-[7.5rem]">{{ $fluxo['label'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex min-h-0 min-w-0 flex-col gap-6 lg:h-full">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                                @foreach ($jp['kpisJornada'] as $kpi)
                                    <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3.5 shadow-sm sm:px-4 sm:py-4">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#F5E6E8] text-[#600020] ring-1 ring-rose-200/80">
                                            <i data-lucide="{{ $kpi['icon'] }}" class="h-5 w-5" stroke-width="1.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">{{ $kpi['label'] }}</p>
                                            <p class="mt-0.5 truncate text-lg font-bold tabular-nums text-[#600020] sm:text-xl">{{ $kpi['value'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="flex min-h-0 flex-col rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#F5E6E8] text-[#600020] ring-1 ring-rose-200/80">
                                            <i data-lucide="lightbulb" class="h-5 w-5" stroke-width="1.5"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-zinc-900">Leitura executiva</h3>
                                    </div>
                                    <p class="text-sm leading-relaxed text-zinc-600">{{ $jp['leitura'] }}</p>
                                </div>
                                <div class="flex min-h-0 flex-col rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#F5E6E8] text-[#600020] ring-1 ring-rose-200/80">
                                            <i data-lucide="flag" class="h-5 w-5" stroke-width="1.5"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-zinc-900">Pontos de atenção</h3>
                                    </div>
                                    <ul class="list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                                        @foreach ($jp['pontos'] as $ponto)
                                            <li>{{ $ponto }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white/20">
                        <i data-lucide="shield-check" class="h-5 w-5" stroke-width="1.5"></i>
                    </div>
                    <p class="min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.2em] sm:text-sm">Jornada e ponto sob controle</p>
                    <div class="hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
                </div>
            </div>
        @endif
    @endunless

    @unless ($semContratosAtivos ?? false)
        @if ($planoAcaoRh ?? null)
            @php $pa = $planoAcaoRh; @endphp
            <div id="rh-card-plano-acao-rh" class="mb-8 overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                <div class="rh-pe-hero-wrap flex flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 lg:flex-row lg:items-start lg:justify-between">
                    <div class="rh-pe-hero min-w-0 flex-1">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                            <i data-lucide="clipboard-check" class="h-8 w-8" stroke-width="1.5"></i>
                        </div>
                        <div class="rh-pe-hero__text pt-0.5">
                            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Plano de Ação de RH</h2>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-500">Acompanhamento das ações priorizadas para suporte, controle e melhoria da gestão de pessoas.</p>
                        </div>
                    </div>
                    <div class="grid w-full shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 lg:max-w-[520px] lg:gap-4">
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Contrato</p>
                                <p class="truncate text-sm font-bold text-zinc-900" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Competência</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $competenciaRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Período</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $periodoRotulo }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-[#E0E0E0] bg-white px-3 py-3 shadow-sm">
                            <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Data limite</p>
                                <p class="text-sm font-bold text-zinc-900">{{ $dataLimiteRotulo }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="border-b border-zinc-100 px-6 py-2.5 text-[11px] leading-relaxed text-zinc-500 sm:px-8">
                    <strong class="text-zinc-700">Plano sintético</strong> derivado dos indicadores do painel (frequência, ponto, jornada e efetivo). Pode ser substituído por cadastro formal de ações quando existir.
                </p>

                <div class="px-6 pb-10 pt-8 sm:px-8">
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                            <div class="mb-6 flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                    <i data-lucide="chart-column-increasing" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <h3 class="text-lg font-bold tracking-tight text-zinc-900">Resumo do plano</h3>
                            </div>
                            <div class="-mx-1 overflow-x-auto px-1 sm:mx-0 sm:overflow-visible sm:px-0">
                                <div class="flex w-full min-w-[32rem] flex-nowrap items-stretch divide-x divide-zinc-200 sm:min-w-0">
                                @foreach ($pa['resumoPlano'] as $slot)
                                    <div class="flex min-w-0 flex-1 flex-col items-center justify-center gap-2.5 px-2 py-1 text-center first:pl-0 last:pr-0 sm:px-3">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#F5E6E8] text-[#600020] ring-1 ring-rose-200/70 sm:h-[3.25rem] sm:w-[3.25rem]">
                                            @if (($slot['key'] ?? '') === 'atrasadas')
                                                <div class="relative flex h-8 w-8 items-center justify-center">
                                                    <i data-lucide="clock" class="h-7 w-7" stroke-width="1.75"></i>
                                                    <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-[#F5E6E8] ring-[1.5px] ring-rose-200/80">
                                                        <i data-lucide="triangle-alert" class="h-2.5 w-2.5 text-[#600020]" stroke-width="2.25"></i>
                                                    </span>
                                                </div>
                                            @else
                                                <i data-lucide="{{ $slot['icon'] }}" class="h-6 w-6 sm:h-7 sm:w-7" stroke-width="1.75"></i>
                                            @endif
                                        </div>
                                        <p class="text-xs font-medium leading-snug text-zinc-900 sm:text-[13px]">{{ $slot['label'] }}</p>
                                        <p class="text-2xl font-bold tabular-nums leading-none text-[#600020] sm:text-[1.65rem]">{{ $slot['value'] }}</p>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                    <i data-lucide="lightbulb" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <h3 class="text-lg font-bold tracking-tight text-zinc-900">Leitura executiva</h3>
                            </div>
                            <p class="text-sm leading-relaxed text-zinc-600 text-justify">{{ $pa['leitura'] }}</p>
                        </div>

                        <div class="min-w-0 rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7 lg:col-span-1">
                            <div class="mb-4 flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                    <i data-lucide="clipboard-list" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <h3 class="text-lg font-bold tracking-tight text-zinc-900">Plano de ação</h3>
                            </div>
                            <div class="overflow-x-auto rounded-lg border border-zinc-200">
                                <table class="min-w-[640px] w-full border-collapse text-left text-sm">
                                    <thead>
                                        <tr class="bg-[#600020] text-white">
                                            <th class="whitespace-nowrap px-3 py-3 font-bold sm:px-4">Ação</th>
                                            <th class="whitespace-nowrap px-3 py-3 font-bold sm:px-4">Indicador</th>
                                            <th class="whitespace-nowrap px-3 py-3 font-bold sm:px-4">Responsável</th>
                                            <th class="whitespace-nowrap px-3 py-3 font-bold sm:px-4">Prazo</th>
                                            <th class="whitespace-nowrap px-3 py-3 font-bold sm:px-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200">
                                        @foreach ($pa['linhas'] as $idx => $linha)
                                            <tr class="{{ $idx % 2 === 1 ? 'bg-zinc-50/90' : 'bg-white' }}">
                                                <td class="max-w-[14rem] px-3 py-2.5 font-medium text-zinc-900 sm:px-4">{{ $linha['acao'] }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-zinc-700 sm:px-4">{{ $linha['indicador'] }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-zinc-700 sm:px-4">{{ $linha['responsavel'] }}</td>
                                                <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-zinc-700 sm:px-4">{{ $linha['prazo'] }}</td>
                                                <td class="px-3 py-2.5 sm:px-4">
                                                    @switch ($linha['status'])
                                                        @case ('em_andamento')
                                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-950 ring-1 ring-amber-200/90">Em andamento</span>
                                                            @break
                                                        @case ('continuo')
                                                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-950 ring-1 ring-sky-200/90">Contínuo</span>
                                                            @break
                                                        @case ('concluido')
                                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-950 ring-1 ring-emerald-200/90">Concluído</span>
                                                            @break
                                                        @case ('pendente')
                                                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 ring-1 ring-zinc-200/90">Pendente</span>
                                                            @break
                                                        @case ('atrasado')
                                                            <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-950 ring-1 ring-rose-200/90">Atrasado</span>
                                                            @break
                                                        @default
                                                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700">{{ $linha['status'] }}</span>
                                                    @endswitch
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-6 shadow-sm sm:p-7 lg:col-span-1">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm ring-2 ring-[#600020]/15">
                                    <i data-lucide="flag" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <h3 class="text-lg font-bold tracking-tight text-zinc-900">Pontos de atenção</h3>
                            </div>
                            <ul class="list-inside list-disc space-y-2.5 text-sm leading-relaxed text-zinc-600">
                                @foreach ($pa['pontos'] as $ponto)
                                    <li>{{ $ponto }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white/20">
                        <i data-lucide="shield-check" class="h-5 w-5" stroke-width="1.5"></i>
                    </div>
                    <p class="min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.2em] sm:text-sm">Plano de ação de RH monitorado</p>
                    <div class="hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
                </div>
            </div>
        @endif
    @endunless

    @if (! ($semContratosAtivos ?? false) && ($chartResumoPeriodo ?? null))
        <script type="application/json" id="chart-rh-resumo-periodo">{!! $json($chartResumoPeriodo) !!}</script>
    @endif

    @unless ($semContratosAtivos ?? false)
        <script>
            (function () {
                const competencia = document.getElementById('input-competencia-painel-rh');
                const inicio = document.querySelector('#form-painel-indicadores-rh [name="periodo_inicio"]');
                const fim = document.querySelector('#form-painel-indicadores-rh [name="periodo_fim"]');
                if (!competencia || !inicio || !fim) return;

                competencia.addEventListener('change', function () {
                    const ym = competencia.value;
                    if (!ym) return;
                    const [y, m] = ym.split('-').map(Number);
                    const ultimo = new Date(y, m, 0).getDate();
                    inicio.value = ym + '-01';
                    fim.value = ym + '-' + String(ultimo).padStart(2, '0');
                });
            })();
        </script>
    @endunless
@endsection
