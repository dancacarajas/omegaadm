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
            <aside class="hidden w-[280px] shrink-0 border-r border-zinc-200/80 bg-white lg:flex lg:flex-col">
                <div class="flex h-16 items-center justify-center border-b border-zinc-100 px-4">
                    <img src="{{ asset('logo.png') }}" alt="Omega Service" class="block object-contain" style="max-height: 42px; max-width: 165px; width: auto; height: auto;">
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
                        <i data-lucide="chevrons-up-down" class="h-4 w-4 text-brand-gray"></i>
                    </div>
                </div>

                @php
                    $rhOpen = request()->routeIs('rh.*');
                    $acessosOpen = request()->routeIs('usuarios.*') || request()->routeIs('perfis.*');
                @endphp

                <nav class="flex-1 px-4 py-5 text-sm">
                    <div class="space-y-1">
                        <a href="{{ route('dashboard') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                            Painel
                        </a>

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
                                <a href="{{ route('rh.frequencia.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rh.frequencia.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="calendar-check" class="h-4 w-4"></i>
                                    Frequência
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('veiculos.index') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('veiculos.*') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="truck" class="h-5 w-5"></i>
                            Veículos
                        </a>
                        <a href="{{ route('sesmt.index') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('sesmt.*') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="hard-hat" class="h-5 w-5"></i>
                            SESMT
                        </a>
                        <a href="{{ route('contratos.index') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('contratos.*') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="file-text" class="h-5 w-5"></i>
                            Contrato
                        </a>
                        <a href="{{ route('patrimonial.index') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('patrimonial.*') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="warehouse" class="h-5 w-5"></i>
                            Patrimonial
                        </a>
                        <a href="{{ route('rdo.index') }}" class="group flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition {{ request()->routeIs('rdo.*') ? 'bg-brand-burgundy text-white shadow-sm shadow-brand-burgundy/20' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                            <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                            RDO
                        </a>

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
                        <button class="flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-black shadow-sm lg:hidden">
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
