@extends('layouts.app')

@section('title', 'Solicitações à Matriz - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Solicitações à Matriz')

@section('actions')
    <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="calculator" class="h-4 w-4 text-brand-burgundy"></i>
        Extrato
    </a>
    <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Benefícios
    </a>
@endsection

@section('content')
    @php
        $filtrosAtivos = $busca !== '' || $beneficioId || $statusFiltro !== 'em_andamento' || (int) $diasAlerta !== 15;
        $kpis = [
            ['key' => 'pendente_formulario', 'label' => 'Aguardando formulário', 'icon' => 'file-pen'],
            ['key' => 'formulario_recebido', 'label' => 'Enviar à Matriz', 'icon' => 'send'],
            ['key' => 'enviado_matriz', 'label' => 'Pedido na Matriz', 'icon' => 'building-2'],
            ['key' => 'aguardando_cartao', 'label' => 'Aguard. aviso', 'icon' => 'hourglass'],
            ['key' => 'cartao_disponivel_coleta', 'label' => 'Para coleta', 'icon' => 'package-check'],
            ['key' => 'cartao_atrasado', 'label' => 'Sem aviso +'.$diasAlerta.'d', 'icon' => 'alarm-clock'],
        ];
        $heroSlot = $resumo['cartao_atrasado'] > 0
            ? '<a href="' . route('rh.beneficios.adesoes.index', ['status' => 'cartao_atrasado', 'dias_alerta' => $diasAlerta]) . '" class="inline-flex h-10 items-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-bold text-white shadow-md transition hover:bg-red-700"><i data-lucide="alarm-clock" class="h-4 w-4"></i>' . $resumo['cartao_atrasado'] . ' atrasado(s)</a>'
            : '';
    @endphp

    @include('rh.beneficios.partials._alerts')

    @include('rh.beneficios.partials._hero', [
        'badgeIcon' => 'clipboard-list',
        'badgeText' => 'Adesão à Matriz',
        'title' => 'Solicitações de benefícios',
        'description' => 'A Matriz não informa previsão: após o pedido, ela avisa quando o cartão está para coleta. Registre as datas e acompanhe os dias de espera.',
        'stats' => [
            ['label' => 'Em andamento', 'value' => array_sum($resumo) - ($resumo['cartao_atrasado'] ?? 0)],
            ['label' => 'Atrasados', 'value' => $resumo['cartao_atrasado']],
        ],
        'heroSlot' => $heroSlot,
    ])

    <section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($kpis as $kpi)
            @php $valor = $resumo[$kpi['key']] ?? 0; @endphp
            <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $kpi['key'] === 'cartao_atrasado' && $valor > 0 ? 'bg-red-100 text-red-700' : 'bg-brand-burgundy-soft text-brand-burgundy' }}">
                    <i data-lucide="{{ $kpi['icon'] }}" class="h-5 w-5"></i>
                </span>
                <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">{{ $kpi['label'] }}</p>
                <p class="mt-1 flex items-baseline gap-1">
                    <span class="text-3xl font-bold tracking-tight text-brand-black">{{ $valor }}</span>
                    @if (! empty($kpi['suffix']) && $valor > 0)
                        <span class="text-xs font-bold text-red-600">{{ $kpi['suffix'] }}</span>
                    @endif
                </p>
            </article>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-5">
            @include('rh.beneficios.partials._section_head', [
                'icon' => 'filter',
                'title' => 'Filtrar solicitações',
                'subtitle' => $vinculos->total() . ' registro(s) · alerta após ' . $diasAlerta . ' dias sem aviso de coleta',
                'actions' => $filtrosAtivos ? '<a href="' . route('rh.beneficios.adesoes.index') . '" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-gray transition hover:text-brand-black"><i data-lucide="x" class="h-3.5 w-3.5"></i>Limpar</a>' : '',
            ])

            <form method="GET" action="{{ route('rh.beneficios.adesoes.index') }}" class="mt-5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="search" class="h-3.5 w-3.5"></i>
                            Colaborador
                        </span>
                        <input name="busca" value="{{ $busca }}" placeholder="Nome ou matrícula…" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="hand-heart" class="h-3.5 w-3.5"></i>
                            Benefício
                        </span>
                        <select name="beneficio_id" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="">Todos</option>
                            @foreach ($beneficios as $b)
                                <option value="{{ $b->id }}" @selected($beneficioId === $b->id)>{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="git-branch" class="h-3.5 w-3.5"></i>
                            Situação
                        </span>
                        <select name="status" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="em_andamento" @selected($statusFiltro === 'em_andamento')>Em andamento</option>
                            <option value="cartao_atrasado" @selected($statusFiltro === 'cartao_atrasado')>Cartão atrasado</option>
                            <option value="todos" @selected($statusFiltro === 'todos')>Todos os status</option>
                            @foreach ($statusOpcoes as $valor => $rotulo)
                                @if (! in_array($valor, ['adesao_automatica', 'beneficio_ativo', 'cancelado'], true))
                                    <option value="{{ $valor }}" @selected($statusFiltro === $valor)>{{ $rotulo }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2 lg:col-span-1">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="timer" class="h-3.5 w-3.5"></i>
                            Dias
                        </span>
                        <input type="number" name="dias_alerta" value="{{ $diasAlerta }}" min="1" max="120" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3 text-sm font-bold tabular-nums outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                    </label>
                    <div class="flex gap-2 lg:col-span-2">
                        <button type="submit" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                            Filtrar
                        </button>
                    </div>
                </div>
                @if ($filtrosAtivos)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Filtros:</span>
                        @if ($busca !== '')
                            <span class="rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-semibold text-brand-burgundy">{{ $busca }}</span>
                        @endif
                        @if ($beneficioId)
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-brand-gray">{{ $beneficios->firstWhere('id', $beneficioId)?->nome }}</span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Colaborador</th>
                        <th class="px-5 py-4">Benefício</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Formulário</th>
                        <th class="px-5 py-4">Pedido Matriz</th>
                        <th class="px-5 py-4">Aviso coleta</th>
                        <th class="px-5 py-4 min-w-[200px]">Prazo</th>
                        <th class="px-5 py-4 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($vinculos as $vinculo)
                        @php
                            $atrasado = $adesao->cartaoAtrasado($vinculo, $diasAlerta);
                            $dataAviso = $adesao->dataAvisoColeta($vinculo);
                        @endphp
                        <tr class="transition {{ $atrasado ? 'bg-red-50/70' : 'hover:bg-zinc-50/80' }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-burgundy-soft text-sm font-bold text-brand-burgundy">
                                        {{ mb_strtoupper(mb_substr($vinculo->colaborador?->nome ?? '?', 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $vinculo->colaborador?->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $vinculo->colaborador?->matricula ? 'Mat. '.$vinculo->colaborador->matricula : '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-medium text-brand-gray">{{ $vinculo->beneficio?->nome }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex max-w-[220px] rounded-full px-2.5 py-1 text-[11px] font-bold leading-snug {{ $vinculo->badgeStatusAdesao() }}">
                                    {{ $vinculo->rotuloStatusAdesao() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 tabular-nums">{{ $vinculo->data_formulario_recebido?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-5 py-4 tabular-nums">{{ $vinculo->data_envio_matriz?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-5 py-4 tabular-nums">{{ $dataAviso?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @if ($vinculo->data_envio_matriz)
                                    @include('rh.beneficios.partials._indicador_prazo_matriz', ['vinculo' => $vinculo, 'adesaoService' => $adesao, 'diasAlerta' => $diasAlerta])
                                @else
                                    <span class="text-xs text-zinc-400">Sem pedido</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('rh.beneficios.show', $vinculo->beneficio_id) }}?busca={{ urlencode($vinculo->colaborador?->nome ?? '') }}" class="inline-flex h-9 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                    Gerenciar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="clipboard-list" class="h-8 w-8"></i>
                                </div>
                                <p class="mt-5 text-lg font-bold text-brand-black">Nenhuma solicitação nesta visão</p>
                                <p class="mt-2 text-sm text-brand-gray">Ajuste os filtros ou cadastre benefícios com controle de adesão à Matriz.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($vinculos->hasPages())
            <div class="border-t border-zinc-100 bg-zinc-50/50 px-6 py-4">{{ $vinculos->links() }}</div>
        @endif
    </section>

    <p class="mt-6 flex items-start gap-2 rounded-2xl border border-zinc-200/80 bg-zinc-50 px-5 py-4 text-xs leading-relaxed text-brand-gray">
        <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
        <span><strong class="text-brand-black">Fluxo:</strong> formulário → pedido à Matriz (e-mail) → aguardar aviso de que o cartão está para <strong>coleta</strong> (a Matriz não informa previsão) → retirar na Matriz e entregar ao colaborador. O indicador de prazo mostra os dias entre o pedido e o aviso, ou quantos dias já passaram aguardando o aviso.</span>
    </p>
@endsection
