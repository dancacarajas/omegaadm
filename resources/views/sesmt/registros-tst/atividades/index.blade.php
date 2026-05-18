@extends('layouts.app')

@section('title', 'Cadastro de Atividades — Registros TST')
@section('eyebrow', 'SSMA / Registros TST')
@section('page-title', 'Cadastro de Atividades')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.registros-tst.registros.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="clipboard-list" class="h-4 w-4"></i>
            Registros de campo
        </a>
        @if ($podeEditar)
            <a href="{{ route('sesmt.registros-tst.atividades.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Nova atividade
            </a>
        @endif
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-950">{{ session('error') }}</div>
    @endif

    <p class="mb-5 max-w-3xl text-sm text-brand-gray">
        Cadastre os tipos de atividade do formulário TST. No <strong class="text-brand-black">painel SSMA</strong>, todas as atividades <strong class="text-brand-black">ativas</strong> ficam disponíveis.
        Marque <strong class="text-brand-black">Exibir no app</strong> para cada atividade que deve aparecer em
        <a href="{{ route('tst-campo.identificar') }}" target="_blank" rel="noopener" class="font-semibold text-brand-burgundy hover:underline">/registro-tst</a> para os colaboradores.
    </p>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-lg font-bold text-brand-black">Atividades cadastradas</h2>
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="flex-1">
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Busca</span>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Nome da atividade..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="sm:w-40">
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
                    <select name="status" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todas</option>
                        <option value="ativas" @selected(request('status') === 'ativas')>Ativas</option>
                        <option value="inativas" @selected(request('status') === 'inativas')>Inativas</option>
                    </select>
                </label>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filtrar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-xs font-bold uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-3">Nome</th>
                        <th class="px-5 py-3">Ordem</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">App colaborador</th>
                        <th class="px-5 py-3">Registros</th>
                        @if ($podeEditar)
                            <th class="px-5 py-3 text-right">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($atividades as $atividade)
                        <tr class="hover:bg-brand-gray-soft/40">
                            <td class="px-5 py-3 font-semibold text-brand-black">{{ $atividade->nome }}</td>
                            <td class="px-5 py-3 text-brand-gray">{{ $atividade->ordem }}</td>
                            <td class="px-5 py-3">
                                @if ($atividade->ativo)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Ativa</span>
                                @else
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600">Inativa</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($atividade->ativo && $atividade->exibir_no_app)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-900">
                                        <i data-lucide="smartphone" class="h-3 w-3"></i>
                                        Sim
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-600">Não</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-brand-gray">{{ $atividade->registros_count }}</td>
                            @if ($podeEditar)
                                <td class="px-5 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('sesmt.registros-tst.atividades.edit', $atividade) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-brand-gray transition hover:border-brand-burgundy hover:text-brand-burgundy" title="Editar">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('sesmt.registros-tst.atividades.destroy', $atividade) }}" class="inline" onsubmit="return confirm('Remover esta atividade?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-brand-gray transition hover:border-red-300 hover:text-red-700" title="Excluir">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $podeEditar ? 6 : 5 }}" class="px-5 py-10 text-center text-sm text-brand-gray">
                                Nenhuma atividade cadastrada.
                                @if ($podeEditar)
                                    <a href="{{ route('sesmt.registros-tst.atividades.create') }}" class="font-semibold text-brand-burgundy hover:underline">Cadastrar a primeira</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($atividades->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4">{{ $atividades->links() }}</div>
        @endif
    </section>
@endsection
