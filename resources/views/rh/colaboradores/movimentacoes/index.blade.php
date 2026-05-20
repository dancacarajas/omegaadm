@extends('layouts.app')

@section('title', 'Movimentações de efetivo - Omega286')
@section('eyebrow', 'Recursos Humanos / Efetivo')
@section('page-title', 'Movimentações de efetivo')

@section('actions')
    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="users" class="h-4 w-4"></i>
        Efetivo
    </a>
@endsection

@section('content')
    <form method="GET" action="{{ route('rh.efetivo.movimentacoes.index') }}" class="mb-5 flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm sm:flex-row sm:flex-wrap sm:items-end">
        <div class="flex-1 min-w-[200px]">
            <label for="busca" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Buscar colaborador</label>
            <input type="search" name="busca" id="busca" value="{{ $busca }}" placeholder="Nome ou matrícula" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
        </div>
        <div class="w-full sm:w-56">
            <label for="tipo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo</label>
            <select name="tipo" id="tipo" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20">
                <option value="">Todos</option>
                @foreach ($tipos as $key => $label)
                    <option value="{{ $key }}" @selected($tipoFiltro === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="h-11 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-burgundy-dark">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] border-collapse text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-bold uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-3">Data</th>
                        <th class="px-4 py-3">Colaborador</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Resumo</th>
                        <th class="px-4 py-3">Registrado por</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($movimentacoes as $mov)
                        <tr class="hover:bg-zinc-50/80">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-brand-black">
                                {{ $mov->data_inicio->format('d/m/Y') }}
                                @if ($mov->data_fim)
                                    <span class="text-brand-gray"> — {{ $mov->data_fim->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('rh.efetivo.show', $mov->colaborador) }}" class="font-semibold text-brand-burgundy hover:underline">{{ $mov->colaborador->nome }}</a>
                                @if ($mov->colaborador->matricula)
                                    <p class="text-xs text-brand-gray">{{ $mov->colaborador->matricula }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-bold text-brand-burgundy">{{ $mov->tipoLabel() }}</span>
                            </td>
                            <td class="max-w-md px-4 py-3 text-brand-gray">{{ $mov->resumoAlteracao() }}</td>
                            <td class="px-4 py-3 text-brand-gray">{{ $mov->registradoPor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('rh.efetivo.show', $mov->colaborador) }}" class="text-xs font-semibold text-brand-burgundy hover:underline">Ficha</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-brand-gray">Nenhuma movimentação registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movimentacoes->hasPages())
            <div class="border-t border-zinc-100 px-4 py-3">{{ $movimentacoes->links() }}</div>
        @endif
    </div>
@endsection
