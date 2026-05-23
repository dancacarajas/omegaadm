@once('script-upload-formulario-adesao')
    @push('scripts')
    <script>
    (function () {
        const MAX_BYTES = 10 * 1024 * 1024;

        function tokenDoForm(form) {
            return form?.querySelector('input[name="_token"]')?.value ?? '';
        }

        function atualizarIcones(raiz) {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons({ icons: lucide.icons, nameAttr: 'data-lucide', attrs: {}, root: raiz || document });
            }
        }

        function mostrarErro(bloco, mensagem) {
            let el = bloco.querySelector('[data-formulario-adesao-erro]');
            if (!el) {
                el = document.createElement('p');
                el.setAttribute('data-formulario-adesao-erro', '');
                el.className = 'mt-2 text-xs font-semibold text-red-700';
                bloco.querySelector('[data-formulario-adesao-upload]')?.after(el);
            }
            el.textContent = mensagem;
            el.classList.remove('hidden');
        }

        function limparErro(bloco) {
            bloco.querySelector('[data-formulario-adesao-erro]')?.classList.add('hidden');
        }

        function renderPreview(bloco, url, nome) {
            let preview = bloco.querySelector('[data-formulario-adesao-preview]');
            if (!preview) {
                preview = document.createElement('div');
                preview.setAttribute('data-formulario-adesao-preview', '');
                preview.className = 'mt-1.5 flex flex-wrap items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/50 px-3 py-2.5';
                const uploadWrap = bloco.querySelector('[data-formulario-adesao-upload]');
                uploadWrap?.parentNode?.insertBefore(preview, uploadWrap);
            }
            preview.innerHTML = '';
            preview.classList.remove('hidden');
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:underline';
            link.innerHTML = '<i data-lucide="file-text" class="h-4 w-4"></i><span></span>';
            link.querySelector('span').textContent = nome ? 'Ver anexo: ' + nome : 'Ver anexo atual';
            preview.appendChild(link);

            const remover = document.createElement('label');
            remover.className = 'inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-brand-gray';
            remover.innerHTML = '<input type="checkbox" name="remover_formulario_adesao" value="1" class="accent-brand-burgundy"> Remover anexo';
            preview.appendChild(remover);
            atualizarIcones(preview);
        }

        function atualizarEmailMatriz(bloco, podeEnviar, problemas) {
            const aviso = bloco.querySelector('[data-email-matriz-aviso-anexo]');
            const botao = bloco.querySelector('[data-email-matriz-botao]');
            const diag = bloco.querySelector('[data-email-matriz-diagnostico]');

            aviso?.classList.add('hidden');

            if (podeEnviar) {
                diag?.classList.add('hidden');
                botao?.classList.remove('hidden');
            } else {
                botao?.classList.add('hidden');
                if (diag && Array.isArray(problemas) && problemas.length) {
                    diag.classList.remove('hidden');
                    const lista = diag.querySelector('[data-email-matriz-problemas]');
                    if (lista) {
                        lista.innerHTML = '';
                        problemas.forEach((texto) => {
                            const li = document.createElement('li');
                            li.textContent = texto;
                            lista.appendChild(li);
                        });
                    }
                }
            }
        }

        function setStatus(bloco, texto) {
            const status = bloco.querySelector('[data-formulario-adesao-status]');
            if (!status) {
                return;
            }
            if (texto) {
                status.textContent = texto;
                status.classList.remove('hidden');
            } else {
                status.classList.add('hidden');
            }
        }

        document.addEventListener('change', async (event) => {
            const input = event.target.closest('[data-auto-upload-formulario-adesao]');
            if (!input || !input.files?.length) {
                return;
            }

            const arquivo = input.files[0];
            const bloco = input.closest('[data-adesao-vinculo-bloco]');
            const form = input.closest('form');
            const url = input.getAttribute('data-upload-url');

            if (!bloco || !form || !url) {
                return;
            }

            limparErro(bloco);

            if (arquivo.size > MAX_BYTES) {
                mostrarErro(bloco, 'O arquivo deve ter no máximo 10 MB.');
                input.value = '';
                return;
            }

            const dados = new FormData();
            dados.append('_token', tokenDoForm(form));
            dados.append('formulario_adesao_assinado', arquivo);

            input.disabled = true;
            setStatus(bloco, 'Salvando anexo automaticamente…');

            try {
                const resposta = await fetch(url, {
                    method: 'POST',
                    body: dados,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const corpo = await resposta.json().catch(() => ({}));

                if (!resposta.ok) {
                    const msg = corpo.message
                        || (corpo.errors?.formulario_adesao_assinado?.[0])
                        || 'Não foi possível salvar o anexo. Tente novamente.';
                    throw new Error(msg);
                }

                renderPreview(bloco, corpo.url_visualizar, corpo.nome_arquivo);
                atualizarEmailMatriz(bloco, !!corpo.pode_enviar_email, corpo.problemas_email || []);
                input.value = '';
            } catch (erro) {
                mostrarErro(bloco, erro.message || 'Falha ao enviar o arquivo.');
            } finally {
                input.disabled = false;
                setStatus(bloco, '');
            }
        });
    })();
    </script>
    @endpush
@endonce
