@php
    $colaboradoresDisponiveis = $colaboradoresDisponiveis ?? collect();
    $excecoesEscala = $excecoesEscala ?? collect();
    $tipoAtual = $tipoAtual ?? old('tipo', $escala->tipo ?? 'semanal');
    $mostraGrupo = in_array($tipoAtual, ['rotativa', 'rotativa_semanal'], true);
    $idsNaEscala = collect(old('escala_colaboradores', []))
        ->pluck('colaborador_id')
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->all();
    if ($idsNaEscala === [] && $escala->exists) {
        $idsNaEscala = $escala->colaboradores->pluck('id')->all();
    }
@endphp

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-brand-black">Colaboradores nesta escala</h2>
    <p class="mt-1 text-sm text-brand-gray">
        Marque quem participa da escala.
        <span data-escala-grupo-ajuda class="{{ $mostraGrupo ? '' : 'hidden' }}">
            Depois defina o <strong>grupo 0 ou 1</strong> na coluna à direita.
        </span>
    </p>

    <div data-escala-grupo-legenda class="mt-4 grid gap-2 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft/30 p-3 text-xs text-brand-black sm:grid-cols-2 {{ $mostraGrupo ? '' : 'hidden' }}">
        <p><span class="font-bold">Grupo 0</span> — Sem. 1: seg, qua, sex · Sem. 2: ter, qui</p>
        <p><span class="font-bold">Grupo 1</span> — Sem. 1: ter, qui · Sem. 2: seg, qua, sex</p>
    </div>

    @if ($colaboradoresDisponiveis->isEmpty())
        <p class="mt-4 text-sm text-brand-gray">Nenhum colaborador ativo disponível para vincular.</p>
    @else
        <div class="mt-5 overflow-x-auto rounded-lg border border-zinc-200">
            <table class="w-full min-w-[720px] border-collapse text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-bold uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="w-24 px-3 py-3">Incluir</th>
                        <th class="px-3 py-3">Colaborador</th>
                        <th data-escala-col-fase-header class="w-[min(280px,40%)] px-3 py-3 text-brand-burgundy {{ $mostraGrupo ? '' : 'hidden' }}">
                            Grupo na rotatividade
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($colaboradoresDisponiveis as $idx => $colab)
                        @php
                            $naEscala = in_array($colab->id, $idsNaEscala, true);
                            $offset = (int) old("escala_colaboradores.$idx.ciclo_offset", $colab->horario_escala_ciclo_offset ?? 0);
                        @endphp
                        <tr class="bg-white" data-escala-colab-row>
                            <td class="align-top px-3 py-3">
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" value="1" data-escala-colab-check class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($naEscala)>
                                    <span class="sr-only">Incluir {{ $colab->nome }}</span>
                                </label>
                                <input type="hidden" name="escala_colaboradores[{{ $idx }}][colaborador_id]" value="{{ $colab->id }}" data-escala-colab-id @disabled(! $naEscala)>
                            </td>
                            <td class="align-top px-3 py-3">
                                <p class="font-semibold leading-snug text-brand-black">{{ $colab->nome }}</p>
                                <p class="mt-0.5 text-xs text-brand-gray">{{ $colab->matricula ?: 'Sem matrícula' }}</p>
                            </td>
                            <td data-escala-col-fase class="align-top px-3 py-3 {{ $mostraGrupo ? '' : 'hidden' }}">
                                <label class="block">
                                    <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-burgundy lg:sr-only">Grupo</span>
                                    <select
                                        name="escala_colaboradores[{{ $idx }}][ciclo_offset]"
                                        class="w-full max-w-full rounded-lg border border-zinc-300 bg-white px-2 py-2 text-sm font-medium text-brand-black shadow-sm focus:border-brand-burgundy focus:outline-none focus:ring-2 focus:ring-brand-burgundy/20 disabled:bg-zinc-100 disabled:text-zinc-400"
                                        @disabled(! $naEscala)
                                        data-escala-colab-offset
                                    >
                                        <option value="0" @selected($offset === 0)>Grupo 0 — seg, qua, sex</option>
                                        <option value="1" @selected($offset === 1)>Grupo 1 — ter, qui</option>
                                    </select>
                                </label>
                                @if (! $naEscala)
                                    <p class="mt-1.5 text-[11px] text-amber-800">Marque «Incluir» para habilitar.</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @error('escala_colaboradores')
        <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</section>

@if ($escala->exists)
    <section class="overflow-hidden rounded-xl border border-violet-200/80 bg-violet-50/40 p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-bold text-brand-black">Exceções administrativas</h2>
        <p class="mt-1 text-sm text-brand-gray">
            Registre ausências temporárias e cobertura. Ex.: Pedro ausente por luto — outro motorista marca ponto <strong>todos os dias</strong> até o retorno, ignorando a folga do ciclo.
        </p>

        @if ($excecoesEscala->isNotEmpty())
            <div class="mt-5 space-y-3">
                @foreach ($excecoesEscala as $exIdx => $excecao)
                    <div class="rounded-lg border border-violet-200/60 bg-white p-4 text-sm">
                        <input type="hidden" name="excecoes[{{ $exIdx }}][id]" value="{{ $excecao->id }}">
                        <input type="hidden" name="excecoes[{{ $exIdx }}][colaborador_ausente_id]" value="{{ $excecao->colaborador_ausente_id }}">
                        <input type="hidden" name="excecoes[{{ $exIdx }}][colaborador_cobertura_id]" value="{{ $excecao->colaborador_cobertura_id }}">
                        <input type="hidden" name="excecoes[{{ $exIdx }}][data_inicio]" value="{{ $excecao->data_inicio->format('Y-m-d') }}">
                        <input type="hidden" name="excecoes[{{ $exIdx }}][data_fim]" value="{{ $excecao->data_fim->format('Y-m-d') }}">
                        <input type="hidden" name="excecoes[{{ $exIdx }}][motivo]" value="{{ $excecao->motivo }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-brand-black">
                                    Ausente: {{ $excecao->colaboradorAusente?->nome }}
                                    @if ($excecao->colaboradorCobertura)
                                        · Cobertura: {{ $excecao->colaboradorCobertura->nome }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-brand-gray">
                                    {{ $excecao->data_inicio->format('d/m/Y') }} — {{ $excecao->data_fim->format('d/m/Y') }}
                                    @if ($excecao->motivo)
                                        · {{ $excecao->motivo }}
                                    @endif
                                </p>
                            </div>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-bold text-red-600">
                                <input type="checkbox" name="excecoes_remover[]" value="{{ $excecao->id }}" class="h-4 w-4 rounded border-red-300 text-red-600 focus:ring-red-500">
                                Remover
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @php
            $colaboradoresEscala = $escala->colaboradores->isNotEmpty()
                ? $escala->colaboradores
                : $colaboradoresDisponiveis->filter(fn ($c) => in_array($c->id, $idsNaEscala, true));
            $novaExIdx = $excecoesEscala->count();
        @endphp

        @if ($colaboradoresEscala->isNotEmpty())
            <div class="mt-6 rounded-lg border border-dashed border-violet-300 bg-white p-4">
                <h3 class="text-sm font-bold text-brand-black">Nova exceção</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Colaborador ausente</label>
                        <select name="excecoes[{{ $novaExIdx }}][colaborador_ausente_id]" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-brand-black">
                            <option value="">— Selecione —</option>
                            @foreach ($colaboradoresEscala as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Cobertura (opcional)</label>
                        <select name="excecoes[{{ $novaExIdx }}][colaborador_cobertura_id]" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-brand-black">
                            <option value="">— Nenhum —</option>
                            @foreach ($colaboradoresEscala as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-brand-gray">Quem cobre pode marcar ponto em qualquer dia do período.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Motivo</label>
                        <input type="text" name="excecoes[{{ $novaExIdx }}][motivo]" maxlength="500" placeholder="Ex.: luto familiar" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-brand-black">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Início</label>
                        <input type="date" name="excecoes[{{ $novaExIdx }}][data_inicio]" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-brand-black">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Fim</label>
                        <input type="date" name="excecoes[{{ $novaExIdx }}][data_fim]" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-brand-black">
                    </div>
                </div>
            </div>
        @else
            <p class="mt-4 text-sm text-amber-800">Salve a escala com ao menos um colaborador vinculado para registrar exceções.</p>
        @endif
    </section>
@endif

@push('scripts')
    <script>
        (function () {
            const tipoSel = document.querySelector('[data-horario-tipo]');
            const faseCells = document.querySelectorAll('[data-escala-col-fase]');
            const faseHeader = document.querySelector('[data-escala-col-fase-header]');
            const legenda = document.querySelector('[data-escala-grupo-legenda]');
            const ajuda = document.querySelector('[data-escala-grupo-ajuda]');

            function tipoMostraGrupo() {
                const v = tipoSel?.value || '';
                return v === 'rotativa' || v === 'rotativa_semanal';
            }

            function syncTipoColab() {
                const show = tipoMostraGrupo();
                [faseHeader, legenda, ajuda].forEach((el) => {
                    if (el) {
                        el.classList.toggle('hidden', !show);
                    }
                });
                faseCells.forEach((el) => {
                    el.classList.toggle('hidden', !show);
                });
            }

            tipoSel?.addEventListener('change', syncTipoColab);

            document.querySelectorAll('[data-escala-colab-check]').forEach((cb) => {
                cb.addEventListener('change', () => {
                    const row = cb.closest('[data-escala-colab-row]');
                    const hidden = row?.querySelector('[data-escala-colab-id]');
                    const offset = row?.querySelector('[data-escala-colab-offset]');
                    if (hidden) {
                        hidden.disabled = !cb.checked;
                    }
                    if (offset) {
                        offset.disabled = !cb.checked;
                    }
                });
            });

            document.querySelector('form[data-horario-escala-form]')?.addEventListener('submit', () => {
                document.querySelectorAll('[data-escala-colab-check]').forEach((cb) => {
                    const row = cb.closest('[data-escala-colab-row]');
                    const hidden = row?.querySelector('[data-escala-colab-id]');
                    const offset = row?.querySelector('[data-escala-colab-offset]');
                    if (cb.checked) {
                        hidden?.removeAttribute('disabled');
                        offset?.removeAttribute('disabled');
                    } else {
                        hidden?.removeAttribute('name');
                        offset?.removeAttribute('name');
                    }
                });
            });

            syncTipoColab();
        })();
    </script>
@endpush
