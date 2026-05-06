@extends('layouts.app')

@section('title', 'Prazos SLA — Registro Mensal SSMA')
@section('eyebrow', 'SSMA')
@section('page-title', 'Prazos do registro mensal (SLA)')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.registros.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Registros
        </a>
        @if ($podeEditar)
            <a href="{{ route('sesmt.registros.prazos.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Novo prazo
            </a>
        @endif
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <section class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-brand-black">Como usar</h2>
        <p class="mt-2 text-sm text-brand-gray">Cadastre uma <strong>data limite</strong> por competência ou escolha <strong>recorrente</strong> para repetir o mesmo dia e hora em todos os meses seguintes (a partir da competência informada). Prazo <strong>único</strong> para um mês específico tem prioridade sobre a regra recorrente. A situação na tabela usa o <strong>mês atual</strong> para linhas recorrentes. Só perfis com <strong>edição</strong> no SSMA alteram cadastros.</p>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Prazos cadastrados</h2>
            <p class="mt-1 text-sm text-brand-gray">Base para acompanhamento e cobrança do preenchimento do registro mensal.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Competência</th>
                        <th class="px-5 py-4">Tipo</th>
                        <th class="px-5 py-4">Limite</th>
                        <th class="px-5 py-4">Critério</th>
                        <th class="px-5 py-4">Situação</th>
                        @if ($podeEditar)
                            <th class="px-5 py-4 text-right">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($linhas as $linha)
                        @php
                            /** @var \App\Models\SsmaRegistroMensalPrazo $p */
                            $p = $linha['prazo'];
                        @endphp
                        <tr class="transition hover:bg-brand-gray-soft/60">
                            <td class="px-5 py-4 font-semibold text-brand-black">{{ $p->competencia?->format('m/Y') }}</td>
                            <td class="px-5 py-4 text-brand-gray">{{ $p->recorrente ? 'Recorrente' : 'Único' }}</td>
                            <td class="px-5 py-4 text-brand-gray">
                                <span class="font-medium text-brand-black">{{ $p->data_limite?->format('d/m/Y H:i') }}</span>
                                @if ($p->recorrente)
                                    <span class="mt-1 block text-xs">Dia {{ $p->data_limite?->format('d') }} às {{ $p->data_limite?->format('H:i') }} em cada mês.</span>
                                    @if (isset($linha['mes_ref_situacao']))
                                        <span class="mt-1 block text-xs font-semibold text-brand-burgundy">Mês ref. situação {{ $linha['mes_ref_situacao']->format('m/Y') }}: {{ $p->dataLimiteEfetiva($linha['mes_ref_situacao'])->format('d/m/Y H:i') }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-5 py-4 text-brand-gray">{{ $p->exige_finalizado ? 'Exige finalizado' : 'Qualquer registro' }}</td>
                            <td class="px-5 py-4">
                                @if ($linha['cumprido'])
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">{{ $linha['rotulo'] }}</span>
                                @elseif ($linha['atrasado'])
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">{{ $linha['rotulo'] }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900">{{ $linha['rotulo'] }}</span>
                                @endif
                            </td>
                            @if ($podeEditar)
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('sesmt.registros.prazos.edit', $p) }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand-burgundy hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('sesmt.registros.prazos.destroy', $p) }}" class="ml-3 inline" onsubmit="return confirm('Remover este prazo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-red-600 hover:underline">Excluir</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $podeEditar ? 6 : 5 }}" class="px-5 py-12 text-center text-brand-gray">
                                Nenhum prazo cadastrado.
                                @if ($podeEditar)
                                    <a href="{{ route('sesmt.registros.prazos.create') }}" class="font-semibold text-brand-burgundy hover:underline">Cadastrar o primeiro</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
