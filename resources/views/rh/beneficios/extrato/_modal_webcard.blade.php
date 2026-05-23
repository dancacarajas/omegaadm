@php
    $cfg = $config->toArray();
    $percentual = (float) ($cfg['percentual_limite_por_solicitacao'] ?? 30);
@endphp

<div id="modal-webcard-{{ $beneficio->id }}" class="extrato-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="extrato-modal__panel extrato-modal__panel--sm">
        <div class="shrink-0 border-b border-violet-100 bg-gradient-to-br from-violet-50 to-violet-100/80 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-violet-900">WebCard — adiantamento salarial</p>
                    <h3 class="mt-1 text-xl font-bold text-brand-black">{{ $beneficio->nome }}</h3>
                    <p class="mt-2 text-sm text-violet-950/90">
                        Direito de <strong>{{ number_format($percentual, 0, ',', '.') }}% do salário</strong> do colaborador,
                        teto mensal <strong>R$ {{ number_format((float) ($cfg['limite_mensal'] ?? 1500), 2, ',', '.') }}</strong>.
                        Renovação do saldo todo dia <strong>{{ $cfg['dia_renovacao_saldo'] ?? 23 }}</strong>.
                        O extrato mostra o direito mensal de cada colaborador (sem controle de pedidos no sistema).
                    </p>
                </div>
                <button type="button" data-fechar-modal-webcard class="rounded-lg bg-white/80 p-2 text-brand-gray hover:bg-white">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('rh.beneficios.extrato.regras.salvar', $beneficio) }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="extrato-modal__body space-y-6 p-6">
                <section class="grid gap-4 sm:grid-cols-2">
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Ano de vigência</span>
                        <input type="number" name="ano_vigencia" value="{{ old('ano_vigencia', $regra->ano_vigencia ?? $config->anoVigencia()) }}" min="2020" max="2100" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Dia de renovação do saldo</span>
                        <input type="number" name="dia_renovacao_saldo" value="{{ old('dia_renovacao_saldo', $cfg['dia_renovacao_saldo'] ?? 23) }}" min="1" max="28" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                        <p class="mt-1 text-[11px] text-brand-gray">Todo dia deste número de cada mês o saldo do cartão é renovado.</p>
                    </label>
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Percentual do salário (direito)</span>
                        <input type="number" step="0.01" min="0.01" max="100" name="percentual_limite_por_solicitacao" value="{{ old('percentual_limite_por_solicitacao', $percentual) }}" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                        <p class="mt-1 text-[11px] text-brand-gray">Usa o salário cadastrado na ficha do colaborador (efetivo).</p>
                    </label>
                    <label>
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Limite mensal (R$)</span>
                        <input type="number" step="0.01" min="0.01" name="limite_mensal" value="{{ old('limite_mensal', $cfg['limite_mensal'] ?? 1500) }}" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                    </label>
                </section>
            </div>
            <div class="flex shrink-0 justify-end gap-3 border-t border-zinc-100 bg-zinc-50/80 px-6 py-4">
                <button type="button" data-fechar-modal-webcard class="h-10 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black">Cancelar</button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-semibold text-white">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar regras
                </button>
            </div>
        </form>
    </div>
</div>
