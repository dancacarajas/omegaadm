@php
    $cfg = $config->toArray();
    $faixasFaltas = $cfg['desconto_faltas'] ?? [];
    $afast = $cfg['afastamento_acidente_trabalho'] ?? [];
    $natal = $cfg['recarga_natal'] ?? [];
    $faixasAtestados = $natal['faixas_atestados'] ?? [];
@endphp

<div id="modal-va-{{ $beneficio->id }}" class="extrato-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="extrato-modal__panel">
        <div class="shrink-0 border-b border-zinc-100 bg-gradient-to-br from-brand-gray to-brand-burgundy/90 px-6 py-5 text-white">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-white/80">Vale / Auxílio Alimentação</p>
                    <h3 class="mt-1 text-xl font-bold">{{ $beneficio->nome }}</h3>
                    <p class="mt-2 text-sm text-white/85">Valor base no cadastro: {{ $beneficio->valor ? 'R$ ' . number_format((float) $beneficio->valor, 2, ',', '.') : 'não informado' }}</p>
                </div>
                <button type="button" data-fechar-modal-va class="rounded-lg bg-white/15 p-2 text-white hover:bg-white/25">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('rh.beneficios.extrato.regras.salvar', $beneficio) }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="extrato-modal__body p-6 space-y-8">
                <section>
                    <h4 class="text-sm font-bold text-brand-black">Vigência</h4>
                    <p class="mt-1 text-xs text-brand-gray">Ano de referência do acordo / ACT aplicado nestas regras.</p>
                    <label class="mt-3 block max-w-xs">
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Ano</span>
                        <input type="number" name="ano_vigencia" value="{{ old('ano_vigencia', $regra->ano_vigencia ?? $config->anoVigencia()) }}" min="2020" max="2100" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                </section>

                <section>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h4 class="text-sm font-bold text-brand-black">Desconto por falta injustificada</h4>
                            <p class="mt-1 text-xs text-brand-gray">Faltas justificadas (ACT / legislação) não entram. O desconto usa a apuração de ponto no período do extrato.</p>
                        </div>
                        <button type="button" data-add-faixa-falta="{{ $beneficio->id }}" class="inline-flex h-9 items-center gap-1 rounded-lg border border-zinc-200 px-3 text-xs font-semibold text-brand-black">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i> Faixa
                        </button>
                    </div>
                    <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200" data-faixas-falta="{{ $beneficio->id }}">
                        <table class="w-full min-w-[420px] text-left text-sm">
                            <thead class="bg-zinc-50 text-xs uppercase text-brand-gray">
                                <tr>
                                    <th class="px-3 py-2">De (faltas)</th>
                                    <th class="px-3 py-2">Até (vazio = ou mais)</th>
                                    <th class="px-3 py-2">Desconto %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($faixasFaltas as $i => $faixa)
                                    <tr>
                                        <td class="px-3 py-2"><input type="number" min="1" name="desconto_faltas[{{ $i }}][de]" value="{{ $faixa['de'] }}" class="h-9 w-20 rounded-lg border border-zinc-200 px-2 text-sm"></td>
                                        <td class="px-3 py-2"><input type="number" min="1" name="desconto_faltas[{{ $i }}][ate]" value="{{ $faixa['ate'] }}" placeholder="∞" class="h-9 w-20 rounded-lg border border-zinc-200 px-2 text-sm"></td>
                                        <td class="px-3 py-2"><input type="number" min="0" max="100" step="0.01" name="desconto_faltas[{{ $i }}][percentual]" value="{{ $faixa['percentual'] }}" class="h-9 w-24 rounded-lg border border-zinc-200 px-2 text-sm"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-[11px] text-brand-gray">Padrão ACT: 1 dia → 20%, 2 dias → 50%, 3+ → 100% (sobre o valor proporcional).</p>
                </section>

                <section class="rounded-xl border border-zinc-100 bg-brand-gray-soft/40 p-4">
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="proporcional_admissao_demissao" value="0">
                        <input type="checkbox" name="proporcional_admissao_demissao" value="1" @checked(old('proporcional_admissao_demissao', $cfg['proporcional_admissao_demissao'] ?? true)) class="h-4 w-4 accent-brand-burgundy">
                        <span class="text-sm font-semibold text-brand-black">Proporcional à admissão ou demissão no mês de pagamento</span>
                    </label>
                    <p class="mt-2 text-xs text-brand-gray">Dias úteis com vínculo ÷ dias úteis do mês na escala.</p>
                </section>

                <section class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                    <h4 class="text-sm font-bold text-blue-900">Afastamento por acidente de trabalho</h4>
                    <p class="mt-1 text-xs text-blue-800">Sem desconto por falta no mês de pagamento, contando os meses de calendário desde o início do afastamento registrado em <strong>Efetivo → Movimentação → Afastamento INSS</strong> com espécie <strong>Acidente de trabalho</strong>.</p>
                    <div class="mt-3 flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 text-sm font-semibold text-brand-black">
                            <input type="hidden" name="afastamento_acidente_trabalho[ativo]" value="0">
                            <input type="checkbox" name="afastamento_acidente_trabalho[ativo]" value="1" @checked(old('afastamento_acidente_trabalho.ativo', $afast['ativo'] ?? true)) class="h-4 w-4 accent-brand-burgundy">
                            Regra ativa
                        </label>
                        <label>
                            <span class="text-[11px] font-bold uppercase text-brand-gray">Meses limite integral</span>
                            <input type="number" min="1" max="24" name="afastamento_acidente_trabalho[meses_limite_integral]" value="{{ old('afastamento_acidente_trabalho.meses_limite_integral', $afast['meses_limite_integral'] ?? 3) }}" class="mt-1 h-10 w-24 rounded-lg border border-zinc-200 px-2 text-sm">
                        </label>
                    </div>
                </section>

                <section class="rounded-xl border border-amber-100 bg-amber-50/40 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h4 class="text-sm font-bold text-amber-950">Recarga extra de Natal</h4>
                            <p class="mt-1 text-xs text-amber-900/90">Valores e faixas configuráveis. Elegibilidade sindical: marque nas observações do vínculo (sindical, SIMETAL, associado).</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-semibold">
                            <input type="hidden" name="recarga_natal[ativo]" value="0">
                            <input type="checkbox" name="recarga_natal[ativo]" value="1" @checked(old('recarga_natal.ativo', $natal['ativo'] ?? true)) class="h-4 w-4 accent-brand-burgundy">
                            Ativa
                        </label>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label>
                            <span class="text-[11px] font-bold uppercase text-brand-gray">Valor integral (R$)</span>
                            <input type="number" step="0.01" min="0" name="recarga_natal[valor_integral]" value="{{ old('recarga_natal.valor_integral', $natal['valor_integral'] ?? 925) }}" class="mt-1 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm">
                        </label>
                        <label>
                            <span class="text-[11px] font-bold uppercase text-brand-gray">Data limite pagamento</span>
                            <input type="date" name="recarga_natal[data_pagamento_limite]" value="{{ old('recarga_natal.data_pagamento_limite', $natal['data_pagamento_limite'] ?? '2025-12-21') }}" class="mt-1 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm">
                        </label>
                        <label>
                            <span class="text-[11px] font-bold uppercase text-brand-gray">Período atestados — início</span>
                            <input type="date" name="recarga_natal[periodo_atestados_inicio]" value="{{ old('recarga_natal.periodo_atestados_inicio', $natal['periodo_atestados_inicio'] ?? '2025-06-20') }}" class="mt-1 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm">
                        </label>
                        <label>
                            <span class="text-[11px] font-bold uppercase text-brand-gray">Período atestados — fim</span>
                            <input type="date" name="recarga_natal[periodo_atestados_fim]" value="{{ old('recarga_natal.periodo_atestados_fim', $natal['periodo_atestados_fim'] ?? '2025-12-20') }}" class="mt-1 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm">
                        </label>
                        <label>
                            <span class="text-[11px] font-bold uppercase text-brand-gray">Perda com 1 falta injustificada (%)</span>
                            <input type="number" min="0" max="100" name="recarga_natal[perda_uma_falta_injustificada_percentual]" value="{{ old('recarga_natal.perda_uma_falta_injustificada_percentual', $natal['perda_uma_falta_injustificada_percentual'] ?? 100) }}" class="mt-1 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm">
                        </label>
                        <label class="flex items-end">
                            <span class="flex items-center gap-2 pb-2 text-sm font-semibold">
                                <input type="hidden" name="recarga_natal[exige_sindicalizado]" value="0">
                                <input type="checkbox" name="recarga_natal[exige_sindicalizado]" value="1" @checked(old('recarga_natal.exige_sindicalizado', $natal['exige_sindicalizado'] ?? true)) class="h-4 w-4 accent-brand-burgundy">
                                Exige sindicalizado/contribuinte
                            </span>
                        </label>
                    </div>
                    <label class="mt-3 block">
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Cargos excluídos (um por linha)</span>
                        <textarea name="recarga_natal[cargos_excluidos_texto]" rows="4" class="mt-1 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm">{{ old('recarga_natal.cargos_excluidos_texto', $natal['cargos_excluidos_texto'] ?? '') }}</textarea>
                    </label>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-brand-gray">Faixas por quantidade de atestados</span>
                        <button type="button" data-add-faixa-atestado="{{ $beneficio->id }}" class="text-xs font-semibold text-brand-burgundy">+ Faixa</button>
                    </div>
                    <div class="mt-2 overflow-x-auto rounded-lg border border-zinc-200 bg-white" data-faixas-atestado="{{ $beneficio->id }}">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 text-xs uppercase text-brand-gray">
                                <tr><th class="px-2 py-2">De</th><th class="px-2 py-2">Até</th><th class="px-2 py-2">% do valor</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($faixasAtestados as $i => $faixa)
                                    <tr>
                                        <td class="px-2 py-1"><input type="number" min="0" name="recarga_natal[faixas_atestados][{{ $i }}][de]" value="{{ $faixa['de'] }}" class="h-8 w-16 rounded border px-1 text-sm"></td>
                                        <td class="px-2 py-1"><input type="number" min="0" name="recarga_natal[faixas_atestados][{{ $i }}][ate]" value="{{ $faixa['ate'] }}" class="h-8 w-16 rounded border px-1 text-sm"></td>
                                        <td class="px-2 py-1"><input type="number" min="0" max="100" name="recarga_natal[faixas_atestados][{{ $i }}][percentual_valor]" value="{{ $faixa['percentual_valor'] }}" class="h-8 w-16 rounded border px-1 text-sm"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <details class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-xs text-brand-gray">
                    <summary class="cursor-pointer font-semibold text-brand-black">Referência — faltas justificadas (Cláusula 49ª ACT)</summary>
                    <ul class="mt-3 list-inside list-disc space-y-1 leading-relaxed">
                        <li>Documentos (RG, CPF, CTPS, etc.) com comprovante em até 5 dias</li>
                        <li>Prova escolar com aviso 48h e comprovação em 4 dias úteis</li>
                        <li>Falecimento de parentes (3 dias + 2 se sepultamento fora)</li>
                        <li>Doença cônjuge/filhos internados</li>
                        <li>Nascimento de filho (5 dias)</li>
                        <li>Casamento (4 dias úteis)</li>
                        <li>Atestado médico válido = justificada (não desconta vale)</li>
                    </ul>
                </details>
            </div>

            <div class="flex shrink-0 justify-end gap-3 border-t border-zinc-100 bg-white px-6 py-4">
                <button type="button" data-fechar-modal-va class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-semibold text-brand-black">Cancelar</button>
                <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar regras {{ $config->anoVigencia() }}
                </button>
            </div>
        </form>
    </div>
</div>

<template id="tpl-faixa-falta-{{ $beneficio->id }}">
    <tr>
        <td class="px-3 py-2"><input type="number" min="1" name="desconto_faltas[__INDEX__][de]" value="1" class="h-9 w-20 rounded-lg border border-zinc-200 px-2 text-sm"></td>
        <td class="px-3 py-2"><input type="number" min="1" name="desconto_faltas[__INDEX__][ate]" class="h-9 w-20 rounded-lg border border-zinc-200 px-2 text-sm"></td>
        <td class="px-3 py-2"><input type="number" min="0" max="100" name="desconto_faltas[__INDEX__][percentual]" value="20" class="h-9 w-24 rounded-lg border border-zinc-200 px-2 text-sm"></td>
    </tr>
</template>
<template id="tpl-faixa-atestado-{{ $beneficio->id }}">
    <tr>
        <td class="px-2 py-1"><input type="number" min="0" name="recarga_natal[faixas_atestados][__INDEX__][de]" value="0" class="h-8 w-16 rounded border px-1 text-sm"></td>
        <td class="px-2 py-1"><input type="number" min="0" name="recarga_natal[faixas_atestados][__INDEX__][ate]" class="h-8 w-16 rounded border px-1 text-sm"></td>
        <td class="px-2 py-1"><input type="number" min="0" max="100" name="recarga_natal[faixas_atestados][__INDEX__][percentual_valor]" value="100" class="h-8 w-16 rounded border px-1 text-sm"></td>
    </tr>
</template>
