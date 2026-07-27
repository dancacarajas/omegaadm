<script>
    (() => {
        const store = window.PresencaObraStore;
        const modoOfflineUrl = @json(route('presenca-obra.modo-offline'));
        const identificarEndpoint = @json(route('presenca-obra.identificar.store'));

        const updateAlert = () => {
            const count = store.pendingCount();

            document.querySelectorAll('[data-presenca-pending-count]').forEach((el) => {
                el.textContent = `${count} pendente${count === 1 ? '' : 's'}`;
            });

            const pending = document.getElementById('presenca-obra-login-pending');
            if (pending) {
                pending.hidden = count === 0;
                const text = pending.querySelector('[data-login-pending-text]');
                if (text) {
                    text.textContent = count === 1
                        ? 'Há 1 confirmação salva neste aparelho. Entre para enviar ao servidor.'
                        : `Há ${count} confirmações salvas neste aparelho. Entre para enviar ao servidor.`;
                }
            }

            const offlineReady = document.getElementById('presenca-obra-offline-ready');
            if (offlineReady) {
                offlineReady.hidden = !store.hasOfflineAccess();
            }
        };

        const showOfflineError = (message) => {
            const alert = document.getElementById('presenca-obra-login-offline');
            if (!alert) {
                return;
            }

            alert.hidden = false;
            const text = alert.querySelector('[data-login-offline-text]');
            if (text) {
                text.textContent = message;
            }
        };

        const tryOfflineLogin = (matricula, cpf) => {
            if (!store.hasOfflineAccess()) {
                showOfflineError('O primeiro acesso neste aparelho precisa de internet. Depois disso, matrícula e CPF funcionam sem internet.');

                return false;
            }

            if (!store.credentialsMatch(matricula, cpf)) {
                showOfflineError('Matrícula ou CPF não conferem com o último acesso salvo neste aparelho.');

                return false;
            }

            window.location.href = modoOfflineUrl;

            return true;
        };

        const form = document.querySelector('form[action="{{ route('presenca-obra.identificar.store') }}"]');

        form?.addEventListener('submit', async (event) => {
            const data = new FormData(form);
            const matricula = data.get('matricula');
            const cpf = data.get('cpf');

            if (!navigator.onLine) {
                event.preventDefault();
                tryOfflineLogin(matricula, cpf);

                return;
            }

            event.preventDefault();

            try {
                const response = await fetch(identificarEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ matricula, cpf }),
                });

                const json = await response.json();

                if (response.ok && json.ok) {
                    store.bootstrapSession({
                        matricula,
                        cpf,
                        confirmador: json.confirmador,
                    });

                    if (json.cache) {
                        store.writeCache(json.cache);
                    }

                    window.location.href = json.redirect || @json(route('presenca-obra.index'));
                    return;
                }

                showOfflineError(json.message || 'Não foi possível entrar. Verifique matrícula e CPF.');
            } catch {
                if (!tryOfflineLogin(matricula, cpf)) {
                    showOfflineError('Sem internet e não foi possível validar o acesso neste aparelho.');
                }
            }
        });

        document.getElementById('presenca-offline-continuar')?.addEventListener('click', () => {
            window.location.href = modoOfflineUrl;
        });

        store.registerServiceWorker();
        updateAlert();
        window.addEventListener('storage', updateAlert);
    })();
</script>
