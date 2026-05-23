@extends('layouts.app')

@section('title', 'Selecionar benefícios — extrato - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Extrato de benefícios')

@section('actions')
    <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Benefícios
    </a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-5 overflow-hidden rounded-2xl border border-zinc-200 bg-brand-gray text-white shadow-sm">
        <div class="p-6">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-bold">
                <i data-lucide="list-checks" class="h-3.5 w-3.5"></i>
                Passo 1 de 3
            </div>
            <h2 class="mt-4 text-2xl font-bold">Quais benefícios entram no extrato?</h2>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-white/85">
                Marque os benefícios e confira o <strong>tipo de cálculo</strong> sugerido (pode ajustar).
                O vínculo é pelo <strong>cadastro do benefício</strong> (ID), não pelo texto exato do nome — «Vales Alimentações» funciona se o tipo estiver correto.
            </p>
        </div>
    </section>

    <form method="POST" action="{{ route('rh.beneficios.extrato.config.salvar') }}">
        @csrf
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
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
                    <div class="grid gap-4 p-5 lg:grid-cols-[auto_1fr_1.3fr] lg:items-center" data-linha-beneficio>
                        <label class="flex items-center">
                            <input type="checkbox" name="beneficios[{{ $beneficio->id }}][ativo]" value="1" @checked($marcado) class="h-5 w-5 accent-brand-burgundy" data-beneficio-check>
                        </label>
                        <div>
                            <p class="font-semibold text-brand-black">{{ $beneficio->nome }}</p>
                            <p class="text-xs text-brand-gray">
                                {{ $beneficio->tipo ?: 'Sem tipo' }}
                                · {{ $beneficio->codigo ?: 'sem código' }}
                                · {{ $beneficio->valor ? 'R$ ' . number_format((float) $beneficio->valor, 2, ',', '.') : 'Valor no cadastro' }}
                            </p>
                            @if ($sugereVa)
                                <p class="mt-1 text-[11px] text-brand-burgundy">Sugestão: vale/alimentação</p>
                            @elseif ($sugereCafe)
                                <p class="mt-1 text-[11px] text-brand-burgundy">Sugestão: café da manhã (dias com horas na apuração)</p>
                            @elseif ($sugereWebcard)
                                <p class="mt-1 text-[11px] text-brand-burgundy">Sugestão: WebCard (adiantamento — desconto na folha)</p>
                            @endif
                        </div>
                        <label class="block">
                            <span class="text-[11px] font-bold uppercase text-brand-gray">Tipo de cálculo no extrato</span>
                            <select name="beneficios[{{ $beneficio->id }}][tipo_regra]" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" data-beneficio-tipo @disabled(! $marcado)>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_ASSIDUIDADE }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_ASSIDUIDADE)>Vale / auxílio alimentação (assiduidade)</option>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_CAFE_MANHA }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_CAFE_MANHA)>Café da manhã (dias trabalhados)</option>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_WEBCARD }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_WEBCARD)>WebCard (adiantamento salarial)</option>
                                <option value="{{ \App\Models\BeneficioExtratoRegra::TIPO_VALOR_FIXO }}" @selected($tipoSugerido === \App\Models\BeneficioExtratoRegra::TIPO_VALOR_FIXO)>Valor fixo do cadastro</option>
                            </select>
                        </label>
                        <div class="flex flex-wrap gap-2 lg:col-span-3 lg:col-start-2">
                            @if ($regra?->configurado)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800">Regras configuradas</span>
                            @elseif ($regra?->ativo)
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-800">Configuração pendente</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="p-8 text-center text-sm text-brand-gray">Nenhum benefício ativo cadastrado.</p>
                @endforelse
            </div>
            <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-100 p-5">
                <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm">
                    Continuar para configuração das regras
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
