@extends('layouts.app')

@section('title', 'Gestão do benefício - Omega286')
@section('eyebrow', 'RH / Benefícios')
@section('page-title', 'Gestão do benefício')

@section('actions')
    <a href="{{ route('rh.beneficios.edit', $beneficio) }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
        <i data-lucide="pencil" class="h-4 w-4"></i>
        Editar
    </a>
    <a href="{{ route('rh.beneficios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        /** POST na mesma URL que o navegador já abriu (crítico em produção com /public na URL). */
        $urlGestaoBeneficio = request()->url();
        $total = $beneficio->colaboradores->count();
        $comDireito = $beneficio->colaboradores->where('tem_direito', true)->count();
        $cartoesPendentes = $beneficio->colaboradores->where('tem_direito', true)->where('cartao_entregue', false)->count();
        $ativos = $beneficio->colaboradores->where('beneficio_ativo', true)->count();
    @endphp

    <div class="mb-5 overflow-hidden rounded-2xl border border-zinc-200 bg-brand-gray text-white shadow-sm">
        <div class="grid gap-6 p-6 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white">
                    <i data-lucide="hand-heart" class="h-3.5 w-3.5"></i>
                    {{ $beneficio->tipo ?: 'Benefício' }}
                </div>
                <h2 class="mt-4 text-2xl font-bold">{{ $beneficio->nome }}</h2>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-white/85">
                    Controle quem tem direito, quem já recebeu cartão e quem está com o benefício ativo.
                </p>
            </div>
            <div class="rounded-2xl border border-white/20 bg-white/10 p-4 text-sm">
                <p class="font-semibold">{{ $beneficio->fornecedor ?: 'Fornecedor não informado' }}</p>
                <p class="mt-1 text-white/80">{{ $beneficio->valor ? 'R$ ' . number_format((float) $beneficio->valor, 2, ',', '.') : 'Valor não informado' }}</p>
            </div>
        </div>
    </div>

    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-brand-gray">Vinculados</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $total }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-brand-gray">Com direito</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $comDireito }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-brand-gray">Cartões pendentes</p>
            <p class="mt-1 text-3xl font-bold text-brand-burgundy">{{ $cartoesPendentes }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-brand-gray">Ativos</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $ativos }}</p>
        </article>
    </section>

    <section class="mb-5 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h3 class="text-lg font-bold text-brand-black">Vincular colaborador</h3>
            <p class="mt-1 text-sm text-brand-gray">Adicione quem tem direito a este benefício e acompanhe a entrega do cartão.</p>
        </div>

        <form method="POST" action="{{ $urlGestaoBeneficio }}" class="grid gap-4 p-5 lg:grid-cols-[1.3fr_repeat(3,auto)] lg:items-end">
            @csrf
            <label>
                <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Colaborador</span>
                <select name="colaborador_id" required class="mt-2 h-12 w-full rounded-xl border border-zinc-200 bg-white px-4 text-sm font-medium text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                    <option value="">Selecione...</option>
                    @foreach ($colaboradoresDisponiveis as $colaborador)
                        <option value="{{ $colaborador->id }}">{{ $colaborador->nome }}{{ $colaborador->cargo ? ' - ' . $colaborador->cargo : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex h-12 items-center gap-2 rounded-xl border border-zinc-200 px-4 text-sm font-semibold text-brand-black">
                <input type="hidden" name="tem_direito" value="0">
                <input type="checkbox" name="tem_direito" value="1" checked class="h-4 w-4 accent-brand-burgundy">
                Tem direito
            </label>
            <label class="flex h-12 items-center gap-2 rounded-xl border border-zinc-200 px-4 text-sm font-semibold text-brand-black">
                <input type="hidden" name="cartao_entregue" value="0">
                <input type="checkbox" name="cartao_entregue" value="1" class="h-4 w-4 accent-brand-burgundy">
                Cartão entregue
            </label>
            <button class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Vincular
            </button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-100 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h3 class="text-lg font-bold text-brand-black">Acompanhamento por colaborador</h3>
            <p class="mt-1 text-sm text-brand-gray">Use esta lista para cobrar pendencias de cartao e ativacao do beneficio.</p>
        </div>

        @php
            $filtrosAtivos = $busca !== '' || $ordenacao !== 'alfabetica' || $cartao !== 'todos';
        @endphp
        <form method="GET" action="{{ route('rh.beneficios.show', $beneficio) }}" class="border-b border-zinc-100 p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                <label class="space-y-1.5 lg:col-span-4">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Buscar por nome</span>
                    <span class="relative block">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                        <input name="busca" value="{{ $busca }}" placeholder="Nome, matrícula ou cargo…" class="h-10 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </span>
                </label>
                <label class="space-y-1.5 lg:col-span-3">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Cartão</span>
                    <select name="cartao" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="todos" @selected($cartao === 'todos')>Todos</option>
                        <option value="entregue" @selected($cartao === 'entregue')>Entregue</option>
                        <option value="pendente" @selected($cartao === 'pendente')>Pendente</option>
                    </select>
                </label>
                <label class="space-y-1.5 lg:col-span-3">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Ordenar por</span>
                    <select name="ordenacao" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="alfabetica" @selected($ordenacao === 'alfabetica')>Ordem alfabética (A–Z)</option>
                        <option value="recentes" @selected($ordenacao === 'recentes')>Mais recentes</option>
                    </select>
                </label>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-2">
                    <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="search" class="h-4 w-4"></i>
                        Aplicar
                    </button>
                    @if ($filtrosAtivos)
                        <a href="{{ route('rh.beneficios.show', $beneficio) }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-gray transition hover:border-zinc-300 hover:text-brand-black" title="Limpar filtros">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </a>
                    @endif
                </div>
            </div>
            @if ($filtrosAtivos)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-bold uppercase text-brand-gray">Filtros ativos:</span>
                    @if ($busca !== '')
                        <span class="rounded-md bg-brand-burgundy-soft px-2 py-0.5 text-xs font-semibold text-brand-burgundy">Busca: {{ $busca }}</span>
                    @endif
                    @if ($cartao === 'entregue')
                        <span class="rounded-md bg-brand-burgundy-soft px-2 py-0.5 text-xs font-semibold text-brand-burgundy">Cartão entregue</span>
                    @elseif ($cartao === 'pendente')
                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">Cartão pendente</span>
                    @endif
                    @if ($ordenacao === 'recentes')
                        <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-brand-gray">Mais recentes</span>
                    @endif
                    <span class="text-xs text-brand-gray">{{ $colaboradoresVinculados->count() }} de {{ $total }} exibido(s)</span>
                </div>
            @endif
        </form>

        <div class="overflow-x-auto">
        <div class="min-w-[1120px] text-sm">
            <div class="grid grid-cols-[minmax(140px,1.2fr)_repeat(3,minmax(100px,0.7fr))_minmax(160px,1fr)_minmax(160px,1fr)_minmax(140px,0.8fr)] gap-2 border-b border-zinc-200 bg-white px-5 py-4 text-xs font-bold uppercase tracking-wide text-brand-gray">
                <span>Colaborador</span>
                <span>Direito</span>
                <span>Cartão</span>
                <span>Ativo</span>
                <span>Direito / Entrega</span>
                <span>Cartão / Obs.</span>
                <span class="text-right">Ações</span>
            </div>
            @forelse ($colaboradoresVinculados as $vinculo)
                <form method="POST" action="{{ $urlGestaoBeneficio }}" class="grid grid-cols-[minmax(140px,1.2fr)_repeat(3,minmax(100px,0.7fr))_minmax(160px,1fr)_minmax(160px,1fr)_minmax(140px,0.8fr)] gap-2 border-b border-zinc-100 px-5 py-4 align-top">
                    @csrf
                    <input type="hidden" name="vinculo_id" value="{{ $vinculo->id }}">
                    <div>
                        <p class="font-semibold text-brand-black">{{ $vinculo->colaborador->nome }}</p>
                        <p class="text-xs text-brand-gray">{{ $vinculo->colaborador->cargo ?: 'Cargo não informado' }}</p>
                    </div>
                    <div>
                        <input type="hidden" name="tem_direito" value="0">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-brand-black">
                            <input type="checkbox" name="tem_direito" value="1" @checked($vinculo->tem_direito) class="h-4 w-4 accent-brand-burgundy">
                            Tem direito
                        </label>
                    </div>
                    <div>
                        <input type="hidden" name="cartao_entregue" value="0">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold {{ $vinculo->tem_direito && ! $vinculo->cartao_entregue ? 'text-brand-burgundy' : 'text-brand-black' }}">
                            <input type="checkbox" name="cartao_entregue" value="1" @checked($vinculo->cartao_entregue) class="h-4 w-4 accent-brand-burgundy">
                            {{ $vinculo->cartao_entregue ? 'Entregue' : 'Pendente' }}
                        </label>
                    </div>
                    <div>
                        <input type="hidden" name="beneficio_ativo" value="0">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-brand-black">
                            <input type="checkbox" name="beneficio_ativo" value="1" @checked($vinculo->beneficio_ativo) class="h-4 w-4 accent-brand-burgundy">
                            Ativo
                        </label>
                    </div>
                    <div class="grid gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Direito (admissao)</p>
                            <p class="mt-1 text-sm font-semibold text-brand-black">
                                {{ $vinculo->data_direito?->format('d/m/Y') ?: ($vinculo->colaborador->data_admissao?->format('d/m/Y') ?: 'Admissao nao informada') }}
                            </p>
                        </div>
                        <label>
                            <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Entrega do cartao</span>
                            <input type="date" name="data_entrega_cartao" value="{{ $vinculo->data_entrega_cartao?->format('Y-m-d') }}" class="mt-1 h-9 w-full rounded-lg border border-zinc-200 px-2 text-xs">
                        </label>
                    </div>
                    <div>
                        <input name="numero_cartao" value="{{ $vinculo->numero_cartao }}" placeholder="Número do cartão" class="h-9 w-full rounded-lg border border-zinc-200 px-3 text-xs">
                        <textarea name="observacoes" placeholder="Observações" class="mt-2 min-h-16 w-full rounded-lg border border-zinc-200 px-3 py-2 text-xs">{{ $vinculo->observacoes }}</textarea>
                    </div>
                    <div class="flex flex-col justify-end gap-2 sm:flex-row">
                        <button type="submit" name="acao" value="salvar" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Salvar
                        </button>
                        <button type="submit" name="acao" value="excluir" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg border border-zinc-200 px-3 text-xs font-semibold text-brand-black" onclick="return confirm('Remover este colaborador do benefício?')">
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                            Excluir
                        </button>
                    </div>
                </form>
            @empty
                <div class="px-5 py-12 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                        <i data-lucide="users" class="h-7 w-7"></i>
                    </div>
                    <p class="mt-4 text-base font-bold text-brand-black">Nenhum colaborador vinculado.</p>
                    <p class="mt-1 text-sm text-brand-gray">
                        @if ($filtrosAtivos)
                            Nenhum colaborador encontrado com os filtros aplicados.
                        @else
                            Selecione um colaborador acima para iniciar o controle deste benefício.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
        </div>
    </section>
@endsection
