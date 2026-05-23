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
        <div class="sm:col-span-2 lg:col-span-3">
            <span class="{{ $labelClass }}">Formulário de adesão assinado</span>
            @if ($vinculo->temFormularioAdesaoAssinado())
                <div class="mt-1.5 flex flex-wrap items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/50 px-3 py-2.5">
                    <a href="{{ $vinculo->urlFormularioAdesaoAssinado() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:underline">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                        Ver anexo atual
                    </a>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-brand-gray">
                        <input type="checkbox" name="remover_formulario_adesao" value="1" class="accent-brand-burgundy">
                        Remover anexo
                    </label>
                </div>
            @endif
            <input
                type="file"
                name="formulario_adesao_assinado"
                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                class="mt-1.5 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-3 file:py-2 file:text-xs file:font-bold file:text-brand-burgundy hover:file:bg-brand-burgundy/10"
            >
            <p class="mt-1.5 text-[10px] leading-relaxed text-brand-gray">PDF ou imagem (JPG/PNG), até 10 MB. {{ $vinculo->temFormularioAdesaoAssinado() ? 'Envie outro arquivo para substituir.' : 'Anexe o formulário assinado pelo colaborador.' }}</p>
        </div>
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

    <div class="mt-4 rounded-xl border border-brand-burgundy/15 bg-brand-burgundy-soft/40 p-4">
        <p class="text-xs font-bold uppercase tracking-wider text-brand-burgundy">Enviar pedido à Matriz</p>
        <p class="mt-1 text-[11px] leading-relaxed text-brand-gray">
            O e-mail será enviado para os destinatários configurados em <strong class="text-brand-black">Configurações → E-mail</strong>
            (seção Benefícios / Matriz), com o formulário assinado em anexo. A mensagem é dirigida a
            <strong class="text-brand-black">{{ \App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService::RESPONSAVEL_MATRIZ }}</strong>.
        </p>
        @if ($vinculo->temFormularioAdesaoAssinado())
            <form method="POST" action="{{ route('rh.beneficios.vinculos.enviar-solicitacao-matriz', ['beneficio' => $vinculo->beneficio_id, 'vinculo' => $vinculo->id]) }}" class="mt-3" onsubmit="return confirm('Enviar e-mail de solicitação à Matriz com o formulário em anexo?');">
                @csrf
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="send" class="h-4 w-4"></i>
                    Enviar solicitação por e-mail
                </button>
            </form>
        @else
            <p class="mt-3 text-xs font-semibold text-amber-800">Anexe o formulário de adesão assinado acima para habilitar o envio.</p>
        @endif
    </div>
</div>
