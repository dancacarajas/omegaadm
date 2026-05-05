@php
    $value = fn (string $field, $default = null) => old($field, data_get($telemetria, $field, $default));
@endphp

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-brand-black">Registro operacional</h2>
    <p class="mt-1 text-sm text-brand-gray">Preencha a planilha diária de telemetria para gerar indicadores mensais da frota.</p>

    <div class="mt-5 grid gap-4 md:grid-cols-4">
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data *</span>
            <input type="date" name="data" value="{{ $value('data', now()->toDateString()) }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
            <input type="text" name="contrato" value="{{ $value('contrato') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Vínculo com mobilização</span>
            <select name="veiculo_solicitacao_id" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="">Selecionar (opcional)</option>
                @foreach ($solicitacoes as $s)
                    <option value="{{ $s->id }}" @selected((string) $value('veiculo_solicitacao_id') === (string) $s->id)>
                        #{{ $s->id }} - {{ $s->placa }} {{ trim(($s->marca ?? '').' '.($s->modelo ?? '')) }}{{ $s->contrato ? ' | '.$s->contrato : '' }}
                    </option>
                @endforeach
            </select>
        </label>
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Veículo *</span>
            <input type="text" name="veiculo" value="{{ $value('veiculo') }}" required placeholder="Nome/ID do veículo" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Placa/TAG</span>
            <input type="text" name="placa_tag" value="{{ $value('placa_tag') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm uppercase outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Motorista/Responsável</span>
            <input type="text" name="motorista_responsavel" value="{{ $value('motorista_responsavel') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
    </div>
</section>

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-brand-black">Indicadores de uso e segurança</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-4">
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">KM inicial</span>
            <input type="number" step="0.01" min="0" name="km_inicial" value="{{ $value('km_inicial') }}" data-km-inicial class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">KM final</span>
            <input type="number" step="0.01" min="0" name="km_final" value="{{ $value('km_final') }}" data-km-final class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">KM rodado</span>
            <input type="number" step="0.01" min="0" name="km_rodado" value="{{ $value('km_rodado') }}" data-km-rodado class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Horas em operação (HH:MM)</span>
            <input type="text" name="horas_operacao" value="{{ $value('horas_operacao') }}" placeholder="08:30" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tempo ocioso (HH:MM)</span>
            <input type="text" name="tempo_ocioso" value="{{ $value('tempo_ocioso') }}" placeholder="01:15" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tempo parado (HH:MM)</span>
            <input type="text" name="tempo_parado" value="{{ $value('tempo_parado') }}" placeholder="02:00" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Velocidade média (km/h)</span>
            <input type="number" step="0.01" min="0" name="velocidade_media" value="{{ $value('velocidade_media') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Consumo estimado (L)</span>
            <input type="number" step="0.01" min="0" name="consumo_estimado" value="{{ $value('consumo_estimado') }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Excesso de velocidade</span>
            <input type="number" min="0" name="excesso_velocidade" value="{{ $value('excesso_velocidade', 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Frenagens bruscas</span>
            <input type="number" min="0" name="frenagens_bruscas" value="{{ $value('frenagens_bruscas', 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Acelerações bruscas</span>
            <input type="number" min="0" name="aceleracoes_bruscas" value="{{ $value('aceleracoes_bruscas', 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Alertas gerados</span>
            <input type="number" min="0" name="alertas_gerados" value="{{ $value('alertas_gerados', 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Rota prevista</span>
            <textarea name="rota_prevista" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('rota_prevista') }}</textarea>
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Rota realizada</span>
            <textarea name="rota_realizada" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('rota_realizada') }}</textarea>
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Localização dos veículos</span>
            <textarea name="localizacao" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('localizacao') }}</textarea>
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Eventos críticos</span>
            <textarea name="eventos_criticos" rows="3" placeholder="Frenagem, aceleração, parada indevida..." class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('eventos_criticos') }}</textarea>
        </label>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Qtd. eventos críticos</span>
            <input type="number" min="0" name="eventos_criticos_qtd" value="{{ $value('eventos_criticos_qtd', 0) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
        </label>
        <div>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Desvio de rota</span>
            <div class="mt-2 flex items-center gap-5">
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-brand-black">
                    <input type="radio" name="desvio_rota" value="1" @checked((string) $value('desvio_rota', '0') === '1') class="h-4 w-4 border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy">
                    Sim
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-brand-black">
                    <input type="radio" name="desvio_rota" value="0" @checked((string) $value('desvio_rota', '0') !== '1') class="h-4 w-4 border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy">
                    Não
                </label>
            </div>
            <textarea name="desvio_justificativa" rows="2" placeholder="Justificativa do desvio" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('desvio_justificativa') }}</textarea>
        </div>
        <label>
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência (arquivo)</span>
            <input type="file" name="evidencia" accept=".pdf,.jpg,.jpeg,.png,.webp,.xls,.xlsx,.csv,.doc,.docx" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
            @if (! empty($telemetria->evidencia_path))
                <a href="{{ asset('storage/'.$telemetria->evidencia_path) }}" target="_blank" class="mt-2 inline-flex text-xs font-bold text-brand-burgundy">Ver evidência atual</a>
            @endif
        </label>
        <label class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observação</span>
            <textarea name="observacao" rows="3" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('observacao') }}</textarea>
        </label>
    </div>
</section>

@push('scripts')
    <script>
        (function () {
            const i = document.querySelector('[data-km-inicial]');
            const f = document.querySelector('[data-km-final]');
            const r = document.querySelector('[data-km-rodado]');
            if (!i || !f || !r) return;

            const recalc = () => {
                const a = parseFloat(i.value || '0');
                const b = parseFloat(f.value || '0');
                if (Number.isNaN(a) || Number.isNaN(b)) return;
                if (document.activeElement === r && r.value !== '') return;
                r.value = Math.max(0, b - a).toFixed(2);
            };
            i.addEventListener('input', recalc);
            f.addEventListener('input', recalc);
        })();
    </script>
@endpush
