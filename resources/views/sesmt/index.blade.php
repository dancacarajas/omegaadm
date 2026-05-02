@extends('layouts.app')

@section('title', 'SESMT - Omega286')
@section('eyebrow', 'Seguranca do Trabalho')
@section('page-title', 'SESMT')

@section('actions')
    <form method="POST" action="{{ route('sesmt.sync') }}">
        @csrf
        <button class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="refresh-cw" class="h-4 w-4"></i>
            Sincronizar
        </button>
    </form>
@endsection

@section('content')
    @php
        $tipos = \App\Models\SesmtTarefa::TIPOS_PADRAO;
        $labels = \App\Models\SesmtTarefa::LABELS;
        $statusLabel = [
            'pendente' => 'Pendente',
            'em_andamento' => 'Andamento',
            'concluido' => 'Concluido',
        ];
        $statusClass = [
            'pendente' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
            'em_andamento' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
            'concluido' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
        ];
    @endphp

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Pendencias SESMT por colaborador</h2>
                <p class="mt-1 text-sm text-brand-gray">Cada coluna representa uma demanda do tecnico de seguranca.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar colaborador..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-80">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1680px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="sticky left-0 z-[1] w-[280px] bg-white px-5 py-4">Colaborador</th>
                        <th class="w-[120px] px-4 py-4">Progresso</th>
                        @foreach ($tipos as $tipo)
                            <th class="w-[132px] px-3 py-4">{{ $labels[$tipo] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($colaboradores as $colaborador)
                        @php
                            $tarefasPorTipo = $colaborador->sesmtTarefas->keyBy('tipo');
                            $totalColaborador = count($tipos);
                            $concluidasColaborador = $colaborador->sesmtTarefas->where('status', 'concluido')->count();
                            $percentual = $totalColaborador > 0 ? round(($concluidasColaborador / $totalColaborador) * 100) : 0;
                        @endphp
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="sticky left-0 z-[1] bg-white px-5 py-4 shadow-[8px_0_18px_rgba(17,17,17,0.03)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-sm font-bold text-brand-burgundy">
                                        {{ mb_substr($colaborador->nome, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $colaborador->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $colaborador->cargo ?: 'Cargo nao informado' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-16 overflow-hidden rounded-full bg-brand-gray-soft">
                                        <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ $percentual }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-brand-black">{{ $percentual }}%</span>
                                </div>
                            </td>
                            @foreach ($tipos as $tipo)
                                @php
                                    $tarefa = $tarefasPorTipo->get($tipo);
                                @endphp
                                <td class="px-3 py-4">
                                    @if ($tarefa)
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 min-w-24 items-center justify-center rounded-full border px-3 text-xs font-bold {{ $statusClass[$tarefa->status] ?? 'border-zinc-200 bg-white text-brand-gray' }}">
                                                {{ $statusLabel[$tarefa->status] ?? $tarefa->status }}
                                            </span>
                                            <button type="button" data-modal-open="#sesmt-tarefa-{{ $tarefa->id }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-gray shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy" title="Gerenciar {{ $labels[$tipo] }}">
                                                <i data-lucide="square-pen" class="h-4 w-4"></i>
                                            </button>
                                        </div>

                                        <div id="sesmt-tarefa-{{ $tarefa->id }}" data-modal class="fixed inset-0 z-50 hidden">
                                            <div data-modal-backdrop class="absolute inset-0 bg-brand-black/35 backdrop-blur-sm"></div>
                                            <div class="relative flex min-h-full items-center justify-center p-4">
                                                <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl">
                                                    <div class="flex items-start justify-between gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/80 p-5">
                                                        <div>
                                                            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">SESMT / {{ $labels[$tipo] }}</p>
                                                            <h3 class="mt-1 text-lg font-bold text-brand-black">{{ $colaborador->nome }}</h3>
                                                            <p class="mt-1 text-sm text-brand-gray">{{ $colaborador->cargo ?: 'Cargo nao informado' }}</p>
                                                        </div>
                                                        <button type="button" data-modal-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-gray shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                                            <i data-lucide="x" class="h-4 w-4"></i>
                                                        </button>
                                                    </div>

                                                    <form method="POST" action="{{ route('sesmt.tarefas.update', $tarefa) }}" class="p-5">
                                                        @csrf
                                                        @method('PUT')

                                                        <div class="grid gap-4 md:grid-cols-2">
                                                            <label class="space-y-2">
                                                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
                                                                <select name="status" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm font-semibold text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                                                    @foreach ($statusLabel as $status => $label)
                                                                        <option value="{{ $status }}" @selected($tarefa->status === $status)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </label>

                                                            <label class="space-y-2">
                                                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsavel</span>
                                                                <input name="responsavel" value="{{ $tarefa->responsavel }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Nome do tecnico">
                                                            </label>

                                                            <label class="space-y-2">
                                                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data prevista</span>
                                                                <input type="date" name="data_prevista" value="{{ optional($tarefa->data_prevista)->format('Y-m-d') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                                            </label>

                                                            <label class="space-y-2">
                                                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de conclusao</span>
                                                                <input type="date" name="data_conclusao" value="{{ optional($tarefa->data_conclusao)->format('Y-m-d') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                                            </label>
                                                        </div>

                                                        <label class="mt-4 block space-y-2">
                                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
                                                            <textarea name="observacoes" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Descreva pendencias, protocolo, motivo de atraso ou evidencias.">{{ $tarefa->observacoes }}</textarea>
                                                        </label>

                                                        <div class="mt-5 flex flex-col-reverse gap-2 border-t border-zinc-200 pt-5 sm:flex-row sm:justify-end">
                                                            <button type="button" data-modal-close class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                                                Cancelar
                                                            </button>
                                                            <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                                                <i data-lucide="save" class="h-4 w-4"></i>
                                                                Salvar alteracoes
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex h-9 w-full items-center justify-center rounded-full border border-zinc-200 bg-brand-gray-soft px-3 text-xs font-bold text-brand-gray">
                                            N/A
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($tipos) + 2 }}" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="hard-hat" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum colaborador encontrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Cadastre colaboradores ativos no Efetivo para gerar as demandas SESMT.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $colaboradores->links() }}
        </div>
    </section>
@endsection
