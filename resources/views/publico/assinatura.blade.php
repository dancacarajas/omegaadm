<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6f1731">
    <title>Baixe sua assinatura de e-mail — Omega Service</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --assinatura-burgundy: #6f1731;
            --assinatura-burgundy-dark: #431020;
            --assinatura-burgundy-glow: rgba(111, 23, 49, 0.18);
            --assinatura-surface: rgba(255, 255, 255, 0.82);
            --assinatura-border: rgba(255, 255, 255, 0.65);
            --assinatura-shadow: 0 28px 80px -24px rgba(67, 16, 32, 0.28);
        }

        body {
            align-items: center;
            display: flex;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            justify-content: center;
            min-height: 100vh;
        }

        .assinatura-bg {
            background:
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(111, 23, 49, 0.09), transparent 55%),
                radial-gradient(ellipse 60% 40% at 100% 100%, rgba(111, 23, 49, 0.06), transparent 50%),
                radial-gradient(ellipse 50% 35% at 0% 80%, rgba(111, 23, 49, 0.05), transparent 45%),
                linear-gradient(165deg, #faf9f8 0%, #ffffff 42%, #f9f4f6 100%);
            inset: 0;
            position: fixed;
            z-index: 0;
        }

        .assinatura-bg::before {
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
            content: '';
            inset: 0;
            opacity: 0.5;
            pointer-events: none;
            position: absolute;
        }

        .assinatura-orb {
            border-radius: 50%;
            filter: blur(72px);
            opacity: 0.55;
            pointer-events: none;
            position: fixed;
            z-index: 0;
        }

        .assinatura-orb--1 {
            animation: assinatura-float 14s ease-in-out infinite;
            background: rgba(111, 23, 49, 0.14);
            height: 22rem;
            right: -4rem;
            top: 8%;
            width: 22rem;
        }

        .assinatura-orb--2 {
            animation: assinatura-float 18s ease-in-out infinite reverse;
            background: rgba(247, 233, 238, 0.9);
            bottom: 10%;
            height: 18rem;
            left: -6rem;
            width: 18rem;
        }

        @keyframes assinatura-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        @keyframes assinatura-fade-up {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .assinatura-pagina {
            animation: assinatura-fade-up 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
            margin: 0 auto;
            max-width: 32rem;
            padding: clamp(1.5rem, 4vw, 2.75rem) 1rem;
            position: relative;
            width: 100%;
            z-index: 10;
        }

        .assinatura-conteudo {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .assinatura-hero {
            margin-bottom: 2rem;
            text-align: center;
        }

        .assinatura-logo-wrap {
            backdrop-filter: blur(12px);
            background: var(--assinatura-surface);
            border: 1px solid var(--assinatura-border);
            border-radius: 1.25rem;
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.9) inset,
                0 12px 40px -12px rgba(111, 23, 49, 0.12);
            display: inline-flex;
            padding: 1.125rem 1.375rem;
        }

        .assinatura-hero h1 {
            color: #18181b;
            font-size: clamp(1.5rem, 4vw, 1.75rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-top: 1.5rem;
        }

        .assinatura-hero p {
            color: #52525b;
            font-size: 0.9375rem;
            line-height: 1.65;
            margin: 0.625rem auto 0;
            max-width: 26rem;
        }

        .assinatura-card {
            backdrop-filter: blur(20px);
            background: var(--assinatura-surface);
            border: 1px solid var(--assinatura-border);
            border-radius: 1.5rem;
            box-shadow: var(--assinatura-shadow);
            overflow: hidden;
            position: relative;
        }

        .assinatura-card::before {
            background: linear-gradient(90deg, transparent, var(--assinatura-burgundy), transparent);
            content: '';
            height: 2px;
            left: 10%;
            opacity: 0.35;
            position: absolute;
            right: 10%;
            top: 0;
        }

        .assinatura-etapa {
            padding: 1.75rem 1.5rem;
        }

        @media (min-width: 640px) {
            .assinatura-etapa {
                padding: 2rem 2rem;
            }
        }

        .assinatura-label {
            color: #71717a;
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .assinatura-input {
            background: #fff;
            border: 1px solid #e4e4e7;
            border-radius: 0.875rem;
            color: #18181b;
            font-size: 0.9375rem;
            margin-top: 0.375rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            width: 100%;
        }

        .assinatura-input:hover {
            border-color: #d4d4d8;
        }

        .assinatura-input:focus {
            border-color: var(--assinatura-burgundy);
            box-shadow: 0 0 0 3px var(--assinatura-burgundy-glow);
        }

        .assinatura-input--lg {
            font-size: 1rem;
            font-weight: 500;
            height: 3rem;
            padding: 0 1rem;
        }

        .assinatura-input--md {
            height: 2.75rem;
            padding: 0 0.875rem;
        }

        .assinatura-input-wrap {
            margin-top: 0.375rem;
            position: relative;
        }

        .assinatura-field-icon {
            color: #a1a1aa;
            height: 1.125rem;
            left: 0.875rem;
            pointer-events: none;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 1.125rem;
            z-index: 1;
        }

        .assinatura-input-wrap:focus-within .assinatura-field-icon {
            color: var(--assinatura-burgundy);
        }

        .assinatura-input--icon {
            padding-left: 2.75rem;
        }

        .assinatura-input-wrap .assinatura-input {
            margin-top: 0;
        }

        .assinatura-input--lg.assinatura-input--icon {
            padding-left: 2.875rem;
        }

        .assinatura-input-group {
            display: flex;
            margin-top: 0.375rem;
            overflow: hidden;
            border: 1px solid #e4e4e7;
            border-radius: 0.875rem;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .assinatura-input-group:focus-within {
            border-color: var(--assinatura-burgundy);
            box-shadow: 0 0 0 3px var(--assinatura-burgundy-glow);
        }

        .assinatura-input-prefix {
            background: #fafafa;
            border-right: 1px solid #e4e4e7;
            color: #52525b;
            flex-shrink: 0;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.625rem 0.75rem;
        }

        .assinatura-input-group-icon {
            align-items: center;
            background: #fafafa;
            border-right: 1px solid #e4e4e7;
            color: #a1a1aa;
            display: flex;
            flex-shrink: 0;
            padding: 0 0.75rem;
        }

        .assinatura-input-group-icon svg {
            height: 1.125rem;
            width: 1.125rem;
        }

        .assinatura-input-group:focus-within .assinatura-input-group-icon {
            color: var(--assinatura-burgundy);
        }

        .assinatura-input-group .assinatura-input {
            border: 0;
            border-radius: 0;
            box-shadow: none;
            margin-top: 0;
        }

        .assinatura-input-group .assinatura-input:focus {
            box-shadow: none;
        }

        .assinatura-btn {
            align-items: center;
            border: none;
            border-radius: 0.875rem;
            cursor: pointer;
            display: inline-flex;
            font-size: 0.875rem;
            font-weight: 700;
            gap: 0.5rem;
            justify-content: center;
            letter-spacing: 0.01em;
            transition: transform 0.15s, box-shadow 0.2s, opacity 0.2s;
            width: 100%;
        }

        .assinatura-btn:active:not(:disabled) {
            transform: scale(0.985);
        }

        .assinatura-btn:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .assinatura-btn--primario {
            background: linear-gradient(165deg, #7d1c3a 0%, var(--assinatura-burgundy) 45%, var(--assinatura-burgundy-dark) 100%);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.12) inset,
                0 10px 28px -8px rgba(111, 23, 49, 0.45);
            color: #fff;
            height: 3rem;
        }

        .assinatura-btn--primario:hover:not(:disabled) {
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.15) inset,
                0 14px 32px -6px rgba(111, 23, 49, 0.5);
        }

        .assinatura-btn--loading {
            pointer-events: none;
            position: relative;
        }

        .assinatura-btn--loading .assinatura-btn-text {
            opacity: 0;
        }

        .assinatura-btn-spinner {
            animation: assinatura-spin 0.7s linear infinite;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            border-top-color: #fff;
            display: none;
            height: 1.125rem;
            left: 50%;
            margin: -0.5625rem 0 0 -0.5625rem;
            position: absolute;
            top: 50%;
            width: 1.125rem;
        }

        .assinatura-btn--loading .assinatura-btn-spinner {
            display: block;
        }

        @keyframes assinatura-spin {
            to { transform: rotate(360deg); }
        }

        .assinatura-erro {
            color: #dc2626;
            font-size: 0.8125rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .assinatura-banner {
            border-bottom: 1px solid #f4f4f5;
            padding: 1.25rem 1.5rem;
        }

        @media (min-width: 640px) {
            .assinatura-banner {
                padding: 1.25rem 2rem;
            }
        }

        .assinatura-banner--sucesso {
            background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(255, 255, 255, 0.6) 100%);
        }

        .assinatura-banner--manual {
            background: linear-gradient(135deg, rgba(254, 252, 232, 0.7) 0%, rgba(255, 255, 255, 0.6) 100%);
        }

        .assinatura-banner-titulo {
            align-items: center;
            color: #18181b;
            display: flex;
            font-size: 0.875rem;
            font-weight: 600;
            gap: 0.5rem;
        }

        .assinatura-banner-titulo::before {
            border-radius: 50%;
            content: '';
            flex-shrink: 0;
            height: 0.5rem;
            width: 0.5rem;
        }

        .assinatura-banner--sucesso .assinatura-banner-titulo::before {
            background: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
        }

        .assinatura-banner--manual .assinatura-banner-titulo::before {
            background: #ca8a04;
            box-shadow: 0 0 0 3px rgba(202, 138, 4, 0.2);
        }

        .assinatura-banner-texto {
            color: #52525b;
            font-size: 0.8125rem;
            line-height: 1.55;
            margin-top: 0.375rem;
        }

        .assinatura-link-voltar {
            align-items: center;
            background: none;
            border: none;
            color: var(--assinatura-burgundy);
            cursor: pointer;
            display: inline-flex;
            font-size: 0.75rem;
            font-weight: 600;
            gap: 0.25rem;
            margin-top: 0.75rem;
            padding: 0;
            transition: color 0.15s, gap 0.15s;
        }

        .assinatura-link-voltar:hover {
            color: var(--assinatura-burgundy-dark);
            gap: 0.375rem;
        }

        .assinatura-info {
            align-items: flex-start;
            background: linear-gradient(135deg, #fafafa 0%, #f4f4f5 100%);
            border: 1px solid #e4e4e7;
            border-radius: 1rem;
            display: flex;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
        }

        .assinatura-info-icon {
            color: var(--assinatura-burgundy);
            flex-shrink: 0;
            height: 1.25rem;
            margin-top: 0.0625rem;
            opacity: 0.85;
            width: 1.25rem;
        }

        .assinatura-info-body {
            min-width: 0;
        }

        .assinatura-info-titulo {
            color: #3f3f46;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .assinatura-info-texto {
            color: #52525b;
            font-size: 0.8125rem;
            line-height: 1.5;
            margin-top: 0.25rem;
        }

        .assinatura-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .assinatura-campo {
            display: flex;
            flex-direction: column;
        }

        #mascote-rh-wrap {
            align-items: flex-end;
            bottom: 0;
            display: none;
            left: calc(100% + clamp(0.75rem, 2vw, 1.5rem));
            position: absolute;
            top: 0;
            width: min(24vw, 16rem);
        }

        #mascote-rh-publico {
            filter: drop-shadow(0 20px 40px rgba(67, 16, 32, 0.12));
            height: 100%;
            max-height: 100%;
            object-fit: contain;
            object-position: bottom left;
            width: auto;
        }

        @media (min-width: 1024px) {
            #mascote-rh-wrap {
                display: flex;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .assinatura-pagina,
            .assinatura-orb,
            .assinatura-btn-spinner {
                animation: none;
            }
        }
    </style>
</head>
<body class="relative min-h-screen overflow-x-hidden text-brand-black antialiased">
    <div class="assinatura-bg" aria-hidden="true"></div>
    <div class="assinatura-orb assinatura-orb--1" aria-hidden="true"></div>
    <div class="assinatura-orb assinatura-orb--2" aria-hidden="true"></div>

    <div class="assinatura-pagina">
        <div class="assinatura-conteudo">
            <header class="assinatura-hero">
                <div class="assinatura-logo-wrap">
                    <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-36 object-contain sm:w-40">
                </div>
                <h1>Baixe sua assinatura de e-mail</h1>
                <p>Informe seu CPF para localizar seus dados e gerar sua assinatura.</p>
            </header>

            <div class="assinatura-card">
                <div id="etapa-cpf" class="assinatura-etapa space-y-5">
                    <div>
                        <label for="cpf" class="assinatura-label">CPF</label>
                        <div class="assinatura-input-wrap">
                            <i data-lucide="id-card" class="assinatura-field-icon" aria-hidden="true"></i>
                            <input
                                type="text"
                                id="cpf"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="000.000.000-00"
                                maxlength="14"
                                class="assinatura-input assinatura-input--lg assinatura-input--icon normal-case"
                            >
                        </div>
                        <p id="cpf-erro" class="assinatura-erro hidden"></p>
                    </div>
                    <button type="button" id="btn-continuar" class="assinatura-btn assinatura-btn--primario">
                        <span class="assinatura-btn-spinner" aria-hidden="true"></span>
                        <span class="assinatura-btn-text inline-flex items-center gap-2">
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            Continuar
                        </span>
                    </button>
                </div>

                <div id="etapa-assinatura" class="hidden">
                    <div id="msg-cadastro-banner" class="assinatura-banner assinatura-banner--manual">
                        <p id="msg-cadastro-titulo" class="assinatura-banner-titulo"></p>
                        <p id="msg-cadastro" class="assinatura-banner-texto"></p>
                        <button type="button" id="btn-voltar-cpf" class="assinatura-link-voltar">
                            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                            Usar outro CPF
                        </button>
                    </div>

                    <form id="form-assinatura" class="assinatura-etapa assinatura-form" novalidate>
                        <div class="assinatura-campo">
                            <label for="campo-nome" class="assinatura-label">Nome completo</label>
                            <div class="assinatura-input-wrap">
                                <i data-lucide="user" class="assinatura-field-icon" aria-hidden="true"></i>
                                <input type="text" id="campo-nome" maxlength="255" required
                                    class="assinatura-input assinatura-input--md assinatura-input--icon normal-case">
                            </div>
                        </div>
                        <div class="assinatura-campo">
                            <label for="campo-funcao" class="assinatura-label">Função / cargo</label>
                            <div class="assinatura-input-wrap">
                                <i data-lucide="briefcase" class="assinatura-field-icon" aria-hidden="true"></i>
                                <input type="text" id="campo-funcao" maxlength="255"
                                    class="assinatura-input assinatura-input--md assinatura-input--icon normal-case">
                            </div>
                        </div>
                        <div class="assinatura-campo">
                            <label for="campo-contrato" class="assinatura-label">Contrato / centro de custo</label>
                            <div class="assinatura-input-wrap">
                                <i data-lucide="building-2" class="assinatura-field-icon" aria-hidden="true"></i>
                                <input type="text" id="campo-contrato" maxlength="255"
                                    class="assinatura-input assinatura-input--md assinatura-input--icon normal-case">
                            </div>
                        </div>
                        <div class="assinatura-campo">
                            <label for="campo-telefone" class="assinatura-label">Telefone</label>
                            <div class="assinatura-input-group">
                                <span class="assinatura-input-group-icon" aria-hidden="true">
                                    <i data-lucide="phone"></i>
                                </span>
                                <span class="assinatura-input-prefix">{{ $telefonePrefixo }}</span>
                                <input type="text" id="campo-telefone" maxlength="80" placeholder="(94) 99999-0000"
                                    class="assinatura-input assinatura-input--md normal-case">
                            </div>
                        </div>
                        <div class="assinatura-campo">
                            <label for="campo-email" class="assinatura-label">E-mail</label>
                            <div class="assinatura-input-wrap">
                                <i data-lucide="mail" class="assinatura-field-icon" aria-hidden="true"></i>
                                <input type="email" id="campo-email" maxlength="255"
                                    class="assinatura-input assinatura-input--md assinatura-input--icon normal-case">
                            </div>
                        </div>

                        <div class="assinatura-info">
                            <i data-lucide="sparkles" class="assinatura-info-icon" aria-hidden="true"></i>
                            <div class="assinatura-info-body">
                                <p class="assinatura-info-titulo">Incluídos automaticamente na assinatura</p>
                                <p class="assinatura-info-texto">{{ $localFixo }} · (94) 3352-0115 · seu telefone</p>
                            </div>
                        </div>

                        <p id="download-erro" class="assinatura-erro hidden"></p>

                        <button type="submit" id="btn-baixar" class="assinatura-btn assinatura-btn--primario">
                            <span class="assinatura-btn-spinner" aria-hidden="true"></span>
                            <span class="assinatura-btn-text inline-flex items-center gap-2">
                                <i data-lucide="download" class="h-5 w-5"></i>
                                <span id="btn-baixar-label">Gerar e baixar assinatura</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div id="mascote-rh-wrap" aria-hidden="true">
            <img
                id="mascote-rh-publico"
                src="{{ asset('images/mascote-rh.png') }}"
                alt=""
            >
        </div>
    </div>

    <script>
        (function () {
            const cpfUrl = @json(route('publico.assinatura.cpf'));
            const jpegUrl = @json(route('publico.assinatura.jpeg'));
            const csrf = @json(csrf_token());

            const etapaCpf = document.getElementById('etapa-cpf');
            const etapaAssinatura = document.getElementById('etapa-assinatura');
            const inputCpf = document.getElementById('cpf');
            const cpfErro = document.getElementById('cpf-erro');
            const btnContinuar = document.getElementById('btn-continuar');
            const btnVoltar = document.getElementById('btn-voltar-cpf');
            const msgCadastroBanner = document.getElementById('msg-cadastro-banner');
            const msgCadastroTitulo = document.getElementById('msg-cadastro-titulo');
            const msgCadastro = document.getElementById('msg-cadastro');
            const form = document.getElementById('form-assinatura');
            const btnBaixar = document.getElementById('btn-baixar');
            const btnBaixarLabel = document.getElementById('btn-baixar-label');
            const downloadErro = document.getElementById('download-erro');
            const campos = {
                nome: document.getElementById('campo-nome'),
                funcao: document.getElementById('campo-funcao'),
                contrato: document.getElementById('campo-contrato'),
                telefone: document.getElementById('campo-telefone'),
                email: document.getElementById('campo-email'),
            };

            function setBtnLoading(btn, loading) {
                btn.disabled = loading;
                btn.classList.toggle('assinatura-btn--loading', loading);
            }

            function refreshIcons() {
                if (window.lucide) lucide.createIcons();
            }

            refreshIcons();

            function soDigitos(v) {
                return (v || '').replace(/\D/g, '');
            }

            function mascaraCpf(v) {
                const d = soDigitos(v).slice(0, 11);
                if (d.length <= 3) return d;
                if (d.length <= 6) return d.slice(0, 3) + '.' + d.slice(3);
                if (d.length <= 9) return d.slice(0, 3) + '.' + d.slice(3, 6) + '.' + d.slice(6);
                return d.slice(0, 3) + '.' + d.slice(3, 6) + '.' + d.slice(6, 9) + '-' + d.slice(9);
            }

            inputCpf.addEventListener('input', () => {
                inputCpf.value = mascaraCpf(inputCpf.value);
                cpfErro.classList.add('hidden');
            });

            function valores() {
                return {
                    nome: campos.nome.value.trim(),
                    funcao: campos.funcao.value.trim(),
                    contrato: campos.contrato.value.trim(),
                    telefone: campos.telefone.value.trim(),
                    email: campos.email.value.trim(),
                };
            }

            function limparCampos() {
                Object.values(campos).forEach((el) => { el.value = ''; });
            }

            function preencherCampos(dados) {
                campos.nome.value = dados.nome || '';
                campos.funcao.value = dados.funcao || '';
                campos.contrato.value = dados.contrato || '';
                campos.telefone.value = dados.telefone || '';
                campos.email.value = dados.email || '';
            }

            function mostrarEtapaAssinatura(encontrado) {
                etapaCpf.classList.add('hidden');
                etapaAssinatura.classList.remove('hidden');
                msgCadastroBanner.classList.remove('assinatura-banner--sucesso', 'assinatura-banner--manual');
                if (encontrado) {
                    msgCadastroBanner.classList.add('assinatura-banner--sucesso');
                    msgCadastroTitulo.textContent = 'Cadastro localizado';
                    msgCadastro.textContent = 'Encontramos seus dados. Confira as informações abaixo antes de baixar sua assinatura de e-mail.';
                    btnBaixarLabel.textContent = 'Baixar assinatura';
                } else {
                    msgCadastroBanner.classList.add('assinatura-banner--manual');
                    msgCadastroTitulo.textContent = 'Não localizamos esse CPF';
                    msgCadastro.textContent = 'Preencha seus dados abaixo para gerar sua assinatura de e-mail.';
                    btnBaixarLabel.textContent = 'Gerar e baixar assinatura';
                }
                downloadErro.classList.add('hidden');
                refreshIcons();
            }

            function voltarCpf() {
                etapaAssinatura.classList.add('hidden');
                etapaCpf.classList.remove('hidden');
                downloadErro.classList.add('hidden');
            }

            btnVoltar.addEventListener('click', voltarCpf);

            btnContinuar.addEventListener('click', async () => {
                const digitos = soDigitos(inputCpf.value);
                if (digitos.length !== 11) {
                    cpfErro.textContent = 'Informe um CPF válido com 11 dígitos.';
                    cpfErro.classList.remove('hidden');
                    return;
                }

                setBtnLoading(btnContinuar, true);
                cpfErro.classList.add('hidden');

                try {
                    const res = await fetch(cpfUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ cpf: digitos }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        cpfErro.textContent = data.message || 'Não foi possível consultar o CPF. Tente novamente.';
                        cpfErro.classList.remove('hidden');
                        return;
                    }

                    limparCampos();
                    if (data.encontrado && data.dados) {
                        preencherCampos(data.dados);
                        mostrarEtapaAssinatura(true);
                    } else {
                        mostrarEtapaAssinatura(false);
                    }
                } catch {
                    cpfErro.textContent = 'Erro de conexão. Verifique sua internet e tente novamente.';
                    cpfErro.classList.remove('hidden');
                } finally {
                    setBtnLoading(btnContinuar, false);
                }
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const v = valores();
                if (!v.nome) {
                    downloadErro.textContent = 'Informe pelo menos o nome completo.';
                    downloadErro.classList.remove('hidden');
                    campos.nome.focus();
                    return;
                }

                setBtnLoading(btnBaixar, true);
                downloadErro.classList.add('hidden');

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

                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        downloadErro.textContent = data.message || 'Não foi possível gerar a assinatura. Tente novamente.';
                        downloadErro.classList.remove('hidden');
                        return;
                    }

                    const blob = await res.blob();
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    const nome = (v.nome || 'assinatura').replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').toLowerCase();
                    a.download = 'assinatura-' + nome + '.jpg';
                    a.click();
                    URL.revokeObjectURL(a.href);
                } catch {
                    downloadErro.textContent = 'Erro de conexão ao baixar. Tente novamente.';
                    downloadErro.classList.remove('hidden');
                } finally {
                    setBtnLoading(btnBaixar, false);
                }
            });

            inputCpf.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnContinuar.click();
                }
            });
        })();
    </script>
</body>
</html>
