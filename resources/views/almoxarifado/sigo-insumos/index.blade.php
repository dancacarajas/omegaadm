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
php artisan sigo:diagnostico
python -m pip install -r scripts/requirements-sigo-extractor.txt
python -m playwright install chromium</pre>
            <p class="mt-3 text-xs">Se ainda falhar, adicione no .env: SIGO_PYTHON=C:\Users\Administrator\AppData\Local\Programs\Python\Python313\python.exe</p>
            <p class="mt-2 text-xs">Reinicie o Laravel e atualize esta página.</p>
        </div>
    @elseif (! empty($pythonDetectado))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm text-emerald-950">
            Ambiente pronto. Python: <span class="font-mono text-xs">{{ $pythonDetectado }}</span>
            @if ($queueConnection !== 'sync')
                <span class="block mt-1 text-xs">Fila: <strong>{{ $queueConnection }}</strong> — mantenha <code class="rounded bg-white/70 px-1">php artisan queue:work</code> rodando.</span>
            @endif
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
                        <p class="text-xs text-brand-gray">A senha é usada só durante a extração e removida em seguida.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('almoxarifado.sigo-insumos.extrair') }}" class="space-y-5 p-6" id="form-sigo-extracao" @if(! $dependenciasOk) onsubmit="return false" @endif>
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
                    <p class="mt-2 text-sky-800">Pode levar vários minutos. Acompanhe o status ao lado.</p>
                </div>

                <button type="submit" id="btn-iniciar-extracao"
                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled(! $dependenciasOk)>
                    <i data-lucide="download-cloud" class="h-4 w-4"></i>
                    Iniciar extração
                </button>
            </form>
        </section>

        <aside class="lg:col-span-2 space-y-6">
            <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100" id="painel-resultado-sigo">
                <h3 class="text-sm font-bold text-brand-black">Status da extração</h3>

                <div id="sigo-status-aguardando" class="mt-4 hidden rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-brand-gray">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-brand-burgundy border-t-transparent"></span>
                        <span id="sigo-status-texto">Aguardando início...</span>
                    </div>
                </div>

                <dl id="sigo-resultado-dl" class="mt-4 hidden space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-brand-gray">Status</dt>
                        <dd id="sigo-campo-status" class="font-semibold text-brand-black">—</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-brand-gray">Data</dt>
                        <dd id="sigo-campo-data" class="font-semibold text-brand-black">—</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-brand-gray">Páginas lidas</dt>
                        <dd id="sigo-campo-paginas" class="font-semibold text-brand-black">0</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-brand-gray">Registros brutos</dt>
                        <dd id="sigo-campo-brutos" class="font-semibold text-brand-black">0</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-brand-gray">Insumos únicos</dt>
                        <dd id="sigo-campo-unicos" class="font-semibold text-brand-burgundy">0</dd>
                    </div>
                </dl>

                <div id="sigo-downloads" class="mt-5 hidden flex-col gap-2"></div>
                <p id="sigo-erro-box" class="mt-4 hidden rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-800"></p>
                <p id="sigo-vazio" class="mt-3 text-sm text-brand-gray">Nenhuma extração em andamento.</p>
            </section>

            <section class="rounded-3xl border border-zinc-200/80 bg-zinc-50/50 p-5 text-xs text-brand-gray">
                <p class="font-bold uppercase tracking-wider text-brand-black">Requisitos no servidor</p>
                <ul class="mt-2 list-disc space-y-1 pl-4">
                    <li>Python 3 com Playwright e Chromium instalados</li>
                    <li>Rede liberada até o host do SIGO</li>
                    <li>Local: <code>php artisan sigo:diagnostico</code></li>
                    <li>Fila database: worker ativo (<code>queue:work</code>)</li>
                </ul>
            </section>
        </aside>
    </div>

    @php
        $extracaoInicial = $extracaoAtiva ? json_encode($extracaoAtiva) : 'null';
        $statusUrlBase = url('/almoxarifado/sigo-insumos/status');
        $downloadUrlBase = url('/almoxarifado/sigo-insumos/download');
    @endphp

    <script>
        (function () {
            const extracaoInicial = {!! $extracaoInicial !!};
            const statusUrlBase = @json($statusUrlBase);
            const downloadUrlBase = @json($downloadUrlBase);

            const elAguardando = document.getElementById('sigo-status-aguardando');
            const elStatusTexto = document.getElementById('sigo-status-texto');
            const elDl = document.getElementById('sigo-resultado-dl');
            const elDownloads = document.getElementById('sigo-downloads');
            const elErro = document.getElementById('sigo-erro-box');
            const elVazio = document.getElementById('sigo-vazio');
            const elForm = document.getElementById('form-sigo-extracao');
            const elBtn = document.getElementById('btn-iniciar-extracao');

            let pollTimer = null;

            function fmt(n) {
                return new Intl.NumberFormat('pt-BR').format(Number(n || 0));
            }

            function labelStatus(s) {
                return {
                    pendente: 'Na fila',
                    executando: 'Executando',
                    concluido: 'Concluído',
                    erro: 'Erro',
                }[s] || s;
            }

            function renderDownloads(uuid, unicos) {
                elDownloads.innerHTML = '';
                if (!uuid || unicos <= 0) {
                    elDownloads.classList.add('hidden');
                    return;
                }
                elDownloads.classList.remove('hidden');
                elDownloads.classList.add('flex');
                const links = [
                    { tipo: 'xlsx', label: 'Baixar XLSX', icon: 'file-spreadsheet', cls: 'border-emerald-200 bg-emerald-50 text-emerald-900 hover:bg-emerald-100' },
                    { tipo: 'csv', label: 'Baixar CSV', icon: 'file-text', cls: 'border-zinc-200 bg-white text-brand-black hover:border-zinc-300' },
                    { tipo: 'log', label: 'Ver log', icon: 'scroll-text', cls: 'border-zinc-200 bg-zinc-50 text-brand-gray hover:border-zinc-300' },
                ];
                links.forEach(function (l) {
                    const a = document.createElement('a');
                    a.href = downloadUrlBase + '/' + encodeURIComponent(uuid) + '/' + l.tipo;
                    a.className = 'inline-flex h-11 items-center justify-center gap-2 rounded-xl border px-4 text-sm font-semibold transition ' + l.cls;
                    a.innerHTML = '<i data-lucide="' + l.icon + '" class="h-4 w-4"></i>' + l.label;
                    elDownloads.appendChild(a);
                });
                if (window.lucide) window.lucide.createIcons();
            }

            function renderErro(msg, uuid) {
                if (!msg) {
                    elErro.classList.add('hidden');
                    elErro.textContent = '';
                    return;
                }
                elErro.classList.remove('hidden');
                elErro.textContent = msg;
                if (uuid) {
                    const dbg = document.createElement('a');
                    dbg.href = downloadUrlBase + '/' + encodeURIComponent(uuid) + '/debug';
                    dbg.className = 'mt-2 block font-semibold underline';
                    dbg.textContent = 'Baixar diagnóstico técnico';
                    elErro.appendChild(document.createElement('br'));
                    elErro.appendChild(dbg);
                }
            }

            function renderPainel(d) {
                if (!d || !d.uuid) {
                    elVazio.classList.remove('hidden');
                    elAguardando.classList.add('hidden');
                    elDl.classList.add('hidden');
                    elDownloads.classList.add('hidden');
                    renderErro(null);
                    return;
                }

                elVazio.classList.add('hidden');
                const emAndamento = d.status === 'pendente' || d.status === 'executando';

                if (emAndamento) {
                    elAguardando.classList.remove('hidden');
                    elStatusTexto.textContent = d.status === 'pendente'
                        ? 'Na fila — aguardando worker...'
                        : 'Executando no SIGO (pode levar vários minutos)...';
                } else {
                    elAguardando.classList.add('hidden');
                }

                elDl.classList.remove('hidden');
                document.getElementById('sigo-campo-status').textContent = labelStatus(d.status);
                document.getElementById('sigo-campo-data').textContent = d.data_extracao || '—';
                document.getElementById('sigo-campo-paginas').textContent = fmt(d.paginas_lidas);
                document.getElementById('sigo-campo-brutos').textContent = fmt(d.registros_brutos);
                document.getElementById('sigo-campo-unicos').textContent = fmt(d.registros_unicos);

                if (d.status === 'concluido') {
                    renderDownloads(d.uuid, d.registros_unicos);
                    renderErro(null);
                    if (elBtn) elBtn.disabled = false;
                } else if (d.status === 'erro') {
                    renderDownloads(d.uuid, 0);
                    renderErro(d.erro || 'Falha na extração.', d.uuid);
                    if (elBtn) elBtn.disabled = false;
                } else {
                    renderDownloads(d.uuid, 0);
                    renderErro(null);
                    if (elBtn) elBtn.disabled = true;
                }
            }

            function poll(uuid) {
                if (pollTimer) clearInterval(pollTimer);
                function tick() {
                    fetch(statusUrlBase + '/' + encodeURIComponent(uuid), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (d) {
                            if (!d) return;
                            renderPainel(d);
                            if (d.status === 'concluido' || d.status === 'erro') {
                                clearInterval(pollTimer);
                                pollTimer = null;
                            }
                        })
                        .catch(function () {});
                }
                tick();
                pollTimer = setInterval(tick, 4000);
            }

            if (extracaoInicial && extracaoInicial.uuid) {
                renderPainel(extracaoInicial);
                if (extracaoInicial.status === 'pendente' || extracaoInicial.status === 'executando') {
                    poll(extracaoInicial.uuid);
                }
            }

            if (elForm) {
                elForm.addEventListener('submit', function () {
                    if (elBtn) {
                        elBtn.disabled = true;
                        elBtn.innerHTML = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span> Iniciando...';
                    }
                });
            }
        })();
    </script>
@endsection
