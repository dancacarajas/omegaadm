<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#6f1731">
        <title>Instalação - Omega286</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f4f5f7] text-brand-black antialiased">
        <main class="min-h-screen px-4 py-8 lg:px-10">
            <div class="mx-auto max-w-6xl">
                <header class="mb-8 flex flex-col gap-5 rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-5">
                        <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-52 object-contain">
                        <div class="hidden h-12 w-px bg-zinc-200 sm:block"></div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-brand-burgundy">Kit de instalação</p>
                            <h1 class="mt-2 text-3xl font-black text-brand-black">Preparar o OmegaADM</h1>
                            <p class="mt-1 text-sm text-brand-gray">Configure o banco de dados, URL de produção e usuário administrador master.</p>
                        </div>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-2 text-xs font-black text-brand-burgundy">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                        Primeiro acesso
                    </span>
                </header>

                @if ($errors->has('install'))
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                        {{ $errors->first('install') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('install.store') }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
                    @csrf
                    <section class="space-y-6">
                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">01 · Aplicação</p>
                            <h2 class="mt-1 text-xl font-bold text-brand-black">Identidade do sistema</h2>
                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Nome do sistema *</span>
                                    <input name="app_name" value="{{ old('app_name', 'OmegaADM') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('app_name') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">URL de produção *</span>
                                    <input name="app_url" value="{{ old('app_url', 'https://omegaadm.feston.net.br') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('app_url') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">02 · Banco de dados</p>
                            <h2 class="mt-1 text-xl font-bold text-brand-black">Conexão MySQL</h2>
                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Host *</span>
                                    <input name="db_host" value="{{ old('db_host', 'localhost') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('db_host') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Porta *</span>
                                    <input name="db_port" value="{{ old('db_port', '3306') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('db_port') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Banco *</span>
                                    <input name="db_database" value="{{ old('db_database', 'u482227589_omegaadm') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('db_database') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Usuário *</span>
                                    <input name="db_username" value="{{ old('db_username', 'u482227589_omegaadm') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('db_username') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="md:col-span-2">
                                    <span class="text-xs font-bold uppercase text-brand-gray">Senha do banco</span>
                                    <input type="password" name="db_password" class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('db_password') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">03 · Administrador</p>
                            <h2 class="mt-1 text-xl font-bold text-brand-black">Usuário master</h2>
                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Nome *</span>
                                    <input name="admin_name" value="{{ old('admin_name', 'Administrador Master') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('admin_name') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">E-mail *</span>
                                    <input type="email" name="admin_email" value="{{ old('admin_email', 'admin@omegaadm.feston.net.br') }}" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('admin_email') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Senha *</span>
                                    <input type="password" name="admin_password" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    @error('admin_password') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="text-xs font-bold uppercase text-brand-gray">Confirmar senha *</span>
                                    <input type="password" name="admin_password_confirmation" required class="mt-1 h-12 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                            </div>
                        </div>
                    </section>

                    <aside class="h-fit rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                            <i data-lucide="rocket" class="h-6 w-6"></i>
                        </div>
                        <h2 class="mt-4 text-xl font-black text-brand-black">O instalador fará tudo</h2>
                        <div class="mt-4 space-y-3 text-sm text-brand-gray">
                            <p class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-4 w-4 text-emerald-600"></i> Testa a conexão com o MySQL.</p>
                            <p class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-4 w-4 text-emerald-600"></i> Gera o arquivo `.env` de produção.</p>
                            <p class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-4 w-4 text-emerald-600"></i> Executa todas as migrations.</p>
                            <p class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-4 w-4 text-emerald-600"></i> Cria o administrador master.</p>
                        </div>
                        <button class="mt-6 inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-black text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="settings" class="h-4 w-4"></i>
                            Instalar sistema
                        </button>
                        <p class="mt-4 text-xs leading-5 text-brand-gray">Após concluir, o sistema bloqueará esta tela e abrirá o login.</p>
                    </aside>
                </form>
            </div>
        </main>
    </body>
</html>
