@php
    $colaboradoresDisponiveis = $colaboradoresDisponiveis ?? collect();
    $excecoesEscala = $excecoesEscala ?? collect();
    $tipoAtual = $tipoAtual ?? old('tipo', $escala->tipo ?? 'semanal');
    $mostraGrupo = in_array($tipoAtual, ['rotativa', 'rotativa_semanal', 'rotativa_dias_uteis', 'rotativa_veiculos'], true);
    $ehDiasUteis = $tipoAtual === 'rotativa_dias_uteis';
    $ehVeiculos = $tipoAtual === 'rotativa_veiculos';
    $posicoesDiasUteis = max(2, min(14, (int) old('ciclo_dias', $escala->ciclo_dias ?? 4)));
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
        <span data-escala-grupo-ajuda class="{{ $mostraGrupo && ! $ehDiasUteis && ! $ehVeiculos ? '' : 'hidden' }}">
            Depois defina o <strong>grupo 0 ou 1</strong> na coluna à direita.
        </span>
        <span data-escala-posicao-ajuda class="{{ ($ehDiasUteis || $ehVeiculos) ? '' : 'hidden' }}">
            Depois defina a <strong>posição no rodízio</strong> (1 = primeiro dia útil do ciclo).
        </span>
    </p>

    <div data-escala-grupo-legenda class="mt-4 grid gap-2 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft/30 p-3 text-xs text-brand-black sm:grid-cols-2 {{ $mostraGrupo && ! $ehDiasUteis && ! $ehVeiculos ? '' : 'hidden' }}">
        <p><span class="font-bold">Grupo 0</span> — Sem. 1: seg, qua, sex · Sem. 2: ter, qui</p>
        <p><span class="font-bold">Grupo 1</span> — Sem. 1: ter, qui · Sem. 2: seg, qua, sex</p>
    </div>

    <div data-escala-posicao-legenda class="mt-4 rounded-lg border border-sky-200/60 bg-sky-50/60 p-3 text-xs text-brand-black {{ ($ehDiasUteis || $ehVeiculos) ? '' : 'hidden' }}">
        <p>Cada posição trabalha em um dia útil do ciclo. Posição visual <strong>1</strong> = offset interno <strong>0</strong>.</p>
        <p class="mt-1 text-brand-gray">Selecione exatamente a quantidade de posições informada, sem duplicar.</p>
    </div>

    <div data-escala-posicao-alerta class="mt-3 hidden rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900" role="alert">
        Há colaboradores em posições que deixaram de existir. Ajuste as posições antes de salvar.
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
                        <th data-escala-col-fase-header class="w-[min(320px,45%)] px-3 py-3 text-brand-burgundy {{ $mostraGrupo ? '' : 'hidden' }}">
                            <span data-escala-col-fase-titulo>{{ ($ehDiasUteis || $ehVeiculos) ? 'Posição no rodízio' : 'Grupo na rotatividade' }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($colaboradoresDisponiveis as $idx => $colab)
                        @php
                            $naEscala = in_array($colab->id, $idsNaEscala, true);
                            $offset = (int) old("escala_colaboradores.$idx.ciclo_offset", $colab->horario_escala_ciclo_offset ?? 0);
                        @endphp
                        <tr class="bg-white" data-escala-colab-row data-colab-nome="{{ $colab->nome }}">
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
                                    <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-brand-burgundy lg:sr-only" data-escala-offset-label>
                                        {{ ($ehDiasUteis || $ehVeiculos) ? 'Posição' : 'Grupo' }}
                                    </span>
                                    <select
                                        name="escala_colaboradores[{{ $idx }}][ciclo_offset]"
                                        class="w-full max-w-full rounded-lg border border-zinc-300 bg-white px-2 py-2 text-sm font-medium text-brand-black shadow-sm focus:border-brand-burgundy focus:outline-none focus:ring-2 focus:ring-brand-burgundy/20 disabled:bg-zinc-100 disabled:text-zinc-400"
                                        @disabled(! $naEscala)
                                        data-escala-colab-offset
                                        data-offset-atual="{{ $offset }}"
                                    >
                                        @if ($ehDiasUteis || $ehVeiculos)
                                            @for ($p = 0; $p < ($ehVeiculos ? 4 : $posicoesDiasUteis); $p++)
                                                <option value="{{ $p }}" @selected($offset === $p)>
                                                    @if ($ehVeiculos)
                                                        Posição {{ $p + 1 }} - {{ ['Micro no dia inicial', 'Caminhonete no dia inicial', 'Micro no dia útil seguinte', 'Caminhonete no dia útil seguinte'][$p] }}
                                                    @else
                                                        Posição {{ $p + 1 }} — {{ $p === 0 ? 'primeiro' : ($p === 1 ? 'segundo' : ($p + 1).'º') }} dia útil do ciclo
                                                    @endif
                                                </option>
                                            @endfor
                                        @else
                                            <option value="0" @selected($offset === 0)>Grupo 0 — seg, qua, sex</option>
                                            <option value="1" @selected($offset === 1)>Grupo 1 — ter, qui</option>
                                        @endif
                                    </select>
                                </label>
                                <p data-escala-offset-invalido class="mt-1.5 hidden text-[11px] font-semibold text-amber-800">Posição inválida para a quantidade atual.</p>
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
            const faseTitulo = document.querySelector('[data-escala-col-fase-titulo]');
            const legenda = document.querySelector('[data-escala-grupo-legenda]');
            const ajuda = document.querySelector('[data-escala-grupo-ajuda]');
            const posicaoLegenda = document.querySelector('[data-escala-posicao-legenda]');
            const posicaoAjuda = document.querySelector('[data-escala-posicao-ajuda]');
            const alertaPosicao = document.querySelector('[data-escala-posicao-alerta]');
            const posicoesInput = document.querySelector('[data-horario-posicoes-dias-uteis]');

            function tipoAtual() {
                return tipoSel?.value || '';
            }

            function tipoMostraGrupo() {
                const v = tipoAtual();
                return v === 'rotativa' || v === 'rotativa_semanal' || v === 'rotativa_dias_uteis' || v === 'rotativa_veiculos';
            }

            function quantidadePosicoes() {
                if (tipoAtual() === 'rotativa_veiculos') return 4;
                return Math.min(14, Math.max(2, parseInt(posicoesInput?.value || '4', 10)));
            }

            function ordinalDiaUtil(n) {
                if (n === 1) return 'primeiro';
                if (n === 2) return 'segundo';
                if (n === 3) return 'terceiro';
                if (n === 4) return 'quarto';
                return n + 'º';
            }

            function rebuildOffsetOptions(select, quantidade, selected) {
                const valorAtual = selected ?? parseInt(select.getAttribute('data-offset-atual') || select.value || '0', 10);
                select.innerHTML = '';
                let temSelecionado = false;
                for (let p = 0; p < quantidade; p++) {
                    const opt = document.createElement('option');
                    opt.value = String(p);
                    if (tipoAtual() === 'rotativa_veiculos') {
                        opt.textContent = [
                            'Posicao 1 - Micro no dia inicial',
                            'Posicao 2 - Caminhonete no dia inicial',
                            'Posicao 3 - Micro no dia util seguinte',
                            'Posicao 4 - Caminhonete no dia util seguinte',
                        ][p] || ('Posicao ' + (p + 1));
                    } else {
                        opt.textContent = 'Posição ' + (p + 1) + ' — ' + ordinalDiaUtil(p + 1) + ' dia útil do ciclo';
                    }
                    if (p === valorAtual) {
                        opt.selected = true;
                        temSelecionado = true;
                    }
                    select.appendChild(opt);
                }
                const invalido = select.closest('td')?.querySelector('[data-escala-offset-invalido]');
                if (!temSelecionado && valorAtual >= quantidade) {
                    const optExtra = document.createElement('option');
                    optExtra.value = String(valorAtual);
                    optExtra.textContent = 'Posição ' + (valorAtual + 1) + ' (inválida — ajuste)';
                    optExtra.selected = true;
                    optExtra.dataset.invalida = '1';
                    select.appendChild(optExtra);
                    select.classList.add('border-amber-400', 'ring-1', 'ring-amber-300');
                    invalido?.classList.remove('hidden');
                    return true;
                }
                select.classList.remove('border-amber-400', 'ring-1', 'ring-amber-300');
                invalido?.classList.add('hidden');
                select.setAttribute('data-offset-atual', String(select.value));
                return false;
            }

            function rebuildGrupoOptions(select) {
                const valor = select.value || '0';
                select.innerHTML = '';
                [
                    ['0', 'Grupo 0 — seg, qua, sex'],
                    ['1', 'Grupo 1 — ter, qui'],
                ].forEach(([v, label]) => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = label;
                    if (v === valor || (valor !== '0' && valor !== '1' && v === '0')) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
                select.classList.remove('border-amber-400', 'ring-1', 'ring-amber-300');
                select.closest('td')?.querySelector('[data-escala-offset-invalido]')?.classList.add('hidden');
            }

            function syncOffsetSelects() {
                const tipo = tipoAtual();
                const diasUteis = tipo === 'rotativa_dias_uteis';
                const veiculos = tipo === 'rotativa_veiculos';
                let houveInvalido = false;
                document.querySelectorAll('[data-escala-colab-offset]').forEach((select) => {
                    if (diasUteis || veiculos) {
                        if (rebuildOffsetOptions(select, quantidadePosicoes())) {
                            houveInvalido = true;
                        }
                    } else if (tipo === 'rotativa' || tipo === 'rotativa_semanal') {
                        rebuildGrupoOptions(select);
                    }
                });
                alertaPosicao?.classList.toggle('hidden', !((diasUteis || veiculos) && houveInvalido));
            }

            function syncTipoColab() {
                const show = tipoMostraGrupo();
                const diasUteis = tipoAtual() === 'rotativa_dias_uteis';
                const veiculos = tipoAtual() === 'rotativa_veiculos';
                [faseHeader].forEach((el) => {
                    if (el) el.classList.toggle('hidden', !show);
                });
                faseCells.forEach((el) => {
                    el.classList.toggle('hidden', !show);
                });
                legenda?.classList.toggle('hidden', !show || diasUteis || veiculos);
                ajuda?.classList.toggle('hidden', !show || diasUteis || veiculos);
                posicaoLegenda?.classList.toggle('hidden', !(diasUteis || veiculos));
                posicaoAjuda?.classList.toggle('hidden', !(diasUteis || veiculos));
                if (faseTitulo) {
                    faseTitulo.textContent = (diasUteis || veiculos) ? 'Posição no rodízio' : 'Grupo na rotatividade';
                }
                document.querySelectorAll('[data-escala-offset-label]').forEach((el) => {
                    el.textContent = (diasUteis || veiculos) ? 'Posição' : 'Grupo';
                });
                syncOffsetSelects();
                document.dispatchEvent(new CustomEvent('horario-escala-colab-alterado'));
            }

            tipoSel?.addEventListener('change', syncTipoColab);
            document.addEventListener('horario-escala-tipo-alterado', syncTipoColab);
            document.addEventListener('horario-escala-posicoes-alteradas', syncOffsetSelects);
            posicoesInput?.addEventListener('change', syncOffsetSelects);

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
                    document.dispatchEvent(new CustomEvent('horario-escala-colab-alterado'));
                });
            });

            document.querySelectorAll('[data-escala-colab-offset]').forEach((sel) => {
                sel.addEventListener('change', () => {
                    sel.setAttribute('data-offset-atual', sel.value);
                    document.dispatchEvent(new CustomEvent('horario-escala-colab-alterado'));
                });
            });

            document.querySelector('form[data-horario-escala-form]')?.addEventListener('submit', (e) => {
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

                if (tipoAtual() === 'rotativa_dias_uteis' || tipoAtual() === 'rotativa_veiculos') {
                    const qtd = quantidadePosicoes();
                    let invalido = false;
                    document.querySelectorAll('[data-escala-colab-row]').forEach((row) => {
                        const check = row.querySelector('[data-escala-colab-check]');
                        if (!check?.checked) return;
                        const offset = parseInt(row.querySelector('[data-escala-colab-offset]')?.value || '-1', 10);
                        if (offset < 0 || offset >= qtd) {
                            invalido = true;
                        }
                    });
                    if (invalido) {
                        e.preventDefault();
                        alertaPosicao?.classList.remove('hidden');
                        alertaPosicao?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            syncTipoColab();
        })();
    </script>
@endpush
