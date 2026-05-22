@extends('layouts.app')

@section('title', 'Efetivo - Omega286')
@section('eyebrow', 'RH / Efetivo')
@section('page-title', 'Efetivo')

@section('actions')
    @php
        $podeRhCriar = auth()->user()?->podeAcaoNoModulo('rh', 'criar') ?? true;
        $podeRhVisualizar = auth()->user()?->podeAcaoNoModulo('rh', 'visualizar') ?? true;
    @endphp
    @if (auth()->user()?->podeSecaoRh('chamados_movimentacao'))
        <a href="{{ route('rh.chamados-movimentacao.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
            <i data-lucide="clipboard-list" class="h-4 w-4 text-brand-burgundy"></i>
            Movimentações
        </a>
    @endif
    @if ($podeRhVisualizar)
        <a href="{{ route('rh.efetivo.exportar-excel', request()->only(['busca', 'cargo', 'ordenacao'])) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
            <i data-lucide="download" class="h-4 w-4"></i>
            Exportar
        </a>
    @endif
    @if ($podeRhCriar)
        <a href="{{ route('rh.efetivo.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="user-plus" class="h-4 w-4"></i>
            Novo colaborador
        </a>
    @endif
@endsection

@section('content')
    @php
        $podeRhCriar = auth()->user()?->podeAcaoNoModulo('rh', 'criar') ?? true;
        $podeRhEditar = auth()->user()?->podeAcaoNoModulo('rh', 'editar') ?? true;
        $podeRhExcluir = auth()->user()?->podeAcaoNoModulo('rh', 'excluir') ?? true;
        $mobilizacaoLabel = [
            'pendente' => 'Pendente',
            'postado_sgc' => 'Postado no SGC',
            'aprovado' => 'Aprovado',
            'mobilizacao_concluida' => 'Mobilização concluída',
        ];
        $statusBadge = [
            'ativo' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            'afastado' => 'bg-amber-50 text-amber-900 ring-amber-200',
            'desligado' => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
        ];
        $statusDot = [
            'ativo' => 'bg-emerald-500',
            'afastado' => 'bg-amber-500',
            'desligado' => 'bg-zinc-400',
        ];
        $totalPagina = $colaboradores->count();
    @endphp

    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-900 shadow-sm">
            <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200/80 bg-gradient-to-r from-red-50 to-white px-5 py-4 text-sm text-red-900 shadow-sm">
            <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0 text-red-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if (session('warning'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200/80 bg-gradient-to-r from-amber-50 to-white px-5 py-4 text-sm text-amber-900 shadow-sm">
            <i data-lucide="alert-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif
    @if (session('import_errors'))
        <div class="mb-6 rounded-2xl border border-amber-200/80 bg-gradient-to-r from-amber-50 to-white px-5 py-4 text-sm text-amber-900 shadow-sm">
            <p class="flex items-center gap-2 font-bold">
                <i data-lucide="file-warning" class="h-4 w-4"></i>
                Algumas linhas não foram importadas
            </p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-amber-800">
                @foreach (session('import_errors') as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Hero (paleta só vinho — sem cinza) --}}
    <section class="relative mb-6 overflow-hidden rounded-3xl border border-brand-burgundy/20 bg-brand-burgundy-dark shadow-lg shadow-brand-burgundy/15">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36]"></div>
        <div class="pointer-events-none absolute -right-10 top-0 h-56 w-56 rounded-full bg-white/[0.07] blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-72 rounded-full bg-black/10 blur-2xl"></div>
        <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between sm:p-8">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-brand-burgundy-soft backdrop-blur-sm">
                    <i data-lucide="users" class="h-3.5 w-3.5 text-white/90"></i>
                    Quadro de pessoal
                </span>
                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">Gestão do efetivo</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-brand-burgundy-soft/90">
                    Cadastro, mobilização SGC, movimentações e vínculos de ponto — tudo em um só lugar.
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3 sm:justify-end">
                <div class="min-w-[5.5rem] rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-center backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy-soft/80">Operacional</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">{{ $resumoEfetivo['efetivo_operacional'] }}</p>
                </div>
                <div class="min-w-[5.5rem] rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-center backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy-soft/80">Cadastros</p>
                    <p class="mt-0.5 text-2xl font-bold tabular-nums text-white">{{ $resumoEfetivo['cadastros_total'] }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- KPIs (fundo branco; cor só no ícone) --}}
    <section class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <article class="rounded-2xl border border-brand-burgundy/15 bg-white p-5 shadow-sm ring-1 ring-brand-burgundy/5 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="user-check" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Colaboradores ativos</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoEfetivo['efetivo_operacional'] }}</p>
            @if ($resumoEfetivo['tem_contrato_ref'])
                <p class="mt-2 text-xs leading-snug text-brand-gray">
                    Contrato {{ $resumoEfetivo['contrato_label'] }}: {{ $resumoEfetivo['efetivo_contrato'] }}
                </p>
            @endif
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-600">
                <i data-lucide="database" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">No sistema</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoEfetivo['cadastros_total'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500">
                <i data-lucide="user-x" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Desligados</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoEfetivo['desligados'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="heart-pulse" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Afastado INSS</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoEfetivo['afastados'] }}</p>
        </article>
        <article class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-100 transition hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </span>
            <p class="mt-4 text-xs font-bold uppercase tracking-wider text-brand-gray">Mobilização OK</p>
            <p class="mt-1 text-3xl font-bold tracking-tight text-brand-black">{{ $resumoEfetivo['mobilizacao_concluida'] }}</p>
        </article>
    </section>

    @if ($podeRhCriar)
    {{-- Importação --}}
    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-4">
            <div class="flex flex-wrap items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                    <i data-lucide="upload" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-bold text-brand-black">Importação em lote</p>
                    <p class="text-xs text-brand-gray">Planilha Excel — criar ou atualizar por matrícula ou CPF</p>
                </div>
                <a href="{{ route('rh.efetivo.modelo-importacao') }}" class="ml-auto inline-flex h-9 items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5"></i>
                    Baixar modelo
                </a>
            </div>
        </div>
        <form method="POST" action="{{ route('rh.efetivo.importar') }}" enctype="multipart/form-data" class="grid gap-4 p-6 lg:grid-cols-[1fr_auto] lg:items-center">
            @csrf
            <label class="block">
                <input type="file" name="arquivo" accept=".xlsx,.xlsm,.csv" required class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3 text-sm text-brand-gray outline-none transition file:mr-4 file:rounded-xl file:border-0 file:bg-brand-burgundy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
            </label>
            <button type="submit" class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                Importar planilha
            </button>
        </form>
    </section>
    @endif

    {{-- Listagem --}}
    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-brand-black">Cadastro de colaboradores</h2>
                        <p class="text-xs text-brand-gray">
                            {{ $colaboradores->total() }} registro(s)
                            @if ($totalPagina > 0)
                                · exibindo {{ $totalPagina }} nesta página
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <form method="GET" class="mt-5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-2 sm:col-span-2 lg:col-span-5">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="search" class="h-3.5 w-3.5"></i>
                            Buscar
                        </span>
                        <input name="busca" value="{{ request('busca') }}" placeholder="Nome, CPF, matrícula ou e-mail…" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="briefcase" class="h-3.5 w-3.5"></i>
                            Função
                        </span>
                        <select name="cargo" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium text-brand-black outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="">Todas as funções</option>
                            @foreach ($funcoes ?? [] as $funcao)
                                <option value="{{ $funcao }}" @selected(request('cargo') === $funcao)>{{ $funcao }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2 lg:col-span-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                            <i data-lucide="arrow-up-down" class="h-3.5 w-3.5"></i>
                            Ordenar
                        </span>
                        <select name="ordenacao" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium text-brand-black outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                            <option value="alfabetica" @selected(($ordenacao ?? request('ordenacao', 'alfabetica')) === 'alfabetica')>Ordem alfabética (A–Z)</option>
                            <option value="recentes" @selected(($ordenacao ?? request('ordenacao')) === 'recentes')>Mais recentes</option>
                        </select>
                    </label>
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                        <button type="submit" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                            <span class="lg:hidden">Filtrar</span>
                        </button>
                        @if (request()->filled('busca') || request()->filled('cargo') || request('ordenacao') === 'recentes')
                            <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-brand-gray transition hover:border-zinc-300 hover:text-brand-black" title="Limpar filtros">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </a>
                        @endif
                    </div>
                </div>

                @if (request()->filled('busca') || request()->filled('cargo') || request('ordenacao') === 'recentes')
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Filtros:</span>
                        @if (request()->filled('busca'))
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-semibold text-brand-burgundy">
                                {{ request('busca') }}
                            </span>
                        @endif
                        @if (request()->filled('cargo'))
                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-brand-gray">{{ request('cargo') }}</span>
                        @endif
                        @if (request('ordenacao') === 'recentes')
                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-brand-gray">Mais recentes</span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        @if ($podeRhExcluir)
        <form method="POST" action="{{ route('rh.efetivo.excluir-massa') }}" id="form-exclusao-massa" class="hidden border-b border-red-100 bg-gradient-to-r from-red-50/90 to-white px-6 py-3" data-barra-exclusao onsubmit="return confirm('Remover permanentemente os colaboradores selecionados do efetivo? Esta ação não pode ser desfeita.');">
            @csrf
            @foreach (request()->only(['busca', 'ordenacao', 'cargo']) as $key => $val)
                @if (filled($val))
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="flex items-center gap-2 text-sm font-semibold text-red-900">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    <span data-contador-selecionados>0</span> selecionado(s)
                </p>
                <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-xl bg-red-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-red-700">
                    Excluir selecionados
                </button>
            </div>
        </form>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <tr>
                        @if ($podeRhExcluir)
                        <th class="w-12 px-4 py-4">
                            <input type="checkbox" id="efetivo-selecionar-todos" class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20" title="Selecionar todos desta página" aria-label="Selecionar todos">
                        </th>
                        @endif
                        <th class="px-5 py-4">Colaborador</th>
                        <th class="px-5 py-4">Matrícula</th>
                        <th class="px-5 py-4">Cargo</th>
                        <th class="px-5 py-4">Escala</th>
                        <th class="px-5 py-4">Admissão</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">SGC Vale</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($colaboradores as $colaborador)
                        @php
                            $st = $colaborador->status ?? 'ativo';
                            $badgeClass = $statusBadge[$st] ?? $statusBadge['ativo'];
                            $dotClass = $statusDot[$st] ?? $statusDot['ativo'];
                        @endphp
                        <tr class="transition hover:bg-zinc-50/80" data-linha-efetivo>
                            @if ($podeRhExcluir)
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" form="form-exclusao-massa" name="colaborador_ids[]" value="{{ $colaborador->id }}" class="cb-efetivo rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20" aria-label="Selecionar {{ $colaborador->nome }}">
                            </td>
                            @endif
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if (filled($colaborador->foto_path))
                                        <div class="h-11 w-11 shrink-0 overflow-hidden rounded-full ring-2 ring-zinc-100">
                                            <img src="{{ $colaborador->urlFotoPerfil() }}" alt="" class="h-full w-full object-cover">
                                        </div>
                                    @else
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-burgundy-soft text-sm font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">
                                            {{ mb_strtoupper(mb_substr($colaborador->nome, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-brand-black">{{ $colaborador->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ filled($colaborador->cpf) ? $colaborador->cpf : 'CPF não informado' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs font-medium text-brand-gray">{{ $colaborador->matricula ?: '—' }}</td>
                            <td class="px-5 py-4 text-brand-gray">{{ $colaborador->cargo ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @if ($colaborador->horarioEscala)
                                    <span class="font-medium text-brand-black">{{ $colaborador->horarioEscala->nome }}</span>
                                    @if ($colaborador->horarioEscala->status !== 'ativo')
                                        <span class="mt-0.5 block text-xs text-amber-700">Escala inativa</span>
                                    @endif
                                @else
                                    <span class="text-xs text-brand-gray">Sem escala</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-brand-gray">{{ $colaborador->data_admissao?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $badgeClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                                    {{ ucfirst($st) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $colaborador->mobilizacao_status === 'mobilizacao_concluida' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200' : 'bg-zinc-100 text-zinc-600 ring-1 ring-inset ring-zinc-200' }}">
                                    {{ $mobilizacaoLabel[$colaborador->mobilizacao_status] ?? 'Pendente' }}
                                </span>
                                @if ($colaborador->sgc_numero_solicitacao)
                                    <p class="mt-1 text-[11px] text-brand-gray">SGC {{ $colaborador->sgc_numero_solicitacao }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('rh.efetivo.show', $colaborador) }}" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-zinc-200/80 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        Ver
                                    </a>
                                    @if ($podeRhEditar)
                                    <a href="{{ route('rh.efetivo.edit', $colaborador) }}" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-brand-burgundy px-3 text-xs font-bold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Editar
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $podeRhExcluir ? 9 : 8 }}" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="user-plus" class="h-8 w-8"></i>
                                </div>
                                <p class="mt-5 text-lg font-bold text-brand-black">Nenhum colaborador cadastrado</p>
                                <p class="mt-1 text-sm text-brand-gray">Cadastre manualmente ou importe uma planilha.</p>
                                @if ($podeRhCriar)
                                <div class="mt-6 flex flex-wrap justify-center gap-3">
                                    <a href="{{ route('rh.efetivo.create') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-burgundy px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                        <i data-lucide="user-plus" class="h-4 w-4"></i>
                                        Novo colaborador
                                    </a>
                                    <a href="{{ route('rh.efetivo.modelo-importacao') }}" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-brand-black shadow-sm hover:border-brand-burgundy">
                                        <i data-lucide="download" class="h-4 w-4"></i>
                                        Baixar modelo
                                    </a>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($colaboradores->hasPages())
            <div class="border-t border-zinc-100 bg-zinc-50/50 px-6 py-4">
                {{ $colaboradores->links() }}
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('form-exclusao-massa');
                const barra = document.querySelector('[data-barra-exclusao]');
                const selecionarTodos = document.getElementById('efetivo-selecionar-todos');
                const contador = document.querySelector('[data-contador-selecionados]');

                function checkboxes() {
                    return Array.from(document.querySelectorAll('.cb-efetivo'));
                }

                function atualizarBarra() {
                    const marcados = checkboxes().filter((cb) => cb.checked);
                    const n = marcados.length;
                    if (contador) contador.textContent = String(n);
                    barra?.classList.toggle('hidden', n === 0);
                    if (selecionarTodos) {
                        const todos = checkboxes();
                        selecionarTodos.checked = todos.length > 0 && todos.every((cb) => cb.checked);
                        selecionarTodos.indeterminate = n > 0 && n < todos.length;
                    }
                }

                selecionarTodos?.addEventListener('change', function () {
                    checkboxes().forEach((cb) => { cb.checked = selecionarTodos.checked; });
                    atualizarBarra();
                });

                checkboxes().forEach((cb) => cb.addEventListener('change', atualizarBarra));
                atualizarBarra();
            })();
        </script>
    @endpush
@endsection
