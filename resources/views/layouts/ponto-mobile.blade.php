<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#6f1731">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Ponto') — Omega</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-[100dvh] bg-zinc-100 font-sans text-brand-black antialiased">
    <div class="mx-auto flex min-h-[100dvh] max-w-lg flex-col">
        @yield('content')
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
