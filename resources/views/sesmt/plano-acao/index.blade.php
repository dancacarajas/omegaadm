@extends('layouts.app')

@section('title', 'Plano de Ação — SSMA - Omega286')
@section('eyebrow', 'SSMA')
@section('page-title', 'Plano de Ação')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Controle de Conformidade
        </a>
        @if ($podeEditar)
            <a href="{{ route('sesmt.plano-acao.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Nova ação
            </a>
        @endif
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">
            {{ session('success') }}
        </div>
    @endif

    <p class="mb-5 max-w-3xl text-sm text-brand-gray">
        Gestão centralizada de ações decorrentes de desvios, inspeções, auditorias, acidentes, quase acidentes e campanhas.
        Para a diretoria, os campos críticos são <strong class="text-brand-black">o que fazer</strong>, <strong class="text-brand-black">prazo</strong> e <strong class="text-brand-black">responsável</strong>.
    </p>

    <section class="mb-5 grid gap-4 sm:grid-cols-3">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Em aberto</p>
            <p class="mt-2 text-3xl font-bold text-brand-black">{{ $totais['abertas'] }}</p>
            <p class="mt-1 text-xs text-brand-gray">Exclui concluída, validada e cancelada</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-red-900/80">Atrasadas (calendário)</p>
            <p class="mt-2 text-3xl font-bold text-red-950">{{ $totais['atrasadas'] }}</p>
            <p class="mt-1 text-xs text-red-900/70">Prazo vencido e ainda não encerradas</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-900/80">Validadas</p>
            <p class="mt-2 text-3xl font-bold text-emerald-950">{{ $totais['validadas'] }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Lista de planos de ação</h2>
                <p class="mt-1 text-sm text-brand-gray">Filtre por status, origem ou responsável. Use a busca livre no texto da ação ou do desvio.</p>
            </div>
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_auto_auto_auto_auto] lg:items-end">
                <label class="relative lg:col-span-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-[2.15rem] h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Busca</span>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Ação, desvio, responsável, detalhe da origem..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
                    <select name="status" class="h-11 w-full min-w-[10rem] rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todos</option>
                        @foreach (\App\Models\SsmaPlanoAcao::STATUS as $k => $label)
                            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Origem</span>
                    <select name="origem" class="h-11 w-full min-w-[10rem] rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todas</option>
                        @foreach (\App\Models\SsmaPlanoAcao::ORIGENS as $k => $label)
                            <option value="{{ $k }}" @selected(request('origem') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável</span>
                    <input name="responsavel" value="{{ request('responsavel') }}" placeholder="Nome..." class="h-11 w-full min-w-[8rem] rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm">
                        <input type="checkbox" name="somente_atrasadas" value="1" @checked(request()->boolean('somente_atrasadas')) class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                        <span class="font-semibold text-brand-black">Só atrasadas</span>
                    </label>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Ação necessária</th>
                        <th class="px-5 py-4">Prazo</th>
                        <th class="px-5 py-4">Responsável</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Origem / tipo</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($planos as $plano)
                        @php
                            $atrasada = $plano->estaAtrasada();
                        @endphp
                        <tr class="transition hover:bg-brand-gray-soft/60 @if ($atrasada) bg-red-50/50 @endif">
                            <td class="px-5 py-4 align-top">
                                <p class="max-w-md font-semibold text-brand-black line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($plano->acao_necessaria), 140) }}</p>
                                <p class="mt-1 max-w-md text-xs text-brand-gray line-clamp-1">Desvio: {{ \Illuminate\Support\Str::limit(strip_tags($plano->descricao_desvio), 80) }}</p>
                            </td>
                            <td class="px-5 py-4 align-top whitespace-nowrap">
                                <span class="font-bold @if ($atrasada) text-red-700 @else text-brand-black @endif">{{ $plano->prazo?->format('d/m/Y') }}</span>
                                @if ($atrasada)
                                    <span class="mt-1 block text-xs font-bold text-red-600">Atrasada</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top font-semibold text-brand-black">{{ $plano->responsavel ?: '—' }}</td>
                            <td class="px-5 py-4 align-top">
                                @php
                                    $st = $plano->status;
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold
                                    @class([
                                        'bg-zinc-100 text-zinc-800' => in_array($st, ['aberta', 'em_andamento'], true),
                                        'bg-amber-100 text-amber-900' => $st === 'aguardando_evidencia',
                                        'bg-blue-100 text-blue-900' => $st === 'concluida',
                                        'bg-emerald-100 text-emerald-900' => $st === 'validada',
                                        'bg-red-100 text-red-900' => $st === 'vencida',
                                        'bg-zinc-200 text-zinc-700' => $st === 'cancelada',
                                    ])">
                                    {{ $plano->rotuloStatus() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top text-brand-gray">
                                <span class="font-medium text-brand-black">{{ $plano->rotuloOrigem() }}</span>
                                @if ($plano->origem_detalhe)
                                    <span class="block text-xs">{{ \Illuminate\Support\Str::limit($plano->origem_detalhe, 48) }}</span>
                                @endif
                                <span class="mt-1 block text-xs">{{ $plano->rotuloTipo() }} · {{ $plano->rotuloPrioridade() }} · Risco {{ $plano->rotuloNivelRisco() }}</span>
                            </td>
                            <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                @if ($podeEditar)
                                    <a href="{{ route('sesmt.plano-acao.edit', $plano) }}" class="inline-flex items-center gap-1 text-sm font-bold text-brand-burgundy underline-offset-2 hover:underline">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('sesmt.plano-acao.destroy', $plano) }}" class="mt-2 inline" onsubmit="return confirm('Remover este plano de ação?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-red-600 underline-offset-2 hover:underline">Excluir</button>
                                    </form>
                                @else
                                    <span class="text-xs text-brand-gray">Somente leitura</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-brand-gray">Nenhum plano de ação encontrado com os filtros atuais.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($planos->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4">
                {{ $planos->links() }}
            </div>
        @endif
    </section>
@endsection
