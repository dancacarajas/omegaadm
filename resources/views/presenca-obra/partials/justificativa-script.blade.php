<script>
    (() => {
        const modal = document.getElementById('presenca-justificativa-modal');
        if (!modal) {
            return;
        }

        const endpoint = @json(route('presenca-obra.justificativa.store'));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const salvarBtn = modal.querySelector('[data-justificativa-salvar]');

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
            const totalAnexos = qtdPendentes + qtdExistentes;
            const temAnexo = totalAnexos > 0;
            const preenchido = texto !== '' || temAnexo;
            const label = button.querySelector('[data-justificativa-label]');

            button.classList.toggle('border-amber-300', temAnexo);
            button.classList.toggle('bg-amber-50', temAnexo);
            button.classList.toggle('text-amber-900', temAnexo);
            button.classList.toggle('border-sky-300', preenchido && !temAnexo);
            button.classList.toggle('bg-sky-50', preenchido && !temAnexo);
            button.classList.toggle('text-sky-900', preenchido && !temAnexo);
            button.classList.toggle('border-zinc-200', !preenchido);
            button.classList.toggle('bg-white', !preenchido);
            button.classList.toggle('text-brand-gray', !preenchido);

            if (label) {
                if (preenchido && temAnexo) {
                    label.textContent = `Justificativa ✓ (${totalAnexos} anexo${totalAnexos === 1 ? '' : 's'})`;
                } else if (preenchido) {
                    label.textContent = 'Justificativa ✓';
                } else {
                    label.textContent = 'Justificativa';
                }
            }
        };

        const atualizarAnexosNoBotao = (button, anexos) => {
            [...button.attributes].forEach((attr) => {
                if (attr.name.startsWith('data-anexo-existente-')) {
                    button.removeAttribute(attr.name);
                }
            });

            anexos.forEach((anexo, index) => {
                button.setAttribute(`data-anexo-existente-${index}-nome`, anexo.nome);
                button.setAttribute(`data-anexo-existente-${index}-url`, anexo.url);
            });

            button.dataset.anexosCount = String(anexos.length);
        };

        const statusSelecionado = (colaboradorId) => {
            return document.querySelector(`input[name="itens[${colaboradorId}][status]"]:checked`)?.value || '';
        };

        const salvar = async () => {
            if (!colaboradorAtual) {
                return;
            }

            const hidden = document.querySelector(`[data-justificativa-input="${colaboradorAtual}"]`);
            const button = document.querySelector(`[data-justificativa-open][data-colaborador-id="${colaboradorAtual}"]`);
            const observacao = textoEl ? textoEl.value.trim() : '';
            const pendentes = arquivosPorColaborador.get(colaboradorAtual) || [];
            const novos = Array.from(arquivosEl?.files || []);
            const arquivos = [...pendentes, ...novos];
            const status = statusSelecionado(colaboradorAtual);
            const data = document.querySelector('#form-presenca-obra input[name="data"]')?.value || '';

            if (!status) {
                window.alert('Marque o colaborador como presente ou ausente antes de salvar a justificativa.');
                return;
            }

            if (!navigator.onLine) {
                if (hidden && textoEl) {
                    hidden.value = observacao;
                }
                if (arquivos.length > 0) {
                    arquivosPorColaborador.set(colaboradorAtual, arquivos);
                }
                if (arquivosEl) {
                    arquivosEl.value = '';
                }
                atualizarBotao(colaboradorAtual);
                window.alert('Sem internet. O texto ficou salvo neste aparelho, mas os anexos só são gravados com conexão.');
                fechar();
                return;
            }

            const formData = new FormData();
            formData.append('data', data);
            formData.append('colaborador_id', colaboradorAtual);
            formData.append('observacao', observacao);
            formData.append('status', status);
            arquivos.forEach((file) => {
                formData.append('anexos[]', file);
            });

            const labelOriginal = salvarBtn?.textContent || 'Salvar justificativa';
            if (salvarBtn) {
                salvarBtn.disabled = true;
                salvarBtn.textContent = 'Salvando...';
            }

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const json = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const mensagem = json.message
                        || Object.values(json.errors || {}).flat().join(' ')
                        || 'Não foi possível salvar a justificativa.';
                    window.alert(mensagem);
                    return;
                }

                if (hidden) {
                    hidden.value = json.observacao || observacao;
                }

                if (button) {
                    button.dataset.justificativaTexto = json.observacao || observacao;
                    atualizarAnexosNoBotao(button, json.anexos || []);
                }

                arquivosPorColaborador.delete(colaboradorAtual);
                if (arquivosEl) {
                    arquivosEl.value = '';
                }

                atualizarBotao(colaboradorAtual);
                fechar();
            } catch {
                window.alert('Não foi possível salvar a justificativa. Verifique sua conexão e tente novamente.');
            } finally {
                if (salvarBtn) {
                    salvarBtn.disabled = false;
                    salvarBtn.textContent = labelOriginal;
                }
            }
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
