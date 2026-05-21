@php $cfg = $config->toArray(); @endphp

<div id="modal-cafe-{{ $beneficio->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
    <div class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl">
        <div class="shrink-0 border-b border-amber-100 bg-gradient-to-br from-amber-50 to-amber-100/80 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-900">Café da manhã — ACT</p>
                    <h3 class="mt-1 text-xl font-bold text-brand-black">{{ $beneficio->nome }}</h3>
                    <p class="mt-2 text-sm text-amber-950/90">
                        Conta apenas dias com <strong>horas trabalhadas na apuração de ponto</strong>.
                        Atestado ou justificativa <strong>sem batida/horas</strong> não gera o valor diário.
                    </p>
                </div>
                <button type="button" data-fechar-modal-cafe class="rounded-lg bg-white/80 p-2 text-brand-gray hover:bg-white">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('rh.beneficios.extrato.regras.salvar', $beneficio) }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="min-h-0 flex-1 overflow-y-auto space-y-6 p-6">
                <section class="grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Ano de vigência</span>
                        <input type="number" name="ano_vigencia" value="{{ old('ano_vigencia', $regra->ano_vigencia ?? $config->anoVigencia()) }}" min="2020" max="2100" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                    <label class="flex items-end">
                        <span class="flex items-center gap-2 pb-2 text-sm font-semibold">
                            <input type="hidden" name="teto_mensal_ativo" value="0">
                            <input type="checkbox" name="teto_mensal_ativo" value="1" @checked(old('teto_mensal_ativo', $cfg['teto_mensal_ativo'] ?? true)) class="h-4 w-4 accent-brand-burgundy">
                            Limitar ao valor mensal cheio
                        </span>
                    </label>
                </section>

                <section class="grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Valor mensal cheio (R$)</span>
                        <input type="number" step="0.01" min="0" name="valor_mensal_cheio" value="{{ old('valor_mensal_cheio', $cfg['valor_mensal_cheio'] ?? 175) }}" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                        <p class="mt-1 text-[11px] text-brand-gray">Padrão ACT: R$ 175,00</p>
                    </label>
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Valor por dia trabalhado (R$)</span>
                        <input type="number" step="0.01" min="0" name="valor_diario" value="{{ old('valor_diario', $cfg['valor_diario'] ?? 7.95) }}" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                        <p class="mt-1 text-[11px] text-brand-gray">Padrão ACT: R$ 7,95</p>
                    </label>
                    <label class="sm:col-span-2">
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Valor dia sáb/dom/feriado trabalhado (opcional)</span>
                        <input type="number" step="0.01" min="0" name="valor_diario_fds_feriado" value="{{ old('valor_diario_fds_feriado', $cfg['valor_diario_fds_feriado'] ?? '') }}" placeholder="Vazio = mesmo valor diário" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                </section>

                <section class="grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Vigência ACT — início</span>
                        <input type="date" name="periodo_vigencia_inicio" value="{{ old('periodo_vigencia_inicio', $cfg['periodo_vigencia_inicio'] ?? '2025-06-01') }}" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Vigência ACT — fim</span>
                        <input type="date" name="periodo_vigencia_fim" value="{{ old('periodo_vigencia_fim', $cfg['periodo_vigencia_fim'] ?? '2026-05-31') }}" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                </section>

                <label class="block max-w-xs">
                    <span class="text-[11px] font-bold uppercase text-brand-gray">Mínimo de minutos trabalhados no dia</span>
                    <input type="number" min="1" name="minutos_minimos_dia_trabalhado" value="{{ old('minutos_minimos_dia_trabalhado', $cfg['minutos_minimos_dia_trabalhado'] ?? 1) }}" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                </label>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-xs leading-relaxed text-brand-gray">
                    <p class="font-semibold text-brand-black">Como o sistema calcula</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        <li>Considera apenas <strong>dias úteis</strong> (segunda a sexta, com jornada na escala). Sábado, domingo e feriados cadastrados <strong>não entram</strong> no cálculo nem nos descontos.</li>
                        <li>Soma o valor diário para cada dia útil do período com <code class="text-[10px]">minutos_trabalhado</code> &gt; 0 no cartão de ponto.</li>
                        <li>Dia útil só com atestado/justificativa e sem horas trabalhadas → <strong>não paga</strong> aquele dia.</li>
                        <li>Valor final = dias × valor diário, limitado ao teto mensal se ativo (~22 dias × 7,95 ≈ 175).</li>
                    </ul>
                </div>
            </div>

            <div class="flex shrink-0 justify-end gap-3 border-t border-zinc-100 px-6 py-4">
                <button type="button" data-fechar-modal-cafe class="h-11 rounded-xl border border-zinc-200 px-5 text-sm font-semibold">Cancelar</button>
                <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar regras
                </button>
            </div>
        </form>
    </div>
</div>
