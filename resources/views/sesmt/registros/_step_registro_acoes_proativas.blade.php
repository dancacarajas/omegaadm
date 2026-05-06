@php
    $fill = $fill ?? [];
    $slug = 'registro_acoes_proativas';
    $blocos = [
        'quase_acidente' => [
            'titulo' => 'Indicador Proativo - Quase Acidente',
            'colunas' => ['data', 'local', 'descricao'],
            'max_rows' => 4,
        ],
        'termo_interdicao_vale' => [
            'titulo' => 'Indicador Proativo - Termo de Interdição - Vale',
            'colunas' => ['data', 'local', 'descricao', 'emissor'],
            'max_rows' => 4,
        ],
        'termo_notificacao_vale' => [
            'titulo' => 'Indicador Proativo - Termo de Notificação - Vale',
            'colunas' => ['data', 'local', 'descricao', 'emissor'],
            'max_rows' => 4,
        ],
        'interdicao_interna_omega' => [
            'titulo' => 'Indicador proativo - Interdição interna - Omega Service',
            'colunas' => ['data', 'local', 'descricao', 'emissor'],
            'max_rows' => 6,
        ],
        'notificacao_interna_omega' => [
            'titulo' => 'Indicador proativo - Notificação interna - Omega Service',
            'colunas' => ['data', 'local', 'descricao', 'emissor'],
            'max_rows' => 6,
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
            $maxRows = (int) ($bloco['max_rows'] ?? 4);
            $totalLinhas = max(1, min($maxRows, is_array($linhasOld) ? count($linhasOld) : 0));
            $hasEmissor = in_array('emissor', $bloco['colunas'], true);
        @endphp
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm" data-proactive-block data-max-rows="{{ $maxRows }}">
            <div class="flex items-center justify-between border-b border-zinc-200 bg-amber-400 px-4 py-2">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-black">{{ $bloco['titulo'] }}</p>
                <div class="flex items-center gap-2">
                    <button type="button" data-add-proactive-row class="inline-flex h-8 items-center gap-1 rounded-md border border-zinc-300 bg-white px-2.5 text-[11px] font-semibold text-brand-black">
                        + Linha
                    </button>
                    <button type="button" data-remove-proactive-row class="inline-flex h-8 items-center gap-1 rounded-md border border-zinc-300 bg-white px-2.5 text-[11px] font-semibold text-brand-black">
                        - Linha
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-xs">
                    <thead class="bg-amber-100 text-brand-black">
                        <tr>
                            <th class="border border-zinc-300 px-2 py-2 text-left uppercase">Data</th>
                            <th class="border border-zinc-300 px-2 py-2 text-left uppercase">Local</th>
                            <th class="border border-zinc-300 px-2 py-2 text-left uppercase">Descrição</th>
                            @if ($hasEmissor)
                                <th class="border border-zinc-300 px-2 py-2 text-left uppercase">Emissor</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $totalLinhas; $i++)
                            <tr data-proactive-row>
                                <td class="border border-zinc-300 px-2 py-2">
                                    <input type="date" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][{{ $i }}][data]" value="{{ old("etapas.$slug.$blocoKey.linhas.$i.data", data_get($fill, "$slug.$blocoKey.linhas.$i.data")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </td>
                                <td class="border border-zinc-300 px-2 py-2">
                                    <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][{{ $i }}][local]" value="{{ old("etapas.$slug.$blocoKey.linhas.$i.local", data_get($fill, "$slug.$blocoKey.linhas.$i.local")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </td>
                                <td class="border border-zinc-300 px-2 py-2">
                                    <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][{{ $i }}][descricao]" value="{{ old("etapas.$slug.$blocoKey.linhas.$i.descricao", data_get($fill, "$slug.$blocoKey.linhas.$i.descricao")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </td>
                                @if ($hasEmissor)
                                    <td class="border border-zinc-300 px-2 py-2">
                                        <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][{{ $i }}][emissor]" value="{{ old("etapas.$slug.$blocoKey.linhas.$i.emissor", data_get($fill, "$slug.$blocoKey.linhas.$i.emissor")) }}" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </td>
                                @endif
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <template data-proactive-row-template>
                <tr data-proactive-row>
                    <td class="border border-zinc-300 px-2 py-2">
                        <input type="date" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][__INDEX__][data]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </td>
                    <td class="border border-zinc-300 px-2 py-2">
                        <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][__INDEX__][local]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </td>
                    <td class="border border-zinc-300 px-2 py-2">
                        <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][__INDEX__][descricao]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </td>
                    @if ($hasEmissor)
                        <td class="border border-zinc-300 px-2 py-2">
                            <input type="text" name="etapas[{{ $slug }}][{{ $blocoKey }}][linhas][__INDEX__][emissor]" class="h-9 w-full rounded border border-zinc-200 px-2 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        </td>
                    @endif
                </tr>
            </template>
            <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-2 text-xs text-brand-gray">Mínimo 1 linha e máximo {{ $maxRows }} linhas neste bloco.</div>
        </div>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            (() => {
                document.querySelectorAll('[data-proactive-block]').forEach((block) => {
                    const tbody = block.querySelector('tbody');
                    const template = block.querySelector('[data-proactive-row-template]');
                    const addBtn = block.querySelector('[data-add-proactive-row]');
                    const removeBtn = block.querySelector('[data-remove-proactive-row]');
                    if (!tbody || !template || !addBtn || !removeBtn) return;

                    const maxRows = parseInt(block.getAttribute('data-max-rows') || '4', 10);
                    const minRows = 1;
                    const rows = () => tbody.querySelectorAll('[data-proactive-row]');
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
