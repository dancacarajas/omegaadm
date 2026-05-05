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

                <div class="border-b border-zinc-100 px-4 py-5">
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft px-3 py-3 shadow-sm">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                            <i data-lucide="user" class="h-5 w-5"></i>
                        </div>
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
                    $rhOpen = request()->routeIs('rh.*');
                    $frequenciaOpen = request()->routeIs('rh.frequencia.*') || request()->routeIs('rh.horarios.*');
                    $veiculosOpen = request()->routeIs('veiculos.*');
                    $veiculosFrotaOpen = request()->routeIs('veiculos.frota.*') || request()->routeIs('veiculos.manutencoes.*');
                    $veiculosTelemetriaOpen = request()->routeIs('veiculos.telemetria.*');
                    $contratosOpen = request()->routeIs('contratos.*') || request()->routeIs('dashboard.pgu');
                    $medicaoOpen = request()->routeIs('medicao.*') || request()->routeIs('rdo.*');
                    $acessosOpen = request()->routeIs('usuarios.*') || request()->routeIs('perfis.*');
                @endphp

                <nav class="flex-1 px-4 py-5 text-sm">
                    <div class="space-y-1">
                        @if ($podeModulo('dashboard'))
                            <a href="{{ route('dashboard') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                                Painel
                            </a>
                        @endif

                        @if ($podeModulo('rh'))
                        <div data-menu-group="rh">
                            <button type="button" data-menu-toggle="rh" class="group flex h-11 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $rhOpen ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="briefcase-business" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">RH</span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $rhOpen ? 'rotate-180' : '' }}" data-menu-chevron="rh"></i>
                            </button>

                            <div data-menu-panel="rh" class="{{ $rhOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                <a href="{{ route('rh.dashboard') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.dashboard') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                    Painel
                                </a>
                                <a href="{{ route('rh.efetivo.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.efetivo.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="users" class="h-4 w-4"></i>
                                    Efetivo
                                </a>
                                <a href="{{ route('rh.beneficios.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.beneficios.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="hand-heart" class="h-4 w-4"></i>
                                    Benefícios
                                </a>
                                <a href="{{ route('rh.recrutamento.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.recrutamento.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="user-round-search" class="h-4 w-4"></i>
                                    Recrutamento
                                </a>
                                <div data-menu-group="frequencia">
                                    <button type="button" data-menu-toggle="frequencia" class="group flex h-10 w-full items-center gap-3 rounded-lg px-3 font-semibold transition {{ $frequenciaOpen ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                        <i data-lucide="calendar-check" class="h-4 w-4"></i>
                                        <span class="flex-1 text-left">Frequência</span>
                                        <i data-lucide="chevron-down" class="h-4 w-4 transition {{ $frequenciaOpen ? 'rotate-180' : '' }}" data-menu-chevron="frequencia"></i>
                                    </button>
                                    <div data-menu-panel="frequencia" class="{{ $frequenciaOpen ? '' : 'hidden' }} mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                        <a href="{{ route('rh.frequencia.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.frequencia.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="clock" class="h-4 w-4"></i>
                                            Ponto diário
                                        </a>
                                        <a href="{{ route('rh.horarios.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('rh.horarios.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                            <i data-lucide="calendar-range" class="h-4 w-4"></i>
                                            Cadastro de horários
                                        </a>
                                    </div>
                                </div>
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
                        @if ($podeModulo('sesmt'))
                        <a href="{{ route('sesmt.index') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('sesmt.*') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="hard-hat" class="h-5 w-5"></i>
                            SESMT
                        </a>
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
                                <a href="{{ route('dashboard.pgu') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('dashboard.pgu') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                                    PGU Command Center
                                </a>
                            </div>
                        </div>
                        @endif
                        @if ($podeModulo('patrimonial'))
                        <a href="{{ route('patrimonial.index') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('patrimonial.*') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="warehouse" class="h-5 w-5"></i>
                            Patrimonial
                        </a>
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
                    <div class="rounded-xl border border-zinc-200 bg-brand-gray p-4 text-white shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15">
                                <i data-lucide="shield-check" class="h-5 w-5 text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Sistema seguro</p>
                                <p class="text-xs text-white/80">Ambiente online</p>
                            </div>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/20">
                            <div class="h-full w-full rounded-full bg-brand-burgundy"></div>
                        </div>
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
                    <div class="flex items-center gap-2">
                        <div class="hidden h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-gray shadow-sm md:flex">
                            <i data-lucide="search" class="h-4 w-4 text-brand-burgundy"></i>
                            Buscar no sistema
                        </div>
                        @yield('actions')
                    </div>
                </header>

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-5 flex items-center gap-3 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-3 text-sm font-semibold text-brand-burgundy shadow-sm">
                            <i data-lucide="circle-check" class="h-5 w-5"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
