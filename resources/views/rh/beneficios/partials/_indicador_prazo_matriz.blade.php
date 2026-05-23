@php
    $indicador = $adesaoService->indicadorPrazoMatriz($vinculo, $diasAlerta ?? 15);
    $classeUi = match ($indicador['tipo']) {
        'aguardando_aviso' => $indicador['alerta'] ? 'beneficio-indicador-matriz--aguardando-alerta' : 'beneficio-indicador-matriz--aguardando',
        'aviso_recebido' => 'beneficio-indicador-matriz--aviso',
        'entregue' => 'beneficio-indicador-matriz--entregue',
        default => 'beneficio-indicador-matriz--neutro',
    };
    $icone = match ($indicador['tipo']) {
        'aguardando_aviso' => 'hourglass',
        'aviso_recebido' => 'package-check',
        'entregue' => 'check-circle-2',
        default => 'info',
    };
@endphp
<div class="beneficio-indicador-matriz {{ $classeUi }}" title="{{ $indicador['texto'] }}">
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
