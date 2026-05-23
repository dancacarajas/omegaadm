@php
    $indicador = $adesaoService->indicadorPrazoMatriz($vinculo, $diasAlerta ?? 15);
    $ui = match ($indicador['tipo']) {
        'aguardando_aviso' => $indicador['alerta']
            ? 'border-amber-200/80 bg-amber-50 text-amber-950 ring-amber-200'
            : 'border-zinc-200/80 bg-zinc-50 text-brand-gray ring-zinc-200',
        'aviso_recebido' => 'border-teal-200/80 bg-teal-50 text-teal-950 ring-teal-200',
        'entregue' => 'border-emerald-200/80 bg-emerald-50 text-emerald-900 ring-emerald-200',
        default => 'border-zinc-200/80 bg-white text-brand-gray ring-zinc-200',
    };
    $icone = match ($indicador['tipo']) {
        'aguardando_aviso' => 'hourglass',
        'aviso_recebido' => 'package-check',
        'entregue' => 'check-circle-2',
        default => 'info',
    };
@endphp
<div class="inline-flex max-w-full items-start gap-2 rounded-xl border px-3 py-2 text-xs font-semibold leading-snug ring-1 ring-inset {{ $ui }}" title="{{ $indicador['texto'] }}">
    <i data-lucide="{{ $icone }}" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i>
    <span class="min-w-0">
        @if ($indicador['dias'] !== null)
            <span class="font-black tabular-nums">{{ $indicador['dias'] }}</span> dia(s)
            @if ($indicador['tipo'] === 'aguardando_aviso')
                <span class="font-medium"> aguardando aviso da Matriz</span>
            @elseif ($indicador['tipo'] === 'aviso_recebido')
                <span class="font-medium"> pedido → aviso coleta</span>
            @else
                <span class="font-medium"> pedido → aviso</span>
            @endif
        @else
            {{ $indicador['texto'] }}
        @endif
    </span>
</div>
