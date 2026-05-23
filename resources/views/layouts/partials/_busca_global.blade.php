<div class="relative flex min-w-0" data-busca-global>
    <form method="GET" action="{{ route('busca.index') }}" class="relative" data-busca-form autocomplete="off">
        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-burgundy"></i>
        <input
            type="search"
            name="q"
            value="{{ request()->routeIs('busca.index') ? request('q') : '' }}"
            placeholder="Buscar no sistema…"
            data-busca-input
            class="h-10 w-56 rounded-xl border border-zinc-200 bg-white pl-10 pr-3 text-sm text-brand-black shadow-sm outline-none transition placeholder:text-brand-gray focus:w-72 focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 lg:w-64 lg:focus:w-80"
        >
    </form>
    <div
        data-busca-painel
        class="absolute right-0 top-[calc(100%+0.5rem)] z-50 hidden w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-xl ring-1 ring-zinc-100"
        role="listbox"
        aria-hidden="true"
    ></div>
</div>

@once
    @push('scripts')
    <script>
    (function () {
        const raiz = document.querySelector('[data-busca-global]');
        if (!raiz) return;

        const input = raiz.querySelector('[data-busca-input]');
        const painel = raiz.querySelector('[data-busca-painel]');
        const form = raiz.querySelector('[data-busca-form]');
        const urlSugestoes = @json(route('busca.sugestoes'));
        let timer = null;
        let abort = null;

        function fecharPainel() {
            painel.classList.add('hidden');
            painel.setAttribute('aria-hidden', 'true');
            painel.innerHTML = '';
        }

        function esc(texto) {
            const el = document.createElement('span');
            el.textContent = texto ?? '';
            return el.innerHTML;
        }

        function renderPainel(dados) {
            const grupos = dados.grupos || [];
            if (!grupos.length) {
                painel.innerHTML = '<p class="px-4 py-3 text-xs text-brand-gray">Nenhum resultado rápido. Pressione Enter para busca completa.</p>';
                painel.classList.remove('hidden');
                painel.setAttribute('aria-hidden', 'false');
                return;
            }

            let html = '';
            grupos.forEach((grupo) => {
                html += '<div class="border-b border-zinc-100 px-3 py-2 last:border-0"><p class="text-[10px] font-bold uppercase tracking-wider text-brand-gray">' + esc(grupo.titulo) + '</p><ul class="mt-1 space-y-0.5">';
                (grupo.itens || []).forEach((item) => {
                    const badge = item.badge ? '<span class="ml-2 shrink-0 text-[10px] font-bold uppercase text-zinc-400">' + esc(item.badge) + '</span>' : '';
                    html += '<li><a href="' + esc(item.url) + '" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm transition hover:bg-brand-burgundy-soft"><span class="min-w-0 truncate font-medium text-brand-black">' + esc(item.titulo) + '</span>' + badge + '</a></li>';
                });
                html += '</ul></div>';
            });
            html += '<a href="' + @json(route('busca.index')) + '?q=' + encodeURIComponent(dados.termo || '') + '" class="block border-t border-zinc-100 bg-zinc-50 px-4 py-2.5 text-center text-xs font-bold text-brand-burgundy hover:bg-brand-burgundy-soft">Ver todos os resultados</a>';
            painel.innerHTML = html;
            painel.classList.remove('hidden');
            painel.setAttribute('aria-hidden', 'false');
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons({ icons: lucide.icons, nameAttr: 'data-lucide', attrs: {}, root: painel });
            }
        }

        function buscarSugestoes() {
            const termo = (input.value || '').trim();
            if (termo.length < 2) {
                fecharPainel();
                return;
            }

            if (abort) abort.abort();
            abort = new AbortController();

            fetch(urlSugestoes + '?q=' + encodeURIComponent(termo), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: abort.signal,
            })
                .then((r) => r.json())
                .then(renderPainel)
                .catch(() => {});
        }

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(buscarSugestoes, 280);
        });

        input.addEventListener('focus', () => {
            if ((input.value || '').trim().length >= 2) buscarSugestoes();
        });

        document.addEventListener('click', (ev) => {
            if (!raiz.contains(ev.target)) fecharPainel();
        });

        form.addEventListener('submit', () => fecharPainel());
    })();
    </script>
    @endpush
@endonce
