<section class="mb-5 overflow-hidden rounded-xl border border-sky-200/80 bg-sky-50/50 p-5 shadow-sm">
    <h2 class="text-sm font-bold text-brand-black">Consulta de confirmações</h2>
    <p class="mt-1 text-sm text-brand-gray">
        Visualize quem os supervisores confirmaram como presente ou ausente na obra.
        Este módulo <strong>não</strong> gera batida de ponto nem altera a frequência do RH.
    </p>
</section>

<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Confirmados no dia</p>
        <p class="mt-1 text-2xl font-bold text-brand-black">{{ $resumo['total'] }}</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Presentes</p>
        <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $resumo['presentes'] }}</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-amber-800">Ausentes</p>
        <p class="mt-1 text-2xl font-bold text-amber-950">{{ $resumo['ausentes'] }}</p>
    </div>
</div>

@if ($podeExportar ?? true)
    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/50 px-5 py-4">
            <h2 class="text-sm font-bold text-brand-black">Exportar folha de ponto</h2>
            <p class="mt-1 text-sm text-brand-gray">
                Gera planilha Excel no formato de folha de ponto: <strong>P</strong> para presente e <strong>F</strong> para falta, por colaborador e por dia.
            </p>
        </div>
        <form method="GET" action="{{ route('medicao.presenca-obra.exportar-excel') }}" class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5">
            @if ($errors->has('data_inicio') || $errors->has('data_fim'))
                <div class="sm:col-span-2 lg:col-span-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first('data_inicio') ?: $errors->first('data_fim') }}
                </div>
            @endif
            <div>
                <label for="data_inicio" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Período inicial</label>
                <input type="date" name="data_inicio" id="data_inicio" value="{{ old('data_inicio', $dataInicioPadrao) }}" required class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
            </div>
            <div>
                <label for="data_fim" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Período final</label>
                <input type="date" name="data_fim" id="data_fim" value="{{ old('data_fim', $dataFimPadrao) }}" required class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
            </div>
            <div>
                <label for="centro_custo_export" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Centro de custo</label>
                <select name="centro_custo" id="centro_custo_export" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
                    <option value="">Todos</option>
                    @foreach ($centrosCusto as $cc)
                        <option value="{{ $cc }}" @selected(old('centro_custo', $centroCusto) === $cc)>{{ $cc }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end sm:col-span-2 lg:col-span-2">
                <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 text-sm font-semibold text-emerald-800 shadow-sm transition hover:border-emerald-400 hover:bg-emerald-100">
                    <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                    Gerar planilha Excel
                </button>
            </div>
        </form>
    </section>
@endif

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
    <form method="GET" action="{{ $urlFiltro }}" class="grid gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/50 p-5 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="data" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Data</label>
            <input type="date" name="data" id="data" value="{{ $data }}" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
        </div>
        <div>
            <label for="status" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Status</label>
            <select name="status" id="status" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
                <option value="">Todos</option>
                <option value="presente" @selected($status === 'presente')>Presente</option>
                <option value="ausente" @selected($status === 'ausente')>Ausente</option>
            </select>
        </div>
        <div>
            <label for="centro_custo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Centro de custo</label>
            <select name="centro_custo" id="centro_custo" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
                <option value="">Todos</option>
                @foreach ($centrosCusto as $cc)
                    <option value="{{ $cc }}" @selected($centroCusto === $cc)>{{ $cc }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="busca" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Busca</label>
            <input type="search" name="busca" id="busca" value="{{ $busca }}" placeholder="Nome ou matrícula" class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black">
        </div>
        <div class="flex items-end">
            <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                <i data-lucide="search" class="h-4 w-4"></i>
                Filtrar
            </button>
        </div>
    </form>

    <div class="lg:hidden divide-y divide-zinc-100">
        @forelse ($registros as $registro)
            <article class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-brand-black">{{ $registro->colaborador?->nome }}</p>
                        <p class="mt-0.5 text-xs text-brand-gray">{{ $registro->colaborador?->matricula ?: 'Sem matrícula' }} · {{ $registro->colaborador?->cargo ?: '—' }}</p>
                    </div>
                    @if ($registro->isPresente())
                        <span class="inline-flex shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Presente</span>
                    @else
                        <span class="inline-flex shrink-0 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">Ausente</span>
                    @endif
                </div>
                <dl class="mt-3 grid gap-2 text-xs">
                    <div class="flex justify-between gap-3">
                        <dt class="font-semibold text-brand-gray">Centro de custo</dt>
                        <dd class="text-right text-brand-black">{{ $registro->centro_custo ?: ($registro->colaborador?->centro_custo ?: '—') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-semibold text-brand-gray">Confirmado por</dt>
                        <dd class="text-right text-brand-black">{{ $registro->confirmadoPor?->nome ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="font-semibold text-brand-gray">Horário</dt>
                        <dd class="text-right text-brand-black">{{ $registro->confirmado_em?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </article>
        @empty
            <p class="px-5 py-12 text-center text-sm text-brand-gray">
                Nenhuma confirmação registrada para esta data.
            </p>
        @endforelse
    </div>

    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-[840px] text-left text-sm">
            <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                <tr>
                    <th class="px-5 py-4">Colaborador</th>
                    <th class="px-4 py-4">Centro de custo</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Confirmado por</th>
                    <th class="px-5 py-4">Horário</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($registros as $registro)
                    <tr class="transition hover:bg-brand-gray-soft/40">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-brand-black">{{ $registro->colaborador?->nome }}</p>
                            <p class="text-xs text-brand-gray">{{ $registro->colaborador?->matricula ?: 'Sem matrícula' }} · {{ $registro->colaborador?->cargo ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-4 text-brand-gray">{{ $registro->centro_custo ?: ($registro->colaborador?->centro_custo ?: '—') }}</td>
                        <td class="px-4 py-4">
                            @if ($registro->isPresente())
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Presente</span>
                            @else
                                <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">Ausente</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-brand-gray">
                            {{ $registro->confirmadoPor?->nome ?? '—' }}
                            <span class="block text-xs">{{ $registro->confirmadoPor?->matricula }}</span>
                        </td>
                        <td class="px-5 py-4 text-brand-gray">
                            {{ $registro->confirmado_em?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-brand-gray">
                            Nenhuma confirmação registrada para esta data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($registros->hasPages())
        <div class="border-t border-zinc-200 px-5 py-4">
            {{ $registros->links() }}
        </div>
    @endif
</section>
