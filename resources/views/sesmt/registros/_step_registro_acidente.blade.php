@php
    $fill = $fill ?? [];
    $slug = 'registro_acidente';
    $linhasFill = data_get($fill, "$slug.linhas", []);
    if (! is_array($linhasFill)) {
        $linhasFill = [];
    }
    $linhasOld = old("etapas.$slug.linhas", $linhasFill);
    if (! is_array($linhasOld)) {
        $linhasOld = [];
    }
    $maxRows = 7;
    $totalLinhas = max(1, min($maxRows, count($linhasOld)));
@endphp

<div class="space-y-5" data-acidente-step>
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm" data-acidente-block data-max-rows="{{ $maxRows }}">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 bg-amber-400 px-4 py-2">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-black">Registro de acidente / incidente</p>
            <div class="flex items-center gap-2">
                <button type="button" data-add-acidente-row class="inline-flex h-8 items-center gap-1 rounded-md border border-zinc-300 bg-white px-2.5 text-[11px] font-semibold text-brand-black">
                    + Linha
                </button>
                <button type="button" data-remove-acidente-row class="inline-flex h-8 items-center gap-1 rounded-md border border-zinc-300 bg-white px-2.5 text-[11px] font-semibold text-brand-black">
                    - Linha
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-xs">
                <thead class="bg-amber-100 text-brand-black">
                    <tr>
                        <th class="border border-zinc-300 px-1 py-2 text-center uppercase" colspan="3">Acidente</th>
                        <th class="border border-zinc-300 px-2 py-2 text-left uppercase" rowspan="2">Data</th>
                        <th class="border border-zinc-300 px-2 py-2 text-left uppercase" rowspan="2">Hora</th>
                        <th class="border border-zinc-300 px-2 py-2 text-left uppercase" rowspan="2">Local</th>
                        <th class="border border-zinc-300 px-2 py-2 text-left uppercase" rowspan="2">Descrição</th>
                    </tr>
                    <tr>
                        <th class="border border-zinc-300 px-1 py-2 text-center uppercase">Material</th>
                        <th class="border border-zinc-300 px-1 py-2 text-center uppercase">Pessoal</th>
                        <th class="border border-zinc-300 px-1 py-2 text-center uppercase">Ambiental</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < $totalLinhas; $i++)
                        <tr data-acidente-row>
                            <td class="border border-zinc-300 px-2 py-2 text-center">
                                <input type="hidden" name="etapas[{{ $slug }}][linhas][{{ $i }}][material]" value="0">
                                <input type="checkbox" name="etapas[{{ $slug }}][linhas][{{ $i }}][material]" value="1" @checked(old("etapas.$slug.linhas.$i.material", data_get($fill, "$slug.linhas.$i.material"))) class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy">
                            </td>
                            <td class="border border-zinc-300 px-2 py-2 text-center">
                                <input type="hidden" name="etapas[{{ $slug }}][linhas][{{ $i }}][pessoal]" value="0">
                                <input type="checkbox" name="etapas[{{ $slug }}][linhas][{{ $i }}][pessoal]" value="1" @checked(old("etapas.$slug.linhas.$i.pessoal", data_get($fill, "$slug.linhas.$i.pessoal"))) class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy">
                            </td>
                            <td class="border border-zinc-300 px-2 py-2 text-center">
                                <input type="hidden" name="etapas[{{ $slug }}][linhas][{{ $i }}][ambiental]" value="0">
                                <input type="checkbox" name="etapas[{{ $slug }}][linhas][{{ $i }}][ambiental]" value="1" @checked(old("etapas.$slug.linhas.$i.ambiental", data_get($fill, "$slug.linhas.$i.ambiental"))) class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy">
                            </td>
                            <td class="border border-zinc-300 px-2 py-2">
                                <input type="date" name="etapas[{{ $slug }}][linhas][{{ $i }}][data]" value="{{ old("etapas.$slug.linhas.$i.data", data_get($fill, "$slug.linhas.$i.data")) }}" class="h-9 w-full min-w-[8.5rem] rounded border border-zinc-200 px-1 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </td>
                            <td class="border border-zinc-300 px-2 py-2">
                                <input type="time" name="etapas[{{ $slug }}][linhas][{{ $i }}][hora]" value="{{ old("etapas.$slug.linhas.$i.hora", data_get($fill, "$slug.linhas.$i.hora")) }}" class="h-9 w-full min-w-[6rem] rounded border border-zinc-200 px-1 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </td>
                            <td class="border border-zinc-300 px-2 py-2">
                                <input type="text" name="etapas[{{ $slug }}][linhas][{{ $i }}][local]" value="{{ old("etapas.$slug.linhas.$i.local", data_get($fill, "$slug.linhas.$i.local")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </td>
                            <td class="border border-zinc-300 px-2 py-2">
                                <input type="text" name="etapas[{{ $slug }}][linhas][{{ $i }}][descricao]" value="{{ old("etapas.$slug.linhas.$i.descricao", data_get($fill, "$slug.linhas.$i.descricao")) }}" class="h-9 w-full min-w-[200px] rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <template data-acidente-row-template>
            <tr data-acidente-row>
                <td class="border border-zinc-300 px-2 py-2 text-center">
                    <input type="hidden" name="etapas[{{ $slug }}][linhas][__INDEX__][material]" value="0">
                    <input type="checkbox" name="etapas[{{ $slug }}][linhas][__INDEX__][material]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy">
                </td>
                <td class="border border-zinc-300 px-2 py-2 text-center">
                    <input type="hidden" name="etapas[{{ $slug }}][linhas][__INDEX__][pessoal]" value="0">
                    <input type="checkbox" name="etapas[{{ $slug }}][linhas][__INDEX__][pessoal]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy">
                </td>
                <td class="border border-zinc-300 px-2 py-2 text-center">
                    <input type="hidden" name="etapas[{{ $slug }}][linhas][__INDEX__][ambiental]" value="0">
                    <input type="checkbox" name="etapas[{{ $slug }}][linhas][__INDEX__][ambiental]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy">
                </td>
                <td class="border border-zinc-300 px-2 py-2">
                    <input type="date" name="etapas[{{ $slug }}][linhas][__INDEX__][data]" class="h-9 w-full min-w-[8.5rem] rounded border border-zinc-200 px-1 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </td>
                <td class="border border-zinc-300 px-2 py-2">
                    <input type="time" name="etapas[{{ $slug }}][linhas][__INDEX__][hora]" class="h-9 w-full min-w-[6rem] rounded border border-zinc-200 px-1 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </td>
                <td class="border border-zinc-300 px-2 py-2">
                    <input type="text" name="etapas[{{ $slug }}][linhas][__INDEX__][local]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </td>
                <td class="border border-zinc-300 px-2 py-2">
                    <input type="text" name="etapas[{{ $slug }}][linhas][__INDEX__][descricao]" class="h-9 w-full min-w-[200px] rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </td>
            </tr>
        </template>
        <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-2 text-xs text-brand-gray">Mínimo 1 linha e máximo {{ $maxRows }} linhas. Marque o tipo de acidente (uma ou mais colunas).</div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ([1 => 'evidencia_1', 2 => 'evidencia_2'] as $num => $field)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <div class="flex gap-3">
                    <div class="flex w-10 shrink-0 items-center justify-center rounded-lg bg-amber-400 py-6">
                        <span class="-rotate-90 whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-white">Foto</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Evidência {{ $num }}</p>
                        <input type="file" name="etapas[{{ $slug }}][{{ $field }}]" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="mt-2 block w-full text-sm text-brand-gray file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white">
                        @php $acEv = data_get($fill, "{$slug}.{$field}_path"); @endphp
                        @if ($acEv)
                            <p class="mt-2 text-xs font-medium text-brand-burgundy"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($acEv) }}" target="_blank" rel="noopener" class="underline underline-offset-2">Arquivo atual (evidência {{ $num }})</a></p>
                        @endif
                        <p class="mt-1 text-xs text-brand-gray">Imagem ou PDF · máx. 5MB</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const block = document.querySelector('[data-acidente-block]');
                if (!block) return;
                const tbody = block.querySelector('tbody');
                const template = block.querySelector('[data-acidente-row-template]');
                const addBtn = block.querySelector('[data-add-acidente-row]');
                const removeBtn = block.querySelector('[data-remove-acidente-row]');
                if (!tbody || !template || !addBtn || !removeBtn) return;

                const maxRows = parseInt(block.getAttribute('data-max-rows') || '7', 10);
                const minRows = 1;
                const rows = () => tbody.querySelectorAll('[data-acidente-row]');
                const updateButtons = () => {
                    addBtn.disabled = rows().length >= maxRows;
                    removeBtn.disabled = rows().length <= minRows;
                    addBtn.classList.toggle('opacity-50', addBtn.disabled);
                    removeBtn.classList.toggle('opacity-50', removeBtn.disabled);
                };
                const nextIndex = () => rows().length;

                addBtn.addEventListener('click', () => {
                    if (rows().length >= maxRows) return;
                    tbody.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex())));
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
