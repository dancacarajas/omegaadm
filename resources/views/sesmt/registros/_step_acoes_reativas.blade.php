@php
    $fill = $fill ?? [];
    $slug = 'acoes_reativas';
    $blocos = [
        'primeiros_socorros' => [
            'titulo' => 'Indicador reativo - Primeiros socorros',
            'max_rows' => 3,
        ],
        'restricao_trabalho' => [
            'titulo' => 'Indicador reativo - Restrição de trabalho',
            'max_rows' => 3,
        ],
        'tratamento_medico' => [
            'titulo' => 'Indicador reativo - Tratamento médico',
            'max_rows' => 7,
        ],
        'regra_ouro' => [
            'titulo' => 'Indicador reativo - Regra de ouro',
            'max_rows' => 3,
        ],
        'telemetria' => [
            'titulo' => 'Indicador reativo - Telemetria',
            'max_rows' => 7,
        ],
    ];
@endphp

<div class="space-y-5">
    @foreach ($blocos as $blocoKey => $bloco)
        @php
            $linhasFill = data_get($fill, "$slug.$blocoKey.linhas", []);
            if (! is_array($linhasFill)) {
                $linhasFill = [];
            }
            $linhasOld = old("etapas.$slug.$blocoKey.linhas", $linhasFill);
            $maxRows = (int) ($bloco['max_rows'] ?? 3);
            $totalLinhas = max(1, min($maxRows, is_array($linhasOld) ? count($linhasOld) : 0));
        @endphp
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm" data-reativo-block data-max-rows="{{ $maxRows }}">
            <div class="flex items-center justify-between border-b border-zinc-200 bg-amber-400 px-4 py-2">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-black">{{ $bloco['titulo'] }}</p>
                <div class="flex items-center gap-2">
                    <button type="button" data-add-reativo-row class="inline-flex h-8 items-center gap-1 rounded-md border border-zinc-300 bg-white px-2.5 text-[11px] font-semibold text-brand-black">
                        + Linha
                    </button>
                    <button type="button" data-remove-reativo-row class="inline-flex h-8 items-center gap-1 rounded-md border border-zinc-300 bg-white px-2.5 text-[11px] font-semibold text-brand-black">
                        - Linha
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-xs">
                    <thead class="bg-amber-100 text-brand-black">
                        <tr>
                            <th class="border border-zinc-300 px-2 py-2 text-left uppercase">Data</th>
                            <th class="border border-zinc-300 px-2 py-2 text-left uppercase">Local</th>
                            <th class="border border-zinc-300 px-2 py-2 text-left uppercase">Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $totalLinhas; $i++)
                            <tr data-reativo-row>
                                <td class="border border-zinc-300 px-2 py-2">
                                    <input type="date" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][{{ $i }}][data]" value="{{ old("etapas.$slug.$blocoKey.linhas.$i.data", data_get($fill, "$slug.$blocoKey.linhas.$i.data")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </td>
                                <td class="border border-zinc-300 px-2 py-2">
                                    <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][{{ $i }}][local]" value="{{ old("etapas.$slug.$blocoKey.linhas.$i.local", data_get($fill, "$slug.$blocoKey.linhas.$i.local")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </td>
                                <td class="border border-zinc-300 px-2 py-2">
                                    <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][{{ $i }}][descricao]" value="{{ old("etapas.$slug.$blocoKey.linhas.$i.descricao", data_get($fill, "$slug.$blocoKey.linhas.$i.descricao")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <template data-reativo-row-template>
                <tr data-reativo-row>
                    <td class="border border-zinc-300 px-2 py-2">
                        <input type="date" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][__INDEX__][data]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </td>
                    <td class="border border-zinc-300 px-2 py-2">
                        <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][__INDEX__][local]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </td>
                    <td class="border border-zinc-300 px-2 py-2">
                        <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][__INDEX__][descricao]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </td>
                </tr>
            </template>
            <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-2 text-xs text-brand-gray">Mínimo 1 linha e máximo {{ $maxRows }} linhas neste indicador.</div>
        </div>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            (() => {
                document.querySelectorAll('[data-reativo-block]').forEach((block) => {
                    const tbody = block.querySelector('tbody');
                    const template = block.querySelector('[data-reativo-row-template]');
                    const addBtn = block.querySelector('[data-add-reativo-row]');
                    const removeBtn = block.querySelector('[data-remove-reativo-row]');
                    if (!tbody || !template || !addBtn || !removeBtn) return;

                    const maxRows = parseInt(block.getAttribute('data-max-rows') || '3', 10);
                    const minRows = 1;
                    const rows = () => tbody.querySelectorAll('[data-reativo-row]');
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
                });
            })();
        </script>
    @endpush
@endonce
