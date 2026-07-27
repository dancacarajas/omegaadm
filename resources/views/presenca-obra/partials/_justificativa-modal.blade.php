<div
    id="presenca-justificativa-modal"
    class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/45 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="presenca-justificativa-titulo"
>
    <div class="max-h-[90vh] w-full max-w-lg overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/50 px-5 py-4">
            <h2 id="presenca-justificativa-titulo" class="text-sm font-bold text-brand-black">Justificativa</h2>
            <p class="mt-1 text-xs text-brand-gray" data-justificativa-colaborador></p>
        </div>

        <div class="space-y-4 overflow-y-auto px-5 py-4" style="max-height: calc(90vh - 9rem);">
            <div>
                <label for="presenca-justificativa-texto" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Texto da justificativa</label>
                <textarea
                    id="presenca-justificativa-texto"
                    rows="4"
                    maxlength="500"
                    class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm text-brand-black"
                    placeholder="Descreva o motivo, se necessário."
                ></textarea>
                <p class="mt-1 text-right text-[11px] text-brand-gray"><span data-justificativa-chars>0</span>/500</p>
            </div>

            <div>
                <label for="presenca-justificativa-arquivos" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Anexar documentos</label>
                <input
                    type="file"
                    id="presenca-justificativa-arquivos"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*,application/pdf"
                    class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy-soft file:px-3 file:py-2 file:text-xs file:font-bold file:text-brand-burgundy"
                >
                <p class="mt-1 text-[11px] leading-relaxed text-brand-gray">PDF, imagens ou Word. Máximo 10 MB por arquivo.</p>
                <ul class="mt-2 space-y-1 text-xs text-brand-gray" data-justificativa-arquivos-novos></ul>
            </div>

            <div data-justificativa-existentes-wrap hidden>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Documentos já enviados</p>
                <ul class="mt-2 space-y-1" data-justificativa-existentes></ul>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 bg-zinc-50 px-5 py-4 sm:flex-row sm:justify-end">
            <button type="button" data-justificativa-cancelar class="inline-flex h-10 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black">
                Cancelar
            </button>
            <button type="button" data-justificativa-salvar class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white">
                Salvar justificativa
            </button>
        </div>
    </div>
</div>
