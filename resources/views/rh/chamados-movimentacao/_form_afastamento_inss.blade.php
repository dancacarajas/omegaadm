@php
    $d = old();
@endphp

<div id="form-afastamento-inss" class="hidden space-y-6 border-t border-zinc-100 pt-6">
    <div class="rounded-2xl border border-blue-200/80 bg-blue-50/80 px-4 py-3 text-sm text-blue-950">
        <strong>LGPD:</strong> informações médicas são dados sensíveis. O campo CID é opcional e restrito. Anexe o atestado médico na abertura.
    </div>

    @if (! empty($chamadoOrigem))
        <input type="hidden" name="chamado_origem_id" value="{{ $chamadoOrigem->id }}">
        <p class="text-sm text-zinc-600">Prorrogação / vínculo ao chamado <strong class="font-mono text-brand-burgundy">{{ $chamadoOrigem->protocolo }}</strong></p>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Data de recebimento do atestado *</span>
            <input type="date" name="data_recebimento_atestado" value="{{ old('data_recebimento_atestado', today()->format('Y-m-d')) }}" required class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Data de início do afastamento *</span>
            <input type="date" name="data_inicio_afastamento" id="data_inicio_afastamento" value="{{ old('data_inicio_afastamento', today()->format('Y-m-d')) }}" required class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Data final prevista no atestado</span>
            <input type="date" name="data_final_atestado" id="data_final_atestado" value="{{ old('data_final_atestado') }}" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Quantidade de dias</span>
            <input type="number" name="quantidade_dias" id="quantidade_dias" min="1" max="730" value="{{ old('quantidade_dias') }}" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2 lg:col-span-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Tipo de afastamento *</span>
            <select name="tipo_afastamento" required class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                @foreach ($tiposAfastamento as $k => $l)
                    <option value="{{ $k }}" @selected(old('tipo_afastamento') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Espécie benefício INSS (previdência)</span>
            <select name="especie_beneficio_inss" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">—</option>
                @foreach ($especiesInss as $k => $l)
                    <option value="{{ $k }}" @selected(old('especie_beneficio_inss') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Responsável pelo recebimento</span>
            <input type="text" name="responsavel_recebimento" value="{{ old('responsavel_recebimento', auth()->user()?->name) }}" maxlength="120" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">CID (opcional — acesso restrito)</span>
            <input type="text" name="cid" value="{{ old('cid') }}" maxlength="20" autocomplete="off" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
    </div>

    <div class="flex flex-wrap gap-4 rounded-2xl border border-zinc-200/80 bg-zinc-50/50 p-4">
        <label class="flex items-center gap-2 text-sm font-medium text-zinc-700"><input type="checkbox" name="doenca_comum" value="1" class="rounded text-brand-burgundy" @checked(old('doenca_comum'))> Doença comum</label>
        <label class="flex items-center gap-2 text-sm font-medium text-zinc-700"><input type="checkbox" name="acidente_trabalho" value="1" class="rounded text-brand-burgundy" @checked(old('acidente_trabalho'))> Acidente de trabalho</label>
        <label class="flex items-center gap-2 text-sm font-medium text-zinc-700"><input type="checkbox" name="acidente_trajeto" value="1" class="rounded text-brand-burgundy" @checked(old('acidente_trajeto'))> Acidente de trajeto</label>
        <label class="flex items-center gap-2 text-sm font-medium text-zinc-700"><input type="checkbox" name="doenca_ocupacional" value="1" class="rounded text-brand-burgundy" @checked(old('doenca_ocupacional'))> Doença ocupacional</label>
        <label class="flex items-center gap-2 text-sm font-medium text-zinc-700"><input type="checkbox" name="recorrencia_atestados" value="1" class="rounded text-brand-burgundy" @checked(old('recorrencia_atestados'))> Recorrência de atestados</label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Atestado médico *</span>
            <input type="file" name="atestado_medico" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full text-sm text-zinc-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-burgundy-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-burgundy">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Relatório médico</span>
            <input type="file" name="relatorio_medico" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full text-sm text-zinc-600 file:mr-3 file:rounded-xl file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-semibold">
        </label>
        <label class="space-y-2 sm:col-span-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Declaração de comparecimento</span>
            <input type="file" name="declaracao_comparecimento" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full text-sm text-zinc-600 file:mr-3 file:rounded-xl file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-semibold">
        </label>
    </div>
</div>
