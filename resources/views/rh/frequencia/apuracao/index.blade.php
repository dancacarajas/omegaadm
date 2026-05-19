@extends('layouts.app')

@section('title', 'Apuração do Ponto - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Apuração do Ponto')

@section('content')
    @php
        $classeLinha = fn (array $linha) => match ($linha['tipo_visual'] ?? 'normal') {
            'folga' => 'text-sky-700',
            'falta' => 'text-red-600 font-semibold',
            'justificado', 'feriado' => 'text-brand-black',
            'incompleto' => 'text-amber-700',
            default => 'text-brand-black',
        };
        $classeBatida = fn (array $linha, string $campo) => match ($linha['tipo_visual'] ?? 'normal') {
            'folga' => 'text-sky-600 font-medium',
            'falta' => 'text-red-600',
            default => 'text-brand-black',
        };
    @endphp

    <form method="GET" action="{{ route('rh.frequencia.apuracao.index') }}" id="form-apuracao" class="space-y-4">
        <input type="hidden" name="colaborador_id" value="{{ $colaboradorId }}" id="input-colaborador-id">

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3">
                <button type="button" data-toggle-filtros class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold text-brand-gray hover:bg-zinc-50">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Mostrar filtros
                </button>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-shift-period="-1" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 text-brand-gray hover:bg-zinc-50" title="Período anterior">
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </button>
                    <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-semibold text-brand-black">
                        <span class="text-brand-gray">De:</span>
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="border-0 bg-transparent p-0 text-sm font-semibold outline-none">
                        <span class="text-brand-gray">a</span>
                        <input type="date" name="data_fim" value="{{ $dataFim }}" class="border-0 bg-transparent p-0 text-sm font-semibold outline-none">
                    </div>
                    <button type="button" data-shift-period="1" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-zinc-200 text-brand-gray hover:bg-zinc-50" title="Próximo período">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white hover:bg-brand-burgundy-dark">
                        Aplicar
                    </button>
                </div>
            </div>

            <div data-painel-filtros class="hidden border-b border-zinc-200 bg-zinc-50/80 px-4 py-4">
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <label class="space-y-1 text-xs font-bold uppercase text-brand-gray">
                        Departamento
                        <select name="departamento" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-2 text-sm">
                            <option value="">Todos</option>
                            @foreach ($departamentos as $dep)
                                <option value="{{ $dep }}" @selected(request('departamento') === $dep)>{{ $dep }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1 text-xs font-bold uppercase text-brand-gray">
                        Centro de custo
                        <select name="centro_custo" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-2 text-sm">
                            <option value="">Todos</option>
                            @foreach ($centrosCusto as $cc)
                                <option value="{{ $cc }}" @selected(request('centro_custo') === $cc)>{{ $cc }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1 text-xs font-bold uppercase text-brand-gray">
                        Cargo
                        <select name="cargo" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-2 text-sm">
                            <option value="">Todos</option>
                            @foreach ($cargos as $c)
                                <option value="{{ $c }}" @selected(request('cargo') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1 text-xs font-bold uppercase text-brand-gray">
                        Horário (escala)
                        <select name="horario_escala_id" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-2 text-sm">
                            <option value="">Todos</option>
                            @foreach ($horarios as $h)
                                <option value="{{ $h->id }}" @selected((int) request('horario_escala_id') === $h->id)>{{ $h->nome }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1 text-xs font-bold uppercase text-brand-gray">
                        Buscar funcionário
                        <input type="search" name="busca" value="{{ $buscaColaborador }}" placeholder="Nome, matrícula ou CPF" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm">
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-zinc-100 px-4 py-2">
                @foreach ($filtrosAtivos as $tag)
                    <span class="rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-brand-gray">
                        {{ $tag['label'] }} ({{ $tag['valor'] }})
                    </span>
                @endforeach
            </div>
        </section>

        <section class="grid min-h-[32rem] gap-4 xl:grid-cols-[320px_1fr]">
            <aside class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 p-4">
                    <label class="text-xs font-bold uppercase text-brand-gray">Selecione</label>
                    <input type="search" id="busca-lista-colaborador" placeholder="Filtrar na lista…" class="mt-2 h-10 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </div>
                <div class="max-h-[calc(100vh-16rem)] overflow-y-auto p-2" id="lista-colaboradores">
                    @forelse ($colaboradores as $item)
                        <button
                            type="submit"
                            name="colaborador_id"
                            value="{{ $item->id }}"
                            data-colaborador-card
                            data-nome="{{ mb_strtolower($item->nome) }}"
                            data-matricula="{{ $item->matricula }}"
                            class="mb-2 w-full rounded-lg border px-3 py-3 text-left transition {{ (int) $colaboradorId === (int) $item->id ? 'border-brand-burgundy bg-brand-burgundy-soft ring-1 ring-brand-burgundy/30' : 'border-zinc-200 hover:border-zinc-300 hover:bg-zinc-50' }}"
                        >
                            <p class="text-sm font-bold text-brand-black">{{ $item->nome }}</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-brand-gray">
                                @if ($item->rg)
                                    <span>RG: {{ $item->rg }}</span><br>
                                @endif
                                @if ($item->cpf)
                                    <span>CPF: {{ $item->cpf }}</span><br>
                                @endif
                                <span>Registro: {{ $item->matricula ?: '—' }}</span>
                            </p>
                        </button>
                    @empty
                        <p class="p-4 text-sm text-brand-gray">Nenhum colaborador encontrado com os filtros atuais.</p>
                    @endforelse
                </div>
            </aside>

            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                @if ($colaborador && $cartao)
                    @if ($resumoRegistros && $resumoRegistros['com_batida'] === 0)
                        <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <p class="font-semibold">Nenhuma batida gravada neste período para este colaborador.</p>
                            <p class="mt-1 text-xs text-amber-800">
                                Registros no banco: {{ $resumoRegistros['total'] }}.
                                @if (($resumoRegistros['csv_ponto'] ?? 0) === 0)
                                    Nenhum dia importado via CSV.
                                @endif
                                Importe o CSV com o período {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
                                e confira matrícula/CPF (cadastro: mat. {{ $colaborador->matricula ?: '—' }}, CPF {{ $colaborador->cpf ?: '—' }}).
                            </p>
                            <a href="{{ route('rh.frequencia.index', ['csv_data_inicio' => $dataInicio, 'csv_data_fim' => $dataFim]) }}" class="mt-2 inline-flex text-xs font-bold text-brand-burgundy underline">Importar CSV</a>
                        </div>
                    @elseif ($resumoRegistros && ($resumoRegistros['csv_ponto'] ?? 0) > 0)
                        <div class="border-b border-emerald-100 bg-emerald-50/80 px-4 py-2 text-xs font-semibold text-emerald-800">
                            {{ $resumoRegistros['com_batida'] }} dia(s) com batida · {{ $resumoRegistros['csv_ponto'] }} via CSV
                        </div>
                    @endif
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3">
                        <div>
                            <p class="text-lg font-bold text-brand-black">{{ $colaborador->nome }}</p>
                            <p class="text-xs text-brand-gray">{{ $colaborador->cargo ?: 'Cargo não informado' }} · Mat. {{ $colaborador->matricula ?: '—' }}</p>
                        </div>
                        <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-1 text-xs font-bold">
                            <span class="rounded-md bg-white px-3 py-1.5 text-brand-burgundy shadow-sm">Apuração</span>
                            <a href="{{ route('rh.frequencia.index', ['busca' => $colaborador->matricula ?: $colaborador->nome]) }}" class="rounded-md px-3 py-1.5 text-brand-gray hover:text-brand-black">Ajustar no ponto diário</a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-zinc-50 text-[10px] font-bold uppercase tracking-wide text-brand-gray">
                                <tr>
                                    <th class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-left">Data / Dia</th>
                                    <th class="px-2 py-2 text-center w-14">Ok</th>
                                    <th class="px-2 py-2 text-center w-14">Editar</th>
                                    <th class="px-2 py-2 text-center">Ent. 1</th>
                                    <th class="px-2 py-2 text-center">Sai. 1</th>
                                    <th class="px-2 py-2 text-center">Ent. 2</th>
                                    <th class="px-2 py-2 text-center">Sai. 2</th>
                                    <th class="px-2 py-2 text-center">Total trab.</th>
                                    <th class="px-2 py-2 text-center">Dia falta</th>
                                    <th class="px-2 py-2 text-center">Horas falta</th>
                                    <th class="px-2 py-2 text-center">Horas extras</th>
                                    <th class="px-2 py-2 text-center">Faltas/atrasos</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($cartao['linhas'] as $linha)
                                    @php
                                        $hoje = $linha['data_ymd'] === today()->toDateString();
                                        $rowBg = $hoje ? 'bg-emerald-50/60' : '';
                                    @endphp
                                    <tr class="{{ $rowBg }} hover:bg-zinc-50/80">
                                        <td class="sticky left-0 z-10 whitespace-nowrap bg-inherit px-3 py-2 font-semibold {{ $classeLinha($linha) }}">
                                            {{ $linha['dia'] }}
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            @if ($linha['apurado'] ?? false)
                                                <i data-lucide="check-circle" class="mx-auto h-4 w-4 text-emerald-600"></i>
                                            @else
                                                <i data-lucide="x-circle" class="mx-auto h-4 w-4 text-red-500"></i>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            @if ($linha['registro_id'])
                                                <a href="{{ route('rh.frequencia.index', ['data' => $linha['data_ymd'], 'busca' => $colaborador->matricula ?: $colaborador->nome]) }}" class="inline-flex text-brand-burgundy hover:text-brand-burgundy-dark" title="Editar no ponto diário">
                                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                                </a>
                                            @endif
                                        </td>
                                        @foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo)
                                            <td class="whitespace-nowrap px-2 py-2 text-center {{ $classeBatida($linha, $campo) }}">
                                                {{ $linha[$campo] ?: '—' }}
                                            </td>
                                        @endforeach
                                        <td class="px-2 py-2 text-center text-brand-black">{{ $linha['total_trabalhado'] ?: '—' }}</td>
                                        <td class="px-2 py-2 text-center {{ ($linha['dia_falta'] ?? '') !== '' ? 'text-red-600 font-semibold' : '' }}">{{ $linha['dia_falta'] ?: '—' }}</td>
                                        <td class="px-2 py-2 text-center {{ ($linha['horas_falta'] ?? '') !== '' ? 'text-red-600' : '' }}">{{ $linha['horas_falta'] ?: '—' }}</td>
                                        <td class="px-2 py-2 text-center text-brand-black">{{ $linha['extras_total'] ?: '—' }}</td>
                                        <td class="px-2 py-2 text-center text-brand-black">{{ $linha['falta_atraso'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-zinc-100 text-xs font-bold text-brand-black">
                                <tr>
                                    <td class="sticky left-0 z-10 bg-zinc-100 px-3 py-2">TOTAL</td>
                                    <td colspan="6"></td>
                                    <td class="px-2 py-2 text-center">{{ $cartao['totais']['trabalhado'] ?: '—' }}</td>
                                    <td class="px-2 py-2 text-center text-red-600">{{ $cartao['totais']['dia_falta'] ?: '—' }}</td>
                                    <td class="px-2 py-2 text-center text-red-600">{{ $cartao['totais']['horas_falta'] ?: '—' }}</td>
                                    <td class="px-2 py-2 text-center">{{ $cartao['totais']['extras'] ?: '—' }}</td>
                                    <td class="px-2 py-2 text-center">{{ $cartao['totais']['falta_atraso'] ?: '—' }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @elseif ($colaborador)
                    <div class="p-10 text-center text-sm text-brand-gray">Não foi possível montar a apuração para o período.</div>
                @else
                    <div class="p-10 text-center text-sm text-brand-gray">Selecione um colaborador na lista ao lado ou ajuste os filtros.</div>
                @endif
            </div>
        </section>

        <div class="flex justify-start">
            <a href="{{ route('rh.frequencia.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-gray hover:bg-zinc-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Voltar
            </a>
        </div>
    </form>

    @push('scripts')
        <script>
            document.querySelector('[data-toggle-filtros]')?.addEventListener('click', function () {
                const painel = document.querySelector('[data-painel-filtros]');
                painel?.classList.toggle('hidden');
            });

            document.getElementById('busca-lista-colaborador')?.addEventListener('input', function () {
                const termo = this.value.trim().toLowerCase();
                document.querySelectorAll('[data-colaborador-card]').forEach(function (card) {
                    const nome = card.dataset.nome || '';
                    const mat = (card.dataset.matricula || '').toLowerCase();
                    card.classList.toggle('hidden', termo !== '' && !nome.includes(termo) && !mat.includes(termo));
                });
            });

            document.querySelectorAll('[data-shift-period]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const dir = parseInt(btn.dataset.shiftPeriod, 10);
                    const ini = document.querySelector('input[name="data_inicio"]');
                    const fim = document.querySelector('input[name="data_fim"]');
                    if (!ini?.value || !fim?.value) return;
                    const dIni = new Date(ini.value + 'T12:00:00');
                    const dFim = new Date(fim.value + 'T12:00:00');
                    const dias = Math.round((dFim - dIni) / 86400000) + 1;
                    dIni.setDate(dIni.getDate() + dir * dias);
                    dFim.setDate(dFim.getDate() + dir * dias);
                    ini.value = dIni.toISOString().slice(0, 10);
                    fim.value = dFim.toISOString().slice(0, 10);
                });
            });

            document.querySelectorAll('[data-colaborador-card]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('input-colaborador-id').value = btn.value;
                });
            });
        </script>
    @endpush
@endsection
