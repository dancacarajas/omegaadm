<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#6f1731">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Presença na obra') — Omega</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="presenca-obra-shell font-sans text-brand-black antialiased">
    <div class="presenca-obra-layout">
        <div data-mobile-nav-backdrop class="fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>

        @include('layouts.partials._presenca-obra-sidebar')

        <div class="presenca-obra-main">
            <div class="presenca-obra-mobile-bar lg:hidden">
                <button type="button" data-mobile-nav-toggle class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-brand-black shadow-sm" aria-expanded="false" aria-controls="presenca-obra-sidebar" title="Abrir menu">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="presenca-obra-frame">
                @yield('content')
            </div>
        </div>
    </div>
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide?.createIcons) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
