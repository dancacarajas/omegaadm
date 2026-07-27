<script>
    window.PresencaObraStore = (() => {
        const QUEUE_KEY = 'omega286:presenca-obra:offline-queue';
        const CACHE_KEY = 'omega286:presenca-obra:page-cache';
        const SESSION_KEY = 'omega286:presenca-obra:device-session';

        const readJson = (key, fallback) => {
            try {
                return JSON.parse(localStorage.getItem(key) || '');
            } catch {
                return fallback;
            }
        };

        const normalizeMatricula = (value) => {
            const digits = String(value || '').replace(/\D+/g, '');
            const trimmed = digits.replace(/^0+/, '');

            return trimmed || digits;
        };

        const normalizeCpf = (value) => String(value || '').replace(/\D+/g, '');

        const readQueue = () => readJson(QUEUE_KEY, []) || [];
        const readCache = () => readJson(CACHE_KEY, null);
        const readSession = () => readJson(SESSION_KEY, null);

        const writeQueue = (items) => {
            localStorage.setItem(QUEUE_KEY, JSON.stringify(items));
        };

        const writeCache = (payload) => {
            localStorage.setItem(CACHE_KEY, JSON.stringify({
                ...payload,
                cached_at: new Date().toISOString(),
            }));
        };

        const writeSession = (payload) => {
            localStorage.setItem(SESSION_KEY, JSON.stringify({
                ...payload,
                cached_at: new Date().toISOString(),
            }));
        };

        const clearSession = () => {
            localStorage.removeItem(SESSION_KEY);
        };

        const credentialsMatch = (matricula, cpf) => {
            const session = readSession();
            if (!session) {
                return false;
            }

            return normalizeMatricula(matricula) === normalizeMatricula(session.matricula)
                && normalizeCpf(cpf) === normalizeCpf(session.cpf);
        };

        const hasOfflineAccess = () => {
            const session = readSession();
            const cache = readCache();

            return Boolean(session && cache?.colaboradores?.length);
        };

        const mergeIntoQueue = (payload) => {
            const queue = readQueue();
            const index = queue.findIndex((item) => item.data === payload.data);

            if (index === -1) {
                queue.push(payload);
                writeQueue(queue);

                return;
            }

            queue[index] = {
                ...queue[index],
                ...payload,
                itens: {
                    ...(queue[index].itens || {}),
                    ...(payload.itens || {}),
                },
                offline_uuid: queue[index].offline_uuid || payload.offline_uuid,
                saved_at: payload.saved_at,
            };

            writeQueue(queue);
        };

        const bootstrapSession = (bootstrap) => {
            if (!bootstrap?.matricula || !bootstrap?.cpf || !bootstrap?.confirmador) {
                return;
            }

            writeSession({
                matricula: bootstrap.matricula,
                cpf: bootstrap.cpf,
                confirmador: bootstrap.confirmador,
            });
        };

        const registerServiceWorker = () => {
            if (!('serviceWorker' in navigator)) {
                return;
            }

            navigator.serviceWorker.register(@json(url('presenca-obra-sw.js')), { scope: '/' })
                .catch(() => {});
        };

        return {
            QUEUE_KEY,
            CACHE_KEY,
            SESSION_KEY,
            readQueue,
            writeQueue,
            readCache,
            writeCache,
            readSession,
            writeSession,
            clearSession,
            credentialsMatch,
            hasOfflineAccess,
            mergeIntoQueue,
            bootstrapSession,
            normalizeMatricula,
            normalizeCpf,
            registerServiceWorker,
            pendingCount: () => readQueue().length,
        };
    })();
</script>
