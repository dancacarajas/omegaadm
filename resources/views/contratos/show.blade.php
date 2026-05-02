@extends('layouts.app')

@section('title', 'Detalhes do contrato - Omega286')
@section('eyebrow', 'Gestão contratual')
@section('page-title', $contrato->nome)

@section('actions')
    <a href="{{ route('contratos.edit', $contrato) }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="pencil" class="h-4 w-4"></i>
        Editar
    </a>
@endsection

@section('content')
    @php
        $statusLabel = [
            'ativo' => 'Ativo',
            'em_analise' => 'Em análise',
            'suspenso' => 'Suspenso',
            'encerrado' => 'Encerrado',
            'cancelado' => 'Cancelado',
            'vencido' => 'Vencido',
        ];
        $linha = function (string $label, $value) {
            return '<div class="rounded-lg border border-zinc-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-brand-gray">'.$label.'</p><p class="mt-2 text-sm font-semibold text-brand-black">'.e($value ?: '-').'</p></div>';
        };
    @endphp

    <div class="space-y-5">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                        <i data-lucide="file-text" class="h-7 w-7"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Contrato nº {{ $contrato->numero }}</p>
                        <h2 class="mt-1 text-2xl font-bold text-brand-black">{{ $contrato->nome }}</h2>
                        <p class="mt-1 text-sm text-brand-gray">{{ $contrato->cliente ?: 'Cliente não informado' }} · {{ $contrato->contratada ?: 'Contratada não informada' }}</p>
                    </div>
                </div>
                <span class="w-fit rounded-full border border-brand-burgundy/20 bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">
                    {{ $statusLabel[$contrato->status] ?? $contrato->status }}
                </span>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {!! $linha('Cliente', $contrato->cliente) !!}
            {!! $linha('Contratada', $contrato->contratada) !!}
            {!! $linha('Valor', $contrato->valor ? 'R$ '.number_format((float) $contrato->valor, 2, ',', '.') : null) !!}
            {!! $linha('Tipo', $contrato->tipo) !!}
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Ficha contratual</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">Informações completas</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {!! $linha('Centro de custo', $contrato->centro_custo) !!}
                {!! $linha('Local de execução', $contrato->local_execucao) !!}
                {!! $linha('Gestor', $contrato->gestor) !!}
                {!! $linha('Fiscal', $contrato->fiscal) !!}
                {!! $linha('Data de início', optional($contrato->data_inicio)->format('d/m/Y')) !!}
                {!! $linha('Data de fim', optional($contrato->data_fim)->format('d/m/Y')) !!}
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 bg-brand-gray-soft/50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Objeto</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-brand-black">{{ $contrato->objeto ?: '-' }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-brand-gray-soft/50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observações</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-brand-black">{{ $contrato->observacoes ?: '-' }}</p>
                </div>
            </div>
            <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição complementar</p>
                <p class="mt-2 whitespace-pre-line text-sm text-brand-black">{{ $contrato->descricao ?: '-' }}</p>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <a href="{{ route('contratos.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Voltar aos contratos
            </a>
            <form method="POST" action="{{ route('contratos.destroy', $contrato) }}" onsubmit="return confirm('Deseja realmente excluir este contrato?');">
                @csrf
                @method('DELETE')
                <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-100">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Excluir contrato
                </button>
            </form>
        </div>
    </div>
@endsection
