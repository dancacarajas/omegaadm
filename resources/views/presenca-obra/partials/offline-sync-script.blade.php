<script>
    (() => {
        const store = window.PresencaObraStore;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const endpoint = @json(route('presenca-obra.salvar'));
        const identificarEndpoint = @json(route('presenca-obra.identificar.store'));

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

            applyPendingMarksToForm();
        };

        const applyPendingMarksToForm = () => {
            const form = document.getElementById('form-presenca-obra');
            if (!form) {
                return;
            }

            const data = form.querySelector('input[name="data"]')?.value;
            if (!data) {
                return;
            }

            const queueItem = store.readQueue().find((item) => item.data === data);
            if (!queueItem?.itens) {
                return;
            }

            Object.entries(queueItem.itens).forEach(([colaboradorId, row]) => {
                const radio = form.querySelector(`input[name="itens[${colaboradorId}][status]"][value="${row.status}"]`);
                if (radio) {
                    radio.checked = true;
                }
            });
        };

        const formToPayload = (form) => {
            const data = new FormData(form);
            const itens = {};

            for (const [name, value] of data.entries()) {
                const match = name.match(/^itens\[(\d+)]\[status]$/);
                if (match && value) {
                    itens[match[1]] = { status: value };
                }
            }

            return {
                offline_uuid: crypto.randomUUID(),
                saved_at: new Date().toISOString(),
                data: data.get('data') || '',
                busca: data.get('busca') || '',
                centro_custo: data.get('centro_custo') || '',
                itens,
            };
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
                showBanner('Não foi possível autenticar para envio. Entre novamente com matrícula e CPF.', 'error');

                return;
            }

            const remaining = [];
            let synced = 0;

            for (const item of queue) {
                try {
                    const response = await fetch(endpoint, {
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

                if (remaining.length === 0) {
                    setTimeout(() => window.location.reload(), 900);
                }
            } else if (manual && remaining.length > 0) {
                showBanner('Não foi possível enviar agora. Tente novamente quando a conexão estiver estável.', 'error');
            }

            updateUi();
        };

        const saveFormOffline = (form) => {
            const payload = formToPayload(form);
            const totalItens = Object.keys(payload.itens).length;

            if (totalItens === 0) {
                showBanner('Marque ao menos um colaborador como presente ou ausente.', 'error');

                return false;
            }

            store.mergeIntoQueue(payload);
            showBanner(
                totalItens === 1
                    ? 'Confirmação salva no aparelho. Envie quando tiver internet.'
                    : `Confirmação de ${totalItens} colaborador(es) salva no aparelho. Envie quando tiver internet.`,
                'warning'
            );

            return true;
        };

        const bindForm = () => {
            const form = document.getElementById('form-presenca-obra');
            if (!form) {
                return;
            }

            form.addEventListener('submit', async (event) => {
                const payload = formToPayload(form);
                if (Object.keys(payload.itens).length === 0) {
                    return;
                }

                if (!navigator.onLine) {
                    event.preventDefault();
                    saveFormOffline(form);

                    return;
                }

                event.preventDefault();

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            data: payload.data,
                            itens: payload.itens,
                            busca: payload.busca,
                            centro_custo: payload.centro_custo,
                        }),
                    });

                    if (response.ok) {
                        const json = await response.json();
                        showBanner(json.message || 'Presença confirmada com sucesso.', 'success');
                        setTimeout(() => window.location.reload(), 900);

                        return;
                    }
                } catch {
                    // cai para fila offline
                }

                saveFormOffline(form);
            });
        };

        const bindFilters = () => {
            document.querySelectorAll('form[action="{{ route('presenca-obra.index') }}"]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (!navigator.onLine) {
                        event.preventDefault();
                        showBanner('Sem internet para atualizar a lista. Você ainda pode confirmar presença com os dados já salvos neste aparelho.', 'warning');
                    }
                });
            });
        };

        const bootstrap = @json($offlineBootstrap ?? null);
        if (bootstrap) {
            store.bootstrapSession(bootstrap);
            if (bootstrap.cache) {
                store.writeCache(bootstrap.cache);
            }
        } else {
            const pageData = @json($pageCachePayload ?? []);
            if (pageData && Object.keys(pageData).length > 0) {
                store.writeCache(pageData);
            }
        }

        window.PresencaObraOffline = {
            sync,
            pendingCount: store.pendingCount,
            saveFormOffline,
        };

        document.querySelector('[data-presenca-sync]')?.addEventListener('click', () => {
            sync({ manual: true });
        });

        bindForm();
        bindFilters();
        store.registerServiceWorker();
        updateUi();
        applyPendingMarksToForm();

        window.addEventListener('online', () => sync());
        window.addEventListener('offline', updateUi);

        if (navigator.onLine && store.pendingCount() > 0) {
            sync();
        }
    })();
</script>
