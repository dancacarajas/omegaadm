@extends('layouts.app')

@section('title', 'Controle de Conformidade — SSMA - Omega286')
@section('eyebrow', 'SSMA / Controle de Conformidade')
@section('page-title', 'Controle de Conformidade')

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
            'concluido' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        ];
    @endphp

    <section class="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Total (resultado)</p>
            <p class="mt-1 text-2xl font-bold text-brand-black">{{ $indicadores['total_resultado'] }}</p>
            <p class="mt-1 text-xs text-brand-gray">De {{ $indicadores['total_ativos'] }} ativos no efetivo</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">100% conformes</p>
            <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $indicadores['conformes_100'] }}</p>
            <p class="mt-1 text-xs text-emerald-800">Todas as pendências concluídas</p>
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-900">Com pendência</p>
            <p class="mt-1 text-2xl font-bold text-amber-950">{{ $indicadores['com_pendencia'] }}</p>
            <p class="mt-1 text-xs text-amber-900">Colaboradores no recorte</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-red-800">Pendências críticas</p>
            <p class="mt-1 text-2xl font-bold text-red-900">{{ $indicadores['pendencias_criticas'] }}</p>
            <p class="mt-1 text-xs text-red-800">Vencidas ou tipo crítico (ART, PAEBM, inspeções, treinamento)</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Pendências vencidas</p>
            <p class="mt-1 text-2xl font-bold text-red-700">{{ $indicadores['pendencias_vencidas'] }}</p>
            <p class="mt-1 text-xs text-brand-gray">Fora da data prevista e não concluídas</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-brand-burgundy-soft/40 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Conformidade geral</p>
            <p class="mt-1 text-2xl font-bold text-brand-burgundy">{{ $indicadores['percentual_geral'] }}%</p>
            <p class="mt-1 text-xs text-brand-gray">Itens concluídos / total no recorte</p>
        </article>
    </section>

    <p class="mb-5 rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-brand-gray shadow-sm">
        Matriz de <strong class="text-brand-black">conformidade individual</strong> (ART, OS, anuências, PAEBM, checklist, EPIs, carômetro, passaporte, DDS, treinamento, inspeções). Os indicadores acima refletem o <strong>recorte filtrado</strong> da tabela. Estes dados poderão alimentar o Painel Executivo; o <a href="{{ route('sesmt.registros.index') }}" class="font-semibold text-brand-burgundy underline-offset-2 hover:underline">Registro mensal SSMA</a> permanece separado.
    </p>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Pendências SSMA por colaborador</h2>
                <p class="mt-1 text-sm text-brand-gray">Cada coluna é uma demanda do técnico de segurança. Use os filtros para focar cargos, status, tipo de pendência, vencidas ou responsável pela tratativa.</p>
            </div>
            <form method="GET" action="{{ route('sesmt.index') }}" class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <label class="md:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Busca rápida</span>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Nome, matrícula ou cargo..." class="mt-1 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Cargo</span>
                    <select name="cargo" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todos</option>
                        @foreach ($opcoesCargo as $c)
                            <option value="{{ $c }}" @selected(request('cargo') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Colaborador</span>
                    <select name="colaborador_id" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todos</option>
                        @foreach ($opcoesColaboradores as $c)
                            <option value="{{ $c->id }}" @selected((string) request('colaborador_id') === (string) $c->id)>{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Status da tarefa</span>
                    <select name="status_tarefa" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Qualquer</option>
                        @foreach ($statusLabel as $val => $lab)
                            <option value="{{ $val }}" @selected(request('status_tarefa') === $val)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo de pendência</span>
                    <select name="tipo_pendencia" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Qualquer</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo }}" @selected(request('tipo_pendencia') === $tipo)>{{ $labels[$tipo] }} (aberta)</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável pela tratativa</span>
                    <input name="responsavel_tratativa" value="{{ request('responsavel_tratativa') }}" placeholder="Nome no cadastro da tarefa" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="flex flex-col justify-end">
                    <span class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-sm font-medium text-brand-black">
                        <input type="checkbox" name="somente_vencidas" value="1" @checked(request()->boolean('somente_vencidas')) class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy">
                        Só com pendências vencidas
                    </span>
                </label>
                <div class="flex flex-wrap items-end gap-2 md:col-span-2 lg:col-span-2">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark sm:flex-none">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Aplicar filtros
                    </button>
                    @if ($filtrosAtivos)
                        <a href="{{ route('sesmt.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                            Limpar
                        </a>
                    @endif
                </div>
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
                                        @php $tarefaVencida = $tarefa->estaVencida(); @endphp
                                        <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-8 min-w-24 items-center justify-center rounded-full border px-3 text-xs font-bold {{ $statusClass[$tarefa->status] ?? 'border-zinc-200 bg-white text-brand-gray' }} {{ $tarefaVencida ? 'ring-2 ring-red-300' : '' }}">
                                                {{ $statusLabel[$tarefa->status] ?? $tarefa->status }}
                                            </span>
                                            <button type="button" data-modal-open="#sesmt-tarefa-{{ $tarefa->id }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-gray shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy" title="Gerenciar {{ $labels[$tipo] }}">
                                                <i data-lucide="square-pen" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                        @if ($tarefaVencida)
                                            <span class="text-[10px] font-bold uppercase tracking-wide text-red-600">Vencida</span>
                                        @endif
                                        </div>

                                        <div id="sesmt-tarefa-{{ $tarefa->id }}" data-modal class="fixed inset-0 z-50 hidden">
                                            <div data-modal-backdrop class="absolute inset-0 bg-brand-black/35 backdrop-blur-sm"></div>
                                            <div class="relative flex min-h-full items-center justify-center p-4">
                                                <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl">
                                                    <div class="flex items-start justify-between gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/80 p-5">
                                                        <div>
                                                            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">SSMA / {{ $labels[$tipo] }}</p>
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
                                                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável pela tratativa</span>
                                                                <input name="responsavel" value="{{ $tarefa->responsavel }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Nome do técnico">
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
                                <p class="mt-1 text-sm text-brand-gray">Cadastre colaboradores ativos no Efetivo para gerar as demandas SSMA.</p>
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
