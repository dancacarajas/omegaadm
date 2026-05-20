@extends('layouts.app')

@section('title', 'Efetivo - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Efetivo')

@section('actions')
    <a href="{{ route('rh.efetivo.exportar-excel', request()->only(['busca', 'cargo', 'ordenacao'])) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-brand-burgundy/25 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
        <i data-lucide="download" class="h-4 w-4"></i>
        Exportar Excel
    </a>
    <a href="{{ route('rh.efetivo.modelo-importacao') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
        Modelo
    </a>
    <a href="{{ route('rh.efetivo.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="user-plus" class="h-4 w-4"></i>
        Novo colaborador
    </a>
@endsection

@section('content')
    @php
        $mobilizacaoLabel = [
            'pendente' => 'Pendente',
            'postado_sgc' => 'Postado no SGC',
            'aprovado' => 'Aprovado',
            'mobilizacao_concluida' => 'Mobilização concluída',
        ];
    @endphp

    @if (session('warning'))
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">{{ session('warning') }}</div>
    @endif

    @if (session('import_errors'))
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-bold">Algumas linhas não foram importadas:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach (session('import_errors') as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="users" class="h-5 w-5"></i>
                </div>
                <span class="rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-bold text-brand-burgundy">RH</span>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Colaboradores ativos</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $resumoEfetivo['efetivo_operacional'] }}</p>
            @if ($resumoEfetivo['tem_contrato_ref'])
                <p class="mt-1 text-xs text-brand-gray">
                    Contrato {{ $resumoEfetivo['contrato_label'] }}: {{ $resumoEfetivo['efetivo_contrato'] }} na data
                </p>
            @endif
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-gray-soft text-brand-black">
                <i data-lucide="users" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Cadastros no sistema</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $resumoEfetivo['cadastros_total'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600">
                <i data-lucide="user-x" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Desligados</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $resumoEfetivo['desligados'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-800">
                <i data-lucide="heart-pulse" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Afastado INSS</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $resumoEfetivo['afastados'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Mobilização concluída</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $resumoEfetivo['mobilizacao_concluida'] }}</p>
        </article>
    </section>

    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="grid gap-4 p-5 lg:grid-cols-[1.2fr_1fr] lg:items-center">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Importação de efetivo</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Incluir colaboradores por planilha</h2>
                <p class="mt-1 text-sm text-brand-gray">Baixe o modelo padrão, preencha os dados e importe para criar ou atualizar colaboradores por matrícula ou CPF.</p>
            </div>
            <form method="POST" action="{{ route('rh.efetivo.importar') }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                @csrf
                <input type="file" name="arquivo" accept=".xlsx,.xlsm,.csv" required class="h-11 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-brand-gray outline-none file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                <a href="{{ route('rh.efetivo.modelo-importacao') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="download" class="h-4 w-4"></i>
                    Baixar modelo
                </a>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Importar
                </button>
            </form>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="space-y-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/60 p-5">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Cadastro de colaboradores</h2>
                <p class="mt-1 text-sm text-brand-gray">Controle do efetivo com dados pessoais, contrato e admissão.</p>
            </div>

            <form method="GET" class="rounded-xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-1.5 sm:col-span-2 lg:col-span-5">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Buscar</span>
                        <span class="relative block">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                            <input name="busca" value="{{ request('busca') }}" placeholder="Nome, CPF ou matrícula…" class="h-10 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        </span>
                    </label>
                    <label class="space-y-1.5 lg:col-span-3">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Função</span>
                        <select name="cargo" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            <option value="">Todas as funções</option>
                            @foreach ($funcoes ?? [] as $funcao)
                                <option value="{{ $funcao }}" @selected(request('cargo') === $funcao)>{{ $funcao }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1.5 lg:col-span-3">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Ordenar por</span>
                        <select name="ordenacao" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            <option value="recentes" @selected(($ordenacao ?? request('ordenacao', 'recentes')) === 'recentes')>Mais recentes</option>
                            <option value="alfabetica" @selected(($ordenacao ?? request('ordenacao')) === 'alfabetica')>Ordem alfabética (A–Z)</option>
                        </select>
                    </label>
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                        <button type="submit" class="inline-flex h-10 min-w-[5.5rem] flex-1 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Aplicar
                        </button>
                        @if (request()->filled('busca') || request()->filled('cargo') || request('ordenacao') === 'alfabetica')
                            <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-gray transition hover:border-zinc-300 hover:text-brand-black" title="Limpar filtros">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </a>
                        @endif
                    </div>
                </div>

                @if (request()->filled('busca') || request()->filled('cargo') || request('ordenacao') === 'alfabetica')
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-3">
                        <span class="text-[11px] font-bold uppercase text-brand-gray">Filtros ativos:</span>
                        @if (request()->filled('busca'))
                            <span class="rounded-md bg-brand-burgundy-soft px-2 py-0.5 text-xs font-semibold text-brand-burgundy">Busca: {{ request('busca') }}</span>
                        @endif
                        @if (request()->filled('cargo'))
                            <span class="rounded-md bg-brand-burgundy-soft px-2 py-0.5 text-xs font-semibold text-brand-burgundy">Função: {{ request('cargo') }}</span>
                        @endif
                        @if (request('ordenacao') === 'alfabetica')
                            <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-brand-gray">Ordem A–Z</span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        <form method="POST" action="{{ route('rh.efetivo.excluir-massa') }}" id="form-exclusao-massa" class="hidden border-b border-zinc-100 bg-zinc-50/80 px-5 py-3" data-barra-exclusao onsubmit="return confirm('Remover permanentemente os colaboradores selecionados do efetivo? Esta ação não pode ser desfeita.');">
            @csrf
            @if (request('busca'))
                <input type="hidden" name="busca" value="{{ request('busca') }}">
            @endif
            @if (request('ordenacao'))
                <input type="hidden" name="ordenacao" value="{{ request('ordenacao') }}">
            @endif
            @if (request('cargo'))
                <input type="hidden" name="cargo" value="{{ request('cargo') }}">
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-brand-black"><span data-contador-selecionados>0</span> selecionado(s)</p>
                <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg bg-red-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-red-700">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Excluir selecionados
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="w-10 px-3 py-4">
                            <input type="checkbox" id="efetivo-selecionar-todos" class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20" title="Selecionar todos desta página" aria-label="Selecionar todos">
                        </th>
                        <th class="px-5 py-4">Colaborador</th>
                        <th class="px-5 py-4">Matrícula</th>
                        <th class="px-5 py-4">Cargo</th>
                        <th class="px-5 py-4">Escala de horários</th>
                        <th class="px-5 py-4">Admissão</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">SGC Vale</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($colaboradores as $colaborador)
                        <tr class="transition hover:bg-brand-gray-soft/60" data-linha-efetivo>
                            <td class="px-3 py-4 text-center">
                                <input type="checkbox" form="form-exclusao-massa" name="colaborador_ids[]" value="{{ $colaborador->id }}" class="cb-efetivo rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20" aria-label="Selecionar {{ $colaborador->nome }}">
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if (filled($colaborador->foto_path))
                                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg ring-1 ring-zinc-200">
                                            <img src="{{ $colaborador->urlFotoPerfil() }}" alt="" class="h-full w-full object-cover">
                                        </div>
                                    @else
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-burgundy-soft text-sm font-bold text-brand-burgundy">
                                            {{ mb_substr($colaborador->nome, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $colaborador->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ filled($colaborador->cpf) ? $colaborador->cpf : 'CPF não informado' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-medium text-brand-gray">{{ $colaborador->matricula ?: '-' }}</td>
                            <td class="px-5 py-4 font-medium text-brand-gray">{{ $colaborador->cargo ?: '-' }}</td>
                            <td class="px-5 py-4">
                                @if ($colaborador->horarioEscala)
                                    <span class="font-medium text-brand-black">{{ $colaborador->horarioEscala->nome }}</span>
                                    @if ($colaborador->horarioEscala->status !== 'ativo')
                                        <span class="mt-0.5 block text-xs text-amber-700">Escala {{ $colaborador->horarioEscala->status === 'inativo' ? 'inativa' : $colaborador->horarioEscala->status }}</span>
                                    @endif
                                @else
                                    <span class="text-brand-gray">—</span>
                                    <span class="mt-0.5 block text-xs text-brand-gray">Edite a ficha para vincular</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-medium text-brand-gray">{{ $colaborador->data_admissao?->format('d/m/Y') ?: '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-bold text-brand-burgundy">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-burgundy"></span>
                                    {{ ucfirst($colaborador->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1.5 rounded-full {{ $colaborador->mobilizacao_status === 'mobilizacao_concluida' ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'bg-brand-gray-soft text-brand-gray' }} px-2.5 py-1 text-xs font-bold">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $colaborador->mobilizacao_status === 'mobilizacao_concluida' ? 'bg-brand-burgundy' : 'bg-brand-gray' }}"></span>
                                        {{ $mobilizacaoLabel[$colaborador->mobilizacao_status] ?? 'Pendente' }}
                                    </span>
                                    <p class="text-xs text-brand-gray">Solicitação: {{ $colaborador->sgc_numero_solicitacao ?: '-' }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('rh.efetivo.show', $colaborador) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 px-3 text-xs font-semibold text-brand-black transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('rh.efetivo.edit', $colaborador) }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="user-plus" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum colaborador cadastrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Comece criando a primeira ficha do efetivo.</p>
                                <a href="{{ route('rh.efetivo.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                                    Cadastrar primeiro colaborador
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $colaboradores->links() }}
        </div>
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
