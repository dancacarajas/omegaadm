<script>
    (() => {
        const modal = document.getElementById('presenca-justificativa-modal');
        if (!modal) {
            return;
        }

        const textoEl = document.getElementById('presenca-justificativa-texto');
        const arquivosEl = document.getElementById('presenca-justificativa-arquivos');
        const colaboradorEl = modal.querySelector('[data-justificativa-colaborador]');
        const charsEl = modal.querySelector('[data-justificativa-chars]');
        const pendentesWrap = modal.querySelector('[data-justificativa-pendentes-wrap]');
        const pendentesEl = modal.querySelector('[data-justificativa-pendentes]');
        const existentesWrap = modal.querySelector('[data-justificativa-existentes-wrap]');
        const existentesEl = modal.querySelector('[data-justificativa-existentes]');

        const arquivosPorColaborador = new Map();
        const objectUrls = new Map();
        let colaboradorAtual = null;

        const liberarObjectUrls = (colaboradorId) => {
            const urls = objectUrls.get(colaboradorId) || [];
            urls.forEach((url) => URL.revokeObjectURL(url));
            objectUrls.delete(colaboradorId);
        };

        const atualizarChars = () => {
            if (charsEl && textoEl) {
                charsEl.textContent = String(textoEl.value.length);
            }
        };

        const criarLinhaAnexo = ({ nome, url, onRemover = null }) => {
            const li = document.createElement('li');
            li.className = 'flex items-center justify-between gap-2 rounded-lg border border-white/80 bg-white px-2.5 py-2 text-xs shadow-sm';

            const nomeEl = document.createElement('span');
            nomeEl.className = 'min-w-0 flex-1 truncate font-medium text-brand-black';
            nomeEl.textContent = nome;
            nomeEl.title = nome;

            const actions = document.createElement('div');
            actions.className = 'flex shrink-0 items-center gap-1.5';

            if (url) {
                const ver = document.createElement('a');
                ver.href = url;
                ver.target = '_blank';
                ver.rel = 'noopener';
                ver.className = 'inline-flex items-center gap-1 rounded-md bg-brand-burgundy px-2 py-1 text-[11px] font-bold text-white hover:bg-brand-burgundy-dark';
                ver.innerHTML = '<i data-lucide="eye" class="h-3 w-3"></i> Visualizar';
                actions.appendChild(ver);
            }

            if (onRemover) {
                const remover = document.createElement('button');
                remover.type = 'button';
                remover.className = 'inline-flex items-center rounded-md border border-zinc-200 px-2 py-1 text-[11px] font-bold text-brand-gray hover:border-red-300 hover:text-red-700';
                remover.textContent = 'Remover';
                remover.addEventListener('click', onRemover);
                actions.appendChild(remover);
            }

            li.append(nomeEl, actions);

            return li;
        };

        const renderAnexosPendentes = (colaboradorId) => {
            if (!pendentesEl || !pendentesWrap) {
                return;
            }

            liberarObjectUrls(colaboradorId);
            pendentesEl.innerHTML = '';

            const files = arquivosPorColaborador.get(colaboradorId) || [];
            pendentesWrap.hidden = files.length === 0;

            const urls = [];
            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                urls.push(url);

                pendentesEl.appendChild(criarLinhaAnexo({
                    nome: `${file.name} (${Math.max(1, Math.round(file.size / 1024))} KB)`,
                    url,
                    onRemover: () => {
                        const atuais = [...(arquivosPorColaborador.get(colaboradorId) || [])];
                        atuais.splice(index, 1);
                        if (atuais.length === 0) {
                            arquivosPorColaborador.delete(colaboradorId);
                        } else {
                            arquivosPorColaborador.set(colaboradorId, atuais);
                        }
                        renderAnexosPendentes(colaboradorId);
                        atualizarBotao(colaboradorId);
                    },
                }));
            });

            if (urls.length > 0) {
                objectUrls.set(colaboradorId, urls);
            }

            window.lucide?.createIcons?.();
        };

        const renderAnexosExistentes = (button) => {
            if (!existentesEl || !existentesWrap) {
                return;
            }

            existentesEl.innerHTML = '';
            let index = 0;

            while (button.getAttribute(`data-anexo-existente-${index}-nome`)) {
                const nome = button.getAttribute(`data-anexo-existente-${index}-nome`);
                const url = button.getAttribute(`data-anexo-existente-${index}-url`);
                existentesEl.appendChild(criarLinhaAnexo({ nome, url }));
                index++;
            }

            existentesWrap.hidden = index === 0;
            window.lucide?.createIcons?.();
        };

        const fechar = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            colaboradorAtual = null;
            document.body.style.overflow = '';
        };

        const abrir = (button) => {
            colaboradorAtual = button.dataset.colaboradorId;
            if (!colaboradorAtual) {
                return;
            }

            const hidden = document.querySelector(`[data-justificativa-input="${colaboradorAtual}"]`);
            const textoSalvo = hidden?.value || button.dataset.justificativaTexto || '';

            if (colaboradorEl) {
                colaboradorEl.textContent = button.dataset.colaboradorNome || '';
            }

            if (textoEl) {
                textoEl.value = textoSalvo;
            }

            if (arquivosEl) {
                arquivosEl.value = '';
            }

            renderAnexosExistentes(button);
            renderAnexosPendentes(colaboradorAtual);
            atualizarChars();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            textoEl?.focus();
        };

        const atualizarBotao = (colaboradorId) => {
            const button = document.querySelector(`[data-justificativa-open][data-colaborador-id="${colaboradorId}"]`);
            const hidden = document.querySelector(`[data-justificativa-input="${colaboradorId}"]`);
            if (!button || !hidden) {
                return;
            }

            const texto = (hidden.value || '').trim();
            const qtdPendentes = arquivosPorColaborador.get(colaboradorId)?.length || 0;
            const qtdExistentes = Number(button.dataset.anexosCount || 0);
            const preenchido = texto !== '' || qtdPendentes > 0 || qtdExistentes > 0;
            const label = button.querySelector('[data-justificativa-label]');

            button.classList.toggle('border-sky-300', preenchido);
            button.classList.toggle('bg-sky-50', preenchido);
            button.classList.toggle('text-sky-900', preenchido);
            button.classList.toggle('border-zinc-200', !preenchido);
            button.classList.toggle('bg-white', !preenchido);
            button.classList.toggle('text-brand-gray', !preenchido);

            if (label) {
                const totalAnexos = qtdPendentes + qtdExistentes;
                if (preenchido && totalAnexos > 0) {
                    label.textContent = `Justificativa ✓ (${totalAnexos} anexo${totalAnexos === 1 ? '' : 's'})`;
                } else if (preenchido) {
                    label.textContent = 'Justificativa ✓';
                } else {
                    label.textContent = 'Justificativa';
                }
            }
        };

        const salvar = () => {
            if (!colaboradorAtual) {
                return;
            }

            const hidden = document.querySelector(`[data-justificativa-input="${colaboradorAtual}"]`);
            if (hidden && textoEl) {
                hidden.value = textoEl.value.trim();
            }

            const pendentes = arquivosPorColaborador.get(colaboradorAtual) || [];
            const novos = Array.from(arquivosEl?.files || []);
            if (novos.length > 0) {
                arquivosPorColaborador.set(colaboradorAtual, [...pendentes, ...novos]);
            }

            if (arquivosEl) {
                arquivosEl.value = '';
            }

            atualizarBotao(colaboradorAtual);
            fechar();
        };

        document.querySelectorAll('[data-justificativa-open]').forEach((button) => {
            button.addEventListener('click', () => abrir(button));
        });

        modal.querySelector('[data-justificativa-cancelar]')?.addEventListener('click', fechar);
        modal.querySelector('[data-justificativa-salvar]')?.addEventListener('click', salvar);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                fechar();
            }
        });

        textoEl?.addEventListener('input', atualizarChars);

        arquivosEl?.addEventListener('change', () => {
            if (!colaboradorAtual || !arquivosEl.files?.length) {
                return;
            }

            const pendentes = arquivosPorColaborador.get(colaboradorAtual) || [];
            arquivosPorColaborador.set(colaboradorAtual, [...pendentes, ...Array.from(arquivosEl.files)]);
            arquivosEl.value = '';
            renderAnexosPendentes(colaboradorAtual);
            atualizarBotao(colaboradorAtual);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                fechar();
            }
        });

        window.PresencaObraJustificativas = {
            hasPendingFiles: () => arquivosPorColaborador.size > 0,
            appendToFormData: (formData) => {
                arquivosPorColaborador.forEach((files, colaboradorId) => {
                    files.forEach((file) => {
                        formData.append(`anexos[${colaboradorId}][]`, file);
                    });
                });
            },
            observacoesFromForm: (form) => {
                const observacoes = {};
                form.querySelectorAll('[data-justificativa-input]').forEach((input) => {
                    const id = input.getAttribute('data-justificativa-input');
                    if (id && input.value.trim() !== '') {
                        observacoes[id] = input.value.trim();
                    }
                });

                return observacoes;
            },
            applyObservacoes: (itens) => {
                if (!itens) {
                    return;
                }

                Object.entries(itens).forEach(([colaboradorId, row]) => {
                    const input = document.querySelector(`[data-justificativa-input="${colaboradorId}"]`);
                    if (input && row?.observacao) {
                        input.value = row.observacao;
                        atualizarBotao(colaboradorId);
                    }
                });
            },
            clearPendingFiles: () => {
                arquivosPorColaborador.clear();
                objectUrls.forEach((urls) => urls.forEach((url) => URL.revokeObjectURL(url)));
                objectUrls.clear();
            },
        };
    })();
</script>
