@extends('layouts.app')

@section('title', 'Cadastro de horários - Omega286')
@section('eyebrow', 'Recursos Humanos')
@section('page-title', 'Cadastro de horários')

@section('actions')
    <a href="{{ route('rh.horarios.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Novo cadastro
    </a>
@endsection

@section('content')
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Escalas de horários</h2>
            <p class="mt-1 text-sm text-brand-gray">Semanal (fixo por dia da semana) ou rotativa (revezamento no calendário, ex.: motoristas).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Nome</th>
                        <th class="px-4 py-4">Tipo</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Dias</th>
                        <th class="px-4 py-4">Colaboradores</th>
                        <th class="px-5 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($escalas as $escala)
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="px-5 py-4 font-semibold text-brand-black">{{ $escala->nome }}</td>
                            <td class="px-4 py-4 text-brand-gray">
                                @switch($escala->tipo)
                                    @case('rotativa_dias_uteis')
                                        Rotativa por dias úteis
                                        @break
                                    @case('rotativa_veiculos')
                                        Rotativa veículos
                                        @break
                                    @case('rotativa_semanal')
                                        Rotativa semanal
                                        @break
                                    @case('rotativa')
                                        Rotativa
                                        @break
                                    @default
                                        Semanal
                                @endswitch
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $escala->status === 'ativo' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-brand-gray-soft text-brand-gray' }}">
                                    {{ $escala->status === 'ativo' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">
                                @if ($escala->tipo === 'rotativa_veiculos')
                                    2 veículos / 4 posições
                                @elseif ($escala->tipo === 'rotativa_dias_uteis')
                                    {{ $escala->ciclo_dias ?? '—' }} posições
                                @elseif ($escala->tipo === 'rotativa_semanal')
                                    Revezamento semanal
                                @elseif ($escala->tipo === 'rotativa')
                                    {{ $escala->ciclo_dias ?? $escala->dias_count }} dias/ciclo
                                @else
                                    {{ $escala->dias_count }} / 7
                                @endif
                            </td>
                            <td class="px-4 py-4 font-medium text-brand-gray">{{ $escala->colaboradores_count }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('rh.horarios.edit', $escala) }}" class="inline-flex h-9 items-center gap-1 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('rh.horarios.destroy', $escala) }}" class="ml-2 inline-block" onsubmit="return confirm('Remover este cadastro de horários?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-brand-gray">Nenhum cadastro ainda. Clique em &quot;Novo cadastro&quot; para criar a primeira escala.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-5">
            {{ $escalas->links() }}
        </div>
    </section>
@endsection
