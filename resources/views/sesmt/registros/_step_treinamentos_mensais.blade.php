@php
    $fill = $fill ?? [];
    $slug = 'treinamentos_mensais';
    $linhasFill = data_get($fill, "$slug.linhas", []);
    if (! is_array($linhasFill)) {
        $linhasFill = [];
    }
    $linhasOld = old("etapas.$slug.linhas", $linhasFill);
    $totalLinhas = max(1, min(13, is_array($linhasOld) ? count($linhasOld) : 0));
@endphp

<div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3">
        <p class="text-xs font-semibold text-brand-gray">Inclua até 13 linhas de treinamento no mês.</p>
        <div class="flex items-center gap-2">
            <button type="button" data-add-training-row class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                Adicionar linha
            </button>
            <button type="button" data-remove-training-row class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black transition hover:border-red-300 hover:text-red-600">
                <i data-lucide="minus" class="h-3.5 w-3.5"></i>
                Remover linha
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[980px] text-left text-xs">
            <thead>
                <tr class="bg-amber-400 text-brand-black">
                    <th colspan="3" class="border border-zinc-300 px-3 py-3 text-center text-sm font-bold uppercase">Tipo de treinamento</th>
                    <th rowspan="2" class="border border-zinc-300 px-3 py-3 text-center text-sm font-bold uppercase">Data</th>
                    <th rowspan="2" class="border border-zinc-300 px-3 py-3 text-center text-sm font-bold uppercase">Instrutor</th>
                    <th rowspan="2" class="border border-zinc-300 px-3 py-3 text-center text-sm font-bold uppercase">Título / Descrição do treinamento</th>
                </tr>
                <tr class="bg-amber-100 text-brand-black">
                    <th class="border border-zinc-300 px-2 py-2 text-center font-bold uppercase">RAC</th>
                    <th class="border border-zinc-300 px-2 py-2 text-center font-bold uppercase">NR</th>
                    <th class="border border-zinc-300 px-2 py-2 text-center font-bold uppercase">PRO / OUTROS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200">
                @for ($i = 0; $i < $totalLinhas; $i++)
                    <tr class="bg-white" data-training-row>
                        <td class="border border-zinc-300 px-2 py-2 text-center">
                            <input type="checkbox" name="etapas[{{ $slug }}][linhas][{{ $i }}][rac]" value="1" @checked(old("etapas.$slug.linhas.$i.rac", data_get($fill, "$slug.linhas.$i.rac"))) class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/30">
                        </td>
                        <td class="border border-zinc-300 px-2 py-2 text-center">
                            <input type="checkbox" name="etapas[{{ $slug }}][linhas][{{ $i }}][nr]" value="1" @checked(old("etapas.$slug.linhas.$i.nr", data_get($fill, "$slug.linhas.$i.nr"))) class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/30">
                        </td>
                        <td class="border border-zinc-300 px-2 py-2 text-center">
                            <input type="text" name="etapas[{{ $slug }}][linhas][{{ $i }}][pro_outros]" value="{{ old("etapas.$slug.linhas.$i.pro_outros", data_get($fill, "$slug.linhas.$i.pro_outros")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Código/descrição">
                        </td>
                        <td class="border border-zinc-300 px-2 py-2">
                            <input type="date" name="etapas[{{ $slug }}][linhas][{{ $i }}][data]" value="{{ old("etapas.$slug.linhas.$i.data", data_get($fill, "$slug.linhas.$i.data")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        </td>
                        <td class="border border-zinc-300 px-2 py-2">
                            <input type="text" name="etapas[{{ $slug }}][linhas][{{ $i }}][instrutor]" value="{{ old("etapas.$slug.linhas.$i.instrutor", data_get($fill, "$slug.linhas.$i.instrutor")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        </td>
                        <td class="border border-zinc-300 px-2 py-2">
                            <input type="text" name="etapas[{{ $slug }}][linhas][{{ $i }}][titulo_descricao]" value="{{ old("etapas.$slug.linhas.$i.titulo_descricao", data_get($fill, "$slug.linhas.$i.titulo_descricao")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <template data-training-row-template>
        <tr class="bg-white" data-training-row>
            <td class="border border-zinc-300 px-2 py-2 text-center">
                <input type="checkbox" name="etapas[{{ $slug }}][linhas][__INDEX__][rac]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/30">
            </td>
            <td class="border border-zinc-300 px-2 py-2 text-center">
                <input type="checkbox" name="etapas[{{ $slug }}][linhas][__INDEX__][nr]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/30">
            </td>
            <td class="border border-zinc-300 px-2 py-2 text-center">
                <input type="text" name="etapas[{{ $slug }}][linhas][__INDEX__][pro_outros]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" placeholder="Código/descrição">
            </td>
            <td class="border border-zinc-300 px-2 py-2">
                <input type="date" name="etapas[{{ $slug }}][linhas][__INDEX__][data]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </td>
            <td class="border border-zinc-300 px-2 py-2">
                <input type="text" name="etapas[{{ $slug }}][linhas][__INDEX__][instrutor]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </td>
            <td class="border border-zinc-300 px-2 py-2">
                <input type="text" name="etapas[{{ $slug }}][linhas][__INDEX__][titulo_descricao]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </td>
        </tr>
    </template>
    <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-brand-gray" data-training-footnote>
        Preencha apenas as linhas utilizadas no mês. Mínimo 1 linha visível e máximo 13 linhas.
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const step = document.getElementById('treinamentos_mensais');
                if (!step) return;

                const tbody = step.querySelector('tbody');
                const template = step.querySelector('[data-training-row-template]');
                const addBtn = step.querySelector('[data-add-training-row]');
                const removeBtn = step.querySelector('[data-remove-training-row]');
                if (!tbody || !template || !addBtn || !removeBtn) return;

                const maxRows = 13;
                const minRows = 1;

                const rows = () => tbody.querySelectorAll('[data-training-row]');
                const updateButtons = () => {
                    addBtn.disabled = rows().length >= maxRows;
                    removeBtn.disabled = rows().length <= minRows;
                    addBtn.classList.toggle('opacity-50', addBtn.disabled);
                    addBtn.classList.toggle('cursor-not-allowed', addBtn.disabled);
                    removeBtn.classList.toggle('opacity-50', removeBtn.disabled);
                    removeBtn.classList.toggle('cursor-not-allowed', removeBtn.disabled);
                };

                const nextIndex = () => rows().length;

                addBtn.addEventListener('click', () => {
                    if (rows().length >= maxRows) return;
                    const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex()));
                    tbody.insertAdjacentHTML('beforeend', html);
                    updateButtons();
                });

                removeBtn.addEventListener('click', () => {
                    const current = rows();
                    if (current.length <= minRows) return;
                    current[current.length - 1].remove();
                    updateButtons();
                });

                updateButtons();
            })();
        </script>
    @endpush
@endonce
