@php
    $value = fn (string $field, $default = null) => old($field, data_get($manutencao, $field, $default));
@endphp

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-brand-black">Registro de manutenção</h2>
    <p class="mt-1 text-sm text-brand-gray">Controle mensal de indisponibilidade, impacto operacional e financeiro.</p>

    <div class="mt-5 grid gap-4 md:grid-cols-4">
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Vínculo com veículo mobilizado</span>
            <select name="veiculo_solicitacao_id" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="">Selecionar (opcional)</option>
                @foreach ($solicitacoes as $s)
                    <option value="{{ $s->id }}" @selected((string) $value('veiculo_solicitacao_id') === (string) $s->id)>
                        #{{ $s->id }} - {{ $s->placa }} {{ trim(($s->marca ?? '').' '.($s->modelo ?? '')) }}{{ $s->contrato ? ' | '.$s->contrato : '' }}
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
            <input type="text" name="contrato" value="{{ $value('contrato') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
            <select name="status" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="aberto" @selected($value('status', 'aberto') === 'aberto')>Aberto</option>
                <option value="em_andamento" @selected($value('status') === 'em_andamento')>Em andamento</option>
                <option value="concluido" @selected($value('status') === 'concluido')>Concluído</option>
            </select>
        </label>

        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Veículo/equipamento *</span>
            <input type="text" name="veiculo_equipamento" value="{{ $value('veiculo_equipamento') }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Placa/TAG</span>
            <input type="text" name="placa_tag" value="{{ $value('placa_tag') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm uppercase outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo</span>
            <input type="text" name="tipo" value="{{ $value('tipo') }}" placeholder="Micro-ônibus, caminhonete..." class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>

        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da solicitação *</span>
            <input type="date" name="data_solicitacao" value="{{ $value('data_solicitacao', now()->toDateString()) }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável pela solicitação</span>
            <input type="text" name="responsavel_solicitacao" value="{{ $value('responsavel_solicitacao') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Motivo *</span>
            <select name="motivo" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="preventiva" @selected($value('motivo', 'preventiva') === 'preventiva')>Preventiva</option>
                <option value="corretiva" @selected($value('motivo') === 'corretiva')>Corretiva</option>
                <option value="falha" @selected($value('motivo') === 'falha')>Falha</option>
                <option value="quebra" @selected($value('motivo') === 'quebra')>Quebra</option>
            </select>
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de envio</span>
            <input type="date" name="data_envio" value="{{ $value('data_envio') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de retorno</span>
            <input type="date" name="data_retorno" value="{{ $value('data_retorno') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Dias parado</span>
            <input type="number" min="0" name="dias_parado" value="{{ $value('dias_parado', 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Impacto financeiro (R$)</span>
            <input type="number" step="0.01" min="0" name="impacto_financeiro" value="{{ $value('impacto_financeiro') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência</span>
            <input type="file" name="evidencia" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
            @if (! empty($manutencao->evidencia_path))
                <a href="{{ asset('storage/'.$manutencao->evidencia_path) }}" target="_blank" class="mt-2 inline-flex text-xs font-bold text-brand-burgundy">Ver evidência atual</a>
            @endif
        </label>

        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Impacto na operação</span>
            <textarea name="impacto_operacao" rows="3" placeholder="Afectou transporte, produção, medição..." class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('impacto_operacao') }}</textarea>
        </label>
        <label class="md:col-span-4">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observação</span>
            <textarea name="observacao" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('observacao') }}</textarea>
        </label>
    </div>
</section>
