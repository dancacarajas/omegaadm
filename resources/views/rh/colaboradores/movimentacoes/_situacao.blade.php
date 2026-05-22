@php
    $situacao = $mov->situacao ?? 'finalizada';
@endphp
@if ($situacao === 'pendente')
    <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800 ring-1 ring-amber-200/80">Pendente</span>
@elseif ($situacao === 'cancelada')
    <span class="inline-flex rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-bold text-zinc-600 ring-1 ring-zinc-200">Cancelada</span>
@else
    <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-800 ring-1 ring-emerald-200/80">Finalizada</span>
@endif
