@extends('layouts.app')

@section('title', 'Medição - Omega286')
@section('eyebrow', 'Operação')
@section('page-title', 'Medição')

@section('content')
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Medição contratual</h2>
            <p class="mt-1 text-sm text-brand-gray">Módulo preparado para consolidar produção, disponibilidade, glosas e performance mensal dos contratos.</p>
        </div>
        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <a href="{{ route('medicao.presenca-obra.index') }}" class="rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft p-4 transition hover:border-brand-burgundy/40">
                <p class="text-sm font-bold text-brand-burgundy">Presença na obra</p>
                <p class="mt-1 text-xs font-medium text-brand-burgundy/80">Consulta quem os supervisores confirmaram como presente ou ausente no dia.</p>
            </a>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm font-medium text-brand-gray">
                Estrutura inicial de medição contratual. Próximo passo: indicadores e fluxo conforme regras do contrato.
            </div>
        </div>
    </section>
@endsection
