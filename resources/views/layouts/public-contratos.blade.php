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
        @php
            $contratosOpen = request()->routeIs('publico.contratos.*') || request()->routeIs('publico.dashboard.pgu');
        @endphp
        <div class="flex min-h-screen">
            <aside class="hidden w-[280px] shrink-0 border-r border-zinc-200/80 bg-white lg:block">
                <div class="flex h-16 items-center border-b border-zinc-100 px-4">
                    <div class="flex w-full justify-center">
                        <img src="{{ asset('logo.png') }}" alt="Omega Service" class="block object-contain" style="max-height: 42px; max-width: 165px; width: auto; height: auto;">
                    </div>
                </div>

                <nav class="px-4 py-5 text-sm">
                    <div class="space-y-1">
                        <div>
                            <div class="flex h-11 items-center gap-3 rounded-lg px-3 font-semibold {{ $contratosOpen ? 'bg-brand-burgundy text-white' : 'text-brand-gray' }}">
                                <i data-lucide="file-text" class="h-5 w-5"></i>
                                <span class="flex-1 text-left">Contrato</span>
                            </div>
                            <div class="mt-2 space-y-1 border-l border-zinc-200 pl-4">
                                <a href="{{ route('publico.contratos.apresentacao') }}" class="group flex h-10 items-center gap-3 rounded-lg px-3 text-xs font-semibold transition {{ request()->routeIs('publico.dashboard.pgu') || request()->routeIs('publico.contratos.apresentacao') ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'text-brand-gray hover:bg-brand-gray-soft hover:text-brand-black' }}">
                                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                    PGU - Visao Completa
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-10 flex h-20 items-center justify-between border-b border-zinc-200/80 bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-burgundy">@yield('eyebrow', 'Omega286')</p>
                        <h1 class="mt-1 text-xl font-bold text-brand-black">@yield('page-title', 'Portal Público')</h1>
                    </div>
                    <div class="flex items-center gap-2">
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
