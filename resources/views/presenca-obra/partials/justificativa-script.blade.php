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
        const novosEl = modal.querySelector('[data-justificativa-arquivos-novos]');
        const existentesWrap = modal.querySelector('[data-justificativa-existentes-wrap]');
        const existentesEl = modal.querySelector('[data-justificativa-existentes]');

        const arquivosPorColaborador = new Map();
        let colaboradorAtual = null;

        const atualizarChars = () => {
            if (charsEl && textoEl) {
                charsEl.textContent = String(textoEl.value.length);
            }
        };

        const listarArquivosNovos = () => {
            if (!novosEl || !arquivosEl) {
                return;
            }

            novosEl.innerHTML = '';
            Array.from(arquivosEl.files || []).forEach((file) => {
                const li = document.createElement('li');
                li.textContent = `${file.name} (${Math.max(1, Math.round(file.size / 1024))} KB)`;
                novosEl.appendChild(li);
            });
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

            if (existentesEl && existentesWrap) {
                existentesEl.innerHTML = '';
                let index = 0;
                while (button.getAttribute(`data-anexo-existente-${index}-nome`)) {
                    const nome = button.getAttribute(`data-anexo-existente-${index}-nome`);
                    const url = button.getAttribute(`data-anexo-existente-${index}-url`);
                    const li = document.createElement('li');
                    li.innerHTML = `<a href="${url}" class="font-semibold text-brand-burgundy hover:underline" target="_blank" rel="noopener">${nome}</a>`;
                    existentesEl.appendChild(li);
                    index++;
                }
                existentesWrap.hidden = index === 0;
            }

            atualizarChars();
            listarArquivosNovos();

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
            const qtdArquivos = arquivosPorColaborador.get(colaboradorId)?.length || 0;
            const qtdExistentes = Number(button.dataset.anexosCount || 0);
            const preenchido = texto !== '' || qtdArquivos > 0 || qtdExistentes > 0;
            const label = button.querySelector('[data-justificativa-label]');

            button.classList.toggle('border-sky-300', preenchido);
            button.classList.toggle('bg-sky-50', preenchido);
            button.classList.toggle('text-sky-900', preenchido);
            button.classList.toggle('border-zinc-200', !preenchido);
            button.classList.toggle('bg-white', !preenchido);
            button.classList.toggle('text-brand-gray', !preenchido);

            if (label) {
                label.textContent = preenchido ? 'Justificativa ✓' : 'Justificativa';
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

            if (arquivosEl?.files?.length) {
                arquivosPorColaborador.set(colaboradorAtual, Array.from(arquivosEl.files));
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
        arquivosEl?.addEventListener('change', listarArquivosNovos);

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
        };
    })();
</script>
