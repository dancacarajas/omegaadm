@php
    use App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService;

    $statusAtual = old('status_adesao', $vinculo->status_adesao ?? \App\Support\Rh\BeneficioAdesaoStatus::PENDENTE_FORMULARIO);
    $avisoColeta = old('data_aviso_coleta_matriz', $adesaoService->dataAvisoColeta($vinculo)?->format('Y-m-d'));
    $indicador = $adesaoService->indicadorPrazoMatriz($vinculo);
    $inputClass = 'mt-1.5 h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10';
    $labelClass = 'text-[10px] font-bold uppercase tracking-wider text-brand-gray';
    $emailMatrizDiag = $emailMatrizDiagnostico ?? app(BeneficioAdesaoMatrizNotificacaoService::class)->diagnosticoEnvio();
@endphp

<div
    class="rounded-xl border border-zinc-200/80 bg-zinc-50/50 p-4"
    data-adesao-vinculo-bloco
    data-email-matriz-action="{{ route('rh.beneficios.vinculos.enviar-solicitacao-matriz', ['beneficio' => $vinculo->beneficio_id, 'vinculo' => $vinculo->id]) }}"
    data-email-matriz-colaborador="{{ $vinculo->colaborador?->nome }}"
    data-email-matriz-beneficio="{{ $vinculo->beneficio?->nome }}"
>
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
        <div class="sm:col-span-2 lg:col-span-3" data-formulario-adesao-upload>
            <span class="{{ $labelClass }}">Formulário de adesão assinado</span>
            @if ($vinculo->temFormularioAdesaoAssinado())
                <div data-formulario-adesao-preview class="mt-1.5 flex flex-wrap items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/50 px-3 py-2.5">
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
                data-auto-upload-formulario-adesao
                data-upload-url="{{ route('rh.beneficios.vinculos.formulario-adesao.upload', ['beneficio' => $vinculo->beneficio_id, 'vinculo' => $vinculo->id]) }}"
                class="mt-1.5 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-3 file:py-2 file:text-xs file:font-bold file:text-brand-burgundy hover:file:bg-brand-burgundy/10"
            >
            <p data-formulario-adesao-status class="mt-1.5 hidden text-xs font-semibold text-brand-burgundy"></p>
            <p class="mt-1.5 text-[10px] leading-relaxed text-brand-gray">
                PDF ou imagem (JPG/PNG), até 10 MB. O anexo é salvo automaticamente ao selecionar o arquivo.
                {{ $vinculo->temFormularioAdesaoAssinado() ? ' Envie outro arquivo para substituir.' : '' }}
            </p>
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
        @include('rh.beneficios.partials._status_email_matriz', ['vinculo' => $vinculo])
        <div
            data-email-matriz-diagnostico
            class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-950 {{ ($emailMatrizDiag['pode_enviar'] ?? false) ? 'hidden' : '' }}"
        >
            <p class="font-bold">E-mail à Matriz indisponível no momento</p>
            <ul data-email-matriz-problemas class="mt-1.5 list-inside list-disc space-y-0.5">
                @foreach ($emailMatrizDiag['problemas'] ?? [] as $problema)
                    <li>{{ $problema }}</li>
                @endforeach
            </ul>
        </div>
        <button
            type="button"
            data-abrir-modal-email-matriz
            data-email-matriz-botao
            data-action="{{ route('rh.beneficios.vinculos.enviar-solicitacao-matriz', ['beneficio' => $vinculo->beneficio_id, 'vinculo' => $vinculo->id]) }}"
            data-colaborador="{{ $vinculo->colaborador?->nome }}"
            data-beneficio="{{ $vinculo->beneficio?->nome }}"
            class="mt-3 inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark {{ ($emailMatrizDiag['pode_enviar'] ?? false) && $vinculo->temFormularioAdesaoAssinado() ? '' : 'hidden' }}"
        >
            <i data-lucide="send" class="h-4 w-4"></i>
            Enviar solicitação por e-mail
        </button>
        <p
            data-email-matriz-aviso-anexo
            class="mt-3 text-xs font-semibold text-amber-800 {{ $vinculo->temFormularioAdesaoAssinado() ? 'hidden' : '' }}"
        >
            Selecione o formulário de adesão assinado acima — o sistema salva o anexo automaticamente e libera o envio por e-mail.
        </p>
    </div>
</div>
