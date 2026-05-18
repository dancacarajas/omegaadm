@extends('layouts.app')

@section('title', 'Registros de campo — Registros TST')
@section('eyebrow', 'SSMA / Registros TST')
@section('page-title', 'Registros de campo')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.registros-tst.atividades.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="list" class="h-4 w-4"></i>
            Cadastro de atividades
        </a>
        @if ($podeCriar)
            <a href="{{ route('sesmt.registros-tst.registros.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Novo registro
            </a>
        @endif
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('success') }}</div>
    @endif

    <p class="mb-5 max-w-3xl text-sm text-brand-gray">
        Registros enviados pelos colaboradores (substitui o Google Forms <strong class="text-brand-black">Registros TST CT-286</strong>).
        @if ($filtrosAtivos)
            Os indicadores e gráficos abaixo refletem os <strong class="text-brand-black">filtros aplicados</strong>.
        @else
            Indicadores consolidados e tendência dos últimos 12 meses.
        @endif
    </p>

    <div class="mb-6 flex flex-col gap-3 rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft/30 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-brand-black">App mobile para colaboradores</p>
            <p class="mt-1 text-xs text-brand-gray">Compartilhe o link para registro em campo (matrícula + CPF, sem login do sistema).</p>
        </div>
        <a href="{{ route('tst-campo.identificar') }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
            <i data-lucide="smartphone" class="h-4 w-4"></i>
            Abrir /registro-tst
        </a>
    </div>

    @php
        $maxMensal = max(1, (int) collect($serieMensal)->max('total'));
        $maxAtividade = max(1, (int) collect($porAtividade)->max('total'));
        $maxColab = max(1, (int) collect($topColaboradores)->max('total'));
    @endphp

    <section class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Total de registros</p>
            <p class="mt-2 text-3xl font-bold text-brand-black">{{ number_format($cartoes['total'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-brand-gray">No recorte atual</p>
        </article>
        <article class="rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft/40 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Mês atual</p>
            <p class="mt-2 text-3xl font-bold text-brand-burgundy">{{ number_format($cartoes['mes_atual'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-brand-gray">{{ now()->translatedFormat('F Y') }}</p>
        </article>
        <article class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-900/80">Colaboradores</p>
            <p class="mt-2 text-3xl font-bold text-blue-950">{{ $cartoes['colaboradores_distintos'] }}</p>
            <p class="mt-1 text-xs text-blue-900/70">Com pelo menos 1 registro</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-900/80">Com atividade</p>
            <p class="mt-2 text-3xl font-bold text-emerald-950">{{ $cartoes['com_atividade'] }}</p>
            <p class="mt-1 text-xs text-emerald-900/70">Tipo informado no formulário</p>
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-900/80">Sem atividade</p>
            <p class="mt-2 text-3xl font-bold text-amber-950">{{ $cartoes['sem_atividade'] }}</p>
            <p class="mt-1 text-xs text-amber-900/70">Campo opcional em branco</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Média / dia</p>
            <p class="mt-2 text-3xl font-bold text-brand-black">{{ $cartoes['media_dia'] !== null ? number_format($cartoes['media_dia'], 1, ',', '.') : '—' }}</p>
            <p class="mt-1 text-xs text-brand-gray">
                @if ($cartoes['dias_periodo'] > 0)
                    Em {{ $cartoes['dias_periodo'] }} dia(s) do período
                @else
                    Sem dados no período
                @endif
            </p>
        </article>
    </section>

    <div class="mb-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Evolução mensal</h2>
                    <p class="mt-1 text-xs text-brand-gray">Registros por competência — últimos 12 meses{{ $filtrosAtivos ? ' (demais filtros aplicados)' : '' }}.</p>
                </div>
            </div>
            <div class="mt-6 flex h-52 items-end gap-1 overflow-x-auto pb-6 sm:gap-2">
                @foreach ($serieMensal as $ponto)
                    @php $altura = round(($ponto['total'] / $maxMensal) * 100); @endphp
                    <div class="flex min-w-[2.25rem] flex-1 flex-col items-center justify-end sm:min-w-0">
                        <div class="flex h-40 w-full max-w-[2.5rem] flex-col items-center justify-end">
                            @if ($ponto['total'] > 0)
                                <span class="mb-1 text-[10px] font-semibold text-brand-black">{{ $ponto['total'] }}</span>
                            @endif
                            <div
                                class="w-full rounded-t bg-brand-burgundy"
                                style="height: {{ $ponto['total'] > 0 ? max(8, $altura) : 4 }}%"
                            title="{{ $ponto['rotulo'] }}: {{ $ponto['total'] }} registro(s)"
                            ></div>
                        </div>
                        <span class="mt-2 truncate text-center text-[9px] font-medium text-brand-gray sm:text-[10px]">{{ $ponto['rotulo'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Por atividade</h2>
            <p class="mt-1 text-xs text-brand-gray">Top {{ count($porAtividade) }} no recorte filtrado.</p>
            @if (count($porAtividade) === 0)
                <p class="mt-8 text-center text-sm text-brand-gray">Sem dados para exibir.</p>
            @else
                <ul class="mt-5 space-y-3">
                    @foreach ($porAtividade as $item)
                        @php $pct = round(($item['total'] / $maxAtividade) * 100); @endphp
                        <li>
                            <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                                <span class="min-w-0 truncate font-semibold text-brand-black" title="{{ $item['nome'] }}">{{ $item['nome'] }}</span>
                                <span class="shrink-0 font-bold text-brand-burgundy">{{ $item['total'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100">
                                <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ max(4, $pct) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Top colaboradores</h2>
            <p class="mt-1 text-xs text-brand-gray">Quem mais registrou no período.</p>
            @if (count($topColaboradores) === 0)
                <p class="mt-8 text-center text-sm text-brand-gray">Sem dados para exibir.</p>
            @else
                <ul class="mt-5 space-y-3">
                    @foreach ($topColaboradores as $item)
                        @php $pct = round(($item['total'] / $maxColab) * 100); @endphp
                        <li>
                            <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                                <span class="min-w-0 truncate font-semibold text-brand-black" title="{{ $item['nome'] }}">{{ $item['nome'] }}</span>
                                <span class="shrink-0 font-bold text-blue-800">{{ $item['total'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ max(4, $pct) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

    </div>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="mb-4 text-lg font-bold text-brand-black">Lista de registros</h2>
            <form method="GET" class="grid gap-3 lg:grid-cols-2 xl:grid-cols-6">
                <label class="lg:col-span-2">
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Busca</span>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Descrição, colaborador, atividade..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">De</span>
                    <input type="date" name="data_de" value="{{ request('data_de') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Até</span>
                    <input type="date" name="data_ate" value="{{ request('data_ate') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Atividade</span>
                    <select name="atividade_id" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todas</option>
                        @foreach ($atividades as $atv)
                            <option value="{{ $atv->id }}" @selected((string) request('atividade_id') === (string) $atv->id)>{{ $atv->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Colaborador</span>
                    <select name="colaborador_id" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todos</option>
                        @foreach ($colaboradores as $colab)
                            <option value="{{ $colab->id }}" @selected((string) request('colaborador_id') === (string) $colab->id)>{{ $colab->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-end lg:col-span-2 xl:col-span-6">
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-xs font-bold uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-3">Data</th>
                        <th class="px-5 py-3">Colaborador</th>
                        <th class="px-5 py-3">Atividade</th>
                        <th class="px-5 py-3">Descrição</th>
                        <th class="px-5 py-3">Enviado em</th>
                        <th class="px-5 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($registros as $registro)
                        <tr class="hover:bg-brand-gray-soft/40">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-brand-black">{{ $registro->data->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">{{ $registro->colaborador?->nome ?? '—' }}</td>
                            <td class="px-5 py-3 text-brand-gray">{{ $registro->atividade?->nome ?? '—' }}</td>
                            <td class="max-w-xs truncate px-5 py-3 text-brand-gray" title="{{ $registro->descricao }}">{{ Str::limit($registro->descricao, 60) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-brand-gray">{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('sesmt.registros-tst.registros.show', $registro) }}" class="inline-flex h-9 items-center rounded-lg border border-zinc-200 px-3 text-xs font-semibold text-brand-black transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-brand-gray">
                                Nenhum registro encontrado.
                                @if ($podeCriar)
                                    <a href="{{ route('sesmt.registros-tst.registros.create') }}" class="font-semibold text-brand-burgundy hover:underline">Criar o primeiro registro</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4">{{ $registros->links() }}</div>
        @endif
    </section>
@endsection
