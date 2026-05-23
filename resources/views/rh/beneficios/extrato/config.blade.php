@extends('layouts.app')

@section('title', 'Selecionar benefícios — extrato - Omega286')
@section('eyebrow', 'RH / Benefícios / Extrato')
@section('page-title', 'Extrato — seleção')

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
    @include('rh.beneficios.partials._alerts')

    @php
        $marcados = $regrasPorBeneficio->filter(fn ($r) => $r->ativo)->count();
    @endphp

    @include('rh.beneficios.partials._hero', [
        'badgeIcon' => 'list-checks',
        'badgeText' => 'Extrato · Passo 1 de 3',
        'title' => 'Quais benefícios entram no extrato?',
        'description' => 'Marque os benefícios e confira o <strong>tipo de cálculo</strong>. O vínculo é pelo cadastro (ID), não pelo nome exato.',
        'stats' => [
            ['label' => 'Disponíveis', 'value' => $beneficios->count()],
            ['label' => 'Marcados', 'value' => $marcados],
        ],
    ])

    <form method="POST" action="{{ route('rh.beneficios.extrato.config.salvar') }}">
        @csrf
        <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
                @include('rh.beneficios.partials._section_head', [
                    'icon' => 'check-square',
                    'title' => 'Seleção e tipo de cálculo',
                    'subtitle' => 'Benefícios ativos no cadastro',
                ])
            </div>

            <div class="divide-y divide-zinc-100">
                @forelse ($beneficios as $beneficio)
                    @php
                        $regra = $regrasPorBeneficio->get($beneficio->id);
                        $marcado = old("beneficios.{$beneficio->id}.ativo", $regra?->ativo ? '1' : '0') === '1' || old("beneficios.{$beneficio->id}.ativo") === true;
                        $tipoSugerido = old("beneficios.{$beneficio->id}.tipo_regra", $regra?->tipo_regra ?? \App\Models\BeneficioExtratoRegra::inferirTipoRegra($beneficio));
                        $sugereVa = \App\Models\BeneficioExtratoRegra::pareceValeAlimentacao($beneficio);
                        $sugereCafe = \App\Models\BeneficioExtratoRegra::pareceCafeDaManha($beneficio);
                        $sugereWebcard = \App\Models\BeneficioExtratoRegra::pareceWebcard($beneficio);
                    @endphp
                    <div class="grid gap-4 p-5 transition hover:bg-zinc-50/50 lg:grid-cols-[auto_1fr_1.3fr] lg:items-center" data-linha-beneficio>
                        <label class="flex h-12 w-12 items-center justify-center rounded-2xl border border-zinc-200 bg-zinc-50">
                            <input type="checkbox" name="beneficios[{{ $beneficio->id }}][ativo]" value="1" @checked($marcado) class="h-5 w-5 accent-brand-burgundy" data-beneficio-check>
                        </label>
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="hand-heart" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <p class="font-semibold text-brand-black">{{ $beneficio->nome }}</p>
                                <p class="text-xs text-brand-gray">
                                    {{ $beneficio->tipo ?: 'Sem tipo' }} · {{ $beneficio->codigo ?: 'sem código' }}
                                </p>
                                @if ($sugereVa)
                                    <p class="mt-1 text-[11px] font-semibold text-brand-burgundy">Sugestão: vale/alimentação</p>
                                @elseif ($sugereCafe)
                                    <p class="mt-1 text-[11px] font-semibold text-brand-burgundy">Sugestão: café da manhã</p>
                                @elseif ($sugereWebcard)
                                    <p class="mt-1 text-[11px] font-semibold text-brand-burgundy">Sugestão: WebCard</p>
                                @endif
                            </div>
                        </div>
                        <label class="block">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Tipo de cálculo</span>
                            <select name="beneficios[{{ $beneficio->id }}][tipo_regra]" class="mt-2 h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10" data-beneficio-tipo @disabled(! $marcado)>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_ASSIDUIDADE }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_ASSIDUIDADE)>Vale / auxílio alimentação</option>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_CAFE_MANHA }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_CAFE_MANHA)>Café da manhã</option>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_WEBCARD }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_WEBCARD)>WebCard</option>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_VALOR_FIXO }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_VALOR_FIXO)>Valor fixo</option>
                            </select>
                        </label>
                        <div class="flex flex-wrap gap-2 lg:col-span-3 lg:pl-14">
                            @if ($regra?->configurado)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">Regras OK</span>
                            @elseif ($regra?->ativo)
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-800 ring-1 ring-amber-200">Config. pendente</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="p-12 text-center text-sm text-brand-gray">Nenhum benefício ativo cadastrado.</p>
                @endforelse
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-100 bg-zinc-50/50 px-6 py-5">
                <button type="submit" class="inline-flex h-12 items-center gap-2 rounded-2xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    Continuar para regras
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </button>
            </div>
        </section>
    </form>

    @push('scripts')
    <script>
        document.querySelectorAll('[data-linha-beneficio]').forEach((linha) => {
            const check = linha.querySelector('[data-beneficio-check]');
            const select = linha.querySelector('[data-beneficio-tipo]');
            if (!check || !select) return;
            const sync = () => { select.disabled = !check.checked; };
            check.addEventListener('change', sync);
            sync();
        });
    </script>
    @endpush
@endsection
