<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#6f1731">
        <title>Redefinir senha - Omega286</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f4f5f7] text-brand-black antialiased">
        <main class="flex min-h-screen items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <div class="mb-8 flex justify-center">
                    <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-56 object-contain">
                </div>

                <section class="rounded-2xl border border-zinc-200 bg-white p-7 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Nova senha</p>
                    <h1 class="mt-2 text-3xl font-black text-brand-black">Redefinir senha</h1>
                    <p class="mt-2 text-sm leading-6 text-brand-gray">Defina a nova senha para a conta <strong>{{ $email }}</strong>.</p>

                    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email }}">
                        <label class="block">
                            <span class="text-xs font-bold uppercase text-brand-gray">Nova senha</span>
                            <input type="password" name="password" required autofocus class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            @error('password') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-xs font-bold uppercase text-brand-gray">Confirmar nova senha</span>
                            <input type="password" name="password_confirmation" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        </label>
                        @error('email') <span class="block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                        <button class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-bold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="key-round" class="h-4 w-4"></i>
                            Salvar nova senha
                        </button>
                    </form>

                    <a href="{{ route('login') }}" class="mt-5 inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        Voltar ao login
                    </a>
                </section>
            </div>
        </main>
    </body>
</html>
