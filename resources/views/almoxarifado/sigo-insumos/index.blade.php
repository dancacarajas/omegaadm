@extends('layouts.app')

@section('title', 'Extrair insumos SIGO')
@section('eyebrow', 'Almoxarifado')
@section('page-title', 'Catálogo de insumos SIGO')

@section('actions')
    <a href="{{ route('almoxarifado.painel') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="layout-dashboard" class="h-4 w-4 text-brand-burgundy"></i>
        Painel
    </a>
@endsection

@section('content')
    @include('almoxarifado.mobilizacao.partials.flash')

    @include('almoxarifado.mobilizacao.partials.hero', [
        'badge' => 'Integração SIGO',
        'icone' => 'database',
        'titulo' => 'Extrair catálogo de insumos',
        'subtitulo' => 'Informe login e senha do SIGO. O sistema acessa a tela Novo Pedido, percorre a busca paginada e gera planilha com COD, INSUMO, DETALHE, UND, GRUPO e FAMÍLIA.',
        'stats' => [],
    ])

    @if (! $dependenciasOk)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950">
            <p class="font-bold">Ambiente não preparado para extração</p>
            <p class="mt-1">{{ $dependenciasMsg }}</p>
            <p class="mt-3 font-semibold">No PowerShell, dentro da pasta do projeto:</p>
            <pre class="mt-2 overflow-x-auto rounded-xl bg-white/80 p-3 text-xs text-zinc-800">cd C:\Users\Administrator\Documents\omega286
python -m pip install -r scripts/requirements-sigo-extractor.txt
python -m playwright install chromium</pre>
            <p class="mt-3 text-xs">Se ainda falhar, adicione no .env: SIGO_PYTHON=C:\Users\Administrator\AppData\Local\Programs\Python\Python313\python.exe</p>
            <p class="mt-2 text-xs">Reinicie o Laravel (php artisan serve --port=2080) e atualize esta página.</p>
        </div>
    @elseif (! empty($pythonDetectado))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm text-emerald-950">
            Ambiente pronto. Python: <span class="font-mono text-xs">{{ $pythonDetectado }}</span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <section class="lg:col-span-3 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/80 to-white px-6 py-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                        <i data-lucide="key-round" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-brand-black">Credenciais SIGO</h2>
                        <p class="text-xs text-brand-gray">A senha não é armazenada — usada apenas durante a extração.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('almoxarifado.sigo-insumos.extrair') }}" class="space-y-5 p-6" @if(! $dependenciasOk) onsubmit="return false" @endif>
                @csrf

                <label class="block">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Usuário SIGO</span>
                    <input type="text" name="sigo_usuario" value="{{ old('sigo_usuario') }}" autocomplete="username" required
                        class="mt-1.5 h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10"
                        @disabled(! $dependenciasOk)>
                </label>

                <label class="block">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Senha SIGO</span>
                    <input type="password" name="sigo_senha" autocomplete="current-password" required
                        class="mt-1.5 h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10"
                        @disabled(! $dependenciasOk)>
                </label>

                <div class="rounded-2xl border border-sky-100 bg-sky-50/80 px-4 py-3 text-xs text-sky-950">
                    <p class="font-semibold">O que será feito</p>
                    <ul class="mt-2 list-disc space-y-1 pl-4">
                        <li>Login em {{ $sigoUrl }}</li>
                        <li>Busca vazia e, se necessário, varredura A–Z e 0–9</li>
                        <li>Percorre todas as páginas de cada busca</li>
                        <li>Deduplica por COD + INSUMO + DETALHE + UND + GRUPO + FAMÍLIA</li>
                    </ul>
                    <p class="mt-2 text-sky-800">Pode levar vários minutos. Não feche a aba até concluir.</p>
                </div>

                <button type="submit"
                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled(! $dependenciasOk)>
                    <i data-lucide="download-cloud" class="h-4 w-4"></i>
                    Iniciar extração
                </button>
            </form>
        </section>

        <aside class="lg:col-span-2 space-y-6">
            <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
                <h3 class="text-sm font-bold text-brand-black">Último resultado</h3>
                @if ($ultimoResultado)
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-brand-gray">Data</dt>
                            <dd class="font-semibold text-brand-black">{{ $ultimoResultado['data_extracao'] ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-brand-gray">Páginas lidas</dt>
                            <dd class="font-semibold text-brand-black">{{ number_format((int) ($ultimoResultado['paginas_lidas'] ?? 0), 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-brand-gray">Registros brutos</dt>
                            <dd class="font-semibold text-brand-black">{{ number_format((int) ($ultimoResultado['registros_brutos'] ?? 0), 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-brand-gray">Insumos únicos</dt>
                            <dd class="font-semibold text-brand-burgundy">{{ number_format((int) ($ultimoResultado['registros_unicos'] ?? 0), 0, ',', '.') }}</dd>
                        </div>
                    </dl>

                    @if (! empty($ultimoResultado['token']) && ($ultimoResultado['registros_unicos'] ?? 0) > 0)
                        @php $token = $ultimoResultado['token']; @endphp
                        <div class="mt-5 flex flex-col gap-2">
                            <a href="{{ route('almoxarifado.sigo-insumos.download', ['token' => $token, 'tipo' => 'xlsx']) }}"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-100">
                                <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                                Baixar XLSX
                            </a>
                            <a href="{{ route('almoxarifado.sigo-insumos.download', ['token' => $token, 'tipo' => 'csv']) }}"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black transition hover:border-zinc-300">
                                <i data-lucide="file-text" class="h-4 w-4"></i>
                                Baixar CSV
                            </a>
                            <a href="{{ route('almoxarifado.sigo-insumos.download', ['token' => $token, 'tipo' => 'log']) }}"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-4 text-xs font-semibold text-brand-gray transition hover:border-zinc-300">
                                <i data-lucide="scroll-text" class="h-4 w-4"></i>
                                Ver log da extração
                            </a>
                        </div>
                    @endif

                    @if (! empty($ultimoResultado['erro']))
                        <p class="mt-4 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-800">{{ $ultimoResultado['erro'] }}</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-brand-gray">Nenhuma extração nesta sessão ainda.</p>
                @endif
            </section>

            <section class="rounded-3xl border border-zinc-200/80 bg-zinc-50/50 p-5 text-xs text-brand-gray">
                <p class="font-bold uppercase tracking-wider text-brand-black">Requisitos no servidor</p>
                <ul class="mt-2 list-disc space-y-1 pl-4">
                    <li>Python 3 com Playwright e Chromium instalados</li>
                    <li>Rede liberada até o host do SIGO</li>
                    <li>Preferível rodar em máquina com acesso à rede interna/VPN do SIGO</li>
                </ul>
            </section>
        </aside>
    </div>
@endsection
