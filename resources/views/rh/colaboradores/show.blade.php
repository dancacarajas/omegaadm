@extends('layouts.app')

@section('title', $colaborador->nome.' - Ficha - Omega286')
@section('eyebrow', 'RH / Efetivo')
@section('page-title', 'Ficha do colaborador')

@section('actions')
    <a href="{{ route('rh.efetivo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
    <a href="{{ route('rh.efetivo.movimentacoes.create', ['colaborador' => $colaborador, 'tipo' => 'afastamento_inss']) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy/40">
        <i data-lucide="git-branch" class="h-4 w-4"></i>
        Movimentação
    </a>
    <a href="{{ route('rh.efetivo.edit', $colaborador) }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="pencil" class="h-4 w-4"></i>
        Editar ficha
    </a>
@endsection

@section('content')
    @php
        $item = fn ($label, $value, bool $wide = false) => [
            'label' => $label,
            'value' => filled($value) ? $value : '—',
            'wide' => $wide,
        ];
        $date = fn ($value) => $value ? $value->format('d/m/Y') : null;
        $money = fn ($value) => filled($value) ? 'R$ '.number_format((float) $value, 2, ',', '.') : null;
        $st = $colaborador->status ?? 'ativo';
        $statusLabel = ['ativo' => 'Ativo', 'afastado' => 'Afastado', 'desligado' => 'Desligado'];
        $statusUiHero = [
            'ativo' => 'bg-emerald-400/25 text-emerald-50 ring-1 ring-inset ring-emerald-300/40',
            'afastado' => 'bg-amber-400/25 text-amber-50 ring-1 ring-inset ring-amber-300/40',
            'desligado' => 'bg-white/15 text-white/80 ring-1 ring-inset ring-white/25',
        ];
        $iniciais = collect(preg_split('/\s+/u', trim($colaborador->nome), -1, PREG_SPLIT_NO_EMPTY))
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->join('');
        $mobilizacaoLabel = [
            'pendente' => 'Pendente',
            'postado_sgc' => 'Postado no SGC',
            'aprovado' => 'Aprovado',
            'mobilizacao_concluida' => 'Mobilização concluída',
        ];
        $nav = [
            ['id' => 'identificacao', 'n' => '01', 't' => 'Identificação'],
            ['id' => 'pessoais', 'n' => '02', 't' => 'Dados pessoais'],
            ['id' => 'documentos', 'n' => '03', 't' => 'Documentos'],
            ['id' => 'contrato', 'n' => '04', 't' => 'Contrato'],
            ['id' => 'admissao', 'n' => '05', 't' => 'Admissão'],
            ['id' => 'movimentacoes', 'n' => '06', 't' => 'Movimentações'],
            ['id' => 'sgc', 'n' => '07', 't' => 'SGC Vale'],
        ];
    @endphp

    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-900 shadow-sm">
            <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @error('foto_perfil')
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200/80 bg-gradient-to-r from-red-50 to-white px-5 py-4 text-sm text-red-900 shadow-sm">
            <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0 text-red-600"></i>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <header class="ficha-hero relative mb-8 overflow-hidden rounded-3xl border border-brand-burgundy/15 bg-white shadow-xl shadow-brand-burgundy/10 ring-1 ring-brand-burgundy/10">
        {{-- Faixa principal (identidade) --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36] px-6 pb-14 pt-7 sm:px-8 sm:pb-16 sm:pt-8">
            <div class="pointer-events-none absolute -right-16 -top-10 h-64 w-64 rounded-full bg-white/[0.08] blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-0 left-0 h-40 w-80 rounded-full bg-black/15 blur-2xl"></div>
            <div class="pointer-events-none absolute right-1/4 top-1/2 h-24 w-24 rounded-full bg-brand-burgundy-soft/[0.12] blur-xl"></div>

            <div class="relative flex flex-col gap-7 lg:flex-row lg:items-start">
                <form
                    id="ficha-foto-form"
                    method="POST"
                    action="{{ route('rh.efetivo.foto.update', $colaborador) }}"
                    enctype="multipart/form-data"
                    class="relative shrink-0"
                >
                    @csrf
                    <input
                        type="file"
                        id="ficha-foto-input"
                        name="foto_perfil"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="sr-only"
                    >
                    <button
                        type="button"
                        id="ficha-foto-trigger"
                        class="group relative block cursor-pointer rounded-full text-left focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50"
                        aria-label="{{ filled($colaborador->foto_path) ? 'Alterar foto de perfil' : 'Anexar foto de perfil' }}"
                        title="Clique para {{ filled($colaborador->foto_path) ? 'alterar' : 'anexar' }} a foto"
                    >
                        @if (filled($colaborador->foto_path))
                            <img
                                src="{{ $colaborador->urlFotoPerfil() }}"
                                alt=""
                                class="relative z-10 h-32 w-32 rounded-full object-cover shadow-2xl shadow-black/30 ring-4 ring-white/90 transition group-hover:brightness-90 sm:h-36 sm:w-36"
                                onerror="this.classList.add('hidden'); document.getElementById('ficha-foto-iniciais')?.classList.remove('hidden');"
                            >
                        @endif
                        <div id="ficha-foto-iniciais" class="{{ filled($colaborador->foto_path) ? 'hidden ' : '' }}relative z-10 flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-white/25 to-white/5 text-4xl font-bold tracking-tight text-white shadow-2xl shadow-black/25 ring-4 ring-white/90 backdrop-blur-sm transition group-hover:from-white/35 sm:h-36 sm:w-36 sm:text-5xl">
                            {{ $iniciais ?: mb_strtoupper(mb_substr($colaborador->nome, 0, 1)) }}
                        </div>
                        <span class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-1 rounded-full bg-black/50 text-white opacity-0 transition duration-200 group-hover:opacity-100 group-focus-visible:opacity-100 max-sm:bg-black/40 max-sm:opacity-100" id="ficha-foto-overlay">
                            <i data-lucide="camera" class="h-6 w-6 sm:h-7 sm:w-7"></i>
                            <span class="hidden px-2 text-center text-[10px] font-bold uppercase tracking-wide sm:block">{{ filled($colaborador->foto_path) ? 'Alterar' : 'Anexar' }}</span>
                        </span>
                        <span id="ficha-foto-loading" class="absolute inset-0 z-30 hidden flex-col items-center justify-center gap-2 rounded-full bg-black/60 text-white">
                            <i data-lucide="loader-2" class="h-8 w-8 animate-spin"></i>
                            <span class="text-xs font-bold">Enviando…</span>
                        </span>
                    </button>
                    <span class="pointer-events-none absolute bottom-0 right-0 z-40 flex h-9 w-9 items-center justify-center rounded-full bg-white text-brand-burgundy shadow-lg ring-2 ring-brand-burgundy/20">
                        <i data-lucide="camera" class="h-4 w-4"></i>
                    </span>
                </form>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold shadow-sm {{ $statusUiHero[$st] ?? $statusUiHero['ativo'] }}">
                            <span class="h-2 w-2 rounded-full bg-current opacity-80"></span>
                            {{ $statusLabel[$st] ?? ucfirst((string) $st) }}
                        </span>
                        @if ($colaborador->matricula)
                            <span class="rounded-full border border-white/25 bg-white/10 px-3 py-1 font-mono text-xs font-bold text-white backdrop-blur-sm">Mat. {{ $colaborador->matricula }}</span>
                        @endif
                        <span class="rounded-full border border-white/30 bg-white/15 px-3 py-1 text-xs font-bold text-brand-burgundy-soft backdrop-blur-sm">
                            {{ $mobilizacaoLabel[$colaborador->mobilizacao_status] ?? 'Pendente' }} · SGC
                        </span>
                    </div>

                    <h2 class="mt-5 text-2xl font-bold leading-tight tracking-tight text-white sm:text-[1.75rem] lg:text-3xl">{{ $colaborador->nome }}</h2>
                    <p class="mt-2 inline-flex items-center gap-2 text-base font-semibold text-brand-burgundy-soft/95 sm:text-lg">
                        <i data-lucide="briefcase" class="h-4 w-4 shrink-0 opacity-80"></i>
                        {{ $colaborador->cargo ?: 'Cargo não informado' }}
                    </p>

                    <ul class="mt-6 flex flex-wrap gap-2">
                        @foreach ([
                            ['building-2', $colaborador->centro_custo],
                            ['clock', $colaborador->horarioEscala?->nome],
                            ['calendar', $colaborador->data_admissao ? 'Admissão '.$colaborador->data_admissao->format('d/m/Y') : null],
                            ['credit-card', $colaborador->cpf],
                        ] as [$chipIcon, $chipVal])
                            @if (filled($chipVal))
                                <li class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-semibold text-white shadow-sm backdrop-blur-md transition hover:border-white/35 hover:bg-white/15">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                                        <i data-lucide="{{ $chipIcon }}" class="h-3.5 w-3.5 text-brand-burgundy-soft"></i>
                                    </span>
                                    {{ $chipVal }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Cards de resumo (sobrepostos à faixa burgundy) --}}
        <div class="relative z-10 -mt-10 px-4 pb-6 sm:-mt-12 sm:px-6">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
            @foreach ([
                ['phone', 'Telefone', $colaborador->telefone, false],
                ['wallet', 'Salário inicial', $money($colaborador->salario_inicial), true],
                ['users', 'Departamento', $colaborador->departamento, false],
                ['map-pin', 'Local de trabalho', $colaborador->local_trabalho, false],
            ] as [$icon, $label, $val, $destaque])
                @php $vazio = ! filled($val); @endphp
                <div class="flex flex-col rounded-2xl border border-brand-burgundy/10 bg-white p-4 shadow-lg shadow-brand-burgundy/5 ring-1 ring-zinc-100/80 transition duration-200 hover:-translate-y-0.5 hover:border-brand-burgundy/25 hover:shadow-xl hover:shadow-brand-burgundy/10 sm:p-5 {{ $destaque && ! $vazio ? 'lg:ring-2 lg:ring-brand-burgundy/15' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $vazio ? 'bg-zinc-100 text-zinc-400' : 'bg-gradient-to-br from-brand-burgundy-soft to-white text-brand-burgundy shadow-inner ring-1 ring-brand-burgundy/10' }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </span>
                        @if ($destaque && ! $vazio)
                            <span class="rounded-full bg-brand-burgundy/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-brand-burgundy">Contrato</span>
                        @endif
                    </div>
                    <p class="mt-4 text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ $label }}</p>
                    <p class="mt-1.5 text-sm font-bold leading-snug {{ $vazio ? 'text-zinc-400' : 'text-zinc-900' }} {{ ! $vazio && strlen((string) $val) > 28 ? 'text-xs sm:text-sm' : '' }}">
                        {{ $vazio ? '—' : $val }}
                    </p>
                </div>
            @endforeach
            </div>
        </div>
    </header>

    <div class="grid gap-8 xl:grid-cols-[252px_minmax(0,1fr)]">
        <aside class="hidden xl:block">
            <nav class="ficha-nav sticky top-24 overflow-hidden rounded-3xl border border-zinc-200/90 bg-white p-2 shadow-md shadow-zinc-200/40 ring-1 ring-zinc-100" aria-label="Seções da ficha">
                <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.05] to-transparent px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-brand-burgundy/80">Navegação</p>
                </div>
                <ul class="space-y-0.5 p-2">
                    @foreach ($nav as $link)
                        <li>
                            <a href="#{{ $link['id'] }}" data-ficha-nav class="ficha-nav-link flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-zinc-600 transition hover:bg-brand-burgundy-soft hover:text-brand-burgundy">
                                <span class="ficha-nav-num flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-xs font-bold text-zinc-500 transition">{{ $link['n'] }}</span>
                                <span class="leading-tight">{{ $link['t'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </aside>

        <div class="min-w-0 space-y-8 pb-24 xl:pb-0">
            @include('rh.colaboradores._ficha_secao', [
                'id' => 'identificacao',
                'numero' => '01',
                'titulo' => 'Identificação',
                'icone' => 'id-card',
                'campos' => [
                    $item('Nome completo', $colaborador->nome),
                    $item('Matrícula', $colaborador->matricula),
                    $item('CPF', $colaborador->cpf),
                    $item('RG', $colaborador->rg),
                    $item('Telefone', $colaborador->telefone),
                    $item('Status no sistema', $statusLabel[$st] ?? $st),
                ],
            ])

            @include('rh.colaboradores._ficha_secao', [
                'id' => 'pessoais',
                'numero' => '02',
                'titulo' => 'Dados pessoais',
                'icone' => 'user',
                'campos' => [
                    $item('Data de nascimento', $date($colaborador->data_nascimento)),
                    $item('Local de nascimento', $colaborador->local_nascimento),
                    $item('UF de nascimento', $colaborador->uf_nascimento),
                    $item('Nacionalidade', $colaborador->nacionalidade),
                    $item('Estado civil', $colaborador->estado_civil),
                    $item('Cônjuge', $colaborador->conjuge),
                    $item('Sexo', $colaborador->sexo),
                    $item('Cor', $colaborador->cor),
                    $item('Grau de instrução', $colaborador->grau_instrucao),
                    $item('Nome do pai', $colaborador->filiacao_pai),
                    $item('Nome da mãe', $colaborador->filiacao_mae),
                ],
            ])

            @include('rh.colaboradores._ficha_secao', [
                'id' => 'documentos',
                'numero' => '03',
                'titulo' => 'Documentos e endereço',
                'icone' => 'map-pin',
                'campos' => [
                    $item('Carteira profissional', $colaborador->carteira_profissional),
                    $item('Série CTPS', $colaborador->serie_ctps),
                    $item('Data CTPS', $date($colaborador->data_ctps)),
                    $item('Vencimento CTPS', $date($colaborador->vencimento_ctps)),
                    $item('PIS', $colaborador->pis),
                    $item('Título de eleitor', $colaborador->titulo_eleitor),
                    $item('Zona / Seção', collect([$colaborador->zona_eleitoral, $colaborador->secao_eleitoral])->filter()->join(' / ') ?: null),
                    $item('Identidade', $colaborador->carteira_identidade),
                    $item('Emissão identidade', $date($colaborador->emissao_identidade)),
                    $item('Órgão emissor', $colaborador->orgao_emissor),
                    $item('Endereço', trim(collect([$colaborador->endereco, $colaborador->numero])->filter()->join(', ')) ?: null),
                    $item('Bairro', $colaborador->bairro),
                    $item('Cidade / UF', trim(collect([$colaborador->cidade, $colaborador->estado])->filter()->join(' — ')) ?: null),
                    $item('CEP', $colaborador->cep),
                ],
            ])

            @include('rh.colaboradores._ficha_secao', [
                'id' => 'contrato',
                'numero' => '04',
                'titulo' => 'Contrato e jornada',
                'icone' => 'briefcase',
                'campos' => [
                    $item('Tipo de contrato', $colaborador->tipo_contrato),
                    $item('Centro de custo', $colaborador->centro_custo),
                    $item('Cargo', $colaborador->cargo),
                    $item('CBO', $colaborador->cbo),
                    $item('Departamento', $colaborador->departamento),
                    $item('Jornada semanal', $colaborador->jornada_semanal),
                    $item('Horário (texto)', $colaborador->horario),
                    $item('Escala cadastrada', $colaborador->horarioEscala?->nome),
                ],
            ])

            @include('rh.colaboradores._ficha_secao', [
                'id' => 'admissao',
                'numero' => '05',
                'titulo' => 'Admissão e contatos',
                'icone' => 'wallet',
                'campos' => [
                    $item('Data de admissão', $date($colaborador->data_admissao)),
                    $item('Opção FGTS', $date($colaborador->data_opcao_fgts)),
                    $item('Data de demissão', $date($colaborador->data_demissao)),
                    $item('Forma de pagamento', $colaborador->forma_pagamento),
                    $item('Salário inicial', $money($colaborador->salario_inicial)),
                    $item('Local de trabalho', $colaborador->local_trabalho),
                    $item('Almoço', $colaborador->almoco),
                    $item('Dependentes', $colaborador->dependentes, true),
                    $item('Emergência — Nome', $colaborador->contato_emergencia_nome),
                    $item('Emergência — Telefone', $colaborador->contato_emergencia_telefone),
                    $item('Emergência — Parentesco', $colaborador->contato_emergencia_parentesco),
                    $item('Observações', $colaborador->observacoes, true),
                ],
            ])

            <section id="movimentacoes" class="ficha-secao scroll-mt-28 overflow-hidden rounded-3xl border border-zinc-200/90 bg-white shadow-md shadow-zinc-200/40 ring-1 ring-zinc-100">
                <div class="flex flex-col gap-4 border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/25">06</span>
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                <i data-lucide="history" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <h3 class="text-lg font-bold tracking-tight text-zinc-900">Movimentações</h3>
                                <p class="mt-0.5 text-sm text-zinc-500">{{ $colaborador->movimentacoes->count() }} registro(s) no histórico</p>
                            </div>
                        </div>
                    </div>
                    <details class="group relative shrink-0">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-xl bg-brand-burgundy px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 marker:content-none transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Nova movimentação
                            <i data-lucide="chevron-down" class="h-4 w-4 transition group-open:rotate-180"></i>
                        </summary>
                        <div class="absolute right-0 z-20 mt-2 w-72 overflow-hidden rounded-2xl border border-zinc-200/90 bg-white p-1.5 shadow-xl ring-1 ring-zinc-100">
                            @foreach ($tiposMovimentacao as $tipoKey => $tipoLabel)
                                <a href="{{ route('rh.efetivo.movimentacoes.create', ['colaborador' => $colaborador, 'tipo' => $tipoKey]) }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold text-zinc-700 transition hover:bg-brand-burgundy-soft hover:text-brand-burgundy">
                                    {{ $tipoLabel }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                </div>

                <div class="relative px-6 py-2">
                    @forelse ($colaborador->movimentacoes as $mov)
                        <article class="group relative border-b border-zinc-100 py-6 pl-8 last:border-b-0 hover:bg-brand-burgundy/[0.02]">
                            <span class="absolute left-0 top-7 h-3 w-3 rounded-full border-2 border-white bg-brand-burgundy shadow-sm ring-2 ring-brand-burgundy/20"></span>
                            @if (! $loop->last)
                                <span class="absolute left-[5px] top-10 bottom-0 w-px bg-gradient-to-b from-brand-burgundy/30 to-zinc-200" aria-hidden="true"></span>
                            @endif
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <time class="text-sm font-bold text-zinc-900">
                                            {{ $mov->data_inicio->format('d/m/Y') }}
                                            @if ($mov->data_fim)
                                                <span class="font-semibold text-zinc-400">→ {{ $mov->data_fim->format('d/m/Y') }}</span>
                                            @else
                                                <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-bold text-amber-800 ring-1 ring-amber-200/80">Em aberto</span>
                                            @endif
                                        </time>
                                        <span class="rounded-full bg-brand-burgundy-soft px-2.5 py-0.5 text-xs font-bold text-brand-burgundy">{{ $mov->tipoLabel() }}</span>
                                    </div>
                                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-700">{{ $mov->resumoAlteracao() }}</p>
                                    @if ($mov->registradoPor)
                                        <p class="mt-2 flex items-center gap-1.5 text-xs text-zinc-400">
                                            <i data-lucide="user-check" class="h-3 w-3"></i>
                                            {{ $mov->registradoPor->name }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <a href="{{ route('rh.efetivo.movimentacoes.edit', [$colaborador, $mov]) }}" class="inline-flex h-9 items-center gap-1 rounded-xl border border-zinc-200 bg-white px-3 text-xs font-bold text-brand-burgundy shadow-sm hover:border-brand-burgundy/30 hover:bg-brand-burgundy-soft">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Alterar
                                    </a>
                                    <form method="POST" action="{{ route('rh.efetivo.movimentacoes.destroy', [$colaborador, $mov]) }}" class="inline" onsubmit="return confirm('Remover este registro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 items-center gap-1 rounded-xl border border-red-200/80 bg-white px-3 text-xs font-bold text-red-600 shadow-sm hover:bg-red-50">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="py-14 text-center">
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400">
                                <i data-lucide="history" class="h-7 w-7"></i>
                            </span>
                            <p class="mt-4 text-base font-semibold text-zinc-500">Nenhuma movimentação registrada</p>
                            <a href="{{ route('rh.efetivo.movimentacoes.create', ['colaborador' => $colaborador, 'tipo' => 'afastamento_inss']) }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-burgundy-soft px-4 py-2 text-sm font-bold text-brand-burgundy hover:bg-brand-burgundy/10">
                                <i data-lucide="plus" class="h-4 w-4"></i>
                                Registrar evento
                            </a>
                        </div>
                    @endforelse
                </div>
            </section>

            @include('rh.colaboradores._ficha_secao', [
                'id' => 'sgc',
                'numero' => '07',
                'titulo' => 'Mobilização SGC Vale',
                'icone' => 'id-card',
                'campos' => [
                    $item('Status', $mobilizacaoLabel[$colaborador->mobilizacao_status] ?? 'Pendente'),
                    $item('Data postagem SGC', $date($colaborador->sgc_data_postagem)),
                    $item('Nº solicitação', $colaborador->sgc_numero_solicitacao),
                    $item('Data aprovação', $date($colaborador->sgc_data_aprovacao)),
                    $item('Entrega do crachá', $date($colaborador->sgc_data_entrega_cracha)),
                    $item('Observações SGC', $colaborador->sgc_observacoes, true),
                ],
            ])

            <section class="overflow-hidden rounded-3xl border border-red-200/60 bg-gradient-to-br from-red-50/80 via-white to-white p-6 shadow-sm ring-1 ring-red-100/80">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                            <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-red-900">Excluir colaborador</h3>
                            <p class="mt-1 text-sm leading-relaxed text-red-800/80">Remove o cadastro permanentemente. Use apenas se o registro não for mais necessário.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('rh.efetivo.destroy', $colaborador) }}" onsubmit="return confirm('Excluir permanentemente este colaborador?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl border border-red-300 bg-white px-5 text-sm font-bold text-red-700 shadow-sm transition hover:bg-red-50">
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                            Remover
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <nav class="ficha-nav-mobile fixed bottom-4 left-3 right-3 z-30 flex gap-1.5 overflow-x-auto rounded-2xl border border-zinc-200/90 bg-white/95 p-1.5 shadow-xl shadow-zinc-300/30 backdrop-blur-md xl:hidden" aria-label="Seções">
        @foreach ($nav as $link)
            <a href="#{{ $link['id'] }}" data-ficha-nav-mobile class="ficha-nav-mobile-link shrink-0 rounded-xl px-3 py-2 text-xs font-bold text-zinc-600 transition">{{ $link['n'] }}</a>
        @endforeach
    </nav>

    <style>
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }
        .ficha-nav-link.is-active,
        .ficha-nav-mobile-link.is-active {
            background-color: rgb(247 233 238);
            color: #6f1731;
        }
        .ficha-nav-link.is-active .ficha-nav-num {
            background-color: #6f1731;
            color: #fff;
        }
        .ficha-nav-mobile-link.is-active {
            background-color: #6f1731;
            color: #fff;
        }
    </style>

    @push('scripts')
        <script>
            (function () {
                const fotoTrigger = document.getElementById('ficha-foto-trigger');
                const fotoInput = document.getElementById('ficha-foto-input');
                const fotoForm = document.getElementById('ficha-foto-form');
                const fotoLoading = document.getElementById('ficha-foto-loading');
                const fotoOverlay = document.getElementById('ficha-foto-overlay');

                if (fotoTrigger && fotoInput && fotoForm) {
                    fotoTrigger.addEventListener('click', () => fotoInput.click());
                    fotoInput.addEventListener('change', () => {
                        if (! fotoInput.files?.length) return;
                        if (fotoLoading) fotoLoading.classList.remove('hidden');
                        if (fotoLoading) fotoLoading.classList.add('flex');
                        if (fotoOverlay) fotoOverlay.classList.add('hidden');
                        fotoTrigger.disabled = true;
                        fotoForm.submit();
                    });
                }
            })();
            (function () {
                const navLinks = document.querySelectorAll('[data-ficha-nav], [data-ficha-nav-mobile]');
                const sections = [...document.querySelectorAll('.ficha-secao, #movimentacoes')];
                if (!sections.length || !navLinks.length) return;

                const setActive = (id) => {
                    navLinks.forEach((a) => {
                        const on = a.getAttribute('href') === '#' + id;
                        a.classList.toggle('is-active', on);
                    });
                };

                const obs = new IntersectionObserver((entries) => {
                    const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (visible?.target?.id) setActive(visible.target.id);
                }, { rootMargin: '-30% 0px -55% 0px', threshold: [0, 0.15, 0.4] });

                sections.forEach((el) => obs.observe(el));
            })();
        </script>
    @endpush

@endsection
