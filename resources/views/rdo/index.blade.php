@extends('layouts.app')

@section('title', 'RDO - Omega286')
@section('eyebrow', 'Operação')
@section('page-title', 'RDO')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('rdo.export.excel', request()->query()) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
            <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
            Excel
        </a>
        <a href="{{ route('rdo.export.pdf', request()->query()) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:bg-brand-burgundy-soft/80">
            <i data-lucide="file-text" class="h-4 w-4"></i>
            PDF
        </a>
        <a href="{{ route('rdo.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Novo RDO
        </a>
    </div>
@endsection

@section('content')
    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">RDOs registrados</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['total'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <i data-lucide="calendar-check" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Hoje</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['hoje'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-gray-soft text-brand-black">
                <i data-lucide="calendar-days" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">No mês</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['mes'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-brand-gray p-5 text-white shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/20 text-white">
                <i data-lucide="image" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-white/80">Com evidência</p>
            <p class="mt-1 text-3xl font-bold">{{ $indicadores['com_evidencia'] }}</p>
        </article>
    </section>

    <section class="mb-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Modo offline</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Diário de bordo mesmo sem internet</h2>
                <p class="mt-1 text-sm text-brand-gray">O RDO pode ser salvo no navegador. Quando a conexão voltar, o sistema transmite automaticamente.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm font-semibold">
                <span id="rdo-online-state" class="rounded-full border border-zinc-200 bg-brand-gray-soft px-3 py-1 text-brand-gray">Verificando conexão...</span>
                <span id="rdo-pending-count" class="rounded-full border border-brand-burgundy/20 bg-brand-burgundy-soft px-3 py-1 text-brand-burgundy">0 pendentes</span>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-brand-black">Relatórios diários</h2>
                    <p class="mt-1 text-sm text-brand-gray">Consulte, filtre e exporte os registros transmitidos pelas equipes de campo.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('rdo.export.excel', request()->query()) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                        <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                        Exportar Excel
                    </a>
                    <a href="{{ route('rdo.export.pdf', request()->query()) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:bg-brand-burgundy-soft/80">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                        Relatório PDF
                    </a>
                </div>
            </div>

            <form method="GET" class="grid gap-2 lg:grid-cols-[1fr_170px_170px_auto]">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por frente, área, supervisor..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">RDO</th>
                        <th class="px-5 py-4">Data</th>
                        <th class="px-5 py-4">Frente / área</th>
                        <th class="px-5 py-4">Responsáveis</th>
                        <th class="px-5 py-4">Atividades</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($relatorios as $relatorio)
                        <tr class="transition hover:bg-brand-gray-soft/60">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $relatorio->titulo ?: 'Relatório diário de obra' }}</p>
                                        <p class="text-xs text-brand-gray">{{ $relatorio->contrato ?: 'Contrato não informado' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-semibold text-brand-black">{{ $relatorio->data?->format('d/m/Y') }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-brand-black">{{ $relatorio->frente ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ $relatorio->area ?: $relatorio->disciplina ?: 'Área não informada' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-brand-black">{{ $relatorio->supervisor_nome ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ $relatorio->encarregado_nome ?: 'Encarregado não informado' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-brand-gray-soft px-3 py-1 text-xs font-bold text-brand-gray">{{ count($relatorio->atividades ?? []) }} itens</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('rdo.show', $relatorio) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('rdo.pdf', $relatorio) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft px-3 text-xs font-semibold text-brand-burgundy shadow-sm transition hover:bg-brand-burgundy-soft/80">
                                        <i data-lucide="file-text" class="h-4 w-4"></i>
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="clipboard-plus" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum RDO transmitido.</p>
                                <p class="mt-1 text-sm text-brand-gray">Crie o primeiro relatório diário de obra.</p>
                                <a href="{{ route('rdo.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Novo RDO
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $relatorios->links() }}
        </div>
    </section>
@endsection

@push('scripts')
    @include('rdo.partials.offline-sync-script')
@endpush
