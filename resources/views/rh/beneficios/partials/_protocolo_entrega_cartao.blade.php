<form
    id="form-protocolo-entrega"
    method="POST"
    action="{{ route('rh.beneficios.protocolo-entrega.pdf', $beneficio) }}"
    target="_blank"
    class="border-b border-zinc-100 px-5 py-4 sm:px-6"
>
    @csrf
    <div class="rounded-2xl border border-zinc-200/80 bg-zinc-50/40 p-4 shadow-sm ring-1 ring-zinc-100/80">
        <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
            <div class="flex items-center gap-3 lg:col-span-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-brand-burgundy/10 text-brand-burgundy">
                    <i data-lucide="file-text" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-brand-black">Protocolo de entrega de cartão</p>
                    <p class="mt-0.5 text-[11px] leading-snug text-brand-gray">
                        Selecione colaboradores na lista · PDF no papel timbrado
                    </p>
                    <p id="protocolo-selecao-hint" class="mt-1 hidden text-[11px] font-bold text-brand-burgundy" aria-live="polite"></p>
                </div>
            </div>

            <label class="space-y-2 lg:col-span-3">
                <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <i data-lucide="user" class="h-3.5 w-3.5"></i>
                    Responsável (opcional)
                </span>
                <input
                    type="text"
                    name="entregador_nome"
                    maxlength="255"
                    placeholder="Nome de quem entrega"
                    autocomplete="name"
                    class="h-12 w-full rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10"
                >
            </label>

            <label class="space-y-2 lg:col-span-2">
                <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                    <i data-lucide="briefcase" class="h-3.5 w-3.5"></i>
                    Cargo
                </span>
                <input
                    type="text"
                    name="entregador_funcao"
                    maxlength="255"
                    placeholder="Função"
                    class="h-12 w-full rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10"
                >
            </label>

            <div class="flex flex-col gap-2 sm:flex-row lg:col-span-3 lg:justify-end">
                <button
                    type="button"
                    id="btn-protocolo-limpar"
                    class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl border border-zinc-200/80 bg-white px-4 text-sm font-semibold text-brand-gray shadow-sm transition hover:border-zinc-300 lg:flex-initial"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                    Limpar
                </button>
                <button
                    type="submit"
                    id="btn-protocolo-gerar"
                    formtarget="_blank"
                    disabled
                    class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-5 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:opacity-50 lg:flex-initial"
                >
                    <i data-lucide="download" class="h-4 w-4"></i>
                    <span id="btn-protocolo-gerar-label" class="truncate">Gerar PDF</span>
                </button>
            </div>
        </div>
    </div>
</form>
