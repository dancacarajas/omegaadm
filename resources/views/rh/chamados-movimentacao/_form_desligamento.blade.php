<div id="campos-desligamento" class="hidden space-y-6 border-t border-zinc-100 pt-6">
    <p class="text-sm font-semibold text-zinc-700">Solicitação de desligamento — dados obrigatórios</p>
    <div class="grid gap-6 lg:grid-cols-2">
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Data prevista do desligamento</span>
            <input type="date" name="data_prevista" value="{{ old('data_prevista') }}" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Último dia trabalhado</span>
            <input type="date" name="ultimo_dia_trabalhado" value="{{ old('ultimo_dia_trabalhado') }}" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Gestor responsável</span>
            <input type="text" name="gestor_responsavel" value="{{ old('gestor_responsavel') }}" placeholder="Nome do gestor" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Haverá substituição da vaga?</span>
            <select name="havera_substituicao_vaga" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">—</option>
                <option value="sim" @selected(old('havera_substituicao_vaga') === 'sim')>Sim</option>
                <option value="nao" @selected(old('havera_substituicao_vaga') === 'nao')>Não</option>
            </select>
        </label>
        <label class="space-y-2 lg:col-span-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Observação da solicitação</span>
            <textarea name="observacoes" rows="2" class="w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 py-3 text-sm outline-none focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">{{ old('observacoes') }}</textarea>
        </label>
    </div>
    <p class="text-xs text-zinc-500">Após abertura, cadastre o desligamento no SIGO e preencha o Nada Consta Demissional antes de finalizar o processo.</p>
</div>
