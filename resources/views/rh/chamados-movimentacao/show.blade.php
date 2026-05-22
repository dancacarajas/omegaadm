@extends('layouts.app')

@section('title', $chamado->protocolo.' - Omega286')
@section('page-title', $chamado->protocolo)

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <header class="mb-6 rounded-xl border bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase text-zinc-500">{{ $chamado->tipoLabel() }}</p>
                <h2 class="text-xl font-bold text-zinc-900">{{ $chamado->colaborador->nome }}</h2>
                <p class="text-sm text-zinc-600">{{ $chamado->colaborador->cargo }} · {{ $chamado->colaborador->centro_custo }}</p>
            </div>
            <div class="text-right">
                <span class="inline-flex rounded-lg bg-zinc-100 px-3 py-1 text-sm font-bold">{{ $chamado->statusLabel() }}</span>
                @if ($chamado->etapaAtual)
                    <p class="mt-2 text-xs text-zinc-500">Etapa: <strong>{{ $chamado->etapaAtual->nome }}</strong></p>
                @endif
            </div>
        </div>
    </header>

    @if ($chamado->isAberto() && count($pendenciasFinalizacao) > 0)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-bold">Pendências para finalizar</p>
            <ul class="mt-2 list-inside list-disc text-xs">
                @foreach ($pendenciasFinalizacao as $p)<li>{{ $p }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="mb-6 rounded-xl border bg-white p-6">
        <h3 class="text-lg font-bold">Linha do tempo — etapas</h3>
        <ol class="mt-4 space-y-4">
            @foreach ($chamado->etapas as $etapa)
                <li class="rounded-lg border p-4 {{ $chamado->etapa_atual_id === $etapa->id ? 'border-brand-burgundy/40 bg-brand-burgundy-soft/30' : '' }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-zinc-400">{{ $etapa->ordem }}.</span>
                            <span class="font-semibold text-zinc-900">{{ $etapa->nome }}</span>
                            <span class="ml-2 rounded bg-zinc-100 px-2 py-0.5 text-[10px] font-bold uppercase">{{ $etapa->status }}</span>
                            @if ($etapa->isAtrasada())<span class="text-xs font-bold text-amber-700">Atrasada</span>@endif
                        </div>
                        @if ($chamado->isAberto() && ! $etapa->isConcluida())
                            <form method="POST" action="{{ route('rh.chamados-movimentacao.etapas.concluir', $etapa) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white">Concluir etapa</button>
                            </form>
                        @endif
                    </div>
                    @if ($etapa->checklistItens->isNotEmpty())
                        <ul class="mt-3 space-y-1 text-xs text-zinc-600">
                            @foreach ($etapa->checklistItens as $item)
                                <li>• {{ $item->nome }} <span class="text-zinc-400">({{ $item->status }})</span></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

    @if ($chamado->isAberto())
        <div class="flex flex-wrap gap-3">
            @if (count($pendenciasFinalizacao) === 0)
                <form method="POST" action="{{ route('rh.chamados-movimentacao.finalizar', $chamado) }}" onsubmit="return confirm('Finalizar chamado e aplicar alterações no cadastro?')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-brand-burgundy px-5 py-2.5 text-sm font-bold text-white">Finalizar processo</button>
                </form>
            @endif
            <form method="POST" action="{{ route('rh.chamados-movimentacao.cancelar', $chamado) }}" class="inline" onsubmit="return confirm('Cancelar chamado?')">
                @csrf
                <input type="hidden" name="motivo_cancelamento" value="Cancelado pelo usuário">
                <button type="submit" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-bold text-red-600">Cancelar</button>
            </form>
        </div>
    @else
        <p class="text-sm text-zinc-500">Chamado encerrado em {{ $chamado->finalizado_em?->format('d/m/Y H:i') ?? $chamado->cancelado_em?->format('d/m/Y H:i') }}.</p>
    @endif
@endsection
