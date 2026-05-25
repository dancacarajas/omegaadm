@extends('layouts.app')

@section('title', 'Configuração de E-mail - Omega286')
@section('eyebrow', 'Configurações')
@section('page-title', 'Configuração de E-mail')

@section('actions')
    <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
        Voltar ao Painel
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            <i data-lucide="check-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @php
        $usaGmail = str_contains(strtolower((string) ($mail_host ?? '')), 'gmail')
            || str_contains(strtolower((string) ($mail_username ?? '')), '@gmail.com');
    @endphp
    @if ($usaGmail)
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950">
            <p class="font-bold">Gmail (smtp.gmail.com)</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-amber-900/90">
                <li>Ative a <strong>verificação em duas etapas</strong> na conta Google.</li>
                <li>Gere uma <strong>senha de app</strong> (16 letras, sem espaços) em <a class="font-semibold underline" href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a> — não use a senha de login do Gmail.</li>
                <li>Cole essa senha no campo <strong>Senha SMTP</strong>, salve e só então envie o teste.</li>
                <li>Com Gmail, use <strong>286omega@gmail.com</strong> como remetente (ou alias já cadastrado em “Enviar e-mail como”). O endereço <code class="rounded bg-amber-100 px-1">noreply@omegaadm.feston.net.br</code> só funciona com SMTP do próprio domínio (ex.: Hostinger), não com conta @gmail.com.</li>
            </ul>
            <p class="mt-2 text-xs text-amber-800">Referência Google: <a class="underline" href="https://support.google.com/mail/?p=BadCredentials" target="_blank" rel="noopener">Username and Password not accepted (535)</a>.</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-zinc-900">Gerador de Assinatura Eletrônica</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-500">
                    Monte a assinatura corporativa (fundo Omega, Arial, exportação HTML e JPEG) com dados do colaborador ou preenchimento manual.
                </p>
            </div>
            <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-brand-burgundy">RH / comunicação</span>
        </div>
        <div class="flex flex-wrap items-center gap-3 p-6 sm:p-8">
            <a href="{{ route('configuracoes.email.assinatura.index') }}"
               class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="signature" class="h-4 w-4"></i>
                Abrir gerador de assinatura
            </a>
            <p class="text-xs text-zinc-500">Pré-visualização 583×186 px · mesmo layout em localhost e produção (assets em <code class="rounded bg-zinc-100 px-1">/public/images/email</code>).</p>
        </div>
    </section>

    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-zinc-900">Servidor de e-mail (SMTP)</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-500">
                    Configure o envio de e-mails do sistema. A senha SMTP só é alterada se você preencher o campo abaixo.
                </p>
            </div>
            <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-brand-burgundy">Notificações e fluxos automáticos</span>
        </div>

        <form method="POST" action="{{ route('configuracoes.email.update') }}" class="space-y-6 p-6 sm:p-8">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Mailer
                    <select name="mail_mailer" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900">
                        @foreach ($mailers as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('mail_mailer', $mail_mailer) === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Criptografia
                    <select name="mail_encryption" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900">
                        @foreach ($criptografias as $valor => $label)
                            <option value="{{ $valor }}" @selected(old('mail_encryption', $mail_encryption) === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Host SMTP
                    <input type="text" name="mail_host" value="{{ old('mail_host', $mail_host) }}" placeholder="smtp.gmail.com" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                </label>
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Porta SMTP
                    <input type="number" name="mail_port" value="{{ old('mail_port', $mail_port) }}" min="1" max="65535" required class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                </label>
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Usuário SMTP
                    <input type="text" name="mail_username" value="{{ old('mail_username', $mail_username) }}" autocomplete="off" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                </label>
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Senha SMTP
                    <input type="password" name="mail_password" value="" placeholder="{{ $senha_configurada ? '•••••••• (deixe em branco para manter)' : 'Informe a senha' }}" autocomplete="new-password" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                </label>
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Nome remetente
                    <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $mail_from_name) }}" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                </label>
                <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:col-span-2">
                    E-mail remetente
                    <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $mail_from_address) }}" placeholder="noreply@empresa.com.br" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
                </label>
            </div>

            <div class="grid gap-4 rounded-2xl border border-zinc-100 bg-zinc-50/60 p-4 sm:grid-cols-2">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Status da senha SMTP</p>
                    <p class="mt-1 text-sm font-semibold {{ $senha_configurada ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $senha_configurada ? 'Configurada' : 'Não configurada' }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Última atualização</p>
                    <p class="mt-1 text-sm font-semibold text-zinc-800">
                        @if ($ultima_atualizacao)
                            {{ $ultima_atualizacao->format('d/m/Y H:i') }}
                            @if ($atualizado_por)
                                <span class="font-normal text-zinc-500">· {{ $atualizado_por }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Salvar configuração de e-mail
                </button>
            </div>
        </form>
    </section>

    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-zinc-900">E-mails de login e acesso</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-500">
                    Pré-visualize cada mensagem de autenticação (cadastro, recuperação e confirmações de senha) antes de publicar em produção.
                </p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200">Login</span>
        </div>
        <ul class="divide-y divide-zinc-100 p-4 sm:p-6">
            @foreach ($authEmailPreviews ?? [] as $slug => $label)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div>
                        <p class="text-sm font-bold text-zinc-900">{{ $label }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Pré-visualização com dados de exemplo</p>
                    </div>
                    <a href="{{ route('configuracoes.email.preview.auth', $slug) }}" target="_blank" rel="noopener"
                       class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-xs font-bold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                        Visualizar
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-zinc-900">E-mails automáticos — Registro TST</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-500">
                    Destinatários e modelo da mensagem enviada quando um registro TST é concluído no painel ou no app do colaborador.
                </p>
            </div>
            <span class="rounded-full bg-sky-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-sky-800 ring-1 ring-sky-200">SSMA / TST</span>
        </div>

        @include('configuracoes._tst_destinatarios')

        <ul class="divide-y divide-zinc-100 p-4 sm:p-6">
            @foreach ($tstEmailPreviews ?? [] as $slug => $label)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div>
                        <p class="text-sm font-bold text-zinc-900">{{ $label }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Pré-visualização com dados de exemplo</p>
                    </div>
                    <a href="{{ route('configuracoes.email.preview.tst', $slug) }}" target="_blank" rel="noopener"
                       class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-xs font-bold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                        Visualizar
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-zinc-900">E-mails — Solicitação de adesão (Matriz)</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-500">
                    Ao enviar a solicitação de adesão, o sistema faz <strong class="text-zinc-700">dois envios separados</strong>:
                    uma cópia para <strong class="text-zinc-700">{{ config('mail.beneficio_adesao_matriz.copia_sistema', 'jarbas.alves@omegaservice.com.br') }}</strong> pelo e-mail do sistema (Configurações SMTP acima)
                    e o pedido para os destinatários abaixo (ex.: {{ \App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService::RESPONSAVEL_MATRIZ }}) pelo SMTP Zimbra do Jarbas, para chegar como enviado por ele.
                    Configure <code class="rounded bg-zinc-100 px-1 text-xs">MAIL_ZIMBRA_*</code> no servidor (.env) com senha de aplicativo Zimbra.
                </p>
            </div>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-900 ring-1 ring-amber-200">RH / Benefícios</span>
        </div>

        @include('configuracoes._beneficio_adesao_matriz_destinatarios')

        <ul class="divide-y divide-zinc-100 p-4 sm:p-6">
            @foreach ($beneficioAdesaoEmailPreviews ?? [] as $slug => $label)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div>
                        <p class="text-sm font-bold text-zinc-900">{{ $label }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Pré-visualização com dados de exemplo</p>
                    </div>
                    <a href="{{ route('configuracoes.email.preview.beneficio-adesao', $slug) }}" target="_blank" rel="noopener"
                       class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-xs font-bold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                        Visualizar
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mb-6 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-zinc-900">Layout do e-mail</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-500">
                    Pré-visualize o modelo transacional (cabeçalho burgundy, tabela de detalhes, botão de ação e rodapé) antes de usar nas notificações automáticas.
                </p>
            </div>
            <span class="rounded-full bg-violet-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-violet-800 ring-1 ring-violet-200">Aprovação</span>
        </div>
        <div class="flex flex-wrap items-center gap-3 p-6 sm:p-8">
            <a href="{{ route('configuracoes.email.layout-preview') }}" target="_blank" rel="noopener"
               class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="eye" class="h-4 w-4"></i>
                Abrir pré-visualização do layout
            </a>
            <p class="text-xs text-zinc-500">Abre em nova aba com exemplo de chamado de movimentação (dados fictícios).</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-zinc-900">Teste de envio</h2>
                <p class="mt-1 max-w-2xl text-sm text-zinc-500">
                    Envia um e-mail real usando as configurações salvas. Use para validar se host, porta, usuário e senha estão corretos.
                </p>
            </div>
            <span class="rounded-full bg-sky-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-sky-800 ring-1 ring-sky-200">Validação SMTP</span>
        </div>

        <form method="POST" action="{{ route('configuracoes.email.testar') }}" class="flex flex-col gap-4 p-6 sm:flex-row sm:items-end sm:p-8">
            @csrf
            <label class="min-w-0 flex-1 space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                E-mail de teste
                <input type="email" name="email_teste" value="{{ old('email_teste', auth()->user()?->email) }}" required placeholder="seu@email.com" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <button type="submit" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-5 text-sm font-bold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="send" class="h-4 w-4"></i>
                Enviar e-mail de teste
            </button>
        </form>
    </section>
@endsection
