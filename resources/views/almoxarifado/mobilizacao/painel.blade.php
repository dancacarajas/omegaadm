@extends('layouts.app')

@section('title', 'Painel — Mobilização de Materiais')
@section('eyebrow', 'Almoxarifado')
@section('page-title', 'Controle de Materiais da Mobilização')

@section('actions')
    <a href="{{ route('almoxarifado.mobilizacao-materiais.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="clipboard-list" class="h-4 w-4 text-brand-burgundy"></i>
        Lista de materiais
    </a>
    <a href="{{ route('almoxarifado.painel') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
        Atualizar painel
    </a>
@endsection

@section('content')
    @include('almoxarifado.mobilizacao.partials.flash')

    @include('almoxarifado.mobilizacao.partials.hero', [
        'badge' => 'Mobilização CT312',
        'icone' => 'package',
        'titulo' => 'Controle de materiais da mobilização',
        'subtitulo' => 'Acompanhe o que falta pedir no SIGO, o que está em Compras e o que já foi recebido — trabalho diário do almoxarife.',
        'stats' => [
            ['label' => 'Total', 'valor' => $indicadores['total']],
            ['label' => 'Atrasados', 'valor' => $indicadores['atrasados']],
        ],
    ])

    @php
        $baseQuery = request()->query();
        $cardUrl = fn (string $filtro) => route('almoxarifado.mobilizacao-materiais.index', array_merge($baseQuery, ['filtro_rapido' => $filtro]));
        $kpis = [
            ['icon' => 'layers', 'label' => 'Total de itens', 'valor' => $indicadores['total'], 'href' => route('almoxarifado.mobilizacao-materiais.index', $baseQuery), 'destaque' => true],
            ['icon' => 'circle-dashed', 'label' => 'Sem tratativa', 'valor' => $indicadores['sem_tratativa'], 'href' => $cardUrl('sem_tratativa')],
            ['icon' => 'send', 'label' => 'Pedido no SIGO', 'valor' => $indicadores['pedido_sigo'], 'href' => $cardUrl('pedido_sigo')],
            ['icon' => 'shopping-cart', 'label' => 'Em compras', 'valor' => $indicadores['em_compras'], 'href' => $cardUrl('em_compras')],
            ['icon' => 'pie-chart', 'label' => 'Compra parcial', 'valor' => $indicadores['compra_parcial'], 'href' => $cardUrl('compra_parcial')],
            ['icon' => 'package-check', 'label' => 'Recebido parcial', 'valor' => $indicadores['recebido_parcial'], 'href' => $cardUrl('recebido_parcial')],
            ['icon' => 'check-circle-2', 'label' => 'Recebido total', 'valor' => $indicadores['recebido_total'], 'href' => $cardUrl('recebido_total')],
            ['icon' => 'ban', 'label' => 'Cancelado', 'valor' => $indicadores['cancelado'], 'href' => $cardUrl('cancelado')],
            ['icon' => 'alarm-clock', 'label' => 'Atrasados', 'valor' => $indicadores['atrasados'], 'href' => $cardUrl('atrasados')],
            ['icon' => 'calendar-x', 'label' => 'Sem previsão', 'valor' => $indicadores['sem_previsao'], 'href' => $cardUrl('sem_previsao')],
        ];
    @endphp

    @include('almoxarifado.mobilizacao.partials.kpi-cards', ['cards' => $kpis])

    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                    <i data-lucide="filter" class="h-5 w-5"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-brand-black">Filtrar e abrir lista</h2>
                    <p class="text-xs text-brand-gray">Os filtros abaixo levam à tabela operacional com todos os campos da planilha.</p>
                </div>
            </div>
            <div class="mt-5">
                @include('almoxarifado.mobilizacao.partials.filtros', [
                    'limparUrl' => route('almoxarifado.painel'),
                    'action' => route('almoxarifado.mobilizacao-materiais.index'),
                ])
            </div>
        </div>
    </section>
@endsection
