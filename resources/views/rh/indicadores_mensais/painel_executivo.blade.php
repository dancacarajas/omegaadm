@extends('layouts.app')

@section('title', 'Painel Executivo de RH - Omega286')
@section('eyebrow', 'RH · Indicadores mensais')
@section('page-title', 'Painel Executivo de RH')

@section('content')
    @php
        use Carbon\Carbon;
        $competenciaRotulo = Carbon::parse($competenciaYm.'-01')->format('m/Y');
        $periodoRotulo = $periodoInicio->format('d/m').' a '.$periodoFim->format('d/m');
        $dataLimiteRotulo = $periodoFim->format('d/m/Y');
        $json = fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp

    <div class="mb-8 overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
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
            <form method="get" action="{{ route('rh.indicadores-mensais.painel-executivo') }}" class="flex flex-col gap-4 border-b border-zinc-100 bg-white px-6 py-5 sm:flex-row sm:flex-wrap sm:items-end sm:px-8">
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
                <label class="block w-full sm:w-52">
                    <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-zinc-500">Competência</span>
                    <input type="month" name="competencia" value="{{ $competenciaYm }}" class="h-12 w-full rounded-xl border border-[#E0E0E0] bg-white px-4 text-sm font-semibold text-zinc-900 shadow-sm focus:border-[#600020] focus:outline-none focus:ring-2 focus:ring-[#600020]/15">
                </label>
                <button type="submit" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-[#600020] px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4a0018]">
                    <i data-lucide="refresh-cw" class="h-4 w-4" stroke-width="1.5"></i>
                    Atualizar
                </button>
            </form>

            <div class="border-b border-amber-200/90 bg-amber-50/80 px-6 py-3.5 text-xs leading-relaxed text-amber-950 sm:px-8">
                <span class="font-semibold">Metodologia:</span>
                efetivo por admissão/demissão; vínculo ao contrato por <strong>centro de custo</strong>, <strong>tipo de contrato</strong>, <strong>vaga de recrutamento</strong> (campo contrato da vaga), ou menção numérica em <strong>local de trabalho</strong>; TRIM e equivalência numérica (286 = 0286). Datas SGC de mobilização não alteram esses totais.
            </div>

            <div class="px-6 pb-10 pt-8 sm:px-8">
                <div class="rh-pe-main">
                    {{-- Coluna esquerda: gráfico + faixa circular --}}
                    <div class="flex min-w-0 flex-col gap-8">
                        @if ($chartResumoPeriodo)
                            <x-rh.chart-card
                                eyebrow="Movimentação"
                                title="Resumo do período"
                                subtitle="Efetivo no fechamento anterior ao período, entradas, saídas e posição ao final do intervalo analisado."
                            >
                                <div class="rounded-xl bg-zinc-50/80 p-4">
                                    <div data-apex-chart="#chart-rh-resumo-periodo" class="rh-pe-chart-host min-h-[300px]"></div>
                                </div>
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

                    {{-- Coluna direita: 2x2 + leitura + pontos --}}
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

    @if (! ($semContratosAtivos ?? false) && $chartResumoPeriodo)
        <script type="application/json" id="chart-rh-resumo-periodo">{!! $json($chartResumoPeriodo) !!}</script>
    @endif
@endsection
