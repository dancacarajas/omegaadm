@if ($vinculo->emailSolicitacaoMatrizJaEnviado())
    <div class="mt-3 flex gap-2.5 rounded-xl border border-emerald-200/90 bg-emerald-50/80 px-3.5 py-3 text-xs leading-relaxed text-emerald-950">
        <i data-lucide="mail-check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700"></i>
        <p>
            <span class="font-bold">E-mail já enviado</span>
            em <strong class="tabular-nums">{{ $vinculo->rotuloEmailSolicitacaoMatrizEnviadoBrasilia() }}</strong>
            <span class="text-emerald-800/90">(horário de Brasília)</span>.
            @if ($mostrarReenvio ?? true)
                Você pode enviar novamente se precisar reforçar o pedido à Matriz.
            @endif
        </p>
    </div>
@endif
