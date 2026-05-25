@extends('layouts.app')

@section('title', 'Mobilização de Materiais')
@section('eyebrow', 'Almoxarifado')
@section('page-title', 'Mobilização de Materiais')

@section('actions')
    <a href="{{ route('almoxarifado.painel') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="layout-dashboard" class="h-4 w-4 text-brand-burgundy"></i>
        Painel
    </a>
    @if ($acesso['exportar'])
        <a href="{{ route('almoxarifado.mobilizacao-materiais.exportar-excel', request()->query()) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
            <i data-lucide="download" class="h-4 w-4"></i>
            Exportar
        </a>
    @endif
    @if ($acesso['criar'])
        <a href="{{ route('almoxarifado.mobilizacao-materiais.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Novo material
        </a>
    @endif
@endsection

@section('content')
    @php
        $totalPagina = $itens->count();
        $statusDot = [
            'SEM_TRATATIVA' => 'bg-zinc-400',
            'PEDIDO_NO_SIGO' => 'bg-amber-500',
            'EM_COMPRAS' => 'bg-blue-500',
            'COMPRA_PARCIAL' => 'bg-orange-500',
            'RECEBIDO_PARCIAL' => 'bg-orange-500',
            'RECEBIDO_TOTAL' => 'bg-emerald-500',
            'CANCELADO_NAO_NECESSARIO' => 'bg-zinc-600',
        ];
    @endphp

    @include('almoxarifado.mobilizacao.partials.flash')

    @include('almoxarifado.mobilizacao.partials.hero', [
        'badge' => 'Lista operacional',
        'icone' => 'clipboard-list',
        'titulo' => 'Mobilização de materiais',
        'subtitulo' => 'Tabela de trabalho diário com as mesmas colunas da planilha CT312 — status, quantidades, PM, OC e ação do dia.',
        'stats' => [
            ['label' => 'Registros', 'valor' => $itens->total()],
            ['label' => 'Nesta página', 'valor' => $totalPagina],
        ],
    ])

    @include('almoxarifado.mobilizacao.partials.kpi-cards', ['cards' => [
        ['icon' => 'layers', 'label' => 'Total', 'valor' => $indicadores['total'], 'destaque' => true],
        ['icon' => 'circle-dashed', 'label' => 'Sem tratativa', 'valor' => $indicadores['sem_tratativa']],
        ['icon' => 'send', 'label' => 'Pedido SIGO', 'valor' => $indicadores['pedido_sigo']],
        ['icon' => 'shopping-cart', 'label' => 'Em compras', 'valor' => $indicadores['em_compras']],
        ['icon' => 'alarm-clock', 'label' => 'Atrasados', 'valor' => $indicadores['atrasados']],
    ]])

    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                        <i data-lucide="package" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-brand-black">Controle de materiais</h2>
                        <p class="text-xs text-brand-gray">
                            {{ $itens->total() }} registro(s)
                            @if ($totalPagina > 0) · exibindo {{ $totalPagina }} nesta página @endif
                        </p>
                    </div>
                </div>
                @if ($acesso['cobranca'])
                    <button type="button" id="btn-gerar-cobranca" class="inline-flex h-10 items-center gap-2 rounded-xl border border-amber-200/80 bg-amber-50 px-4 text-sm font-semibold text-amber-900 shadow-sm transition hover:bg-amber-100">
                        <i data-lucide="message-square" class="h-4 w-4"></i>
                        Gerar cobrança
                    </button>
                @endif
            </div>
            <div class="mt-5">
                @include('almoxarifado.mobilizacao.partials.filtros', ['limparUrl' => route('almoxarifado.mobilizacao-materiais.index')])
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1280px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <tr>
                        <th class="w-12 px-4 py-4"><input type="checkbox" id="check-all-itens" class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20" title="Selecionar todos"></th>
                        <th class="px-4 py-4">Status</th>
                        <th class="min-w-[150px] px-4 py-4">Ação do dia</th>
                        <th class="px-4 py-4">Material</th>
                        <th class="px-4 py-4">Disciplina</th>
                        <th class="px-4 py-4">Categoria</th>
                        <th class="px-4 py-4 text-right">Necess.</th>
                        <th class="px-4 py-4 text-right">SIGO</th>
                        <th class="px-4 py-4 text-right">Compra</th>
                        <th class="px-4 py-4 text-right">Receb.</th>
                        <th class="px-4 py-4">PM</th>
                        <th class="px-4 py-4">OC</th>
                        <th class="px-4 py-4">Prev.</th>
                        <th class="min-w-[180px] px-4 py-4">Alterar status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($itens as $item)
                        @php
                            $badge = $statusBadges[$item->status] ?? 'border-zinc-200 bg-zinc-50 text-zinc-700';
                            $calculo = app(\App\Support\Almoxarifado\MobilizacaoMaterialCalculoService::class);
                            $atrasado = $calculo->estaAtrasado($item);
                            $dot = $statusDot[$item->status] ?? 'bg-zinc-400';
                        @endphp
                        <tr class="transition hover:bg-zinc-50/80 {{ $atrasado ? 'bg-red-50/50' : '' }}">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" class="item-check rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20" value="{{ $item->id }}">
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold {{ $badge }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
                                    {{ $statusLabels[$item->status] ?? $item->status }}
                                </span>
                                @if ($atrasado)
                                    <span class="mt-1 block text-[10px] font-bold uppercase text-red-600">Atrasado</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-xs font-medium leading-snug text-brand-black">{{ $item->acao_do_dia }}</td>
                            <td class="px-4 py-4">
                                <p class="max-w-[220px] font-semibold text-brand-black">{{ Str::limit($item->descricao_material, 55) }}</p>
                                <p class="mt-0.5 text-xs text-brand-gray">{{ $item->unidade_medida ?? 'UND' }} @if($item->codigo_material)· {{ $item->codigo_material }}@endif</p>
                            </td>
                            <td class="px-4 py-4 text-xs text-brand-gray">{{ $item->disciplina ?? '—' }}</td>
                            <td class="px-4 py-4 text-xs text-brand-gray">{{ Str::limit($item->categoria_descricao ?? '—', 28) }}</td>
                            <td class="px-4 py-4 text-right font-mono text-xs tabular-nums">{{ number_format($item->quantidade_necessaria, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-right font-mono text-xs tabular-nums">{{ number_format($item->quantidade_pedida_sigo, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-right font-mono text-xs tabular-nums">{{ number_format($item->quantidade_em_compra, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 text-right font-mono text-xs tabular-nums">{{ number_format($item->quantidade_recebida, 2, ',', '.') }}</td>
                            <td class="px-4 py-4 font-mono text-xs">{{ $item->numero_pm ?? '—' }}</td>
                            <td class="px-4 py-4 font-mono text-xs">{{ $item->numero_oc ?? '—' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-xs text-brand-gray">{{ $item->previsao_entrega?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-4">
                                @if ($acesso['alterarStatus'])
                                    <form method="POST" action="{{ route('almoxarifado.mobilizacao-materiais.status', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()"
                                            class="w-full min-w-[160px] rounded-xl border border-zinc-200 bg-white px-2 py-2 text-xs font-semibold text-brand-black outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                                            @foreach ($statusLabels as $key => $label)
                                                @if ($key === 'CANCELADO_NAO_NECESSARIO' && ! ($acesso['cancelar'] ?? false))
                                                    @continue
                                                @endif
                                                <option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="text-xs text-brand-gray">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="package" class="h-8 w-8"></i>
                                </div>
                                <p class="mt-5 text-lg font-bold text-brand-black">Nenhum material encontrado</p>
                                <p class="mt-1 text-sm text-brand-gray">Ajuste os filtros ou cadastre um novo item.</p>
                                @if ($acesso['criar'])
                                    <a href="{{ route('almoxarifado.mobilizacao-materiais.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-brand-burgundy px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                        Novo material
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($itens->hasPages())
            <div class="border-t border-zinc-100 bg-zinc-50/50 px-6 py-4">{{ $itens->links() }}</div>
        @endif
    </section>

    <dialog id="dialog-cobranca" class="w-full max-w-2xl rounded-3xl border border-zinc-200/80 p-0 shadow-2xl backdrop:bg-black/40">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
            <h3 class="text-lg font-bold text-brand-black">Texto de cobrança — Compras</h3>
            <p class="mt-1 text-xs text-brand-gray">Copie e envie aos responsáveis por compras.</p>
        </div>
        <textarea id="texto-cobranca" rows="14" readonly class="w-full border-0 px-6 py-4 text-sm leading-relaxed"></textarea>
        <div class="flex justify-end gap-2 border-t border-zinc-100 px-6 py-4">
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('texto-cobranca').value)" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold shadow-sm hover:border-brand-burgundy">
                <i data-lucide="copy" class="h-4 w-4"></i>
                Copiar
            </button>
            <button type="button" onclick="document.getElementById('dialog-cobranca').close()" class="h-10 rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20">Fechar</button>
        </div>
    </dialog>

    @if ($acesso['cobranca'])
    @push('scripts')
    <script>
        document.getElementById('check-all-itens')?.addEventListener('change', (e) => {
            document.querySelectorAll('.item-check').forEach(cb => { cb.checked = e.target.checked; });
        });
        document.getElementById('btn-gerar-cobranca')?.addEventListener('click', async () => {
            const ids = [...document.querySelectorAll('.item-check:checked')].map(cb => cb.value);
            if (!ids.length) { alert('Selecione ao menos um item.'); return; }
            const res = await fetch('{{ route('almoxarifado.mobilizacao-materiais.gerar-cobranca') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ itens: ids }),
            });
            if (!res.ok) { alert('Erro ao gerar texto.'); return; }
            document.getElementById('texto-cobranca').value = (await res.json()).texto;
            document.getElementById('dialog-cobranca').showModal();
        });
    </script>
    @endpush
    @endif
@endsection
