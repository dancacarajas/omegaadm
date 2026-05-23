<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6f1731">
    <title>Baixe sua Assinatura — Omega Service</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-zinc-50 via-white to-brand-burgundy-soft/30 text-brand-black antialiased">
    <main class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10 sm:px-6">
        <div class="mb-8 text-center">
            <div class="inline-flex rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-lg ring-1 ring-black/5">
                <img src="{{ asset('logo.png') }}" alt="Omega Service" class="h-auto w-40 object-contain sm:w-44">
            </div>
            <h1 class="mt-6 text-2xl font-bold tracking-tight text-zinc-900">Baixe sua Assinatura</h1>
            <p class="mt-2 text-sm leading-relaxed text-zinc-600">
                Informe seu CPF para buscar seus dados no cadastro. Se não estiver cadastrado, preencha manualmente e baixe a imagem para usar no e-mail.
            </p>
        </div>

        <div class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-xl shadow-zinc-200/50 ring-1 ring-zinc-100">
            {{-- Etapa CPF --}}
            <div id="etapa-cpf" class="space-y-5 p-6 sm:p-8">
                <div>
                    <label for="cpf" class="text-xs font-semibold uppercase tracking-wide text-zinc-500">CPF</label>
                    <input
                        type="text"
                        id="cpf"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="000.000.000-00"
                        maxlength="14"
                        class="normal-case mt-1 h-12 w-full rounded-xl border border-zinc-200 bg-white px-4 text-base font-medium text-zinc-900 outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/20"
                    >
                    <p id="cpf-erro" class="mt-2 hidden text-sm font-medium text-red-600"></p>
                </div>
                <button type="button" id="btn-continuar"
                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/25 transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:opacity-50">
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    Continuar
                </button>
            </div>

            {{-- Etapa dados + download --}}
            <div id="etapa-assinatura" class="hidden border-t border-zinc-100">
                <div class="border-b border-zinc-100 bg-zinc-50/80 px-6 py-4 sm:px-8">
                    <p id="msg-cadastro" class="text-sm text-zinc-600"></p>
                    <button type="button" id="btn-voltar-cpf"
                        class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-burgundy hover:underline">
                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                        Alterar CPF
                    </button>
                </div>

                <form id="form-assinatura" class="space-y-4 p-6 sm:p-8" novalidate>
                    <div class="space-y-1">
                        <label for="campo-nome" class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Nome completo</label>
                        <input type="text" id="campo-nome" maxlength="255" required
                            class="normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm text-zinc-900">
                    </div>
                    <div class="space-y-1">
                        <label for="campo-funcao" class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Função / cargo</label>
                        <input type="text" id="campo-funcao" maxlength="255"
                            class="normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm text-zinc-900">
                    </div>
                    <div class="space-y-1">
                        <label for="campo-contrato" class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Contrato / centro de custo</label>
                        <input type="text" id="campo-contrato" maxlength="255"
                            class="normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm text-zinc-900">
                    </div>
                    <div class="space-y-1">
                        <label for="campo-telefone" class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Telefone</label>
                        <div class="mt-1 flex items-center gap-0 overflow-hidden rounded-xl border border-zinc-200 bg-white">
                            <span class="shrink-0 border-r border-zinc-200 bg-zinc-50 px-3 py-2.5 text-xs font-medium text-zinc-600">{{ $telefonePrefixo }}</span>
                            <input type="text" id="campo-telefone" maxlength="80" placeholder="(94) 99999-0000"
                                class="normal-case min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-zinc-900 outline-none">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label for="campo-email" class="text-xs font-semibold uppercase tracking-wide text-zinc-500">E-mail</label>
                        <input type="email" id="campo-email" maxlength="255"
                            class="normal-case mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm text-zinc-900">
                    </div>

                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50 px-4 py-3 text-xs text-zinc-600">
                        <p class="font-bold text-zinc-800">Incluídos automaticamente na assinatura</p>
                        <p class="mt-1">{{ $localFixo }} · {{ $telefonePrefixo }} + seu telefone</p>
                    </div>

                    <p id="download-erro" class="hidden text-sm font-medium text-red-600"></p>

                    <button type="submit" id="btn-baixar"
                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-burgundy text-sm font-bold text-white shadow-md shadow-brand-burgundy/25 transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:opacity-50">
                        <i data-lucide="download" class="h-5 w-5"></i>
                        Baixe sua Assinatura
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-8 text-center text-xs text-zinc-500">
            Imagem JPEG no padrão corporativo Omega · uso em clientes de e-mail (Outlook, Gmail, etc.)
        </p>
    </main>

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
            const msgCadastro = document.getElementById('msg-cadastro');
            const form = document.getElementById('form-assinatura');
            const btnBaixar = document.getElementById('btn-baixar');
            const downloadErro = document.getElementById('download-erro');
            const campos = {
                nome: document.getElementById('campo-nome'),
                funcao: document.getElementById('campo-funcao'),
                contrato: document.getElementById('campo-contrato'),
                telefone: document.getElementById('campo-telefone'),
                email: document.getElementById('campo-email'),
            };

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
                msgCadastro.textContent = encontrado
                    ? 'Encontramos seu cadastro. Confira os dados abaixo e baixe sua assinatura em JPEG.'
                    : 'CPF não encontrado no cadastro. Preencha seus dados manualmente para gerar a assinatura.';
                downloadErro.classList.add('hidden');
                if (window.lucide) lucide.createIcons();
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

                btnContinuar.disabled = true;
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
                    btnContinuar.disabled = false;
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

                btnBaixar.disabled = true;
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
                    btnBaixar.disabled = false;
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
