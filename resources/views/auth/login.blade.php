<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#6f1731">
        <title>Login - Omega286</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f4f5f7] text-brand-black antialiased">
        <main class="grid min-h-screen lg:grid-cols-[1fr_520px]">
            <section class="hidden bg-brand-gray p-10 text-white lg:flex lg:flex-col lg:justify-between">
                <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-56 object-contain">
                <div class="max-w-xl">
                    <p class="text-sm font-black uppercase tracking-[0.2em] text-white/60">Gestão empresarial</p>
                    <h1 class="mt-4 text-5xl font-black leading-tight">Controle integrado, seguro e pronto para operação.</h1>
                    <p class="mt-5 text-base leading-7 text-white/75">Acesse os módulos de RH, veículos, contratos, patrimônio, RDO e indicadores executivos em um ambiente único.</p>
                </div>
                <div class="grid grid-cols-3 gap-3 text-sm font-semibold text-white/80">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">RH</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">RDO offline</div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">Contratos</div>
                </div>
            </section>

            <section class="flex items-center justify-center px-5 py-10">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex justify-center lg:hidden">
                        <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-52 object-contain">
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-7 shadow-sm">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Acesso ao sistema</p>
                            <h2 class="mt-2 text-3xl font-black text-brand-black">Entrar</h2>
                            <p class="mt-2 text-sm text-brand-gray">Informe seu e-mail e senha para continuar.</p>
                        </div>

                        @if (session('success'))
                            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                            @csrf
                            <label class="block">
                                <span class="text-xs font-bold uppercase text-brand-gray">E-mail</span>
                                <input type="email" name="email" value="{{ old('email') }}" autofocus required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                @error('email') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-bold uppercase text-brand-gray">Senha</span>
                                <input type="password" name="password" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                @error('password') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="flex items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-sm font-semibold text-brand-gray">
                                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy">
                                    Lembrar acesso
                                </label>
                                <a href="{{ route('password.request') }}" class="text-sm font-bold text-brand-burgundy hover:underline">Esqueci minha senha</a>
                            </div>
                            <button class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-bold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                <i data-lucide="log-in" class="h-4 w-4"></i>
                                Entrar no sistema
                            </button>
                        </form>

                        <div class="mt-5 rounded-xl bg-brand-gray-soft px-4 py-3 text-xs text-brand-gray">
                            Primeiro acesso: <strong class="text-brand-black">admin@omega286.local</strong> / <strong class="text-brand-black">123456</strong>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
