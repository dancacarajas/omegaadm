@extends('layouts.app')

@section('title', 'Registro Mensal SSMA - Omega286')
@section('eyebrow', 'SSMA')
@section('page-title', 'Registro Mensal')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.registros.prazos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="timer" class="h-4 w-4"></i>
            Prazos (SLA)
        </a>
        <a href="{{ route('sesmt.registros.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Novo registro
        </a>
    </div>
@endsection

@section('content')
    @if (! empty($situacaoSla))
        @php
            $sla = $situacaoSla['prazo'];
        @endphp
        <div class="mb-5 rounded-xl border px-5 py-4 shadow-sm
            @if ($situacaoSla['cumprido']) border-emerald-200 bg-emerald-50 text-emerald-950
            @elseif ($situacaoSla['atrasado']) border-red-200 bg-red-50 text-red-950
            @else border-amber-200 bg-amber-50 text-amber-950
            @endif">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide opacity-80">SLA — competência {{ $situacaoSla['competencia_filtro_label'] ?? $sla->competencia?->format('m/Y') }}</p>
                    <p class="mt-1 text-sm font-semibold">
                        Prazo limite: {{ $situacaoSla['data_limite_efetiva']->format('d/m/Y H:i') }}
                        @if ($sla->recorrente)
                            <span class="font-normal">(regra recorrente · cadastro dia {{ $sla->data_limite?->format('d') }}/hora {{ $sla->data_limite?->format('H:i') }})</span>
                        @endif
                        · {{ $sla->exige_finalizado ? 'Exige registro finalizado' : 'Basta existir registro (qualquer status)' }}
                    </p>
                    <p class="mt-2 text-sm font-bold">{{ $situacaoSla['rotulo'] }}</p>
                </div>
                @if (auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'))
                    <a href="{{ route('sesmt.registros.prazos.edit', $sla) }}" class="shrink-0 text-sm font-bold text-brand-burgundy underline-offset-2 hover:underline">Editar prazo</a>
                @endif
            </div>
        </div>
    @endif

    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Registros do mês</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $total }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-gray-soft text-brand-black">
                <i data-lucide="file-edit" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Rascunhos</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $rascunhos }}</p>
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-200/80 text-amber-950">
                <i data-lucide="send" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Em validação</p>
            <p class="mt-1 text-xs text-brand-gray">Enviado ou validado</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $emValidacao }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="circle-check" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Finalizados</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $finalizados }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Cadastro de registros mensais</h2>
                <p class="mt-1 text-sm text-brand-gray">Base para evolução de gráficos e slides executivos do SSMA.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <input type="month" name="competencia" value="{{ $competenciaFiltro }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por título, responsável, contrato ou local..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-80">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Competência</th>
                        <th class="px-5 py-4">Título</th>
                        <th class="px-5 py-4">Responsável</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Atualizado em</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($registros as $registro)
                        <tr class="transition hover:bg-brand-gray-soft/60">
                            <td class="px-5 py-4 font-semibold text-brand-black">{{ $registro->competencia?->format('m/Y') }}</td>
                            <td class="px-5 py-4 text-brand-gray">{{ $registro->titulo ?: 'Registro mensal SSMA' }}</td>
                            <td class="px-5 py-4 text-brand-gray">{{ $registro->responsavel ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @switch($registro->status)
                                    @case('finalizado')
                                        <span class="inline-flex items-center rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-bold text-brand-burgundy">Finalizado</span>
                                        @break
                                    @case('validado')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Validado</span>
                                        @break
                                    @case('enviado')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900">Enviado</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center rounded-full bg-brand-gray-soft px-2.5 py-1 text-xs font-bold text-brand-gray">Rascunho</span>
                                @endswitch
                            </td>
                            <td class="px-5 py-4 text-brand-gray">{{ $registro->updated_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('sesmt.registros.edit', $registro) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-brand-burgundy/30 bg-brand-burgundy-soft px-3 py-1.5 text-xs font-bold text-brand-burgundy transition hover:border-brand-burgundy">
                                    <i data-lucide="pencil-line" class="h-3.5 w-3.5"></i>
                                    Preencher
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="clipboard-list" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum registro mensal encontrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Crie o primeiro cadastro para iniciar os indicadores SSMA.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $registros->links() }}
        </div>
    </section>
@endsection
