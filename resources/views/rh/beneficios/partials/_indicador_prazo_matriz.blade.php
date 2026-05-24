@php
    $indicador = $adesaoService->indicadorPrazoMatriz($vinculo, $diasAlerta ?? 15);

    if ($indicador['tipo'] === 'sem_pedido') {
        return;
    }

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
    $textoExibicao = match ($indicador['tipo']) {
        'aguardando_aviso' => ($indicador['dias'] ?? 0).' dia(s) aguardando aviso da Matriz',
        'aviso_recebido' => ($indicador['dias'] ?? 0).' dia(s) entre pedido e aviso de coleta',
        'entregue' => $indicador['dias'] !== null
            ? ($indicador['dias']).' dia(s) entre pedido e aviso de coleta'
            : 'Cartão já entregue ao colaborador',
        default => $indicador['texto'],
    };
@endphp
<div class="beneficio-indicador-matriz shrink min-w-0 {{ $classeUi }}" title="{{ $indicador['texto'] }}">
    <i data-lucide="{{ $icone }}" class="beneficio-indicador-matriz__icone shrink-0"></i>
    <span class="beneficio-indicador-matriz__texto truncate">{{ $textoExibicao }}</span>
</div>
