@extends('layouts.app')

@section('title', 'Gestão de Riscos — SSMA - Omega286')
@section('eyebrow', 'SSMA')
@section('page-title', 'Gestão de Riscos')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Controle de Conformidade
        </a>
        @if ($podeEditar)
            <a href="{{ route('sesmt.riscos.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Novo risco
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

    <p class="mb-6 max-w-3xl text-sm text-brand-gray">
        Cadastro dedicado de riscos SSMA, fora do registro mensal. A <strong class="text-brand-black">classificação final</strong> é calculada automaticamente a partir da probabilidade e da severidade (matriz 5×5).
    </p>

    @php
        $matriz = $painel['matriz'];
        $maxArea = max(1, $painel['por_area']->max('total') ?? 1);
        $maxEvo = max(1, collect($painel['evolucao'])->max('total') ?? 1);
    @endphp

    <section class="mb-6 grid gap-4 lg:grid-cols-3">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Matriz de risco (P × S)</h2>
            <p class="mt-1 text-xs text-brand-gray">Contagem de registros ativos por célula (exclui cancelados). Linhas = probabilidade · Colunas = severidade.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[320px] border-collapse text-center text-xs">
                    <thead>
                        <tr>
                            <th class="border border-zinc-200 bg-zinc-50 p-2 text-brand-gray">P \ S</th>
                            @for ($s = 1; $s <= 5; $s++)
                                <th class="border border-zinc-200 bg-zinc-50 p-2 font-semibold text-brand-black">{{ $s }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @for ($p = 5; $p >= 1; $p--)
                            <tr>
                                <th class="border border-zinc-200 bg-zinc-50 p-2 font-semibold text-brand-black">{{ $p }}</th>
                                @for ($s = 1; $s <= 5; $s++)
                                    @php
                                        $n = $matriz[$p][$s] ?? 0;
                                        $cls = \App\Models\SsmaRisco::classificacaoFromScores($p, $s);
                                        $bg = match ($cls) {
                                            'baixo' => 'bg-emerald-100',
                                            'medio' => 'bg-amber-100',
                                            'alto' => 'bg-orange-200',
                                            'critico' => 'bg-red-200',
                                            default => 'bg-zinc-100',
                                        };
                                    @endphp
                                    <td class="border border-zinc-200 p-2 {{ $bg }}">
                                        <span class="font-bold text-brand-black">{{ $n }}</span>
                                    </td>
                                @endfor
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex flex-wrap gap-3 text-xs text-brand-gray">
                <span><span class="inline-block h-3 w-3 rounded bg-emerald-100 align-middle"></span> Baixo</span>
                <span><span class="inline-block h-3 w-3 rounded bg-amber-100 align-middle"></span> Médio</span>
                <span><span class="inline-block h-3 w-3 rounded bg-orange-200 align-middle"></span> Alto</span>
                <span><span class="inline-block h-3 w-3 rounded bg-red-200 align-middle"></span> Crítico</span>
            </div>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-red-900/80">Riscos críticos</h2>
            <p class="mt-2 text-4xl font-bold text-red-950">{{ $painel['criticos'] }}</p>
            <p class="mt-2 text-xs text-red-900/80">Registros não cancelados com classificação <strong>Crítica</strong> (pela matriz).</p>
        </article>
    </section>

    <section class="mb-6 grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Riscos por área / local</h2>
            <p class="mt-1 text-xs text-brand-gray">Até 12 áreas com mais registros (ativos).</p>
            <ul class="mt-4 space-y-3">
                @forelse ($painel['por_area'] as $row)
                    @php $w = round(($row['total'] / $maxArea) * 100); @endphp
                    <li>
                        <div class="flex justify-between text-xs font-semibold text-brand-black">
                            <span class="truncate pr-2">{{ $row['rotulo'] }}</span>
                            <span>{{ $row['total'] }}</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-100">
                            <div class="h-full rounded-full bg-brand-burgundy transition-all" style="width: {{ $w }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-brand-gray">Nenhum risco cadastrado ainda.</li>
                @endforelse
            </ul>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Riscos por categoria</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach (\App\Models\SsmaRisco::CATEGORIAS as $key => $label)
                    @php
                        $row = $painel['por_categoria']->get($key);
                        $total = $row ? (int) $row->total : 0;
                    @endphp
                    <div class="rounded-lg border border-zinc-100 bg-brand-gray-soft/40 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-bold text-brand-black">{{ $total }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Evolução — riscos tratados por mês</h2>
        <p class="mt-1 text-xs text-brand-gray">Quantidade de registros com status <strong>Tratado</strong> e data de tratamento no mês (últimos 12 meses).</p>
        <div class="mt-6 flex h-40 items-end gap-1 sm:gap-2">
            @foreach ($painel['evolucao'] as $ponto)
                @php $h = round(($ponto['total'] / $maxEvo) * 100); @endphp
                <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1">
                    <span class="text-[10px] font-bold text-brand-black sm:text-xs">{{ $ponto['total'] }}</span>
                    <div class="w-full max-w-[2.5rem] rounded-t-md bg-emerald-500/90 transition-all" style="height: {{ max(8, $h) }}%" title="{{ $ponto['rotulo'] }}: {{ $ponto['total'] }}"></div>
                    <span class="truncate text-[9px] text-brand-gray sm:text-[10px]">{{ $ponto['rotulo'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Cadastro de riscos</h2>
                <p class="mt-1 text-sm text-brand-gray">Filtros e listagem. Edite o registro para anexar evidência ou alterar status.</p>
            </div>
            <form method="GET" class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
                <label class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-[2.1rem] h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Busca</span>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Risco, atividade, área, responsável..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
                    <select name="status" class="h-11 w-full min-w-[10rem] rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todos</option>
                        @foreach (\App\Models\SsmaRisco::STATUS as $k => $label)
                            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Categoria</span>
                    <select name="categoria" class="h-11 w-full min-w-[10rem] rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todas</option>
                        @foreach (\App\Models\SsmaRisco::CATEGORIAS as $k => $label)
                            <option value="{{ $k }}" @selected(request('categoria') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-wide text-brand-gray">Classificação</span>
                    <select name="classificacao" class="h-11 w-full min-w-[10rem] rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todas</option>
                        @foreach (\App\Models\SsmaRisco::CLASSIFICACOES as $k => $label)
                            <option value="{{ $k }}" @selected(request('classificacao') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filtrar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Risco</th>
                        <th class="px-5 py-4">Área</th>
                        <th class="px-5 py-4">Categoria</th>
                        <th class="px-5 py-4">P×S</th>
                        <th class="px-5 py-4">Classif.</th>
                        <th class="px-5 py-4">Responsável</th>
                        <th class="px-5 py-4">Prazo</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($riscos as $risco)
                        <tr class="transition hover:bg-brand-gray-soft/60">
                            <td class="px-5 py-4 align-top">
                                <p class="max-w-xs font-semibold text-brand-black line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($risco->risco_identificado), 120) }}</p>
                            </td>
                            <td class="px-5 py-4 align-top text-brand-gray">{{ $risco->area_local ?: '—' }}</td>
                            <td class="px-5 py-4 align-top">{{ $risco->rotuloCategoria() }}</td>
                            <td class="px-5 py-4 align-top whitespace-nowrap font-mono text-xs">{{ $risco->probabilidade }}×{{ $risco->severidade }}</td>
                            <td class="px-5 py-4 align-top">
                                @php $cf = $risco->classificacao_final; @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold
                                    @class([
                                        'bg-emerald-100 text-emerald-900' => $cf === 'baixo',
                                        'bg-amber-100 text-amber-900' => $cf === 'medio',
                                        'bg-orange-200 text-orange-950' => $cf === 'alto',
                                        'bg-red-200 text-red-950' => $cf === 'critico',
                                    ])">{{ $risco->rotuloClassificacao() }}</span>
                            </td>
                            <td class="px-5 py-4 align-top font-medium text-brand-black">{{ $risco->responsavel ?: '—' }}</td>
                            <td class="px-5 py-4 align-top whitespace-nowrap">{{ $risco->prazo?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-5 py-4 align-top">
                                <span class="text-xs font-semibold text-brand-gray">{{ $risco->rotuloStatus() }}</span>
                            </td>
                            <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                @if ($podeEditar)
                                    <a href="{{ route('sesmt.riscos.edit', $risco) }}" class="inline-flex items-center gap-1 text-sm font-bold text-brand-burgundy underline-offset-2 hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('sesmt.riscos.destroy', $risco) }}" class="mt-2 inline" onsubmit="return confirm('Remover este risco?');">
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
                            <td colspan="9" class="px-5 py-12 text-center text-sm text-brand-gray">Nenhum risco com os filtros atuais.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($riscos->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4">{{ $riscos->links() }}</div>
        @endif
    </section>
@endsection
