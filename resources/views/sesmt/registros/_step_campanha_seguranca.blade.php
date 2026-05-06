@php
    $fill = $fill ?? [];
    $slug = 'campanha_seguranca';
    $itensFill = data_get($fill, "$slug.itens", data_get($fill, "$slug.campanhas", []));
    if (! is_array($itensFill)) {
        $itensFill = [];
    }
    $itensOld = old("etapas.$slug.itens", $itensFill);
    if (! is_array($itensOld)) {
        $itensOld = [];
    }
    $numItens = max(1, count($itensOld));
@endphp

<div class="space-y-4" data-campanha-root>
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-brand-gray-soft/50 px-4 py-3">
        <p class="text-sm font-medium text-brand-gray">Inclua uma ou mais campanhas / reuniões. Cada bloco é independente (título, local, evidências).</p>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-campanha-add class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Adicionar campanha
            </button>
        </div>
    </div>

    <div class="space-y-5" data-campanha-list>
        @for ($idx = 0; $idx < $numItens; $idx++)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm" data-campanha-item>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 pb-3">
                    <p class="text-sm font-bold text-brand-black">Campanha <span data-campanha-num>{{ $idx + 1 }}</span></p>
                    <button type="button" data-campanha-remove class="inline-flex h-9 items-center gap-1 rounded-lg border border-zinc-200 px-3 text-xs font-semibold text-brand-gray transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 {{ $numItens <= 1 ? 'hidden' : '' }}">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        Remover
                    </button>
                </div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Dados da campanha / reunião participativa</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <label class="lg:col-span-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Título</span>
                        <input type="text" name="etapas[{{ $slug }}][itens][{{ $idx }}][titulo]" value="{{ old("etapas.$slug.itens.$idx.titulo", data_get($fill, "$slug.itens.$idx.titulo", data_get($fill, "$slug.campanhas.$idx.titulo"))) }}" placeholder="Ex.: GP - Reunião participativa gerencial" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <label>
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data</span>
                        <input type="date" name="etapas[{{ $slug }}][itens][{{ $idx }}][data_reuniao]" value="{{ old("etapas.$slug.itens.$idx.data_reuniao", data_get($fill, "$slug.itens.$idx.data_reuniao", data_get($fill, "$slug.campanhas.$idx.data_reuniao"))) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <label>
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Local</span>
                        <input type="text" name="etapas[{{ $slug }}][itens][{{ $idx }}][local]" value="{{ old("etapas.$slug.itens.$idx.local", data_get($fill, "$slug.itens.$idx.local", data_get($fill, "$slug.campanhas.$idx.local"))) }}" placeholder="Ex.: Espaço Safe Hub" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <label>
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável</span>
                        <input type="text" name="etapas[{{ $slug }}][itens][{{ $idx }}][responsavel_campanha]" value="{{ old("etapas.$slug.itens.$idx.responsavel_campanha", data_get($fill, "$slug.itens.$idx.responsavel_campanha", data_get($fill, "$slug.campanhas.$idx.responsavel_campanha"))) }}" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <label>
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Gerência</span>
                        <input type="text" name="etapas[{{ $slug }}][itens][{{ $idx }}][gerencia]" value="{{ old("etapas.$slug.itens.$idx.gerencia", data_get($fill, "$slug.itens.$idx.gerencia", data_get($fill, "$slug.campanhas.$idx.gerencia"))) }}" placeholder="Ex.: Manutenção Usina Serra Sul" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                </div>
                <label class="mt-4 block">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição / relato da reunião</span>
                    <textarea name="etapas[{{ $slug }}][itens][{{ $idx }}][descricao]" rows="5" placeholder="Objetivo do encontro, temas de segurança, escuta ativa, transformação cultural…" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ old("etapas.$slug.itens.$idx.descricao", data_get($fill, "$slug.itens.$idx.descricao", data_get($fill, "$slug.campanhas.$idx.descricao"))) }}</textarea>
                </label>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ([1 => 'evidencia_1', 2 => 'evidencia_2'] as $num => $field)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Foto | Evidência {{ $num }}</p>
                            <input type="file" name="etapas[{{ $slug }}][itens][{{ $idx }}][{{ $field }}]" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="mt-3 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                            @php $campEv = data_get($fill, "$slug.itens.$idx.{$field}_path", data_get($fill, "$slug.campanhas.$idx.{$field}_path")); @endphp
                            @if ($campEv)
                                <p class="mt-2 text-xs font-medium text-brand-burgundy"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($campEv) }}" target="_blank" rel="noopener" class="underline underline-offset-2">Arquivo atual (evidência {{ $num }})</a></p>
                            @endif
                            <p class="mt-2 text-xs text-brand-gray">PNG, JPG, WebP, GIF ou PDF · máximo 5MB</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endfor
    </div>

    <template id="tpl-campanha-item">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm" data-campanha-item>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 pb-3">
                <p class="text-sm font-bold text-brand-black">Campanha <span data-campanha-num>__NUM__</span></p>
                <button type="button" data-campanha-remove class="inline-flex h-9 items-center gap-1 rounded-lg border border-zinc-200 px-3 text-xs font-semibold text-brand-gray transition hover:border-red-200 hover:bg-red-50 hover:text-red-700">
                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    Remover
                </button>
            </div>
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Dados da campanha / reunião participativa</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <label class="lg:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Título</span>
                    <input type="text" name="etapas[campanha_seguranca][itens][__IDX__][titulo]" placeholder="Ex.: GP - Reunião participativa gerencial" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data</span>
                    <input type="date" name="etapas[campanha_seguranca][itens][__IDX__][data_reuniao]" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Local</span>
                    <input type="text" name="etapas[campanha_seguranca][itens][__IDX__][local]" placeholder="Ex.: Espaço Safe Hub" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsável</span>
                    <input type="text" name="etapas[campanha_seguranca][itens][__IDX__][responsavel_campanha]" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label>
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Gerência</span>
                    <input type="text" name="etapas[campanha_seguranca][itens][__IDX__][gerencia]" placeholder="Ex.: Manutenção Usina Serra Sul" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
            </div>
            <label class="mt-4 block">
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Descrição / relato da reunião</span>
                <textarea name="etapas[campanha_seguranca][itens][__IDX__][descricao]" rows="5" placeholder="Objetivo do encontro, temas de segurança…" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"></textarea>
            </label>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Foto | Evidência 1</p>
                    <input type="file" name="etapas[campanha_seguranca][itens][__IDX__][evidencia_1]" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="mt-3 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                    <p class="mt-2 text-xs text-brand-gray">PNG, JPG, WebP, GIF ou PDF · máximo 5MB</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Foto | Evidência 2</p>
                    <input type="file" name="etapas[campanha_seguranca][itens][__IDX__][evidencia_2]" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="mt-3 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white">
                    <p class="mt-2 text-xs text-brand-gray">PNG, JPG, WebP, GIF ou PDF · máximo 5MB</p>
                </div>
            </div>
        </div>
    </template>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const root = document.querySelector('[data-campanha-root]');
                if (!root) return;
                const list = root.querySelector('[data-campanha-list]');
                const tpl = document.getElementById('tpl-campanha-item');
                const addBtn = root.querySelector('[data-campanha-add]');
                const maxItems = 15;
                const minItems = 1;

                const items = () => list.querySelectorAll('[data-campanha-item]');

                const reindex = () => {
                    items().forEach((wrap, i) => {
                        wrap.querySelectorAll('input[name], textarea[name]').forEach((el) => {
                            el.name = el.name.replace(/\[itens\]\[\d+\]/, `[itens][${i}]`);
                        });
                        const num = wrap.querySelector('[data-campanha-num]');
                        if (num) num.textContent = String(i + 1);
                        const rm = wrap.querySelector('[data-campanha-remove]');
                        if (rm) rm.classList.toggle('hidden', items().length <= minItems);
                    });
                    if (addBtn) {
                        addBtn.disabled = items().length >= maxItems;
                        addBtn.classList.toggle('opacity-50', addBtn.disabled);
                    }
                };

                const bindRemove = (wrap) => {
                    wrap.querySelector('[data-campanha-remove]')?.addEventListener('click', () => {
                        if (items().length <= minItems) return;
                        wrap.remove();
                        reindex();
                        if (window.lucide) window.lucide.createIcons();
                    });
                };

                items().forEach(bindRemove);

                addBtn?.addEventListener('click', () => {
                    if (!tpl || items().length >= maxItems) return;
                    const idx = items().length;
                    const html = tpl.innerHTML.replaceAll('__IDX__', String(idx)).replace('__NUM__', String(idx + 1));
                    list.insertAdjacentHTML('beforeend', html);
                    const added = list.lastElementChild;
                    if (added) bindRemove(added);
                    reindex();
                    if (window.lucide) window.lucide.createIcons();
                });

                reindex();
            })();
        </script>
    @endpush
@endonce
