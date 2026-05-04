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
    <body class="min-h-screen bg-white text-brand-black antialiased">
        <main class="grid min-h-screen lg:grid-cols-[minmax(0,42%)_minmax(0,58%)]">
            {{-- Painel hero --}}
            <section class="relative hidden min-h-[280px] overflow-hidden lg:block">
                <div
                    class="absolute inset-0 bg-cover bg-center"
                    style="background-image: url('{{ asset('tela1.jpg') }}');"
                    aria-hidden="true"
                ></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-black/25"></div>
                <div class="relative z-10 flex min-h-screen flex-col justify-between p-10 text-white xl:p-14">
                    <div class="inline-flex max-w-fit items-center justify-center rounded-2xl border border-white/60 bg-white/93 p-5 shadow-[0_12px_40px_rgba(0,0,0,0.28)] ring-1 ring-black/5 backdrop-blur-sm">
                        <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-52 object-contain drop-shadow-[0_1px_2px_rgba(0,0,0,0.15)] xl:w-56">
                    </div>
                    <div class="max-w-lg pb-4">
                        <p class="text-sm font-black tracking-wide text-white/75">Portal de Gestão Contratual</p>
                        <p class="mt-4 text-2xl font-bold leading-snug tracking-tight md:text-3xl md:leading-tight">
                            Controle integrado dos contratos da Omega Service.
                        </p>
                        <p class="mt-5 text-base leading-relaxed text-white/85">
                            Centralize a gestão de contratos em um único ambiente, com acesso às rotinas de RH, veículos, patrimônio e demais frentes essenciais para o acompanhamento operacional e administrativo.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Formulário --}}
            <section class="relative flex flex-col justify-center bg-gradient-to-br from-zinc-50 via-white to-zinc-50/80 px-6 py-12 sm:px-10 lg:px-14 xl:px-20">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(111,23,49,0.06),transparent)]" aria-hidden="true"></div>
                <div class="relative mx-auto w-full max-w-md">
                    <div class="mb-10 flex justify-center lg:hidden">
                        <div class="inline-flex rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-lg ring-1 ring-black/5">
                            <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-44 object-contain drop-shadow-[0_1px_2px_rgba(0,0,0,0.1)] sm:w-48">
                        </div>
                    </div>

                    <div class="rounded-3xl border border-zinc-200/70 bg-white/90 p-8 shadow-[0_24px_64px_-16px_rgba(15,23,42,0.12),0_0_0_1px_rgba(255,255,255,0.8)_inset] backdrop-blur-sm sm:p-10">
                        <header class="mb-8 text-center sm:text-left">
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-brand-burgundy">Acesso ao sistema</p>
                            <h1 class="mt-2 text-3xl font-black tracking-tight text-brand-black sm:text-4xl">Entrar</h1>
                            <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-brand-gray sm:mx-0">
                                Informe seu e-mail e senha para continuar.
                            </p>
                        </header>

                        @if ($errors->any())
                            <div class="mb-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-left text-sm text-red-800" role="alert">
                                <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0 text-red-600"></i>
                                <div>
                                    @foreach ($errors->all() as $message)
                                        <p class="font-semibold">{{ $message }}</p>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="mb-6 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                                <i data-lucide="circle-check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label for="login-email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-brand-gray">E-mail</label>
                                <div class="group relative rounded-xl border border-zinc-200 bg-zinc-50/40 shadow-sm transition duration-200 focus-within:border-brand-burgundy focus-within:bg-white focus-within:shadow-md focus-within:ring-4 focus-within:ring-brand-burgundy/12">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-gray/50 transition group-focus-within:text-brand-burgundy/70">
                                        <i data-lucide="mail" class="h-4 w-4"></i>
                                    </span>
                                    <input
                                        id="login-email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        autofocus
                                        required
                                        autocomplete="email"
                                        autocapitalize="off"
                                        spellcheck="false"
                                        class="h-12 w-full rounded-xl border-0 bg-transparent pl-11 pr-4 text-sm text-brand-black outline-none ring-0 transition placeholder:text-zinc-400 focus:ring-0"
                                        placeholder="voce@empresa.com.br"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="login-password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-brand-gray">Senha</label>
                                <div class="group relative rounded-xl border border-zinc-200 bg-zinc-50/40 shadow-sm transition duration-200 focus-within:border-brand-burgundy focus-within:bg-white focus-within:shadow-md focus-within:ring-4 focus-within:ring-brand-burgundy/12">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-gray/50 transition group-focus-within:text-brand-burgundy/70">
                                        <i data-lucide="lock" class="h-4 w-4"></i>
                                    </span>
                                    <input
                                        id="login-password"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        class="h-12 w-full rounded-xl border-0 bg-transparent pl-11 pr-12 text-sm text-brand-black outline-none ring-0 transition placeholder:text-zinc-400 focus:ring-0"
                                        placeholder="••••••••"
                                    >
                                    <button
                                        type="button"
                                        id="login-password-toggle"
                                        class="absolute right-1.5 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-brand-gray transition hover:bg-zinc-100 hover:text-brand-black focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-burgundy/40"
                                        aria-label="Mostrar senha"
                                        aria-pressed="false"
                                        tabindex="0"
                                    >
                                        <span id="login-password-eye" class="flex items-center justify-center">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </span>
                                        <span id="login-password-eye-off" class="hidden flex items-center justify-center">
                                            <i data-lucide="eye-off" class="h-4 w-4"></i>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
                                <a href="{{ route('password.request') }}" class="order-2 inline-flex items-center gap-1.5 text-sm font-bold text-brand-burgundy transition hover:text-brand-burgundy-dark sm:order-1">
                                    <i data-lucide="key" class="h-3.5 w-3.5 opacity-80"></i>
                                    Esqueci minha senha
                                </a>
                                <label class="order-1 flex cursor-pointer select-none items-center gap-3 sm:order-2">
                                    <span class="text-sm font-semibold text-brand-gray">Lembrar acesso</span>
                                    <input type="checkbox" name="remember" value="1" class="peer sr-only">
                                    <span class="relative inline-flex h-7 w-12 shrink-0 rounded-full bg-zinc-200 shadow-inner transition peer-checked:bg-brand-burgundy after:pointer-events-none after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform after:content-[''] peer-checked:after:translate-x-[1.25rem]"></span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="group/btn relative mt-2 inline-flex h-[3.25rem] w-full items-center justify-center gap-2 overflow-hidden rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-lg shadow-brand-burgundy/25 transition duration-200 hover:bg-brand-burgundy-dark hover:shadow-xl hover:shadow-brand-burgundy/30 active:scale-[0.99]"
                            >
                                <span class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent opacity-0 transition group-hover/btn:opacity-100" aria-hidden="true"></span>
                                <span class="relative inline-flex items-center gap-2">
                                    <i data-lucide="log-in" class="h-4 w-4"></i>
                                    Entrar no sistema
                                </span>
                            </button>
                        </form>

                        <div class="relative my-8">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-zinc-200"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white px-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Acesso corporativo</span>
                            </div>
                        </div>

                        <p class="flex items-start justify-center gap-2 text-center text-xs leading-relaxed text-brand-gray">
                            <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600/90"></i>
                            <span>Credenciais atribuídas pelo administrador. Ambiente interno — não há cadastro público nesta página.</span>
                        </p>

                        <p class="mt-6 border-t border-zinc-100 pt-6 text-center text-sm text-brand-gray">
                            Não consegue entrar?
                            <a href="{{ route('password.request') }}" class="font-bold text-brand-burgundy underline-offset-2 transition hover:text-brand-burgundy-dark hover:underline">Redefinir senha</a>
                            ou fale com o ADM responsável pelo seu contrato.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var input = document.getElementById('login-password');
                var btn = document.getElementById('login-password-toggle');
                var eye = document.getElementById('login-password-eye');
                var eyeOff = document.getElementById('login-password-eye-off');
                if (!input || !btn || !eye || !eyeOff) return;

                btn.addEventListener('click', function () {
                    var show = input.getAttribute('type') === 'password';
                    input.setAttribute('type', show ? 'text' : 'password');
                    eye.classList.toggle('hidden', show);
                    eyeOff.classList.toggle('hidden', !show);
                    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                    btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
                });
            });
        </script>
    </body>
</html>
