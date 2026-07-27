<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#6f1731">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Omega286'))</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="min-h-screen bg-[#f6f6f7] text-brand-black antialiased">
        <div class="flex min-h-screen">
            <div data-mobile-nav-backdrop class="fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>

            <aside id="presenca-obra-sidebar" data-app-sidebar class="fixed inset-y-0 left-0 z-50 flex w-[280px] shrink-0 -translate-x-full flex-col border-r border-zinc-200/80 bg-white shadow-xl transition-transform duration-200 ease-out lg:static lg:inset-y-auto lg:left-auto lg:z-auto lg:flex lg:w-[280px] lg:translate-x-0 lg:shadow-none">
                <div class="flex h-16 items-center border-b border-zinc-100 px-4">
                    <div class="flex flex-1 justify-center">
                        <img src="{{ asset('logo.png') }}" alt="Omega Service" class="block object-contain" style="max-height: 42px; max-width: 165px; width: auto; height: auto;">
                    </div>
                    <button type="button" data-mobile-nav-close class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy lg:hidden" aria-label="Fechar menu">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <nav class="flex-1 px-4 py-5 text-sm">
                    <div class="space-y-1">
                        <div class="flex h-11 items-center gap-3 rounded-lg bg-brand-burgundy px-3 font-semibold text-white shadow-sm shadow-brand-burgundy/20">
                            <i data-lucide="hard-hat" class="h-5 w-5"></i>
                            <span class="flex-1 text-left">Presença na obra</span>
                        </div>
                        <div class="mt-2 space-y-1 border-l border-zinc-200 pl-4">
                            <a href="{{ route('medicao.presenca-obra.index') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('medicao.presenca-obra.index') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="layout-grid" class="h-4 w-4"></i>
                                Portal público
                            </a>
                            <a href="{{ route('presenca-obra.identificar') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('presenca-obra.*') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                <i data-lucide="smartphone" class="h-4 w-4"></i>
                                Confirmar presença
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="border-t border-zinc-100 px-4 py-4">
                    <p class="text-xs leading-relaxed text-brand-gray">
                        Acesso público para consulta e confirmação de presença na obra. Não substitui o ponto do RH.
                    </p>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-10 flex min-h-20 flex-wrap items-center justify-between gap-3 border-b border-zinc-200/80 bg-white/90 px-4 py-3 backdrop-blur-xl sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                        <button type="button" data-mobile-nav-toggle class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-black shadow-sm lg:hidden" aria-expanded="false" aria-controls="presenca-obra-sidebar" title="Abrir menu">
                            <i data-lucide="menu" class="h-5 w-5"></i>
                        </button>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-burgundy">@yield('eyebrow', 'Medição')</p>
                            <h1 class="mt-1 truncate text-lg font-bold text-brand-black sm:text-xl">@yield('page-title', 'Presença na obra')</h1>
                        </div>
                    </div>
                    <div class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto">
                        @yield('actions')
                    </div>
                </header>

                <main class="min-w-0 max-w-full px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-5 flex items-center gap-3 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-3 text-sm font-semibold text-brand-burgundy shadow-sm">
                            <i data-lucide="circle-check" class="h-5 w-5"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->has('identificacao'))
                        <div class="mb-5 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm" role="alert">
                            <i data-lucide="alert-circle" class="h-5 w-5"></i>
                            <span>{{ $errors->first('identificacao') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 shadow-sm">
                            <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
