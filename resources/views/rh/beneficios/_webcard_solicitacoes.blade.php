@php
    $config = $webcardConfig ?? \App\Support\Rh\WebcardRegraConfig::resolver(null);
    $percentual = $config->percentualLimitePorSolicitacao();
@endphp

<section class="mb-5 overflow-hidden rounded-2xl border border-violet-200/80 bg-white shadow-sm">
    <div class="border-b border-violet-100 bg-gradient-to-br from-violet-50/90 to-white p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-brand-black">Solicitações WebCard (adiantamento)</h3>
                <p class="mt-1 max-w-2xl text-sm text-brand-gray">
                    Registre cada uso do cartão. O valor é descontado na folha do <strong>mesmo mês</strong> da solicitação.
                    Limite: <strong>{{ number_format($percentual, 0, ',', '.') }}% do salário</strong> por vez ·
                    R$ {{ number_format($config->limiteMensal(), 2, ',', '.') }}/mês ·
                    renovação do saldo dia {{ $config->diaRenovacaoSaldo() }}.
                </p>
            </div>
            <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-3 text-xs font-semibold text-violet-900">
                <i data-lucide="file-text" class="h-4 w-4"></i>
                Ver no extrato
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('rh.beneficios.webcard.solicitacoes.store', $beneficio) }}" class="grid gap-4 border-b border-zinc-100 p-5 lg:grid-cols-12 lg:items-end" id="form-webcard-solicitacao">
        @csrf
        <label class="lg:col-span-4">
            <span class="text-[11px] font-bold uppercase text-brand-gray">Colaborador vinculado</span>
            <select name="colaborador_beneficio_id" id="webcard-colaborador-select" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                <option value="">Selecione...</option>
                @foreach ($beneficio->colaboradores->where('tem_direito', true) as $v)
                    @php
                        $salario = filled($v->colaborador?->salario_inicial) ? (float) $v->colaborador->salario_inicial : 0;
                        $limite = $config->limitePorSolicitacaoParaSalario($salario > 0 ? $salario : null);
                    @endphp
                    <option value="{{ $v->id }}" data-salario="{{ $salario > 0 ? number_format($salario, 2, '.', '') : '' }}" data-limite="{{ $limite > 0 ? number_format($limite, 2, '.', '') : '' }}">
                        {{ $v->colaborador?->nome }}
                        @if ($limite > 0)
                            (máx. R$ {{ number_format($limite, 2, ',', '.') }})
                        @elseif ($salario <= 0)
                            (sem salário cadastrado)
                        @endif
                    </option>
                @endforeach
            </select>
        </label>
        <label class="lg:col-span-2">
            <span class="text-[11px] font-bold uppercase text-brand-gray">Data da solicitação</span>
            <input type="date" name="data_solicitacao" value="{{ old('data_solicitacao', today()->format('Y-m-d')) }}" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
        </label>
        <label class="lg:col-span-2">
            <span class="text-[11px] font-bold uppercase text-brand-gray">Valor (R$)</span>
            <input type="number" step="0.01" min="0.01" name="valor" id="webcard-valor-input" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="Selecione o colaborador">
            <p id="webcard-limite-hint" class="mt-1 text-[11px] text-brand-gray"></p>
        </label>
        <label class="lg:col-span-3">
            <span class="text-[11px] font-bold uppercase text-brand-gray">Observação</span>
            <input type="text" name="observacao" maxlength="500" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="Opcional">
        </label>
        <div class="lg:col-span-1">
            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-semibold text-white">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Registrar
            </button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-brand-gray">
                <tr>
                    <th class="px-5 py-3">Colaborador</th>
                    <th class="px-5 py-3">Data</th>
                    <th class="px-5 py-3">Valor</th>
                    <th class="px-5 py-3">Observação</th>
                    <th class="px-5 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($webcardSolicitacoes ?? [] as $sol)
                    <tr>
                        <td class="px-5 py-3 font-semibold text-brand-black">{{ $sol->vinculo?->colaborador?->nome ?? '—' }}</td>
                        <td class="px-5 py-3">{{ $sol->data_solicitacao->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 font-bold tabular-nums text-violet-900">R$ {{ number_format((float) $sol->valor, 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-brand-gray">{{ $sol->observacao ?: '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('rh.beneficios.webcard.solicitacoes.destroy', [$beneficio, $sol]) }}" class="inline" onsubmit="return confirm('Remover esta solicitação?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-sm text-brand-gray">Nenhuma solicitação registrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    const select = document.getElementById('webcard-colaborador-select');
    const input = document.getElementById('webcard-valor-input');
    const hint = document.getElementById('webcard-limite-hint');
    const percentual = {{ json_encode($percentual) }};
    if (!select || !input || !hint) return;

    function atualizarLimite() {
        const opt = select.selectedOptions[0];
        const limite = opt?.dataset?.limite ? parseFloat(opt.dataset.limite) : NaN;
        const salario = opt?.dataset?.salario ? parseFloat(opt.dataset.salario) : NaN;
        if (!opt?.value) {
            input.removeAttribute('max');
            hint.textContent = '';
            return;
        }
        if (!salario || salario <= 0) {
            input.removeAttribute('max');
            hint.textContent = 'Cadastre o salário na ficha do colaborador para liberar solicitações.';
            hint.classList.add('text-amber-800');
            return;
        }
        hint.classList.remove('text-amber-800');
        input.max = limite;
        hint.textContent = 'Máximo: ' + percentual.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + '% do salário (R$ ' + limite.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ').';
    }

    select.addEventListener('change', atualizarLimite);
    atualizarLimite();
})();
</script>
