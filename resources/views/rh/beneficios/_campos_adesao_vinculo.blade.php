@php
    $statusAtual = old('status_adesao', $vinculo->status_adesao ?? \App\Support\Rh\BeneficioAdesaoStatus::PENDENTE_FORMULARIO);
    $avisoColeta = old('data_aviso_coleta_matriz', $adesaoService->dataAvisoColeta($vinculo)?->format('Y-m-d'));
    $indicador = $adesaoService->indicadorPrazoMatriz($vinculo);
    $inputClass = 'mt-1.5 h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10';
    $labelClass = 'text-[10px] font-bold uppercase tracking-wider text-brand-gray';
@endphp

<div class="rounded-xl border border-zinc-200/80 bg-zinc-50/50 p-4">
    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-brand-gray">Adesão à Matriz</p>
            <p class="mt-1 text-[11px] leading-relaxed text-brand-gray">
                A Matriz não informa previsão de entrega. Após o pedido, registre quando avisarem que o cartão está disponível para <strong class="text-brand-black">coleta</strong> — depois entregue ao colaborador.
            </p>
        </div>
        @include('rh.beneficios.partials._indicador_prazo_matriz', ['vinculo' => $vinculo, 'adesaoService' => $adesaoService])
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <label class="sm:col-span-2 lg:col-span-3">
            <span class="{{ $labelClass }}">Status da adesão</span>
            <select name="status_adesao" class="{{ $inputClass }}">
                @foreach ($statusAdesaoOpcoes as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected($statusAtual === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span class="{{ $labelClass }}">Formulário recebido</span>
            <input type="date" name="data_formulario_recebido" value="{{ old('data_formulario_recebido', $vinculo->data_formulario_recebido?->format('Y-m-d')) }}" class="{{ $inputClass }}">
        </label>
        <label>
            <span class="{{ $labelClass }}">Pedido enviado à Matriz</span>
            <input type="date" name="data_envio_matriz" value="{{ old('data_envio_matriz', $vinculo->data_envio_matriz?->format('Y-m-d')) }}" class="{{ $inputClass }}">
        </label>
        <label>
            <span class="{{ $labelClass }}">Protocolo / ref. do pedido</span>
            <input type="text" name="protocolo_matriz" value="{{ old('protocolo_matriz', $vinculo->protocolo_matriz) }}" maxlength="120" placeholder="E-mail, chamado, ticket…" class="{{ $inputClass }}">
        </label>
        <label class="sm:col-span-2 lg:col-span-2">
            <span class="{{ $labelClass }}">Matriz avisou: cartão para coleta</span>
            <input type="date" name="data_aviso_coleta_matriz" value="{{ $avisoColeta }}" class="{{ $inputClass }}">
            <p class="mt-1.5 text-[10px] leading-relaxed text-brand-gray">Data em que a Matriz comunicou que o cartão está na unidade para você retirar.</p>
        </label>
    </div>
</div>
