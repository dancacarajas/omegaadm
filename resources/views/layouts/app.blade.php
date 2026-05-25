<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#6f1731">
        <title>@yield('title', config('app.name', 'Omega286'))</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f6f6f7] text-brand-black antialiased">
        <div class="flex min-h-screen">
            <div data-mobile-nav-backdrop class="fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>
            <aside id="app-sidebar" data-app-sidebar class="fixed inset-y-0 left-0 z-50 flex w-[280px] shrink-0 -translate-x-full flex-col border-r border-zinc-200/80 bg-white shadow-xl transition-transform duration-200 ease-out lg:static lg:inset-y-auto lg:left-auto lg:z-auto lg:flex lg:w-[280px] lg:translate-x-0 lg:shadow-none">
                <div class="flex h-16 items-center border-b border-zinc-100 px-4">
                    <div class="flex flex-1 justify-center lg:flex-none lg:w-full lg:justify-center">
                        <img src="{{ asset('logo.png') }}" alt="Omega Service" class="block object-contain" style="max-height: 42px; max-width: 165px; width: auto; height: auto;">
                    </div>
                    <button type="button" data-mobile-nav-close class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy lg:hidden" aria-label="Fechar menu">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                @php
                    $usuarioLogado = auth()->user();
                    $fotoUsuarioSidebar = $usuarioLogado?->urlFotoPerfil();
                @endphp
                <div class="border-b border-zinc-100 px-4 py-5">
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft px-3 py-3 shadow-sm">
                        @if ($fotoUsuarioSidebar)
                            <img src="{{ $fotoUsuarioSidebar }}" alt="Foto de {{ $usuarioLogado?->name }}" class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-brand-burgundy/15">
                        @else
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-burgundy-soft text-xs font-bold text-brand-burgundy ring-1 ring-brand-burgundy/10">
                                {{ $usuarioLogado?->iniciais() ?? '?' }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-brand-black">{{ auth()->user()?->name ?? 'Usuário' }}</p>
                            <p class="truncate text-xs font-medium text-brand-gray">{{ auth()->user()?->email ?? 'omega286.local' }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Sair do sistema" class="flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-gray shadow-sm transition hover:border-brand-burgundy hover:bg-brand-burgundy hover:text-white">
                                <i data-lucide="log-out" class="h-4 w-4"></i>
                            </button>
                        </form>
                    </div>
                </div>

                @php
                    auth()->user()?->loadMissing('perfil');
                    $podeModulo = fn (string $m) => auth()->user() && auth()->user()->temQualquerPermissaoNoModulo($m);
                    $podeSecaoSesmt = fn (string $secao) => auth()->user() && auth()->user()->podeSecaoSesmt($secao);
                    $temAlgumaSecaoSesmt = fn () => auth()->user() && auth()->user()->temAlgumaSecaoSesmt();
                    $podeSecaoRh = fn (string $secao) => auth()->user() && auth()->user()->podeSecaoRh($secao);
                    $podeRhAcao = fn (string $acao) => auth()->user() && auth()->user()->podeAcaoNoModulo('rh', $acao);
                    $temAlgumaSecaoRh = fn () => auth()->user() && auth()->user()->temAlgumaSecaoRh();
                    $podeSecaoAlmoxarifado = fn (string $secao) => auth()->user() && auth()->user()->podeSecaoAlmoxarifado($secao);
                    $temAlgumaSecaoAlmoxarifado = fn () => auth()->user() && auth()->user()->temAlgumaSecaoAlmoxarifado();
                    $temSecaoRhFrequencia = fn () => collect(['frequencia_ponto', 'frequencia_apuracao', 'frequencia_feriados', 'frequencia_justificativas', 'horarios'])
                        ->contains(fn (string $s) => $podeSecaoRh($s));
                    $temSecaoRhRelatorios = fn () => collect(['frequencia_ponto', 'beneficios', 'recrutamento'])
                        ->contains(fn (string $s) => $podeSecaoRh($s));
                    $rhOpen = request()->routeIs('rh.*');
                    $frequenciaOpen = request()->routeIs('rh.frequencia.*') || request()->routeIs('rh.horarios.*');
                    $relatoriosOpen = request()->routeIs('rh.frequencia.extrato-faltas')
                        || request()->routeIs('rh.frequencia.cartao-ponto.*')
                        || request()->routeIs('rh.beneficios.extrato.*')
                        || request()->routeIs('rh.recrutamento.painel-preenchimento*')
                        || request()->routeIs('rh.efetivo.contrato-webcard.*');
                    $indicadoresMensaisOpen = request()->routeIs('rh.indicadores-mensais.*');
                    $veiculosOpen = request()->routeIs('veiculos.*');
                    $veiculosFrotaOpen = request()->routeIs('veiculos.frota.*') || request()->routeIs('veiculos.manutencoes.*');
                    $veiculosTelemetriaOpen = request()->routeIs('veiculos.telemetria.*');
                    $contratosOpen = request()->routeIs('contratos.*') || request()->routeIs('dashboard.pgu') || request()->routeIs('contratos.apresentacao');
                    $patrimonialOpen = request()->routeIs('patrimonial.*');
                    $almoxarifadoOpen = request()->routeIs('almoxarifado.*');
                    $medicaoOpen = request()->routeIs('medicao.*') || request()->routeIs('rdo.*');
                    $acessosOpen = request()->routeIs('usuarios.*') || request()->routeIs('perfis.*');
                    $configuracoesOpen = request()->routeIs('configuracoes.*');
                    $ssmaOpen = request()->routeIs('sesmt.*');
                    $ssmaIndicadoresMensaisOpen = request()->routeIs('sesmt.indicadores-mensais.*');
                    $ssmaRegistrosTstOpen = request()->routeIs('sesmt.registros-tst.*');
                @endphp

                <nav class="flex-1 px-4 py-5 text-sm">
                    <div class="space-y-1">
                        @if ($podeModulo('dashboard'))
                            <a href="{{ route('dashboard') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                                Painel
                            </a>
                        @endif

                        @if ($podeModulo('rh') && $temAlgumaSecaoRh())
                        <div data-menu-group="rh">
                            <button type="button" data-menu-toggle="rh" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $rhOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="briefcase-business" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">RH</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $rhOpen ? 'rotate-180' : '' }}" data-menu-chevron="rh"></i>
                            </button>

                            <div data-menu-panel="rh" class="{{ $rhOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                @if ($podeSecaoRh('dashboard'))
                                <a href="{{ route('rh.dashboard') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.dashboard') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                    Painel
                                </a>
                                @endif
                                @if ($podeSecaoRh('efetivo'))
                                <a href="{{ route('rh.efetivo.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.efetivo.index') || request()->routeIs('rh.efetivo.show') || request()->routeIs('rh.efetivo.create') || request()->routeIs('rh.efetivo.edit') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="users" class="h-4 w-4"></i>
                                    Efetivo
                                </a>
                                @endif
                                @if ($podeSecaoRh('chamados_movimentacao'))
                                <a href="{{ route('rh.chamados-movimentacao.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.chamados-movimentacao.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                                    Movimentações
                                </a>
                                @endif
                                @if ($podeSecaoRh('beneficios'))
                                <a href="{{ route('rh.beneficios.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.beneficios.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="hand-heart" class="h-4 w-4"></i>
                                    Benefícios
                                </a>
                                @endif
                                @if ($podeSecaoRh('recrutamento'))
                                <a href="{{ route('rh.recrutamento.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.recrutamento.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="user-round-search" class="h-4 w-4"></i>
                                    Recrutamento
                                </a>
                                @endif
                                @if ($temSecaoRhFrequencia())
                                <div data-menu-group="frequencia">
                                    <button type="button" data-menu-toggle="frequencia" class="group flex h-10 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $frequenciaOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                        <i data-lucide="calendar-check" class="h-4 w-4"></i>
                                        <span class="flex-1 text-left">Frequência</span>
                                        <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $frequenciaOpen ? 'rotate-180' : '' }}" data-menu-chevron="frequencia"></i>
                                    </button>
                                    <div data-menu-panel="frequencia" class="{{ $frequenciaOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                        @if ($podeSecaoRh('frequencia_ponto'))
                                        <a href="{{ route('rh.frequencia.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.frequencia.index') || request()->routeIs('rh.frequencia.marcacao') || request()->routeIs('rh.frequencia.importar-*') || request()->routeIs('rh.frequencia.exportar-*') || request()->routeIs('rh.frequencia.cartao-ponto.*') || request()->routeIs('rh.frequencia.limpar-marcacoes') || request()->routeIs('rh.frequencia.justificar') || request()->routeIs('rh.frequencia.extrato-faltas') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="clock" class="h-4 w-4"></i>
                                            Ponto diário
                                        </a>
                                        @endif
                                        @if ($podeSecaoRh('frequencia_apuracao'))
                                        <a href="{{ route('rh.frequencia.apuracao.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.frequencia.apuracao.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="table-2" class="h-4 w-4"></i>
                                            Apuração do Ponto
                                        </a>
                                        @endif
                                        @if ($podeSecaoRh('frequencia_feriados'))
                                        <a href="{{ route('rh.frequencia.feriados.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.frequencia.feriados.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="calendar-off" class="h-4 w-4"></i>
                                            Feriados
                                        </a>
                                        @endif
                                        @if ($podeSecaoRh('frequencia_justificativas'))
                                        <a href="{{ route('rh.frequencia.justificativa-tipos.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.frequencia.justificativa-tipos.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="file-badge" class="h-4 w-4"></i>
                                            Tipos de justificativa
                                        </a>
                                        @endif
                                        @if ($podeSecaoRh('horarios'))
                                        <a href="{{ route('rh.horarios.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.horarios.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="calendar-range" class="h-4 w-4"></i>
                                            Cadastro de horários
                                        </a>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                @if ($temSecaoRhRelatorios())
                                <div data-menu-group="relatorios">
                                    <button type="button" data-menu-toggle="relatorios" class="group flex h-10 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $relatoriosOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                        <i data-lucide="file-bar-chart" class="h-4 w-4"></i>
                                        <span class="flex-1 text-left">Relatórios</span>
                                        <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $relatoriosOpen ? 'rotate-180' : '' }}" data-menu-chevron="relatorios"></i>
                                    </button>
                                    <div data-menu-panel="relatorios" class="{{ $relatoriosOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                        @if ($podeSecaoRh('frequencia_ponto'))
                                        <a href="{{ route('rh.frequencia.extrato-faltas') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.frequencia.extrato-faltas') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="file-text" class="h-4 w-4"></i>
                                            Extrato de ausências
                                        </a>
                                        <a href="{{ route('rh.frequencia.index') }}#relatorio-cartao-ponto" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.frequencia.cartao-ponto.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="id-card" class="h-4 w-4"></i>
                                            Cartão de ponto (PDF)
                                        </a>
                                        @endif
                                        @if ($podeSecaoRh('beneficios'))
                                        <a href="{{ route('rh.beneficios.extrato.gerar') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.beneficios.extrato.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="receipt" class="h-4 w-4"></i>
                                            Extrato de benefícios
                                        </a>
                                        @endif
                                        @if ($podeSecaoRh('recrutamento'))
                                        <a href="{{ route('rh.recrutamento.painel-preenchimento') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.recrutamento.painel-preenchimento*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="users-round" class="h-4 w-4"></i>
                                            Painel de preenchimento
                                        </a>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                @if ($podeSecaoRh('indicadores_mensais'))
                                <div data-menu-group="indicadores-mensais">
                                    <button type="button" data-menu-toggle="indicadores-mensais" class="group flex h-10 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $indicadoresMensaisOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                        <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                                        <span class="flex-1 text-left">Indicadores mensais</span>
                                        <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $indicadoresMensaisOpen ? 'rotate-180' : '' }}" data-menu-chevron="indicadores-mensais"></i>
                                    </button>
                                    <div data-menu-panel="indicadores-mensais" class="{{ $indicadoresMensaisOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                        <a href="{{ route('rh.indicadores-mensais.painel-executivo') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.indicadores-mensais.painel-executivo') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                            Painel Executivo de RH
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if ($podeModulo('veiculos'))
                        <div data-menu-group="veiculos">
                            <button type="button" data-menu-toggle="veiculos" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $veiculosOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="truck" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Veículos</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $veiculosOpen ? 'rotate-180' : '' }}" data-menu-chevron="veiculos"></i>
                            </button>

                            <div data-menu-panel="veiculos" class="{{ $veiculosOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                <a href="{{ route('veiculos.frota.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ $veiculosFrotaOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="gauge" class="h-4 w-4"></i>
                                    Frota
                                </a>
                                <a href="{{ route('veiculos.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('veiculos.index') || request()->routeIs('veiculos.create') || request()->routeIs('veiculos.edit') || request()->routeIs('veiculos.solicitacoes.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                                    Mobilização
                                </a>
                                <a href="{{ route('veiculos.telemetria.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ $veiculosTelemetriaOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="activity" class="h-4 w-4"></i>
                                    Telemetria
                                </a>
                            </div>
                        </div>
                        @endif
                        @if ($podeModulo('sesmt') && $temAlgumaSecaoSesmt())
                        <div data-menu-group="ssma">
                            <button type="button" data-menu-toggle="ssma" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $ssmaOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="hard-hat" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">SSMA</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $ssmaOpen ? 'rotate-180' : '' }}" data-menu-chevron="ssma"></i>
                            </button>

                            <div data-menu-panel="ssma" class="{{ $ssmaOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                @if ($podeSecaoSesmt('conformidade'))
                                <a href="{{ route('sesmt.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.index') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                                    Controle de Conformidade
                                </a>
                                @endif
                                @if ($podeSecaoSesmt('plano_acao'))
                                <a href="{{ route('sesmt.plano-acao.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.plano-acao.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="list-todo" class="h-4 w-4"></i>
                                    Plano de Ação
                                </a>
                                @endif
                                @if ($podeSecaoSesmt('gestao_riscos'))
                                <a href="{{ route('sesmt.riscos.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.riscos.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="shield-alert" class="h-4 w-4"></i>
                                    Gestão de Riscos
                                </a>
                                @endif
                                @if ($podeSecaoSesmt('epi_epc'))
                                <a href="{{ route('sesmt.epi-epc.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.epi-epc.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="construction" class="h-4 w-4"></i>
                                    Gestão de EPI/EPC
                                </a>
                                @endif
                                @if ($podeSecaoSesmt('meio_ambiente'))
                                <a href="{{ route('sesmt.meio-ambiente.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.meio-ambiente.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="leaf" class="h-4 w-4"></i>
                                    Meio Ambiente
                                </a>
                                @endif
                                @if ($podeSecaoSesmt('registro_mensal'))
                                <a href="{{ route('sesmt.registros.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.registros.*') && ! request()->routeIs('sesmt.registros.prazos.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="calendar-range" class="h-4 w-4"></i>
                                    Registro Mensal
                                </a>
                                @endif
                                @if ($podeSecaoSesmt('prazos_sla'))
                                <a href="{{ route('sesmt.registros.prazos.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.registros.prazos.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="timer" class="h-4 w-4"></i>
                                    Prazos (SLA)
                                </a>
                                @endif
                                @if ($podeSecaoSesmt('indicadores_mensais'))
                                <div data-menu-group="ssma-indicadores-mensais">
                                    <button type="button" data-menu-toggle="ssma-indicadores-mensais" class="group flex h-10 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $ssmaIndicadoresMensaisOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                        <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                                        <span class="flex-1 text-left">Indicadores mensais</span>
                                        <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $ssmaIndicadoresMensaisOpen ? 'rotate-180' : '' }}" data-menu-chevron="ssma-indicadores-mensais"></i>
                                    </button>
                                    <div data-menu-panel="ssma-indicadores-mensais" class="{{ $ssmaIndicadoresMensaisOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                        <a href="{{ route('sesmt.indicadores-mensais.painel-executivo') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.indicadores-mensais.painel-executivo') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                            Painel Executivo de SSMA
                                        </a>
                                    </div>
                                </div>
                                @endif
                                @if ($podeSecaoSesmt('registros_tst'))
                                <div data-menu-group="ssma-registros-tst">
                                    <button type="button" data-menu-toggle="ssma-registros-tst" class="group flex h-10 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $ssmaRegistrosTstOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                                        <span class="flex-1 text-left">Registros TST</span>
                                        <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $ssmaRegistrosTstOpen ? 'rotate-180' : '' }}" data-menu-chevron="ssma-registros-tst"></i>
                                    </button>
                                    <div data-menu-panel="ssma-registros-tst" class="{{ $ssmaRegistrosTstOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                        <a href="{{ route('sesmt.registros-tst.registros.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.registros-tst.registros.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                                            Registros de campo
                                        </a>
                                        <a href="{{ route('sesmt.registros-tst.atividades.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('sesmt.registros-tst.atividades.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="list" class="h-4 w-4"></i>
                                            Cadastro de Atividades
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if ($podeModulo('contratos'))
                        <div data-menu-group="contratos">
                            <button type="button" data-menu-toggle="contratos" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $contratosOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="file-text" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Contrato</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $contratosOpen ? 'rotate-180' : '' }}" data-menu-chevron="contratos"></i>
                            </button>

                            <div data-menu-panel="contratos" class="{{ $contratosOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                <a href="{{ route('contratos.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('contratos.index') || request()->routeIs('contratos.create') || request()->routeIs('contratos.edit') || request()->routeIs('contratos.show') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="list" class="h-4 w-4"></i>
                                    Gestão de contratos
                                </a>
                                <a href="{{ route('contratos.histograma.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('contratos.histograma.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                                    Histograma
                                </a>
                                <a href="{{ route('contratos.apresentacao') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('contratos.apresentacao') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                    Apresentação
                                </a>
                                <a href="{{ route('contratos.acoes-recomendadas.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('contratos.acoes-recomendadas.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="clipboard-pen-line" class="h-4 w-4"></i>
                                    Ações Recomendadas
                                </a>
                                <a href="{{ route('dashboard.pgu') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('dashboard.pgu') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                                    PGU — visão completa
                                </a>
                            </div>
                        </div>
                        @endif
                        @if ($podeModulo('patrimonial'))
                        <div data-menu-group="patrimonial">
                            <button type="button" data-menu-toggle="patrimonial" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $patrimonialOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="warehouse" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Patrimonial</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $patrimonialOpen ? 'rotate-180' : '' }}" data-menu-chevron="patrimonial"></i>
                            </button>

                            <div data-menu-panel="patrimonial" class="{{ $patrimonialOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                <a href="{{ route('patrimonial.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('patrimonial.index') || request()->routeIs('patrimonial.create') || request()->routeIs('patrimonial.edit') || request()->routeIs('patrimonial.show') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="list" class="h-4 w-4"></i>
                                    Equipamentos
                                </a>
                                <a href="{{ route('patrimonial.histograma.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('patrimonial.histograma.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                                    Histograma
                                </a>
                            </div>
                        </div>
                        @endif
                        @if ($podeModulo('almoxarifado') && $temAlgumaSecaoAlmoxarifado())
                        <div data-menu-group="almoxarifado">
                            <button type="button" data-menu-toggle="almoxarifado" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $almoxarifadoOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="package" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Almoxarifado</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $almoxarifadoOpen ? 'rotate-180' : '' }}" data-menu-chevron="almoxarifado"></i>
                            </button>
                            <div data-menu-panel="almoxarifado" class="{{ $almoxarifadoOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                @if ($podeSecaoAlmoxarifado('painel'))
                                <a href="{{ route('almoxarifado.painel') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('almoxarifado.painel') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                    Painel
                                </a>
                                @endif
                                @if ($podeSecaoAlmoxarifado('mobilizacao_materiais'))
                                <a href="{{ route('almoxarifado.mobilizacao-materiais.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('almoxarifado.mobilizacao-materiais.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                                    Mobilização de Materiais
                                </a>
                                @endif
                                @if ($podeSecaoAlmoxarifado('sigo_insumos'))
                                <a href="{{ route('almoxarifado.sigo-insumos.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('almoxarifado.sigo-insumos.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="database" class="h-4 w-4"></i>
                                    Extrair insumos SIGO
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if ($podeModulo('medicao') || $podeModulo('rdo'))
                        <div data-menu-group="medicao">
                            <button type="button" data-menu-toggle="medicao" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $medicaoOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="calculator" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Medição</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $medicaoOpen ? 'rotate-180' : '' }}" data-menu-chevron="medicao"></i>
                            </button>

                            <div data-menu-panel="medicao" class="{{ $medicaoOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                @if ($podeModulo('medicao'))
                                <a href="{{ route('medicao.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('medicao.index') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="gauge" class="h-4 w-4"></i>
                                    Painel de medição
                                </a>
                                <a href="{{ route('medicao.contratual.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('medicao.contratual.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                    Medição contratual
                                </a>
                                <a href="{{ route('medicao.fluxo-financeiro.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('medicao.fluxo-financeiro.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="line-chart" class="h-4 w-4"></i>
                                    Fluxo financeiro
                                </a>
                                @endif
                                @if ($podeModulo('rdo'))
                                <a href="{{ route('rdo.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rdo.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                                    RDO
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if ($podeModulo('configuracoes'))
                        <div data-menu-group="configuracoes">
                            <button type="button" data-menu-toggle="configuracoes" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $configuracoesOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="settings" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Configurações</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $configuracoesOpen ? 'rotate-180' : '' }}" data-menu-chevron="configuracoes"></i>
                            </button>
                            <div data-menu-panel="configuracoes" class="{{ $configuracoesOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                <a href="{{ route('configuracoes.email.edit') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('configuracoes.email.edit') || request()->routeIs('configuracoes.email.update') || request()->routeIs('configuracoes.email.testar') || request()->routeIs('configuracoes.email.layout-preview') || request()->routeIs('configuracoes.email.preview.*') || request()->routeIs('configuracoes.email.tst-destinatarios.*') || request()->routeIs('configuracoes.email.beneficio-adesao-matriz-destinatarios.*') || request()->routeIs('configuracoes.email.zimbra-jarbas.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="mail" class="h-4 w-4"></i>
                                    E-mail
                                </a>
                                <a href="{{ route('configuracoes.email.assinatura.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('configuracoes.email.assinatura.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="signature" class="h-4 w-4"></i>
                                    Gerador de Assinatura Eletrônica
                                </a>
                            </div>
                        </div>
                        @endif

                        @if ($podeModulo('acessos'))
                        <div data-menu-group="acessos">
                            <button type="button" data-menu-toggle="acessos" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $acessosOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="shield-check" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Acessos</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $acessosOpen ? 'rotate-180' : '' }}" data-menu-chevron="acessos"></i>
                            </button>

                            <div data-menu-panel="acessos" class="{{ $acessosOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                <a href="{{ route('usuarios.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('usuarios.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="users-round" class="h-4 w-4"></i>
                                    Usuários
                                </a>
                                <a href="{{ route('perfis.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('perfis.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="shield" class="h-4 w-4"></i>
                                    Perfis
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </nav>

                <div class="border-t border-zinc-100 px-4 py-5">
                    <form method="POST" action="{{ route('logout') }}" class="mb-3">
                        @csrf
                        <button class="flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                            Sair do sistema
                        </button>
                    </form>
                    <div class="relative overflow-hidden rounded-2xl border border-zinc-200/90 bg-gradient-to-br from-white via-brand-burgundy-soft/40 to-zinc-50/80 p-4 shadow-sm ring-1 ring-zinc-100/80">
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-brand-burgundy/[0.06]"></div>
                        <div class="pointer-events-none absolute -bottom-10 -left-6 h-20 w-20 rounded-full bg-brand-burgundy/[0.04]"></div>
                        <div class="relative flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-burgundy to-brand-burgundy-dark text-white shadow-md shadow-brand-burgundy/20">
                                <i data-lucide="shield-check" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold tracking-tight text-brand-black">Sessão protegida</p>
                                <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-brand-gray">
                                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-emerald-500/20" aria-hidden="true"></span>
                                    Ambiente online
                                </p>
                            </div>
                        </div>
                        <p class="relative mt-3 border-t border-zinc-200/70 pt-3 text-[10px] font-medium leading-relaxed text-brand-gray">
                            Conexão segura · {{ config('app.name', 'Omega286') }}
                        </p>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-10 flex h-20 items-center justify-between border-b border-zinc-200/80 bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                    <div class="flex items-center gap-4">
                        <button type="button" data-mobile-nav-toggle class="flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-black shadow-sm lg:hidden" aria-expanded="false" aria-controls="app-sidebar" title="Abrir menu">
                            <i data-lucide="menu" class="h-5 w-5"></i>
                        </button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-burgundy">@yield('eyebrow', 'Omega286')</p>
                            <h1 class="mt-1 text-xl font-bold text-brand-black">@yield('page-title', 'Dashboard')</h1>
                        </div>
                    </div>
                    <div class="flex min-w-0 max-w-full flex-wrap items-center justify-end gap-2">
                        @include('layouts.partials._busca_global')
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @yield('actions')
                        </div>
                    </div>
                </header>

                <main class="min-w-0 max-w-full px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-5 flex items-center gap-3 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-3 text-sm font-semibold text-brand-burgundy shadow-sm">
                            <i data-lucide="circle-check" class="h-5 w-5"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-5 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 shadow-sm">
                            <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                            <span>{{ session('warning') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm">
                            <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0"></i>
                            <span class="whitespace-pre-line">{{ session('error') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
        @stack('modals')
        @stack('scripts')
    </body>
</html>
