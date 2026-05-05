@php
    $diasNomes = [
        1 => '2ª feira',
        2 => '3ª feira',
        3 => '4ª feira',
        4 => '5ª feira',
        5 => '6ª feira',
        6 => 'Sábado',
        7 => 'Domingo',
    ];
    $fmtTime = static function ($v) {
        if ($v === null || $v === '') {
            return '';
        }
        $s = (string) $v;

        return strlen($s) >= 5 ? substr($s, 0, 5) : $s;
    };
@endphp

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold text-brand-black">Dados gerais</h2>
    <p class="mt-1 text-sm text-brand-gray">Identifique a escala e o status de uso no sistema.</p>

    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="nome" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Nome</label>
            <input type="text" name="nome" id="nome" value="{{ old('nome', $escala->nome) }}" required maxlength="255" placeholder="Ex.: 0003 - Fábrica Parauapebas" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-brand-black shadow-sm transition focus:border-brand-burgundy focus:outline-none focus:ring-2 focus:ring-brand-burgundy/20">
            @error('nome')
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="tipo" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo</label>
            <select name="tipo" id="tipo" class="mt-2 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-brand-black shadow-sm transition focus:border-brand-burgundy focus:outline-none focus:ring-2 focus:ring-brand-burgundy/20">
                <option value="semanal" @selected(old('tipo', $escala->tipo) === 'semanal')>Semanal</option>
            </select>
            @error('tipo')
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
            <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Status</span>
            <div class="mt-2 flex flex-wrap gap-4">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-brand-black">
                    <input type="radio" name="status" value="ativo" class="h-4 w-4 border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked(old('status', $escala->status ?? 'ativo') === 'ativo')>
                    Ativo
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-brand-black">
                    <input type="radio" name="status" value="inativo" class="h-4 w-4 border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked(old('status', $escala->status) === 'inativo')>
                    Inativo
                </label>
            </div>
            @error('status')
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</section>

<section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-brand-black">Grade semanal</h2>
        <p class="mt-1 text-sm text-brand-gray">Informe entradas e saídas; o total do dia e da semana são calculados automaticamente.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[960px] border-collapse text-left text-sm" data-horario-escala-table>
            <thead>
                <tr class="border-b border-zinc-200 bg-white text-xs font-bold uppercase tracking-wide text-brand-gray">
                    <th class="whitespace-nowrap px-3 py-3">Dia</th>
                    <th class="whitespace-nowrap px-2 py-3">Entrada 1</th>
                    <th class="whitespace-nowrap px-2 py-3">Saída 1</th>
                    <th class="whitespace-nowrap px-2 py-3">Entrada 2</th>
                    <th class="whitespace-nowrap px-2 py-3">Saída 2</th>
                    <th class="whitespace-nowrap px-2 py-3">Total</th>
                    <th class="whitespace-nowrap px-2 py-3 text-center">Almoço livre</th>
                    <th class="whitespace-nowrap px-2 py-3 text-center">Compensado</th>
                    <th class="whitespace-nowrap px-2 py-3 text-center">Neutro</th>
                    <th class="whitespace-nowrap px-2 py-3 text-center">
                        <span class="block">Not.</span>
                        <button type="button" title="Preencher segunda a sexta (08–12 / 13–18; sexta até 17h)" data-horario-padrao class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-brand-burgundy/30 bg-brand-burgundy-soft text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                        </button>
                    </th>
                    <th class="whitespace-nowrap px-3 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach (range(1, 7) as $d)
                    @php
                        /** @var \App\Models\HorarioEscalaDia $dia */
                        $dia = $diasPorSemana[$d] ?? new \App\Models\HorarioEscalaDia(['dia_semana' => $d]);
                        $e1 = old("dias.$d.entrada_1", $fmtTime($dia->entrada_1));
                        $s1 = old("dias.$d.saida_1", $fmtTime($dia->saida_1));
                        $e2 = old("dias.$d.entrada_2", $fmtTime($dia->entrada_2));
                        $s2 = old("dias.$d.saida_2", $fmtTime($dia->saida_2));
                        $al = (string) old("dias.$d.almoco_livre", $dia->almoco_livre ? '1' : '0');
                        $cp = (string) old("dias.$d.compensado", $dia->compensado ? '1' : '0');
                        $nt = (string) old("dias.$d.neutro", $dia->neutro ? '1' : '0');
                        $no = (string) old("dias.$d.noturno", $dia->noturno ? '1' : '0');
                    @endphp
                    <tr class="bg-white transition hover:bg-brand-gray-soft/30" data-horario-dia-row="{{ $d }}">
                        <td class="whitespace-nowrap px-3 py-2 font-semibold text-brand-black">{{ $diasNomes[$d] }}</td>
                        <td class="px-2 py-2">
                            <input type="time" name="dias[{{ $d }}][entrada_1]" value="{{ $e1 }}" step="60" data-horario-field="e1" class="w-[7.25rem] rounded border border-zinc-200 px-2 py-1.5 text-sm font-medium text-brand-black focus:border-brand-burgundy focus:outline-none focus:ring-1 focus:ring-brand-burgundy/30">
                            @error("dias.$d.entrada_1")
                                <p class="mt-0.5 text-[10px] font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </td>
                        <td class="px-2 py-2">
                            <input type="time" name="dias[{{ $d }}][saida_1]" value="{{ $s1 }}" step="60" data-horario-field="s1" class="w-[7.25rem] rounded border border-zinc-200 px-2 py-1.5 text-sm font-medium text-brand-black focus:border-brand-burgundy focus:outline-none focus:ring-1 focus:ring-brand-burgundy/30">
                            @error("dias.$d.saida_1")
                                <p class="mt-0.5 text-[10px] font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </td>
                        <td class="px-2 py-2">
                            <input type="time" name="dias[{{ $d }}][entrada_2]" value="{{ $e2 }}" step="60" data-horario-field="e2" class="w-[7.25rem] rounded border border-zinc-200 px-2 py-1.5 text-sm font-medium text-brand-black focus:border-brand-burgundy focus:outline-none focus:ring-1 focus:ring-brand-burgundy/30">
                            @error("dias.$d.entrada_2")
                                <p class="mt-0.5 text-[10px] font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </td>
                        <td class="px-2 py-2">
                            <input type="time" name="dias[{{ $d }}][saida_2]" value="{{ $s2 }}" step="60" data-horario-field="s2" class="w-[7.25rem] rounded border border-zinc-200 px-2 py-1.5 text-sm font-medium text-brand-black focus:border-brand-burgundy focus:outline-none focus:ring-1 focus:ring-brand-burgundy/30">
                            @error("dias.$d.saida_2")
                                <p class="mt-0.5 text-[10px] font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </td>
                        <td class="px-2 py-2">
                            <span class="inline-block min-w-[3.5rem] rounded border border-zinc-100 bg-zinc-50 px-2 py-1.5 text-center text-sm font-bold text-brand-black" data-horario-dia-total>0:00</span>
                        </td>
                        <td class="px-2 py-2 text-center align-middle">
                            <input type="hidden" name="dias[{{ $d }}][almoco_livre]" value="0">
                            <input type="checkbox" name="dias[{{ $d }}][almoco_livre]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($al === '1')>
                        </td>
                        <td class="px-2 py-2 text-center align-middle">
                            <input type="hidden" name="dias[{{ $d }}][compensado]" value="0">
                            <input type="checkbox" name="dias[{{ $d }}][compensado]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($cp === '1')>
                        </td>
                        <td class="px-2 py-2 text-center align-middle">
                            <input type="hidden" name="dias[{{ $d }}][neutro]" value="0">
                            <input type="checkbox" name="dias[{{ $d }}][neutro]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($nt === '1')>
                        </td>
                        <td class="px-2 py-2 text-center align-middle">
                            <input type="hidden" name="dias[{{ $d }}][noturno]" value="0">
                            <input type="checkbox" name="dias[{{ $d }}][noturno]" value="1" class="h-4 w-4 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($no === '1')>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-right">
                            <button type="button" title="Copiar do dia anterior" data-horario-copy-prev class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-200 text-brand-burgundy transition hover:border-brand-burgundy hover:bg-brand-burgundy-soft" @if ($d === 1) disabled aria-disabled="true" @endif>
                                <i data-lucide="arrow-down" class="h-4 w-4"></i>
                            </button>
                            <button type="button" title="Limpar linha" data-horario-clear-row class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-600 transition hover:bg-red-50">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-zinc-200 bg-brand-gray-soft/50 font-bold text-brand-black">
                    <td colspan="5" class="px-3 py-3 text-right text-sm">Total semanal</td>
                    <td class="px-2 py-3">
                        <span class="inline-block min-w-[3.5rem] text-sm" data-horario-semana-total>0:00</span>
                    </td>
                    <td colspan="5"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @error('dias')
        <p class="px-5 py-2 text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</section>

@push('scripts')
    <script>
        (function () {
            const table = document.querySelector('[data-horario-escala-table]');
            if (!table) return;

            function parseTime(v) {
                if (!v || typeof v !== 'string') return null;
                const p = v.trim().split(':');
                if (p.length < 2) return null;
                const h = parseInt(p[0], 10);
                const m = parseInt(p[1], 10);
                if (Number.isNaN(h) || Number.isNaN(m)) return null;
                return h * 60 + m;
            }

            function segmentMinutes(start, end) {
                const a = parseTime(start);
                const b = parseTime(end);
                if (a === null || b === null) return 0;
                let diff = b - a;
                if (diff < 0) diff += 24 * 60;
                return diff;
            }

            function formatHhMm(totalMin) {
                const h = Math.floor(totalMin / 60);
                const m = totalMin % 60;
                return h + ':' + String(m).padStart(2, '0');
            }

            function rowMinutes(tr) {
                const e1 = tr.querySelector('[data-horario-field="e1"]')?.value || '';
                const s1 = tr.querySelector('[data-horario-field="s1"]')?.value || '';
                const e2 = tr.querySelector('[data-horario-field="e2"]')?.value || '';
                const s2 = tr.querySelector('[data-horario-field="s2"]')?.value || '';
                return segmentMinutes(e1, s1) + segmentMinutes(e2, s2);
            }

            function refresh() {
                let semana = 0;
                table.querySelectorAll('tbody tr[data-horario-dia-row]').forEach((tr) => {
                    const min = rowMinutes(tr);
                    semana += min;
                    const el = tr.querySelector('[data-horario-dia-total]');
                    if (el) el.textContent = formatHhMm(min);
                });
                const foot = table.querySelector('[data-horario-semana-total]');
                if (foot) foot.textContent = formatHhMm(semana);
            }

            table.addEventListener('input', (e) => {
                if (e.target?.matches?.('input[type="time"]')) refresh();
            });
            table.addEventListener('change', (e) => {
                if (e.target?.matches?.('input[type="time"], input[type="checkbox"]')) refresh();
            });

            const flagCb = (tr, flag) => tr.querySelector('input[type="checkbox"][name$="[' + flag + ']"]');

            table.querySelector('[data-horario-padrao]')?.addEventListener('click', () => {
                const rows = table.querySelectorAll('tbody tr[data-horario-dia-row]');
                rows.forEach((tr) => {
                    const d = parseInt(tr.getAttribute('data-horario-dia-row'), 10);
                    const set = (sel, v) => {
                        const inp = tr.querySelector(sel);
                        if (inp) inp.value = v;
                    };
                    if (d >= 1 && d <= 4) {
                        set('[data-horario-field="e1"]', '08:00');
                        set('[data-horario-field="s1"]', '12:00');
                        set('[data-horario-field="e2"]', '13:00');
                        set('[data-horario-field="s2"]', '18:00');
                        const al = flagCb(tr, 'almoco_livre');
                        if (al) al.checked = true;
                    } else if (d === 5) {
                        set('[data-horario-field="e1"]', '08:00');
                        set('[data-horario-field="s1"]', '12:00');
                        set('[data-horario-field="e2"]', '13:00');
                        set('[data-horario-field="s2"]', '17:00');
                        const al = flagCb(tr, 'almoco_livre');
                        if (al) al.checked = true;
                    } else {
                        set('[data-horario-field="e1"]', '');
                        set('[data-horario-field="s1"]', '');
                        set('[data-horario-field="e2"]', '');
                        set('[data-horario-field="s2"]', '');
                        ['almoco_livre', 'compensado', 'neutro', 'noturno'].forEach((f) => {
                            const cb = flagCb(tr, f);
                            if (cb) cb.checked = false;
                        });
                    }
                });
                refresh();
            });

            table.querySelectorAll('tbody tr[data-horario-dia-row]').forEach((tr) => {
                tr.querySelector('[data-horario-copy-prev]')?.addEventListener('click', () => {
                    const d = parseInt(tr.getAttribute('data-horario-dia-row'), 10);
                    if (d <= 1) return;
                    const prev = table.querySelector('tbody tr[data-horario-dia-row="' + (d - 1) + '"]');
                    if (!prev) return;
                    ['e1', 's1', 'e2', 's2'].forEach((key) => {
                        const from = prev.querySelector('[data-horario-field="' + key + '"]');
                        const to = tr.querySelector('[data-horario-field="' + key + '"]');
                        if (from && to) to.value = from.value;
                    });
                    ['almoco_livre', 'compensado', 'neutro', 'noturno'].forEach((namePart) => {
                        const fromCb = flagCb(prev, namePart);
                        const toCb = flagCb(tr, namePart);
                        if (fromCb && toCb) toCb.checked = fromCb.checked;
                    });
                    refresh();
                });
                tr.querySelector('[data-horario-clear-row]')?.addEventListener('click', () => {
                    tr.querySelectorAll('input[type="time"]').forEach((inp) => {
                        inp.value = '';
                    });
                    tr.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
                        cb.checked = false;
                    });
                    refresh();
                });
            });

            refresh();
        })();
    </script>
@endpush
