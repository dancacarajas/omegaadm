@once('modal-email-matriz')
    @push('modals')
        <div id="modal-email-matriz" class="extrato-modal" role="dialog" aria-modal="true" aria-labelledby="modal-email-matriz-titulo" aria-hidden="true">
            <div class="extrato-modal__panel" style="max-width:32rem;">
                <div class="shrink-0 border-b border-brand-burgundy/15 bg-gradient-to-br from-brand-burgundy-soft via-white to-white px-6 py-5">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-lg shadow-brand-burgundy/25">
                            <i data-lucide="send" class="h-6 w-6"></i>
                        </span>
                        <div class="min-w-0 flex-1 pt-0.5">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-burgundy">Adesão à Matriz</p>
                            <h2 id="modal-email-matriz-titulo" class="mt-1 text-lg font-bold leading-snug text-brand-black">
                                Enviar solicitação por e-mail
                            </h2>
                        </div>
                        <button type="button" data-fechar-modal-email-matriz class="shrink-0 rounded-xl border border-zinc-200/80 bg-white p-2 text-brand-gray shadow-sm transition hover:border-brand-burgundy/30 hover:text-brand-burgundy" aria-label="Fechar">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div class="extrato-modal__body px-6 py-5">
                    <p id="modal-email-matriz-texto" class="text-sm leading-relaxed text-brand-gray">
                        O e-mail será enviado à Matriz com o formulário de adesão assinado em anexo.
                    </p>
                    <ul class="mt-4 space-y-2.5 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-4 py-3.5 text-sm text-brand-black">
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="paperclip" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                            <span>Formulário assinado anexado ao e-mail</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="users" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                            <span>Destinatários definidos em <strong>Configurações → E-mail</strong></span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i data-lucide="file-text" class="mt-0.5 h-4 w-4 shrink-0 text-brand-burgundy"></i>
                            <span>Link no e-mail para abrir o PDF no navegador</span>
                        </li>
                    </ul>
                </div>

                <form id="form-email-matriz-submit" method="POST" action="" class="shrink-0 border-t border-zinc-100 bg-zinc-50/80 px-6 py-4">
                    @csrf
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" data-fechar-modal-email-matriz class="inline-flex h-11 items-center justify-center rounded-xl border border-zinc-200 bg-white px-5 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
                            Cancelar
                        </button>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-burgundy px-5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Enviar e-mail
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endpush

    @push('scripts')
        <script>
        (function () {
            const modal = document.getElementById('modal-email-matriz');
            const form = document.getElementById('form-email-matriz-submit');
            const texto = document.getElementById('modal-email-matriz-texto');
            if (!modal || !form) return;

            const abrir = (btn) => {
                const action = btn.getAttribute('data-action');
                if (!action) return;

                form.setAttribute('action', action);

                const colab = btn.getAttribute('data-colaborador') || '';
                const beneficio = btn.getAttribute('data-beneficio') || '';
                if (texto) {
                    if (colab && beneficio) {
                        texto.textContent = '';
                        texto.append(
                            document.createTextNode('Confirma o envio da solicitação de adesão ao benefício '),
                        );
                        const strongB = document.createElement('strong');
                        strongB.textContent = beneficio;
                        texto.append(strongB, document.createTextNode(' para a Matriz, referente a '));
                        const strongC = document.createElement('strong');
                        strongC.textContent = colab;
                        texto.append(strongC, document.createTextNode('? O formulário assinado será anexado.'));
                    } else {
                        texto.textContent = 'Confirma o envio do e-mail de solicitação à Matriz com o formulário de adesão assinado em anexo?';
                    }
                }

                modal.classList.add('extrato-modal--open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('extrato-modal-open');
                if (window.lucide) window.lucide.createIcons();
            };

            const fechar = () => {
                modal.classList.remove('extrato-modal--open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('extrato-modal-open');
            };

            document.querySelectorAll('[data-abrir-modal-email-matriz]').forEach((btn) => {
                btn.addEventListener('click', () => abrir(btn));
            });

            document.querySelectorAll('[data-fechar-modal-email-matriz]').forEach((btn) => {
                btn.addEventListener('click', fechar);
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) fechar();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('extrato-modal--open')) {
                    fechar();
                }
            });
        })();
        </script>
    @endpush
@endonce
