@extends('layouts.app')

@section('title', 'Frota - Veículos - Omega286')
@section('eyebrow', 'Operação / Veículos')
@section('page-title', 'Controle mensal da frota')

@section('actions')
    <a href="{{ route('veiculos.manutencoes.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="wrench" class="h-4 w-4"></i>
        Nova manutenção
    </a>
@endsection

@section('content')
    @php
        $fmtMin = fn ($min) => sprintf('%dh %02dmin', intdiv((int) $min, 60), ((int) $min) % 60);
    @endphp

    <section class="mb-5 grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Disponibilidade</p>
            <p class="mt-2 text-3xl font-black text-brand-black">{{ $indicadores['disponiveis'] }}/{{ $indicadores['ativos_total'] }}</p>
            <p class="mt-1 text-xs text-brand-gray">Indisponíveis: {{ $indicadores['indisponiveis'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Manutenções no mês</p>
            <p class="mt-2 text-3xl font-black text-brand-black">{{ $indicadores['manutencoes_mes'] }}</p>
            <p class="mt-1 text-xs text-brand-gray">Dias parados: {{ $indicadores['dias_parados'] }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Uso operacional</p>
            <p class="mt-2 text-3xl font-black text-brand-black">{{ number_format($indicadores['km_total'], 1, ',', '.') }} km</p>
            <p class="mt-1 text-xs text-brand-gray">Horas: {{ $fmtMin($indicadores['horas_total_min']) }} · Ocioso: {{ $fmtMin($indicadores['ociosidade_total_min']) }}</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Impacto financeiro</p>
            <p class="mt-2 text-3xl font-black text-brand-black">R$ {{ number_format($indicadores['impacto_financeiro'], 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-brand-gray">Perdas, glosas e custos do mês</p>
        </article>
    </section>

    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Frota mobilizada por contrato</h2>
                <p class="mt-1 text-sm text-brand-gray">Veículos com mobilização concluída já entram automaticamente como ativos na frota do contrato.</p>
            </div>
            <form method="GET" class="grid gap-2 sm:grid-cols-[160px_auto] sm:items-center">
                <input type="month" name="mes" value="{{ $mes }}" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <button class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    Aplicar
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-4">Veículo</th>
                        <th class="px-4 py-4">Placa</th>
                        <th class="px-4 py-4">Tipo</th>
                        <th class="px-4 py-4">Contrato</th>
                        <th class="px-4 py-4">Disponibilidade</th>
                        <th class="px-4 py-4">KM mês</th>
                        <th class="px-4 py-4">Horas</th>
                        <th class="px-4 py-4">Ocioso</th>
                        <th class="px-4 py-4">Desvios/Excesso</th>
                        <th class="px-4 py-4">Alertas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($itensFrota as $item)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $item['veiculo'] }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $item['placa'] }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $item['tipo'] }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $item['contrato'] }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $item['disponivel'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                                    {{ $item['disponivel'] ? 'Disponível' : 'Indisponível' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ number_format((float) $item['km_rodado'], 1, ',', '.') }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $fmtMin($item['horas_operacao_min']) }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $fmtMin($item['tempo_ocioso_min']) }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $item['desvios'] }}/{{ $item['excesso_velocidade'] }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $item['alertas'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-12 text-center text-sm text-brand-gray">Nenhum veículo com mobilização concluída na frota.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Controle de manutenção</h2>
            <p class="mt-1 text-sm text-brand-gray">Preventiva/corretiva, dias parado, evidência, impacto operacional e impacto financeiro.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1320px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-4">Ativo</th>
                        <th class="px-4 py-4">Placa/TAG</th>
                        <th class="px-4 py-4">Tipo</th>
                        <th class="px-4 py-4">Solicitação</th>
                        <th class="px-4 py-4">Motivo</th>
                        <th class="px-4 py-4">Envio/Retorno</th>
                        <th class="px-4 py-4">Dias parado</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Impacto financeiro</th>
                        <th class="px-4 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($manutencoes as $m)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $m->veiculo_equipamento }}</p>
                                <p class="text-xs text-brand-gray">{{ $m->contrato ?: 'Sem contrato' }}</p>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">{{ $m->placa_tag ?: '-' }}</td>
                            <td class="px-4 py-4 text-brand-gray">{{ $m->tipo ?: '-' }}</td>
                            <td class="px-4 py-4 text-brand-gray">
                                <p>{{ $m->data_solicitacao?->format('d/m/Y') }}</p>
                                <p class="text-xs">{{ $m->responsavel_solicitacao ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">{{ ucfirst($m->motivo) }}</td>
                            <td class="px-4 py-4 text-brand-gray">
                                <p>{{ $m->data_envio?->format('d/m/Y') ?: '-' }}</p>
                                <p>{{ $m->data_retorno?->format('d/m/Y') ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $m->dias_parado }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $m->status === 'concluido' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($m->status === 'em_andamento' ? 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy' : 'border-zinc-200 bg-brand-gray-soft text-brand-gray') }}">
                                    {{ str_replace('_', ' ', ucfirst($m->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">R$ {{ number_format((float) $m->impacto_financeiro, 2, ',', '.') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('veiculos.manutencoes.edit', $m) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('veiculos.manutencoes.destroy', $m) }}" onsubmit="return confirm('Excluir manutenção?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-12 text-center text-sm text-brand-gray">Nenhuma manutenção cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-5">
            {{ $manutencoes->links() }}
        </div>
    </section>
@endsection
