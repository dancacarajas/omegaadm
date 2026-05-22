@php
    $d = $chamado->dados_depois_json ?? [];
    $classificacoes = $classificacoesAfastamento ?? [];
    $resultados = $resultadosFinais ?? [];
@endphp

<section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
    <div class="border-b border-zinc-100 bg-gradient-to-r from-blue-500/[0.06] via-zinc-50/90 to-white px-6 py-5">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md">
                <i data-lucide="heart-pulse" class="h-5 w-5"></i>
            </span>
            <div>
                <h3 class="text-lg font-bold tracking-tight text-zinc-900">Afastamento INSS — dados do processo</h3>
                <p class="text-xs text-zinc-500">11 etapas: atestado → triagem → classificação → TST → DP/eSocial → INSS → benefícios → acompanhamento → retorno → ASO → finalização</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">
        <div><p class="text-[10px] font-bold uppercase text-zinc-400">Recebimento atestado</p><p class="font-semibold text-zinc-900">{{ isset($d['data_recebimento_atestado']) ? \Carbon\Carbon::parse($d['data_recebimento_atestado'])->format('d/m/Y') : '—' }}</p></div>
        <div><p class="text-[10px] font-bold uppercase text-zinc-400">Início afastamento</p><p class="font-semibold text-zinc-900">{{ isset($d['data_inicio_afastamento']) ? \Carbon\Carbon::parse($d['data_inicio_afastamento'])->format('d/m/Y') : '—' }}</p></div>
        <div><p class="text-[10px] font-bold uppercase text-zinc-400">Fim previsto</p><p class="font-semibold text-zinc-900">{{ isset($d['data_final_atestado']) ? \Carbon\Carbon::parse($d['data_final_atestado'])->format('d/m/Y') : '—' }}</p></div>
        <div><p class="text-[10px] font-bold uppercase text-zinc-400">Dias</p><p class="font-semibold text-zinc-900">{{ $d['quantidade_dias'] ?? '—' }}</p></div>
        <div><p class="text-[10px] font-bold uppercase text-zinc-400">Tipo</p><p class="font-semibold text-zinc-900">{{ $d['tipo_afastamento'] ?? '—' }}</p></div>
        <div><p class="text-[10px] font-bold uppercase text-zinc-400">Classificação</p><p class="font-semibold text-zinc-900">{{ $classificacoes[$d['classificacao'] ?? ''] ?? ($d['classificacao'] ?? '—') }}</p></div>
        @if ($podeVerDadosSensiveis && filled($d['cid'] ?? null))
            <div><p class="text-[10px] font-bold uppercase text-zinc-400">CID (restrito)</p><p class="font-mono font-semibold text-zinc-900">{{ $d['cid'] }}</p></div>
        @endif
        @if ($chamado->chamadoOrigem)
            <div class="sm:col-span-2"><p class="text-[10px] font-bold uppercase text-zinc-400">Chamado origem (prorrogação)</p><a href="{{ route('rh.chamados-movimentacao.show', $chamado->chamadoOrigem) }}" class="font-mono font-bold text-brand-burgundy hover:underline">{{ $chamado->chamadoOrigem->protocolo }}</a></div>
        @endif
    </div>

    @if ($chamado->anexos->isNotEmpty())
        <div class="border-t border-zinc-100 px-6 py-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">Anexos</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($chamado->anexos as $anexo)
                    <li class="flex items-center gap-2">
                        <i data-lucide="paperclip" class="h-3.5 w-3.5 text-brand-burgundy"></i>
                        <a href="{{ asset('storage/'.$anexo->caminho) }}" target="_blank" rel="noopener" class="font-medium text-brand-burgundy hover:underline">{{ $anexo->nome_arquivo }}</a>
                        <span class="text-xs text-zinc-400">({{ $anexo->tipo_documento }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($chamado->isAberto())
        <form method="POST" action="{{ route('rh.chamados-movimentacao.dados-afastamento', $chamado) }}" class="border-t border-zinc-100 bg-zinc-50/40 p-6">
            @csrf
            @method('PATCH')
            <p class="mb-4 text-sm font-semibold text-zinc-700">Atualizar classificação e resultado (antes de finalizar)</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-[11px] font-bold uppercase text-zinc-400">Classificação (etapa 3)</span>
                    <select name="classificacao" class="h-11 w-full rounded-xl border px-3 text-sm">
                        <option value="">—</option>
                        @foreach ($classificacoes as $k => $l)
                            <option value="{{ $k }}" @selected(($d['classificacao'] ?? '') === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-[11px] font-bold uppercase text-zinc-400">Resultado final</span>
                    <select name="resultado_final" class="h-11 w-full rounded-xl border px-3 text-sm">
                        <option value="">—</option>
                        @foreach ($resultados as $k => $l)
                            <option value="{{ $k }}" @selected(($d['resultado_final'] ?? '') === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-[11px] font-bold uppercase text-zinc-400">Retorno previsto</span>
                    <input type="date" name="data_retorno_prevista" value="{{ $d['data_retorno_prevista'] ?? '' }}" class="h-11 w-full rounded-xl border px-3 text-sm">
                </label>
                <label class="space-y-2">
                    <span class="text-[11px] font-bold uppercase text-zinc-400">Nº benefício INSS</span>
                    <input type="text" name="numero_beneficio_inss" value="{{ $d['numero_beneficio_inss'] ?? '' }}" class="h-11 w-full rounded-xl border px-3 text-sm">
                </label>
                <label class="space-y-2 sm:col-span-2">
                    <span class="text-[11px] font-bold uppercase text-zinc-400">Protocolo eSocial</span>
                    <input type="text" name="protocolo_esocial" value="{{ $d['protocolo_esocial'] ?? '' }}" class="h-11 w-full rounded-xl border px-3 text-sm">
                </label>
            </div>
            <button type="submit" class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-bold text-white">Salvar dados</button>
        </form>
    @endif
</section>
