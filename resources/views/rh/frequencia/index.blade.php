@extends('layouts.app')

@section('title', 'Frequência - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Frequência')

@section('content')
    @php
        $statusLabel = [
            'presente' => 'Presente',
            'falta' => 'Falta',
            'folga' => 'Folga (escala)',
            'justificado' => 'Justificado',
            'incompleto' => 'Incompleto',
        ];
        $statusClass = [
            'presente' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'falta' => 'border-red-200 bg-red-50 text-red-700',
            'folga' => 'border-sky-200 bg-sky-50 text-sky-800',
            'justificado' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
            'incompleto' => 'border-amber-200 bg-amber-50 text-amber-700',
        ];
        $fmtHora = fn ($t) => $t ? substr((string) $t, 0, 5) : '';
    @endphp

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                <i data-lucide="users" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Efetivo ativo</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['colaboradores'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <i data-lucide="circle-check" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Presentes no dia</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['presentes'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-700">
                <i data-lucide="calendar-x" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-brand-gray">Faltas no dia</p>
            <p class="mt-1 text-3xl font-bold text-brand-black">{{ $indicadores['faltas'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-brand-gray p-5 text-white shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/20 text-white">
                <i data-lucide="file-check-2" class="h-5 w-5"></i>
            </div>
            <p class="mt-5 text-sm font-semibold text-white/80">Justificados</p>
            <p class="mt-1 text-3xl font-bold">{{ $indicadores['justificados'] }}</p>
        </article>
    </section>

    @php
        $exportInicio = request('data_inicio', $data ?? ($absenteismo['inicio'] ?? now()->startOfMonth()->toDateString()));
        $exportFim = request('data_fim', $data ?? ($absenteismo['fim'] ?? now()->endOfMonth()->toDateString()));
    @endphp

    <nav class="mb-5 flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm" aria-label="Atalhos da página">
        <span class="mr-1 text-xs font-bold uppercase tracking-wide text-brand-gray">Ir para:</span>
        <a href="#ponto-diario" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-burgundy px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-burgundy-dark">
            <i data-lucide="clock" class="h-3.5 w-3.5"></i>
            Ponto diário
        </a>
        <a href="#analise-periodo" class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-black hover:bg-zinc-50">
            <i data-lucide="percent" class="h-3.5 w-3.5 text-brand-burgundy"></i>
            Absenteísmo
        </a>
        <a href="{{ route('rh.frequencia.apuracao.index', ['data_inicio' => $absenteismo['inicio'], 'data_fim' => $absenteismo['fim']]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-black hover:bg-zinc-50">
            <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-brand-burgundy"></i>
            Apuração
        </a>
        <a href="#ferramentas" class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-black hover:bg-zinc-50">
            <i data-lucide="wrench" class="h-3.5 w-3.5 text-brand-burgundy"></i>
            Importação / PDF
        </a>
    </nav>

    <section id="ponto-diario" class="mb-5 scroll-mt-4 rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="space-y-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/60 p-5">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Operação do dia</p>
                <h2 class="text-xl font-bold text-brand-black">Ponto diário do efetivo</h2>
                <p class="mt-1 text-sm text-brand-gray">Acompanhe marcações, faltas, atestados e justificativas por data.</p>
                <p class="mt-2 max-w-3xl rounded-lg border border-brand-burgundy/15 bg-brand-burgundy-soft/40 px-3 py-2 text-xs font-medium text-brand-burgundy">
                    <strong>Editar ou apagar batidas:</strong> use a coluna <strong>Marcações</strong> à esquerda; resumo e justificativa ficam à direita.
                </p>
            </div>

            <form method="GET" class="rounded-xl border border-zinc-200/80 bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <label class="space-y-1.5 lg:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Data</span>
                        <input type="date" name="data" value="{{ $data }}" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <label class="space-y-1.5 lg:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Mês ref.</span>
                        <input type="month" name="mes" value="{{ $mes }}" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <label class="space-y-1.5 sm:col-span-2 lg:col-span-3">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Buscar</span>
                        <span class="relative block">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                            <input name="busca" value="{{ request('busca') }}" placeholder="Nome ou matrícula…" class="h-10 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        </span>
                    </label>
                    <label class="space-y-1.5 lg:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Função</span>
                        <select name="cargo" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            <option value="">Todas</option>
                            @foreach ($funcoes ?? [] as $funcao)
                                <option value="{{ $funcao }}" @selected(request('cargo') === $funcao)>{{ $funcao }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1.5 lg:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-brand-gray">Ordenar</span>
                        <select name="ordenacao" class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm text-brand-black outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            <option value="prioridade" @selected(($ordenacao ?? request('ordenacao', 'prioridade')) === 'prioridade')>Faltas primeiro</option>
                            <option value="alfabetica" @selected(($ordenacao ?? request('ordenacao')) === 'alfabetica')>A–Z</option>
                        </select>
                    </label>
                    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                        <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-burgundy-dark">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            <span class="hidden sm:inline">Aplicar</span>
                        </button>
                    </div>
                </div>
                @if (request()->hasAny(['absenteismo_inicio', 'absenteismo_fim', 'absenteismo_colaborador_id', 'absenteismo_calcular']))
                    <input type="hidden" name="absenteismo_inicio" value="{{ $absenteismo['inicio'] }}">
                    <input type="hidden" name="absenteismo_fim" value="{{ $absenteismo['fim'] }}">
                    @if ($absenteismoColaboradorId ?? null)
                        <input type="hidden" name="absenteismo_colaborador_id" value="{{ $absenteismoColaboradorId }}">
                    @endif
                    @if (request()->boolean('absenteismo_calcular'))
                        <input type="hidden" name="absenteismo_calcular" value="1">
                    @endif
                @endif
            </form>
        </div>

        <div class="overflow-x-auto overflow-y-visible">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Colaborador</th>
                        <th class="w-[6.5rem] px-4 py-4">Data</th>
                        <th class="min-w-[32rem] bg-brand-burgundy-soft/30 px-4 py-4 text-brand-burgundy">Marcações (editar / limpar)</th>
                        <th class="min-w-[22rem] px-4 py-4">Resumo, status e justificativa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($registros as $registro)
                        @php
                            $calcHoras = \App\Support\FrequenciaCalculo::resumo($registro);
                            $avaliacaoPonto = app(\App\Support\EscalaPontoRegras::class)->avaliarMarcacao(
                                $registro->colaborador,
                                $registro->data,
                                true
                            );
                            $pontoBloqueado = ! $avaliacaoPonto['permitido'];
                        @endphp
                        <tr class="align-top overflow-visible transition hover:bg-brand-gray-soft/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-sm font-bold text-brand-burgundy">
                                        {{ mb_substr($registro->colaborador->nome, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $registro->colaborador->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $registro->colaborador->matricula ?: 'Sem matrícula' }} · {{ $registro->colaborador->cargo ?: 'Cargo não informado' }}</p>
                                        @php
                                            $diaEscala = $registro->colaborador->horarioEscalaDiaNaData($registro->data);
                                        @endphp
                                        @if ($registro->colaborador->horarioEscala)
                                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-brand-burgundy">
                                                Escala: {{ $registro->colaborador->horarioEscala->nome }}
                                                @if ($registro->colaborador->horarioEscala->isRotativaSemanal())
                                                    · rotativa semanal · grupo {{ (int) $registro->colaborador->horario_escala_ciclo_offset }}
                                                @elseif ($registro->colaborador->horarioEscala->isRotativa())
                                                    · rotativa · fase {{ (int) $registro->colaborador->horario_escala_ciclo_offset }}
                                                @endif
                                            </p>
                                            <p class="text-[10px] text-brand-gray">Previsto hoje: {{ $diaEscala?->textoGrade() ?? '—' }}</p>
                                            @if ($pontoBloqueado)
                                                <p class="mt-1 text-[10px] font-bold text-amber-800">{{ $avaliacaoPonto['motivo'] }}</p>
                                            @endif
                                        @else
                                            <p class="mt-1 text-[10px] text-brand-gray">Sem escala — usa jornada padrão ({{ \App\Support\FrequenciaCalculo::formatarMinutos(\App\Support\FrequenciaCalculo::jornadaMinutosEsperados()) }})</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $registro->data?->format('d/m/Y') }}</td>
                            <td class="overflow-visible bg-brand-burgundy-soft/10 px-4 py-4 align-top">
                                @php
                                    $temBatida = collect(['entrada_1', 'saida_1', 'entrada_2', 'saida_2'])
                                        ->contains(fn ($c) => ! \App\Support\FrequenciaCalculo::horarioArmazenadoVazio($registro->{$c}));
                                    $origemLabel = match ($registro->origem) {
                                        'app_colaborador' => 'App colaborador',
                                        'manual' => 'Manual RH',
                                        'afd' => 'Importação AFD',
                                        'csv_ponto' => 'Importação CSV (ponto)',
                                        'grade' => 'Grade automática',
                                        'feriado' => 'Feriado cadastrado',
                                        default => strtoupper((string) $registro->origem),
                                    };
                                @endphp
                                <p class="mb-3 text-[10px] font-bold uppercase tracking-wide text-brand-gray">Origem: {{ $origemLabel }}</p>
                                <form method="POST" action="{{ route('rh.frequencia.marcacao', $registro) }}" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                                        @foreach (['entrada_1' => 'Entrada', 'saida_1' => 'Saída 1 (alm.)', 'entrada_2' => 'Entrada 2 (alm.)', 'saida_2' => 'Saída final'] as $field => $label)
                                            <label class="block min-w-[7.5rem] text-[10px] font-bold uppercase tracking-wide text-brand-gray">
                                                <span class="mb-2 block leading-snug">{{ $label }}</span>
                                                <input type="time" name="{{ $field }}" value="{{ $fmtHora($registro->{$field}) }}" step="60" @disabled($pontoBloqueado) class="freq-marcacao-time h-12 w-full min-h-12 min-w-[7.5rem] box-border rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold leading-normal text-brand-black outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-400">
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($errors->has('marcacao') && (int) old('_registro_id') === $registro->id)
                                        <p class="text-[10px] font-bold text-red-600">{{ $errors->first('marcacao') }}</p>
                                    @endif
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" @disabled($pontoBloqueado) class="inline-flex h-9 flex-1 items-center justify-center gap-1 rounded-lg bg-brand-burgundy px-3 text-xs font-bold text-white shadow-sm transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:text-zinc-500 sm:flex-none sm:min-w-[9rem]">
                                            <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                            {{ $pontoBloqueado ? 'Bloqueado' : 'Salvar marcações' }}
                                        </button>
                                        @if ($temBatida)
                                            <button
                                                type="submit"
                                                form="limpar-marcacoes-{{ $registro->id }}"
                                                class="inline-flex h-9 items-center justify-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-bold text-red-700 transition hover:bg-red-100"
                                                onclick="return confirm('Remover todas as batidas deste dia para {{ addslashes($registro->colaborador->nome) }}?');"
                                            >
                                                <i data-lucide="eraser" class="h-3.5 w-3.5"></i>
                                                Limpar batidas do dia
                                            </button>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-brand-gray">Deixe um campo em branco e salve para apagar só aquela batida.</p>
                                    <input type="hidden" name="_registro_id" value="{{ $registro->id }}">
                                </form>
                                @if ($temBatida)
                                    <form id="limpar-marcacoes-{{ $registro->id }}" method="POST" action="{{ route('rh.frequencia.limpar-marcacoes', $registro) }}" class="hidden">
                                        @csrf
                                    </form>
                                @endif
                            </td>
                            <td class="overflow-visible px-4 py-4 align-top">
                                {{-- Linha 1: totais e status --}}
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-lg border border-zinc-200 bg-white px-3 py-2.5">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gray">Horas trabalhadas</p>
                                        <p class="mt-1 text-sm font-bold text-brand-black">{{ $calcHoras['trabalhadas_fmt'] }}</p>
                                        <p class="mt-0.5 text-[10px] text-brand-gray">Soma dos intervalos</p>
                                    </div>
                                    <div class="rounded-lg border border-zinc-200 bg-white px-3 py-2.5">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gray">Horas falta</p>
                                        <p class="mt-1 text-sm font-bold {{ ($calcHoras['falta'] ?? 0) > 0 ? 'text-red-700' : 'text-brand-gray' }}">{{ $calcHoras['falta_fmt'] }}</p>
                                        <p class="mt-0.5 text-[10px] text-brand-gray">Vs. {{ $calcHoras['jornada_esperada_fmt'] ?? 'jornada' }}</p>
                                    </div>
                                    <div class="rounded-lg border border-zinc-200 bg-white px-3 py-2.5">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gray">Horas extras</p>
                                        <p class="mt-1 text-sm font-bold {{ $calcHoras['extras'] > 0 ? 'text-emerald-700' : 'text-brand-gray' }}">{{ $calcHoras['extras_fmt'] }}</p>
                                        <p class="mt-0.5 text-[10px] text-brand-gray">Acima da jornada</p>
                                    </div>
                                    <div class="rounded-lg border border-zinc-200 bg-white px-3 py-2.5">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gray">Status</p>
                                        <span class="mt-1.5 inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass[$registro->status] ?? $statusClass['falta'] }}">
                                            {{ $statusLabel[$registro->status] ?? $registro->status }}
                                        </span>
                                        <p class="mt-1.5 text-[10px] text-brand-gray">Origem: {{ strtoupper($registro->origem) }}</p>
                                    </div>
                                </div>

                                {{-- Linha 2: justificativa registrada + formulário --}}
                                <div class="mt-4 grid gap-3 border-t border-zinc-200 pt-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                                    <div class="rounded-lg border border-zinc-200 bg-brand-gray-soft/30 px-3 py-2.5">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-brand-gray">Justificativa</p>
                                        <p class="mt-1 font-semibold text-brand-black">{{ $registro->justificativa_tipo ? ucfirst($registro->justificativa_tipo) : '—' }}</p>
                                        <p class="mt-1 text-xs leading-relaxed text-brand-gray">{{ $registro->justificativa_texto ?: 'Sem justificativa registrada.' }}</p>
                                        @if ($registro->anexo_path)
                                            <a href="{{ asset('storage/'.$registro->anexo_path) }}" target="_blank" class="mt-2 inline-flex text-xs font-bold text-brand-burgundy">Ver anexo</a>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('rh.frequencia.justificar', $registro) }}" enctype="multipart/form-data" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-[8.5rem_1fr] xl:grid-cols-[8.5rem_1fr_10rem_auto] xl:items-center">
                                        @csrf
                                        <select name="justificativa_tipo" class="h-10 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:col-span-2 xl:col-span-1">
                                            @foreach (['atestado' => 'Atestado', 'justificativa' => 'Justificativa', 'abono' => 'Abono', 'outro' => 'Outro'] as $value => $label)
                                                <option value="{{ $value }}" @selected($registro->justificativa_tipo === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input name="justificativa_texto" value="{{ $registro->justificativa_texto }}" placeholder="Descreva o motivo..." class="h-10 min-w-0 rounded-lg border border-zinc-200 bg-white px-3 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:col-span-2 xl:col-span-1">
                                        <input type="file" name="anexo" class="h-10 min-w-0 rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-xs text-brand-gray file:mr-2 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-2 file:py-1 file:text-xs file:font-semibold file:text-white sm:col-span-2 xl:col-span-1">
                                        <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark sm:col-span-2 xl:col-span-1">
                                            <i data-lucide="save" class="h-4 w-4"></i>
                                            Salvar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="clock" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum colaborador ativo no efetivo.</p>
                                <p class="mt-1 text-sm text-brand-gray">Cadastre colaboradores em RH / Efetivo para lançar frequência, ou verifique o filtro de data.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-5 py-3 text-xs text-brand-gray">
            <strong class="text-brand-black">Cálculo:</strong> horas trabalhadas = (Saída 1 − Entrada 1) + (Saída 2 − Entrada 2), <strong>somente com batidas registradas</strong>. <strong>Folga na escala</strong> (rotativa / dia sem jornada): jornada 0h, sem horas falta — dia abonado. Demais dias: jornada pela escala do efetivo. <strong>Horas extras</strong> só após a <strong>saída final</strong> registrada. Justificado: falta como “—”.
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $registros->links() }}
        </div>
    </section>

    <section id="analise-periodo" class="mb-5 scroll-mt-4 space-y-5">
        <div class="rounded-xl border border-zinc-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Gestão do período</p>
            <h2 class="text-xl font-bold text-brand-black">Absenteísmo e rankings</h2>
            <p class="mt-1 text-sm text-brand-gray">Defina o período com <strong>Calcular taxa</strong>. Os rankings usam as mesmas datas. Por padrão considera <strong>todo o efetivo ativo</strong>; no Painel Executivo (Indicadores mensais) o recorte é por <strong>contrato</strong>.</p>
        </div>
        <div class="grid gap-5 xl:grid-cols-[.95fr_1.05fr]">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Absenteísmo</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Taxa por período</h2>
                <p class="mt-1 text-sm text-brand-gray">Indicador gerencial por horas: atestados e abonos entram no absenteísmo geral; folgas e feriados não. A folha pode tratar faltas injustificadas à parte.</p>
            </div>
            <form method="GET" class="grid gap-3 p-5">
                <input type="hidden" name="data" value="{{ $data }}">
                <input type="hidden" name="mes" value="{{ $mes }}">
                <input type="hidden" name="absenteismo_calcular" value="1">
                @if (request('busca'))
                    <input type="hidden" name="busca" value="{{ request('busca') }}">
                @endif
                @if (request('cargo'))
                    <input type="hidden" name="cargo" value="{{ request('cargo') }}">
                @endif
                @if (request('ordenacao'))
                    <input type="hidden" name="ordenacao" value="{{ request('ordenacao') }}">
                @endif
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Colaborador</span>
                    <select name="absenteismo_colaborador_id" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                        <option value="">Todo o efetivo ativo</option>
                        @foreach ($colaboradoresAtivos as $c)
                            <option value="{{ $c->id }}" @selected(($absenteismoColaboradorId ?? null) === $c->id)>
                                {{ $c->nome }}{{ $c->matricula ? ' ('.$c->matricula.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Início</span>
                    <input type="date" name="absenteismo_inicio" value="{{ $absenteismo['inicio'] }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Fim</span>
                    <input type="date" name="absenteismo_fim" value="{{ $absenteismo['fim'] }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="calculator" class="h-4 w-4"></i>
                    Calcular taxa
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Resultado do período</p>
                    <h2 class="mt-1 text-xl font-bold text-brand-black">{{ \Carbon\Carbon::parse($absenteismo['inicio'])->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($absenteismo['fim'])->format('d/m/Y') }}</h2>
                    @if ($absenteismoColaborador ?? null)
                        <p class="mt-1 text-sm font-semibold text-brand-burgundy">{{ $absenteismoColaborador->nome }} · {{ $absenteismoColaborador->matricula ?: 'sem matrícula' }}</p>
                    @else
                        <p class="mt-1 text-sm text-brand-gray">Todo o efetivo ativo</p>
                    @endif
                </div>
                <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">{{ $absenteismo['dias'] }} dia(s)</span>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-amber-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Absenteísmo geral</p>
                    <p class="mt-2 text-3xl font-black text-amber-700">{{ number_format($absenteismo['taxa_geral'] ?? $absenteismo['taxa'], 1, ',', '.') }}%</p>
                    <p class="mt-1 text-[11px] text-amber-800">{{ number_format($absenteismo['horas_ausencia_geral'] ?? 0, 1, ',', '.') }}h ÷ {{ number_format($absenteismo['horas_previstas'] ?? 0, 1, ',', '.') }}h</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-800">Justificado</p>
                    <p class="mt-2 text-3xl font-black text-blue-900">{{ number_format($absenteismo['taxa_justificada'] ?? 0, 1, ',', '.') }}%</p>
                    <p class="mt-1 text-[11px] text-blue-800">{{ number_format($absenteismo['horas_ausencia_justificada'] ?? 0, 1, ',', '.') }}h</p>
                </div>
                <div class="rounded-xl bg-red-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-red-700">Injustificado</p>
                    <p class="mt-2 text-3xl font-black text-red-700">{{ number_format($absenteismo['taxa_injustificada'] ?? 0, 1, ',', '.') }}%</p>
                    <p class="mt-1 text-[11px] text-red-800">{{ $absenteismo['ausencias'] }} dia(s) integ. · {{ number_format($absenteismo['horas_ausencia_injustificada'] ?? 0, 1, ',', '.') }}h</p>
                </div>
                <div class="rounded-xl bg-brand-gray-soft p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Dias com jornada</p>
                    <p class="mt-2 text-3xl font-black text-brand-black">{{ $absenteismo['base'] }}</p>
                </div>
            </div>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-zinc-100">
                <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ min(100, $absenteismo['taxa_geral'] ?? $absenteismo['taxa']) }}%"></div>
            </div>
            <p class="mt-3 text-sm font-semibold text-brand-gray">
                Absenteísmo geral = horas de ausência (atestados, abonos, faltas, atrasos) ÷ horas previstas. Folgas e feriados não entram.
            </p>
            <div class="mt-4 flex flex-wrap gap-3 border-t border-zinc-100 pt-4">
                <a href="{{ route('rh.frequencia.extrato-faltas', array_filter([
                    'data_inicio' => $absenteismo['inicio'],
                    'data_fim' => $absenteismo['fim'],
                    'colaborador_id' => $absenteismoColaboradorId ?? null,
                ])) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-brand-burgundy/30 bg-brand-burgundy-soft px-4 text-sm font-semibold text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    Extrato de ausências
                </a>
                @if ($absenteismoColaborador ?? null)
                    <a href="{{ route('rh.frequencia.apuracao.index', ['colaborador_id' => $absenteismoColaborador->id, 'data_inicio' => $absenteismo['inicio'], 'data_fim' => $absenteismo['fim']]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black transition hover:bg-zinc-50">
                        <i data-lucide="clipboard-list" class="h-4 w-4 text-brand-burgundy"></i>
                        Ver apuração
                    </a>
                @endif
            </div>

        </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Ranking do período</p>
                    <h2 class="mt-1 text-xl font-bold text-brand-black">Top 5 faltas</h2>
                    <p class="mt-1 text-sm text-brand-gray">
                        Mesmo período do absenteísmo:
                        <strong>{{ \Carbon\Carbon::parse($absenteismo['inicio'])->format('d/m/Y') }}</strong>
                        a
                        <strong>{{ \Carbon\Carbon::parse($absenteismo['fim'])->format('d/m/Y') }}</strong>.
                        @if ($absenteismoColaborador ?? null)
                            · <strong>{{ $absenteismoColaborador->nome }}</strong>
                        @endif
                    </p>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse ($ranking as $item)
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-semibold text-brand-black">{{ $item->colaborador?->nome }}</p>
                                <p class="text-xs text-brand-gray">{{ $item->colaborador?->cargo ?: 'Cargo não informado' }}</p>
                            </div>
                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">{{ $item->total_faltas }} {{ $item->total_faltas === 1 ? 'falta' : 'faltas' }}</span>
                        </div>
                    @empty
                        <div class="p-5 text-sm text-brand-gray">Nenhuma falta injustificada neste período.</div>
                    @endforelse
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Ranking do período</p>
                    <h2 class="mt-1 text-xl font-bold text-brand-black">Top 5 atestados médicos</h2>
                    <p class="mt-1 text-sm text-brand-gray">
                        Dias justificados por atestado no mesmo período
                        (<strong>{{ \Carbon\Carbon::parse($absenteismo['inicio'])->format('d/m/Y') }}</strong>
                        a
                        <strong>{{ \Carbon\Carbon::parse($absenteismo['fim'])->format('d/m/Y') }}</strong>).
                    </p>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse ($rankingAtestados as $item)
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-semibold text-brand-black">{{ $item->colaborador?->nome }}</p>
                                <p class="text-xs text-brand-gray">{{ $item->colaborador?->cargo ?: 'Cargo não informado' }}</p>
                            </div>
                            <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-800">{{ $item->total_atestados }} {{ $item->total_atestados === 1 ? 'dia' : 'dias' }}</span>
                        </div>
                    @empty
                        <div class="p-5 text-sm text-brand-gray">Nenhum atestado médico registrado neste período.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </section>


    <section id="ferramentas" class="mb-5 scroll-mt-4 border-t border-zinc-200 pt-8">
        <div class="mb-5">
            <p class="text-xs font-black uppercase tracking-wide text-brand-gray">Uso eventual</p>
            <h2 class="text-xl font-bold text-brand-black">Importação e relatórios</h2>
            <p class="mt-1 text-sm text-brand-gray">Importe arquivos do relógio ou gere cartão de ponto em PDF.</p>
        </div>
        <div class="grid gap-5 xl:grid-cols-2">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Importação de ponto</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Importar e exportar marcações</h2>
                <p class="mt-1 text-sm text-brand-gray">Importe a exportação CSV do sistema de ponto (batidas, folgas e justificativas) ou use AFD do relógio. Exporte AFD para outros programas.</p>
            </div>

            <div class="space-y-6 p-5">
                <div>
                    <h3 class="text-sm font-bold text-brand-black">Importar CSV (exportação do ponto)</h3>
                    <p class="mt-1 text-xs text-brand-gray">Separador <code class="rounded bg-zinc-100 px-1">;</code> — matrícula, CPF, Dia e as quatro marcações. Só grava linhas cuja data estiver no período escolhido.</p>
                    <form method="POST" action="{{ route('rh.frequencia.importar-csv') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                        @csrf
                        <input type="hidden" name="escopo_colaboradores" value="todos">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data início</span>
                                <input type="date" name="data_inicio" value="{{ old('data_inicio', request('csv_data_inicio', $exportInicio)) }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                            </label>
                            <label class="space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data fim</span>
                                <input type="date" name="data_fim" value="{{ old('data_fim', request('csv_data_fim', $exportFim)) }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                            </label>
                        </div>
                        <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                            <label class="space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Arquivo CSV</span>
                                <input type="file" name="arquivo" accept=".csv,.txt,text/csv" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-brand-gray outline-none file:mr-3 file:rounded-md file:border-0 file:bg-emerald-700 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                            </label>
                            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                                <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                                Importar CSV
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-zinc-100 pt-5">
                    <h3 class="text-sm font-bold text-brand-black">Importar AFD</h3>
                    <form method="POST" action="{{ route('rh.frequencia.importar-afd') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                        @csrf
                        <label class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Arquivo AFD</span>
                            <input type="file" name="arquivo" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-brand-gray outline-none file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                        </label>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                            <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                            Importar
                        </button>
                    </form>
                </div>

                <div class="border-t border-zinc-100 pt-5">
                    <h3 class="text-sm font-bold text-brand-black">Exportar AFD</h3>
                    <p class="mt-1 text-xs text-brand-gray">Gera um arquivo <code class="rounded bg-zinc-100 px-1">.txt</code> com registro tipo 3 (Portaria 1510). Usa PIS, ou matrícula/CPF se o PIS não estiver cadastrado.</p>
                    <form method="GET" action="{{ route('rh.frequencia.exportar-afd') }}" class="mt-3 space-y-3">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                            <label class="space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data início</span>
                                <input type="date" name="data_inicio" value="{{ $exportInicio }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                            <label class="space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data fim</span>
                                <input type="date" name="data_fim" value="{{ $exportFim }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-brand-burgundy/30 bg-brand-burgundy-soft px-5 text-sm font-bold text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white">
                                <i data-lucide="download" class="h-4 w-4"></i>
                                Exportar AFD
                            </button>
                        </div>
                        <input type="hidden" name="data" value="{{ $data }}">
                        <input type="hidden" name="mes" value="{{ $mes }}">
                        @if (request('busca'))
                            <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-brand-gray">
                                <input type="checkbox" name="filtrar_busca" value="1" checked class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                                <span>Aplicar busca da listagem: «{{ request('busca') }}»</span>
                            </label>
                            <input type="hidden" name="busca" value="{{ request('busca') }}">
                        @endif
                    </form>
                </div>
            </div>
        </div>
    @include('rh.frequencia._cartao_ponto')
        </div>
    </section>

@endsection
