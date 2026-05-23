@extends('layouts.app')

@section('title', 'Gerador de Assinatura Eletrônica - Omega286')
@section('eyebrow', 'Configurações')
@section('page-title', 'Gerador de Assinatura Eletrônica')

@section('actions')
    <a href="{{ route('configuracoes.email.edit') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
        <i data-lucide="mail" class="h-4 w-4"></i>
        Configuração de E-mail
    </a>
@endsection

@section('content')
    @php($assinaturaFontCss = app(\App\Services\EmailAssinaturaService::class)->cssFonteArial())
    <style>
        {!! preg_replace('/<\/?style[^>]*>/i', '', $assinaturaFontCss) !!}
        #preview-assinatura,
        #preview-assinatura table,
        #preview-assinatura td,
        #preview-assinatura div,
        #preview-assinatura span,
        #preview-assinatura p {
            font-family: Arial, sans-serif !important;
        }
    </style>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,400px)_1fr]">
        <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
            <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
                <h2 class="text-lg font-bold text-zinc-900">Dados da assinatura</h2>
                <p class="mt-1 text-sm text-zinc-500">
                    Selecione um colaborador cadastrado ou preencha manualmente. Os campos mostram o cadastro como está; na assinatura, nome, função e contrato são formatados como no modelo (ex.: JARBAS ALVES → Jarbas Alves), sem alterar o cadastro.
                </p>
            </div>

            <div class="space-y-5 p-6">
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="modo-colaborador" data-modo="colaborador"
                        class="modo-btn rounded-xl border border-brand-burgundy bg-brand-burgundy px-4 py-2 text-xs font-bold uppercase tracking-wide text-white shadow-sm">
                        Colaborador cadastrado
                    </button>
                    <button type="button" id="modo-manual" data-modo="manual"
                        class="modo-btn rounded-xl border border-zinc-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-zinc-600 shadow-sm hover:border-zinc-300">
                        Preenchimento manual
                    </button>
                </div>

                <div id="wrap-colaborador" class="block space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Colaborador</span>
                    <select id="colaborador_id" class="normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900">
                        <option value="">— Selecione —</option>
                        @foreach ($colaboradores as $colab)
                            <option value="{{ $colab->id }}"
                                data-nome="{{ e($colab->nome) }}"
                                data-funcao="{{ e($colab->cargo ?? '') }}"
                                data-contrato="{{ e($colab->centro_custo ?? '') }}"
                                data-telefone="{{ e($colab->telefone ?? '') }}"
                                data-email="{{ e($colab->email ?? '') }}">
                                {{ $colab->nome }}@if ($colab->matricula) ({{ $colab->matricula }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-1">
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Nome completo</span>
                        <input type="text" id="campo-nome" maxlength="255" disabled
                            class="campo-dinamico normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 disabled:cursor-not-allowed disabled:opacity-70">
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Função</span>
                        <input type="text" id="campo-funcao" maxlength="255" disabled
                            class="campo-dinamico normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 disabled:cursor-not-allowed disabled:opacity-70">
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Contrato (centro de custo)</span>
                        <input type="text" id="campo-contrato" maxlength="255" disabled
                            class="campo-dinamico normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 disabled:cursor-not-allowed disabled:opacity-70">
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Telefone do colaborador</span>
                        <div class="mt-1 flex items-center gap-0 overflow-hidden rounded-xl border border-zinc-200 bg-white">
                            <span class="normal-case shrink-0 bg-zinc-100 px-3 py-2.5 text-sm font-medium text-zinc-600">{{ $telefonePrefixo }}</span>
                            <input type="text" id="campo-telefone" maxlength="80" disabled
                                class="campo-dinamico normal-case min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-zinc-900 outline-none disabled:cursor-not-allowed disabled:opacity-70"
                                placeholder="(94) 99999-9999">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">E-mail</span>
                        <input type="email" id="campo-email" maxlength="255" disabled
                            class="campo-dinamico normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-900 disabled:cursor-not-allowed disabled:opacity-70"
                            placeholder="nome@omegaservice.com.br">
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-100 bg-zinc-50 px-4 py-3 text-xs text-zinc-600">
                    <p class="font-bold text-zinc-800">Campos fixos (sempre na assinatura)</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        <li>{{ $localFixo }}</li>
                        <li>{{ $telefonePrefixo }}<span class="text-zinc-400">telefone do colaborador</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-zinc-900">Pré-visualização</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ $largura }}×{{ $altura }} px · Arial · JPEG em alta resolução ({{ \App\Services\EmailAssinaturaJpegService::EXPORT_SCALE }}×)</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="btn-copiar-html" disabled
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft px-4 text-sm font-semibold text-brand-burgundy transition hover:border-brand-burgundy disabled:cursor-not-allowed disabled:opacity-50">
                        <i data-lucide="copy" class="h-4 w-4"></i>
                        Copiar HTML
                    </button>
                    <button type="button" id="btn-baixar-html" disabled
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 disabled:cursor-not-allowed disabled:opacity-50">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        Baixar .html
                    </button>
                    <button type="button" id="btn-baixar-jpeg" disabled
                        class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 disabled:cursor-not-allowed disabled:opacity-50">
                        <i data-lucide="image" class="h-4 w-4"></i>
                        Baixar .jpeg
                    </button>
                </div>
            </div>

            <div class="flex flex-col items-start gap-4 p-6">
                <div id="preview-wrap" class="overflow-hidden" style="width: {{ $largura }}px; max-width: 100%; line-height: 0;">
                    <div id="preview-assinatura" class="normal-case" style="width: {{ $largura }}px; height: {{ $altura }}px; text-transform: none; font-family: Arial, sans-serif; border: 0; outline: 0;">
                        <p class="flex h-full items-center justify-center text-center text-xs text-zinc-400 px-4">
                            Selecione um colaborador ou preencha os campos para gerar a assinatura.
                        </p>
                    </div>
                </div>
                <p id="copiado-msg" class="hidden text-sm font-semibold text-emerald-700">
                    <i data-lucide="check" class="inline h-4 w-4"></i>
                    HTML copiado. Cole no Outlook em Assinaturas → Nova → colar o conteúdo.
                </p>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        (function () {
            const previewUrl = @json(route('configuracoes.email.assinatura.preview'));
            const jpegUrl = @json(route('configuracoes.email.assinatura.jpeg'));
            const csrf = @json(csrf_token());
            const assinaturaLargura = @json($largura);
            const assinaturaAltura = @json($altura);
            const modoColabBtn = document.getElementById('modo-colaborador');
            const modoManualBtn = document.getElementById('modo-manual');
            const selectColab = document.getElementById('colaborador_id');
            const wrapColab = document.getElementById('wrap-colaborador');
            const campos = {
                nome: document.getElementById('campo-nome'),
                funcao: document.getElementById('campo-funcao'),
                contrato: document.getElementById('campo-contrato'),
                telefone: document.getElementById('campo-telefone'),
                email: document.getElementById('campo-email'),
            };
            const preview = document.getElementById('preview-assinatura');
            const btnCopiar = document.getElementById('btn-copiar-html');
            const btnBaixar = document.getElementById('btn-baixar-html');
            const btnBaixarJpeg = document.getElementById('btn-baixar-jpeg');
            const copiadoMsg = document.getElementById('copiado-msg');
            let modo = 'colaborador';
            let htmlAtual = '';

            function setModo(novo) {
                modo = novo;
                const ativo = 'border-brand-burgundy bg-brand-burgundy text-white';
                const inativo = 'border-zinc-200 bg-white text-zinc-600';
                modoColabBtn.className = 'modo-btn rounded-xl border px-4 py-2 text-xs font-bold uppercase tracking-wide shadow-sm ' + (modo === 'colaborador' ? ativo : inativo);
                modoManualBtn.className = 'modo-btn rounded-xl border px-4 py-2 text-xs font-bold uppercase tracking-wide shadow-sm hover:border-zinc-300 ' + (modo === 'manual' ? ativo : inativo);
                wrapColab.classList.toggle('hidden', modo === 'manual');
                Object.values(campos).forEach((el) => {
                    el.disabled = modo === 'colaborador';
                    el.classList.toggle('bg-zinc-50', modo === 'colaborador');
                    el.classList.toggle('bg-white', modo === 'manual');
                });
                if (modo === 'manual') {
                    selectColab.value = '';
                    Object.values(campos).forEach((el) => { el.value = ''; });
                } else {
                    preencherDeSelect();
                }
                atualizarPreview();
            }

            function valores() {
                return {
                    nome: campos.nome.value.trim(),
                    funcao: campos.funcao.value.trim(),
                    contrato: campos.contrato.value.trim(),
                    telefone: campos.telefone.value.trim(),
                    email: campos.email.value.trim(),
                };
            }

            function preencherDeSelect() {
                const opt = selectColab.selectedOptions[0];
                if (!opt || !opt.value) {
                    Object.values(campos).forEach((el) => { el.value = ''; });
                    return;
                }
                campos.nome.value = opt.dataset.nome || '';
                campos.funcao.value = opt.dataset.funcao || '';
                campos.contrato.value = opt.dataset.contrato || '';
                campos.telefone.value = opt.dataset.telefone || '';
                campos.email.value = opt.dataset.email || '';
            }

            let debounceTimer;
            function atualizarPreview() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(async () => {
                    const v = valores();
                    const vazio = !v.nome && !v.funcao && !v.contrato && !v.telefone && !v.email;
                    if (vazio) {
                        htmlAtual = '';
                        preview.innerHTML = '<p class="flex h-full items-center justify-center text-center text-xs text-zinc-400 px-4">Selecione um colaborador ou preencha os campos para gerar a assinatura.</p>';
                        btnCopiar.disabled = true;
                        btnBaixar.disabled = true;
                        btnBaixarJpeg.disabled = true;
                        return;
                    }
                    try {
                        const res = await fetch(previewUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify(v),
                        });
                        if (!res.ok) throw new Error('preview');
                        const data = await res.json();
                        htmlAtual = data.html || '';
                        preview.innerHTML = htmlAtual;
                        btnCopiar.disabled = !htmlAtual;
                        btnBaixar.disabled = !htmlAtual;
                        btnBaixarJpeg.disabled = !htmlAtual;
                        copiadoMsg.classList.add('hidden');
                        if (window.lucide) lucide.createIcons();
                    } catch {
                        preview.innerHTML = '<p class="flex h-full items-center justify-center text-xs text-red-600 px-4">Erro ao gerar pré-visualização.</p>';
                    }
                }, 280);
            }

            modoColabBtn.addEventListener('click', () => setModo('colaborador'));
            modoManualBtn.addEventListener('click', () => setModo('manual'));
            selectColab.addEventListener('change', () => { preencherDeSelect(); atualizarPreview(); });
            Object.values(campos).forEach((el) => el.addEventListener('input', atualizarPreview));

            btnCopiar.addEventListener('click', async () => {
                if (!htmlAtual) return;
                try {
                    await navigator.clipboard.writeText(htmlAtual);
                    copiadoMsg.classList.remove('hidden');
                } catch {
                    const ta = document.createElement('textarea');
                    ta.value = htmlAtual;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                    copiadoMsg.classList.remove('hidden');
                }
            });

            btnBaixar.addEventListener('click', () => {
                if (!htmlAtual) return;
                const blob = new Blob([htmlAtual], { type: 'text/html;charset=utf-8' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'assinatura-omega.html';
                a.click();
                URL.revokeObjectURL(a.href);
            });

            btnBaixarJpeg.addEventListener('click', async () => {
                const v = valores();
                const vazio = !v.nome && !v.funcao && !v.contrato && !v.telefone && !v.email;
                if (vazio) return;
                btnBaixarJpeg.disabled = true;
                try {
                    const res = await fetch(jpegUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'image/jpeg',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify(v),
                    });
                    if (!res.ok) throw new Error('jpeg');
                    const blob = await res.blob();
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    const nome = (v.nome || 'assinatura').replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').toLowerCase();
                    a.download = 'assinatura-' + nome + '.jpg';
                    a.click();
                    URL.revokeObjectURL(a.href);
                } catch (e) {
                    console.error(e);
                    alert('Não foi possível gerar o JPEG. Tente novamente.');
                } finally {
                    btnBaixarJpeg.disabled = vazio;
                }
            });

            setModo('colaborador');
        })();
    </script>
    @endpush
@endsection
