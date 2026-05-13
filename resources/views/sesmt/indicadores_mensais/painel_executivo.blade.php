@extends('layouts.app')

@section('title', 'SSMA · Indicadores mensais · Painel executivo de SESMT')
@section('eyebrow', 'SSMA · Indicadores mensais')
@section('page-title', 'Painel Executivo de SESMT')

@section('content')
    @php
        use Carbon\Carbon;
        $competenciaRotulo = Carbon::parse($competenciaYm.'-01')->format('m/Y');
        $periodoRotulo = $periodoInicio->format('d/m').' a '.$periodoFim->format('d/m');
        $dataLimiteRotulo = $periodoFim->format('d/m/Y');
        $cartoesPainel = $cartoesPainel ?? [];
        $visaoProativos = (int) ($visaoProativos ?? 0);
        $visaoReativos = (int) ($visaoReativos ?? 0);
        $visaoConformidade = (int) ($visaoConformidade ?? 0);
        $desempenhoGeralLabel = $desempenhoGeralLabel ?? '—';
        $leituraExecutiva = $leituraExecutiva ?? '';
        $pontosAtencao = $pontosAtencao ?? [];
    @endphp

    <div class="mb-8 max-w-full rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
        <div id="sesmt-card-painel-executivo" class="max-w-full overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
            <div class="flex min-w-0 flex-col gap-8 border-b border-zinc-100 bg-gradient-to-br from-white to-zinc-50/60 px-6 py-8 sm:px-8 xl:flex-row xl:items-start xl:justify-between xl:gap-10">
                <div class="min-w-0 flex-1 basis-0">
                    <div class="flex min-w-0 w-full gap-5">
                        <div class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-md ring-4 ring-[#600020]/10">
                            <i data-lucide="hard-hat" class="h-9 w-9" stroke-width="1.5"></i>
                            <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow ring-2 ring-white">
                                <i data-lucide="cross" class="h-3.5 w-3.5 text-[#600020]" stroke-width="2.5"></i>
                            </span>
                        </div>
                        <div class="min-w-0 max-w-2xl flex-1 pt-0.5">
                            <h2 class="text-balance text-2xl font-bold tracking-tight text-zinc-900 sm:text-[1.65rem]">Painel Executivo <span class="whitespace-nowrap">de SESMT</span></h2>
                            <p class="mt-2 max-w-prose text-sm leading-relaxed text-zinc-500">Resumo mensal dos principais indicadores de saúde, segurança e conformidade.</p>
                        </div>
                    </div>
                </div>
                @unless ($semContratosAtivos ?? false)
                    <div class="w-full max-w-full shrink-0 rounded-xl border border-[#E0E0E0] bg-white shadow-sm xl:w-auto xl:max-w-[min(100%,540px)]">
                        <div class="grid grid-cols-2 divide-x divide-[#E0E0E0] sm:grid-cols-4 sm:divide-y-0">
                            <div class="flex items-center gap-3 px-4 py-3.5">
                                <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Contrato</p>
                                    <p class="truncate text-sm font-bold text-[#600020]" title="{{ $contratoLabel }}">{{ $contratoLabel }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 px-4 py-3.5">
                                <i data-lucide="calendar" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Competência</p>
                                    <p class="text-sm font-bold text-[#600020]">{{ $competenciaRotulo }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 border-t border-[#E0E0E0] px-4 py-3.5 sm:border-t-0">
                                <i data-lucide="calendar-range" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Período</p>
                                    <p class="text-sm font-bold text-[#600020]">{{ $periodoRotulo }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 border-t border-[#E0E0E0] px-4 py-3.5 sm:border-t-0">
                                <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0 text-[#600020]" stroke-width="1.5"></i>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#600020]">Data limite</p>
                                    <p class="text-sm font-bold text-[#600020]">{{ $dataLimiteRotulo }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endunless
            </div>

            @if ($semContratosAtivos ?? false)
                <div class="px-8 py-14 text-center">
                    <p class="text-sm font-semibold text-zinc-900">Não há contratos ativos vinculados ao seu acesso.</p>
                    <p class="mt-2 text-sm text-zinc-500">Cadastre contratos ativos ou ajuste permissões para visualizar indicadores de SESMT.</p>
                </div>
            @else
                <form method="get" action="{{ route('sesmt.indicadores-mensais.painel-executivo') }}" class="flex flex-col gap-4 border-b border-zinc-100 bg-white px-6 py-5 sm:flex-row sm:flex-wrap sm:items-end sm:px-8">
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

                <div class="px-6 py-8 sm:px-8 sm:py-10">
                    <div class="flex min-w-0 flex-col gap-8 xl:flex-row xl:items-start xl:gap-10">
                        <aside class="w-full max-w-full shrink-0 rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm xl:w-auto xl:max-w-[20rem] xl:p-6">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#600020] text-white shadow-sm">
                                    <i data-lucide="chart-column-increasing" class="h-5 w-5" stroke-width="1.5"></i>
                                </div>
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <h3 class="text-sm font-bold tracking-tight text-zinc-900">Visão geral do período</h3>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#600020]" aria-hidden="true"></span>
                                <div class="h-px min-w-0 flex-1 bg-[#600020]"></div>
                            </div>
                            <p class="mt-2.5 text-xs font-normal leading-snug text-zinc-700">Resumo dos indicadores por natureza</p>

                            <ul class="mt-6 divide-y divide-[#E8E8E8] overflow-hidden rounded-xl border border-[#E0E0E0] bg-white">
                                <li class="flex items-center gap-3 px-4 py-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                                        <i data-lucide="shield-plus" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1 pr-2">
                                        <p class="text-[11px] font-bold uppercase leading-snug tracking-wide text-[#600020]">Indicadores proativos</p>
                                        <p class="mt-0.5 text-xs font-normal leading-snug text-zinc-600">Foco em prevenção e controle</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-2xl font-bold tabular-nums leading-none text-[#600020] sm:text-[1.65rem]">{{ $visaoProativos }}</p>
                                        <p class="mt-1 text-[11px] font-normal leading-none text-zinc-600">indicadores</p>
                                    </div>
                                </li>
                                <li class="flex items-center gap-3 px-4 py-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                                        <i data-lucide="triangle-alert" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1 pr-2">
                                        <p class="text-[11px] font-bold uppercase leading-snug tracking-wide text-[#600020]">Indicadores reativos</p>
                                        <p class="mt-0.5 text-xs font-normal leading-snug text-zinc-600">Ocorrências e quase acidentes</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-2xl font-bold tabular-nums leading-none text-[#600020] sm:text-[1.65rem]">{{ $visaoReativos }}</p>
                                        <p class="mt-1 text-[11px] font-normal leading-none text-zinc-600">indicadores</p>
                                    </div>
                                </li>
                                <li class="flex items-center gap-3 px-4 py-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f5e8ec]">
                                        <i data-lucide="clipboard-check" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1 pr-2">
                                        <p class="text-[11px] font-bold uppercase leading-snug tracking-wide text-[#600020]">Conformidade</p>
                                        <p class="mt-0.5 text-xs font-normal leading-snug text-zinc-600">Inspeções, treinamentos e ações corretivas</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-2xl font-bold tabular-nums leading-none text-[#600020] sm:text-[1.65rem]">{{ $visaoConformidade }}</p>
                                        <p class="mt-1 text-[11px] font-normal leading-none text-zinc-600">indicadores</p>
                                    </div>
                                </li>
                            </ul>

                            <div class="mt-6 border-t border-dashed border-zinc-200 pt-6">
                                <p class="text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Desempenho geral do período</p>
                                <div class="mt-4 flex items-center justify-center gap-4">
                                    <i data-lucide="shield" class="h-6 w-6 shrink-0 text-[#600020]/70" stroke-width="1.5"></i>
                                    <p class="text-center text-lg font-bold leading-tight tracking-tight text-[#600020] sm:text-xl">{{ $desempenhoGeralLabel }}</p>
                                    @if (str_contains(strtoupper($desempenhoGeralLabel), 'BOM'))
                                        <i data-lucide="circle-check" class="h-6 w-6 shrink-0 text-[#600020]/70" stroke-width="1.5"></i>
                                    @else
                                        <i data-lucide="triangle-alert" class="h-6 w-6 shrink-0 text-[#600020]/70" stroke-width="1.5"></i>
                                    @endif
                                </div>
                            </div>
                        </aside>

                        <div class="min-w-0 flex-1 basis-0">
                            <div class="grid min-w-0 grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
                                @foreach ($cartoesPainel as $card)
                                    <x-sesmt.painel-metric-card
                                        :icon="$card['icon']"
                                        :value="$card['value']"
                                        :label="$card['label']"
                                        class="min-h-[10.25rem] min-w-0 sm:min-h-[10.75rem]"
                                    />
                                @endforeach
                            </div>

                            <div class="mt-10 grid gap-4 lg:grid-cols-2">
                                <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020]/10">
                                            <i data-lucide="lightbulb" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-bold text-zinc-900">Leitura executiva</h4>
                                            <p class="mt-3 text-sm leading-relaxed text-zinc-600">{{ $leituraExecutiva }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-[14px] border border-[#E0E0E0] bg-white p-5 shadow-sm sm:p-6">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#600020]/10">
                                            <i data-lucide="flag" class="h-5 w-5 text-[#600020]" stroke-width="1.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-bold text-zinc-900">Pontos de atenção</h4>
                                            <ul class="mt-3 list-inside list-disc space-y-2 text-sm leading-relaxed text-zinc-600">
                                                @forelse ($pontosAtencao as $ponto)
                                                    <li>{{ $ponto }}</li>
                                                @empty
                                                    <li>Manter rotina de inspeções e treinamentos obrigatórios.</li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4 rounded-b-2xl border-t border-white/10 bg-[#600020] px-6 py-4 text-white sm:px-8">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white/15">
                    <i data-lucide="shield-plus" class="h-5 w-5 text-white" stroke-width="1.5"></i>
                </div>
                <p class="min-w-0 flex-1 text-center text-xs font-bold uppercase tracking-[0.2em] sm:text-sm">Indicadores de SESMT consolidados</p>
                <div class="hidden w-9 shrink-0 sm:block" aria-hidden="true"></div>
            </div>
        </div>
    </div>

    @if (! ($semContratosAtivos ?? false))
        @isset($cardReativos)
            <div class="mb-8 rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
                <div id="sesmt-card-indicadores-reativos" class="overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                    @include('sesmt.indicadores_mensais._card_indicadores_reativos', ['card' => $cardReativos])
                </div>
            </div>
        @endisset
        @isset($cardProativos)
            <div class="mb-8 rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
                <div id="sesmt-card-indicadores-proativos" class="overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                    @include('sesmt.indicadores_mensais._card_indicadores_proativos', ['card' => $cardProativos])
                </div>
            </div>
        @endisset
        @isset($cardTreinamentos)
            <div class="mb-8 rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
                <div id="sesmt-card-treinamentos-integracoes-campanhas" class="overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                    @include('sesmt.indicadores_mensais._card_treinamentos_integracoes_campanhas', ['card' => $cardTreinamentos])
                </div>
            </div>
        @endisset
        @isset($cardInspecoesConformidade)
            <div class="mb-8 rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
                <div id="sesmt-card-inspecoes-auditorias-conformidade" class="overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                    @include('sesmt.indicadores_mensais._card_inspecoes_auditorias_conformidade', ['card' => $cardInspecoesConformidade])
                </div>
            </div>
        @endisset
        @isset($cardDesviosTratativas)
            <div class="mb-8 rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
                <div id="sesmt-card-desvios-notificacoes-tratativas" class="overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                    @include('sesmt.indicadores_mensais._card_desvios_notificacoes_tratativas', ['card' => $cardDesviosTratativas])
                </div>
            </div>
        @endisset
        @isset($cardBoasPraticasKaizen)
            <div class="mb-8 rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
                <div id="sesmt-card-boas-praticas-kaizen-melhorias" class="overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                    @include('sesmt.indicadores_mensais._card_boas_praticas_kaizen_melhorias', ['card' => $cardBoasPraticasKaizen])
                </div>
            </div>
        @endisset
        @isset($cardPlanoAcaoSesmt)
            <div class="mb-8 rounded-2xl bg-zinc-100/95 p-3 shadow-sm sm:p-4">
                <div id="sesmt-card-plano-acao-sesmt" class="overflow-hidden rounded-2xl border border-[#E0E0E0] bg-white shadow-md">
                    @include('sesmt.indicadores_mensais._card_plano_acao_sesmt', ['card' => $cardPlanoAcaoSesmt])
                </div>
            </div>
        @endisset
    @endif
@endsection
