<div
    id="medicao-justificativa-ver-modal"
    class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/45 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="medicao-justificativa-ver-titulo"
>
    <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/50 px-5 py-4">
            <h2 id="medicao-justificativa-ver-titulo" class="text-sm font-bold text-brand-black">Justificativa</h2>
            <p class="mt-1 text-xs text-brand-gray" data-justificativa-ver-colaborador></p>
        </div>

        <div class="space-y-4 overflow-y-auto px-5 py-4" style="max-height: calc(90vh - 7rem);">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Texto da justificativa</p>
                <div
                    data-justificativa-ver-texto
                    class="mt-2 min-h-[5rem] whitespace-pre-wrap rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-brand-black"
                ></div>
            </div>

            <div data-justificativa-ver-anexos-wrap hidden class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-3">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-900">Documentos anexados</p>
                <ul class="mt-2 space-y-2" data-justificativa-ver-anexos></ul>
            </div>
        </div>

        <div class="flex justify-end border-t border-zinc-200 bg-zinc-50 px-5 py-4">
            <button type="button" data-justificativa-ver-fechar class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black">
                Fechar
            </button>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('medicao-justificativa-ver-modal');
            if (!modal) {
                return;
            }

            const colaboradorEl = modal.querySelector('[data-justificativa-ver-colaborador]');
            const textoEl = modal.querySelector('[data-justificativa-ver-texto]');
            const anexosWrap = modal.querySelector('[data-justificativa-ver-anexos-wrap]');
            const anexosEl = modal.querySelector('[data-justificativa-ver-anexos]');

            const fechar = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            };

            const abrir = (button) => {
                if (colaboradorEl) {
                    colaboradorEl.textContent = button.dataset.colaboradorNome || '';
                }

                const observacao = (button.dataset.observacao || '').trim();
                if (textoEl) {
                    textoEl.textContent = observacao !== '' ? observacao : 'Nenhuma justificativa em texto registrada.';
                    textoEl.classList.toggle('text-brand-gray', observacao === '');
                    textoEl.classList.toggle('italic', observacao === '');
                }

                if (anexosEl && anexosWrap) {
                    anexosEl.innerHTML = '';
                    let index = 0;

                    while (button.getAttribute(`data-anexo-existente-${index}-nome`)) {
                        const nome = button.getAttribute(`data-anexo-existente-${index}-nome`);
                        const url = button.getAttribute(`data-anexo-existente-${index}-url`);

                        const li = document.createElement('li');
                        li.className = 'flex items-center justify-between gap-2 rounded-lg border border-white/80 bg-white px-2.5 py-2 text-xs shadow-sm';

                        const nomeEl = document.createElement('span');
                        nomeEl.className = 'min-w-0 flex-1 truncate font-medium text-brand-black';
                        nomeEl.textContent = nome;
                        nomeEl.title = nome;

                        const ver = document.createElement('a');
                        ver.href = url;
                        ver.target = '_blank';
                        ver.rel = 'noopener';
                        ver.className = 'inline-flex shrink-0 items-center gap-1 rounded-md bg-brand-burgundy px-2 py-1 text-[11px] font-bold text-white hover:bg-brand-burgundy-dark';
                        ver.innerHTML = '<i data-lucide="eye" class="h-3 w-3"></i> Visualizar';

                        li.append(nomeEl, ver);
                        anexosEl.appendChild(li);
                        index++;
                    }

                    anexosWrap.hidden = index === 0;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
                window.lucide?.createIcons?.();
            };

            document.querySelectorAll('[data-justificativa-ver-open]').forEach((button) => {
                button.addEventListener('click', () => abrir(button));
            });

            modal.querySelector('[data-justificativa-ver-fechar]')?.addEventListener('click', fechar);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    fechar();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    fechar();
                }
            });
        })();
    </script>
@endpush
