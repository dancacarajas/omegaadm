<script>
    (() => {
        const key = 'omega286:rdo:offline-queue';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const endpoint = @json(route('rdo.store'));

        const read = () => JSON.parse(localStorage.getItem(key) || '[]');
        const write = (items) => {
            localStorage.setItem(key, JSON.stringify(items));
            updateBadges();
        };

        const updateBadges = () => {
            const online = navigator.onLine;
            document.querySelectorAll('#rdo-online-state').forEach((el) => {
                el.textContent = online ? 'Online' : 'Offline';
                el.className = online
                    ? 'rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700'
                    : 'rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700';
            });
            document.querySelectorAll('#rdo-pending-count').forEach((el) => {
                const count = read().length;
                el.textContent = `${count} pendente${count === 1 ? '' : 's'}`;
            });
        };

        const fileToBase64 = (file) => new Promise((resolve) => {
            if (!file) return resolve(null);
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => resolve(null);
            reader.readAsDataURL(file);
        });

        const formToObject = async (form) => {
            const data = new FormData(form);
            const payload = { offline_uuid: crypto.randomUUID(), atividades: [], equipe: [] };

            for (const [name, value] of data.entries()) {
                if (value instanceof File) continue;

                const activity = name.match(/^atividades\[(\d+)]\[(\w+)]$/);
                const team = name.match(/^equipe\[(\d+)]\[(\w+)]$/);

                if (activity) {
                    payload.atividades[activity[1]] ??= {};
                    payload.atividades[activity[1]][activity[2]] = value;
                    continue;
                }

                if (team) {
                    payload.equipe[team[1]] ??= {};
                    payload.equipe[team[1]][team[2]] = value;
                    continue;
                }

                payload[name] = value;
            }

            payload.atividades = payload.atividades.filter(Boolean);
            payload.equipe = payload.equipe.filter(Boolean);
            const evidenceInput = Array.from(form.querySelectorAll('input[name="evidencia"]'))
                .find((input) => input.files?.[0]);

            const fileEvidence = await fileToBase64(evidenceInput?.files?.[0]);
            payload.evidencia_base64 = fileEvidence || payload.evidencia_base64 || null;

            return payload;
        };

        const sync = async () => {
            if (!navigator.onLine) {
                updateBadges();
                return;
            }

            const queue = read();
            const remaining = [];

            for (const item of queue) {
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify(item),
                    });

                    if (!response.ok) {
                        remaining.push(item);
                    }
                } catch (error) {
                    remaining.push(item);
                }
            }

            write(remaining);
        };

        window.RdoOfflineQueue = {
            saveForm: async (form) => {
                const queue = read();
                queue.push(await formToObject(form));
                write(queue);
            },
            sync,
        };

        window.addEventListener('online', sync);
        window.addEventListener('offline', updateBadges);
        updateBadges();
        sync();
    })();
</script>
