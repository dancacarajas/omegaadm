@extends('layouts.app')

@section('title', 'Tipos de justificativa - Omega286')
@section('eyebrow', 'Frequência')
@section('page-title', 'Tipos de justificativa')

@section('actions')
    <a href="{{ route('rh.frequencia.apuracao.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm">Apuração do Ponto</a>
    <a href="{{ route('rh.frequencia.justificativa-tipos.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo tipo
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-200 p-5">
            <div>
                <h2 class="text-lg font-bold text-brand-black">Catálogo de justificativas</h2>
                <p class="mt-1 text-sm text-brand-gray">Tipos usados na apuração e no ponto (atestado, abono, compensação, etc.).</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="search" name="busca" value="{{ request('busca') }}" placeholder="Buscar…" class="h-10 rounded-lg border border-zinc-200 px-3 text-sm">
                <button type="submit" class="h-10 rounded-lg border border-zinc-200 px-4 text-sm font-semibold">Filtrar</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-brand-gray">
                    <tr>
                        <th class="px-5 py-3">Ordem</th>
                        <th class="px-5 py-3">Nome</th>
                        <th class="px-5 py-3">Categoria</th>
                        <th class="px-5 py-3">Limpa batidas</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($tipos as $tipo)
                        <tr class="hover:bg-zinc-50/80">
                            <td class="px-5 py-3">{{ $tipo->ordem }}</td>
                            <td class="px-5 py-3 font-semibold">{{ $tipo->nome }}</td>
                            <td class="px-5 py-3">{{ \App\Models\FrequenciaJustificativaTipo::CATEGORIAS[$tipo->categoria] ?? $tipo->categoria }}</td>
                            <td class="px-5 py-3">{{ $tipo->limpa_batidas ? 'Sim' : 'Não' }}</td>
                            <td class="px-5 py-3">
                                @if ($tipo->ativo)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Ativo</span>
                                @else
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600">Inativo</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('rh.frequencia.justificativa-tipos.edit', $tipo) }}" class="text-xs font-semibold text-brand-burgundy hover:underline">Editar</a>
                                <form method="POST" action="{{ route('rh.frequencia.justificativa-tipos.destroy', $tipo) }}" class="ml-2 inline" onsubmit="return confirm('Excluir este tipo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-brand-gray">Nenhum tipo cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tipos->hasPages())
            <div class="border-t px-5 py-4">{{ $tipos->links() }}</div>
        @endif
    </section>
@endsection
