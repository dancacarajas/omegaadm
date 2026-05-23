{{-- Indicadores compactos: direito, cartão, ativo (padrão Efetivo — ícones alinhados). --}}
@php
    $itens = [
        ['shield-check', 'Tem direito ao benefício', $vinculo->tem_direito],
        ['credit-card', 'Cartão entregue', $vinculo->cartao_entregue],
        ['badge-check', 'Benefício ativo no sistema', $vinculo->beneficio_ativo],
    ];
@endphp
<div class="inline-flex items-center gap-0.5 rounded-xl border border-zinc-200/80 bg-white p-0.5 shadow-sm ring-1 ring-zinc-100" role="group" aria-label="Situação do vínculo">
    @foreach ($itens as [$icone, $titulo, $ativo])
        <span
            title="{{ $titulo }}"
            class="flex h-8 w-8 items-center justify-center rounded-lg transition {{ $ativo ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-zinc-300' }}"
        >
            <i data-lucide="{{ $icone }}" class="h-3.5 w-3.5"></i>
        </span>
    @endforeach
</div>
