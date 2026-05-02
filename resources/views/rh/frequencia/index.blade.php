@extends('layouts.app')

@section('title', 'Frequência - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Frequência')

@section('content')
    @php
        $statusLabel = [
            'presente' => 'Presente',
            'falta' => 'Falta',
            'justificado' => 'Justificado',
            'incompleto' => 'Incompleto',
        ];
        $statusClass = [
            'presente' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'falta' => 'border-red-200 bg-red-50 text-red-700',
            'justificado' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
            'incompleto' => 'border-amber-200 bg-amber-50 text-amber-700',
        ];
    @endphp

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
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

    <section class="mb-5 grid gap-5 xl:grid-cols-[1.4fr_.8fr]">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Importação AFD</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Importar relógio de ponto</h2>
                <p class="mt-1 text-sm text-brand-gray">Envie o arquivo AFD do relógio. O sistema lê as marcações, vincula ao efetivo por PIS/matrícula e registra falta para quem não tiver ponto no dia importado.</p>
            </div>
            <form method="POST" action="{{ route('rh.frequencia.importar-afd') }}" enctype="multipart/form-data" class="grid gap-3 p-5 lg:grid-cols-[1fr_auto] lg:items-end">
                @csrf
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Arquivo AFD</span>
                    <input type="file" name="arquivo" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-brand-gray outline-none file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                    Importar AFD
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Ranking mensal</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Top 5 faltas</h2>
                <p class="mt-1 text-sm text-brand-gray">Colaboradores com mais faltas no mês filtrado.</p>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($ranking as $item)
                    <div class="flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-semibold text-brand-black">{{ $item->colaborador?->nome }}</p>
                            <p class="text-xs text-brand-gray">{{ $item->colaborador?->cargo ?: 'Cargo não informado' }}</p>
                        </div>
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">{{ $item->total_faltas }} faltas</span>
                    </div>
                @empty
                    <div class="p-5 text-sm text-brand-gray">Nenhuma falta registrada no mês.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mb-5 grid gap-5 xl:grid-cols-[.9fr_1.1fr]">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 p-5">
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Absenteísmo</p>
                <h2 class="mt-1 text-xl font-bold text-brand-black">Taxa por período</h2>
                <p class="mt-1 text-sm text-brand-gray">Filtre o intervalo para calcular ausências sobre a base de colaboradores ativos.</p>
            </div>
            <form method="GET" class="grid gap-3 p-5">
                <input type="hidden" name="data" value="{{ $data }}">
                <input type="hidden" name="mes" value="{{ $mes }}">
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
                </div>
                <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">{{ $absenteismo['dias'] }} dia(s)</span>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-red-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-red-700">Ausências</p>
                    <p class="mt-2 text-3xl font-black text-red-700">{{ $absenteismo['ausencias'] }}</p>
                </div>
                <div class="rounded-xl bg-brand-gray-soft p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Base prevista</p>
                    <p class="mt-2 text-3xl font-black text-brand-black">{{ $absenteismo['base'] }}</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Taxa</p>
                    <p class="mt-2 text-3xl font-black text-amber-700">{{ number_format($absenteismo['taxa'], 1, ',', '.') }}%</p>
                </div>
            </div>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-zinc-100">
                <div class="h-full rounded-full bg-brand-burgundy" style="width: {{ min(100, $absenteismo['taxa']) }}%"></div>
            </div>
            <p class="mt-3 text-sm font-semibold text-brand-gray">
                Cálculo: {{ $absenteismo['ausencias'] }} ausências ÷ {{ $absenteismo['base'] }} possibilidades de presença.
            </p>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Ponto diário do efetivo</h2>
                <p class="mt-1 text-sm text-brand-gray">Acompanhe marcações, faltas, atestados e justificativas por data.</p>
            </div>
            <form method="GET" class="grid gap-2 sm:grid-cols-[150px_130px_1fr_auto] sm:items-center">
                <input type="date" name="data" value="{{ $data }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <input type="month" name="mes" value="{{ $mes }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar colaborador..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Filtrar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1280px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Colaborador</th>
                        <th class="px-4 py-4">Data</th>
                        <th class="px-4 py-4">Marcações</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Justificativa</th>
                        <th class="px-5 py-4">Anexar atestado / justificativa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($registros as $registro)
                        <tr class="align-top transition hover:bg-brand-gray-soft/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-sm font-bold text-brand-burgundy">
                                        {{ mb_substr($registro->colaborador->nome, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $registro->colaborador->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $registro->colaborador->matricula ?: 'Sem matrícula' }} · {{ $registro->colaborador->cargo ?: 'Cargo não informado' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $registro->data?->format('d/m/Y') }}</td>
                            <td class="px-4 py-4">
                                <div class="grid grid-cols-4 gap-2 text-xs font-semibold text-brand-gray">
                                    @foreach (['entrada_1' => 'Entrada', 'saida_1' => 'Saída', 'entrada_2' => 'Retorno', 'saida_2' => 'Saída'] as $field => $label)
                                        <span class="rounded-lg border border-zinc-200 bg-white px-2 py-2 text-center">
                                            <span class="block text-[10px] uppercase">{{ $label }}</span>
                                            {{ $registro->{$field} ? substr($registro->{$field}, 0, 5) : '--:--' }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass[$registro->status] ?? $statusClass['falta'] }}">
                                    {{ $statusLabel[$registro->status] ?? $registro->status }}
                                </span>
                                <p class="mt-1 text-xs text-brand-gray">Origem: {{ strtoupper($registro->origem) }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $registro->justificativa_tipo ? ucfirst($registro->justificativa_tipo) : '-' }}</p>
                                <p class="mt-1 max-w-xs text-xs text-brand-gray">{{ $registro->justificativa_texto ?: 'Sem justificativa registrada.' }}</p>
                                @if ($registro->anexo_path)
                                    <a href="{{ asset('storage/'.$registro->anexo_path) }}" target="_blank" class="mt-2 inline-flex text-xs font-bold text-brand-burgundy">Ver anexo</a>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('rh.frequencia.justificar', $registro) }}" enctype="multipart/form-data" class="grid gap-2 lg:grid-cols-[140px_1fr_170px_auto] lg:items-center">
                                    @csrf
                                    <select name="justificativa_tipo" class="h-10 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        @foreach (['atestado' => 'Atestado', 'justificativa' => 'Justificativa', 'abono' => 'Abono', 'outro' => 'Outro'] as $value => $label)
                                            <option value="{{ $value }}" @selected($registro->justificativa_tipo === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input name="justificativa_texto" value="{{ $registro->justificativa_texto }}" placeholder="Descreva o motivo..." class="h-10 rounded-lg border border-zinc-200 bg-white px-3 text-xs outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    <input type="file" name="anexo" class="h-10 rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-xs text-brand-gray file:mr-2 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-2 file:py-1 file:text-xs file:font-semibold file:text-white">
                                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="save" class="h-4 w-4"></i>
                                        Salvar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="clock" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum ponto registrado para esta data.</p>
                                <p class="mt-1 text-sm text-brand-gray">Importe um arquivo AFD para gerar as marcações e faltas do efetivo.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $registros->links() }}
        </div>
    </section>
@endsection
