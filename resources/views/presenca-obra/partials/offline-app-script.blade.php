<script>
    (() => {
        const store = window.PresencaObraStore;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const salvarEndpoint = @json(route('presenca-obra.salvar'));
        const identificarEndpoint = @json(route('presenca-obra.identificar.store'));
        const identificarUrl = @json(route('presenca-obra.identificar'));

        const state = {
            data: new Date().toISOString().slice(0, 10),
            busca: '',
            centroCusto: '',
            marcacoes: {},
        };

        const showBanner = (message, type = 'info') => {
            const el = document.getElementById('presenca-obra-feedback');
            if (!el) {
                return;
            }

            const styles = {
                success: 'border-emerald-200 bg-emerald-50 text-emerald-950',
                warning: 'border-amber-200 bg-amber-50 text-amber-950',
                error: 'border-red-200 bg-red-50 text-red-950',
                info: 'border-sky-200 bg-sky-50 text-sky-950',
            };

            el.className = `ponto-card ${styles[type] || styles.info}`;
            el.hidden = false;
            el.querySelector('[data-feedback-text]').textContent = message;
        };

        const updateUi = () => {
            const online = navigator.onLine;
            const count = store.pendingCount();

            document.querySelectorAll('[data-presenca-online-state]').forEach((el) => {
                el.textContent = online ? 'Com internet' : 'Sem internet';
                el.className = online
                    ? 'rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700'
                    : 'rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-800';
            });

            document.querySelectorAll('[data-presenca-pending-count]').forEach((el) => {
                el.textContent = `${count} pendente${count === 1 ? '' : 's'}`;
            });

            const alert = document.getElementById('presenca-obra-pending-alert');
            if (alert) {
                alert.hidden = count === 0;
                const text = alert.querySelector('[data-pending-alert-text]');
                if (text) {
                    text.textContent = count === 1
                        ? 'Você tem 1 confirmação de presença aguardando envio ao servidor.'
                        : `Você tem ${count} confirmações de presença aguardando envio ao servidor.`;
                }
            }
        };

        const ensureServerSession = async () => {
            const session = store.readSession();
            if (!session) {
                return false;
            }

            const response = await fetch(identificarEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    matricula: session.matricula,
                    cpf: session.cpf,
                }),
            });

            if (!response.ok) {
                return false;
            }

            const json = await response.json();

            return Boolean(json.ok);
        };

        const sync = async ({ manual = false } = {}) => {
            if (!navigator.onLine) {
                updateUi();
                if (manual) {
                    showBanner('Sem internet. Conecte-se para enviar os registros pendentes.', 'warning');
                }

                return;
            }

            const queue = store.readQueue();
            if (queue.length === 0) {
                updateUi();

                return;
            }

            const syncBtn = document.querySelector('[data-presenca-sync]');
            if (syncBtn) {
                syncBtn.disabled = true;
                syncBtn.textContent = 'Enviando...';
            }

            const sessionOk = await ensureServerSession();
            if (!sessionOk) {
                if (syncBtn) {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = '<i data-lucide="upload-cloud" class="h-4 w-4"></i> Enviar registros pendentes';
                    window.lucide?.createIcons?.();
                }
                showBanner('Não foi possível autenticar para envio. Entre novamente com matrícula e CPF quando estiver online.', 'error');

                return;
            }

            const remaining = [];
            let synced = 0;

            for (const item of queue) {
                try {
                    const response = await fetch(salvarEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            data: item.data,
                            itens: item.itens,
                            busca: item.busca || '',
                            centro_custo: item.centro_custo || '',
                        }),
                    });

                    if (response.ok) {
                        synced++;
                    } else {
                        remaining.push(item);
                    }
                } catch {
                    remaining.push(item);
                }
            }

            store.writeQueue(remaining);

            if (syncBtn) {
                syncBtn.disabled = false;
                syncBtn.innerHTML = '<i data-lucide="upload-cloud" class="h-4 w-4"></i> Enviar registros pendentes';
                window.lucide?.createIcons?.();
            }

            if (synced > 0) {
                showBanner(
                    synced === 1 ? '1 registro enviado com sucesso.' : `${synced} registros enviados com sucesso.`,
                    'success'
                );
            } else if (manual) {
                showBanner('Não foi possível enviar agora. Tente novamente quando a conexão estiver estável.', 'error');
            }

            updateUi();
        };

        const getFilteredColaboradores = () => {
            const cache = store.readCache();
            if (!cache?.colaboradores) {
                return [];
            }

            const busca = state.busca.trim().toLowerCase();
            const centro = state.centroCusto.trim();

            return cache.colaboradores.filter((colab) => {
                if (centro && colab.centro_custo !== centro) {
                    return false;
                }

                if (!busca) {
                    return true;
                }

                return String(colab.nome || '').toLowerCase().includes(busca)
                    || String(colab.matricula || '').toLowerCase().includes(busca);
            });
        };

        const applyQueueToState = () => {
            const queueItem = store.readQueue().find((item) => item.data === state.data);
            state.marcacoes = { ...(queueItem?.itens || {}) };
        };

        const renderLista = () => {
            const lista = document.getElementById('presenca-offline-lista');
            const totais = document.getElementById('presenca-offline-totais');
            const colaboradores = getFilteredColaboradores();

            if (!lista || !totais) {
                return;
            }

            const presentes = Object.values(state.marcacoes).filter((row) => row.status === 'presente').length;
            const ausentes = Object.values(state.marcacoes).filter((row) => row.status === 'ausente').length;

            totais.innerHTML = `
                <div class="rounded-xl border border-zinc-200 bg-white px-2 py-3">
                    <p class="font-bold text-brand-black">${colaboradores.length}</p>
                    <p class="text-brand-gray">Na lista</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-2 py-3">
                    <p class="font-bold text-emerald-800">${presentes}</p>
                    <p class="text-emerald-700">Presentes</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-2 py-3">
                    <p class="font-bold text-amber-900">${ausentes}</p>
                    <p class="text-amber-800">Ausentes</p>
                </div>
            `;

            if (colaboradores.length === 0) {
                lista.innerHTML = '<div class="rounded-xl border border-dashed border-zinc-300 bg-white p-6 text-center text-sm text-brand-gray">Nenhum colaborador encontrado com os filtros atuais.</div>';

                return;
            }

            lista.innerHTML = colaboradores.map((colab) => {
                const atual = state.marcacoes[colab.id]?.status || '';

                return `
                    <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-sm" data-presenca-row data-colab-id="${colab.id}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-brand-black">${colab.nome || ''}</p>
                            <p class="mt-0.5 text-[11px] text-brand-gray">
                                ${colab.matricula || 'Sem matrícula'}${colab.centro_custo ? ` · ${colab.centro_custo}` : ''}${colab.cargo ? ` · ${colab.cargo}` : ''}
                            </p>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button type="button" data-status="presente" class="rounded-lg border px-2 py-2 text-xs font-bold ${atual === 'presente' ? 'border-emerald-500 bg-emerald-50 text-emerald-900' : ''}">Presente</button>
                            <button type="button" data-status="ausente" class="rounded-lg border px-2 py-2 text-xs font-bold ${atual === 'ausente' ? 'border-amber-500 bg-amber-50 text-amber-950' : ''}">Ausente</button>
                        </div>
                    </div>
                `;
            }).join('');

            lista.querySelectorAll('[data-presenca-row]').forEach((row) => {
                row.querySelectorAll('[data-status]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const colabId = row.getAttribute('data-colab-id');
                        state.marcacoes[colabId] = { status: button.getAttribute('data-status') };
                        renderLista();
                    });
                });
            });
        };

        const salvar = () => {
            const itens = Object.fromEntries(
                Object.entries(state.marcacoes).filter(([, row]) => row?.status)
            );

            if (Object.keys(itens).length === 0) {
                showBanner('Marque ao menos um colaborador como presente ou ausente.', 'error');

                return;
            }

            store.mergeIntoQueue({
                offline_uuid: crypto.randomUUID(),
                saved_at: new Date().toISOString(),
                data: state.data,
                busca: state.busca,
                centro_custo: state.centroCusto,
                itens,
            });

            showBanner('Confirmação salva neste aparelho. Envie quando tiver internet.', 'warning');
            updateUi();

            if (navigator.onLine) {
                sync();
            }
        };

        const init = () => {
            store.registerServiceWorker();

            const session = store.readSession();
            const cache = store.readCache();

            if (!session || !cache?.colaboradores?.length) {
                document.getElementById('presenca-offline-empty').hidden = false;

                return;
            }

            document.getElementById('presenca-offline-app').hidden = false;
            document.getElementById('presenca-offline-nome').textContent = session.confirmador?.nome || 'Supervisor';
            document.getElementById('presenca-offline-meta').textContent = `${session.confirmador?.matricula || 'Sem matrícula'} · usando dados salvos neste aparelho`;

            const dataInput = document.getElementById('data');
            const centroSelect = document.getElementById('centro_custo');
            const buscaInput = document.getElementById('busca');

            state.data = cache.data || new Date().toISOString().slice(0, 10);
            dataInput.value = state.data;

            centroSelect.innerHTML = '<option value="">Todos</option>' + (cache.centros_custo || [])
                .map((cc) => `<option value="${cc}">${cc}</option>`)
                .join('');

            applyQueueToState();
            renderLista();

            dataInput.addEventListener('change', () => {
                state.data = dataInput.value;
                applyQueueToState();
                renderLista();
            });

            centroSelect.addEventListener('change', () => {
                state.centroCusto = centroSelect.value;
                renderLista();
            });

            buscaInput.addEventListener('input', () => {
                state.busca = buscaInput.value;
                renderLista();
            });

            document.querySelectorAll('[data-marcar-todos]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const status = btn.getAttribute('data-marcar-todos');
                    getFilteredColaboradores().forEach((colab) => {
                        state.marcacoes[colab.id] = { status };
                    });
                    renderLista();
                });
            });

            document.getElementById('presenca-offline-salvar')?.addEventListener('click', salvar);
            document.getElementById('presenca-offline-sair')?.addEventListener('click', () => {
                window.location.href = identificarUrl;
            });

            document.querySelector('[data-presenca-sync]')?.addEventListener('click', () => sync({ manual: true }));

            updateUi();
            window.addEventListener('online', () => sync());
            window.addEventListener('offline', updateUi);

            if (navigator.onLine && store.pendingCount() > 0) {
                sync();
            }
        };

        window.PresencaObraOffline = { sync, pendingCount: store.pendingCount };
        init();
    })();
</script>
