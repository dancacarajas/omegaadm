@extends('layouts.app')

@section('title', 'Feriados - Omega286')
@section('eyebrow', 'Recursos Humanos / Frequência')
@section('page-title', 'Feriados')

@section('actions')
    <a href="{{ route('rh.frequencia.feriados.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo feriado
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Cadastro de feriados</h2>
                <p class="mt-1 text-sm text-brand-gray">Dias cadastrados aqui são abonados automaticamente no ponto (apuração, cartão e frequência diária).</p>
            </div>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <label class="space-y-1">
                    <span class="text-xs font-bold uppercase text-brand-gray">Ano</span>
                    <input type="number" name="ano" value="{{ $ano }}" min="2020" max="2100" class="h-10 w-24 rounded-lg border border-zinc-200 px-2 text-sm">
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-bold uppercase text-brand-gray">Busca</span>
                    <input type="search" name="busca" value="{{ request('busca') }}" placeholder="Nome…" class="h-10 w-48 rounded-lg border border-zinc-200 px-3 text-sm">
                </label>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-zinc-200 px-4 text-sm font-semibold text-brand-black hover:bg-zinc-50">Filtrar</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-3">Data</th>
                        <th class="px-5 py-3">Nome</th>
                        <th class="px-5 py-3">Recorrente</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($feriados as $feriado)
                        <tr class="hover:bg-zinc-50/80">
                            <td class="px-5 py-4 font-semibold text-brand-black">
                                {{ $feriado->data->format('d/m/Y') }}
                                @if ($feriado->recorrente)
                                    <span class="text-xs font-normal text-brand-gray">(todo ano)</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">{{ $feriado->nome }}</td>
                            <td class="px-5 py-4">
                                @if ($feriado->recorrente)
                                    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-800">Sim</span>
                                @else
                                    <span class="text-brand-gray">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($feriado->ativo)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Ativo</span>
                                @else
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-600">Inativo</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('rh.frequencia.index', ['data' => $feriado->recorrente ? $ano.'-'.str_pad((string) $feriado->data->month, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $feriado->data->day, 2, '0', STR_PAD_LEFT) : $feriado->data->format('Y-m-d')]) }}" class="text-xs font-semibold text-brand-gray hover:text-brand-burgundy" title="Ver ponto do dia">Ponto</a>
                                    <a href="{{ route('rh.frequencia.feriados.edit', $feriado) }}" class="text-xs font-semibold text-brand-burgundy hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('rh.frequencia.feriados.destroy', $feriado) }}" class="inline" onsubmit="return confirm('Remover este feriado?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-brand-gray">Nenhum feriado para {{ $ano }}. Cadastre Sexta-feira Santa, feriados nacionais, etc.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($feriados->hasPages())
            <div class="border-t border-zinc-100 px-5 py-4">{{ $feriados->links() }}</div>
        @endif
    </section>
@endsection
