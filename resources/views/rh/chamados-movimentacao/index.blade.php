@extends('layouts.app')

@section('title', 'Chamados de Movimentação - Omega286')
@section('page-title', 'Chamados de Movimentação de RH')

@section('actions')
    <a href="{{ route('rh.chamados-movimentacao.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo chamado
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <section class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-6">
        <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold uppercase text-zinc-500">Abertos</p><p class="text-2xl font-bold">{{ $resumo['abertos'] }}</p></article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-xs font-bold uppercase text-amber-800">Atrasados</p><p class="text-2xl font-bold text-amber-900">{{ $resumo['atrasados'] }}</p></article>
        <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold uppercase text-zinc-500">Aguard. aprovação</p><p class="text-2xl font-bold">{{ $resumo['aguardando_aprovacao'] }}</p></article>
        <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold uppercase text-zinc-500">Aguard. DP</p><p class="text-2xl font-bold">{{ $resumo['aguardando_dp'] }}</p></article>
        <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold uppercase text-zinc-500">Aguard. exame</p><p class="text-2xl font-bold">{{ $resumo['aguardando_exame'] }}</p></article>
        <article class="rounded-xl border bg-white p-4"><p class="text-xs font-bold uppercase text-zinc-500">Concluídos (mês)</p><p class="text-2xl font-bold">{{ $resumo['concluidos_mes'] }}</p></article>
    </section>

    <form method="GET" class="mb-4 flex flex-wrap gap-3 rounded-xl border bg-white p-4">
        <input type="search" name="busca" value="{{ $busca }}" placeholder="Colaborador ou matrícula" class="h-10 min-w-[200px] flex-1 rounded-lg border px-3 text-sm">
        <select name="tipo" class="h-10 rounded-lg border px-3 text-sm">
            <option value="">Todos os tipos</option>
            @foreach ($tipos as $k => $l)<option value="{{ $k }}" @selected($tipoFiltro === $k)>{{ $l }}</option>@endforeach
        </select>
        <select name="status" class="h-10 rounded-lg border px-3 text-sm">
            <option value="">Todos os status</option>
            @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($statusFiltro === $k)>{{ $l }}</option>@endforeach
        </select>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="atrasados" value="1" @checked(request()->boolean('atrasados'))> Atrasados</label>
        <button type="submit" class="h-10 rounded-lg bg-brand-burgundy px-4 text-sm font-bold text-white">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-xs font-bold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Chamado</th>
                    <th class="px-4 py-3">Colaborador</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Etapa atual</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Previsto</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($chamados as $c)
                    <tr class="hover:bg-zinc-50">
                        <td class="px-4 py-3 font-mono text-xs font-bold">{{ $c->protocolo }}</td>
                        <td class="px-4 py-3">{{ $c->colaborador->nome }}</td>
                        <td class="px-4 py-3">{{ $c->tipoLabel() }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $c->etapaAtual?->nome ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-bold">{{ $c->statusLabel() }}</span>
                            @if ($c->isAtrasado())<span class="ml-1 text-xs font-bold text-amber-700">Atrasado</span>@endif
                        </td>
                        <td class="px-4 py-3">{{ $c->data_prevista?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('rh.chamados-movimentacao.show', $c) }}" class="text-xs font-bold text-brand-burgundy hover:underline">Ver chamado</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-zinc-500">Nenhum chamado encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-4 py-3">{{ $chamados->links() }}</div>
    </div>
@endsection
