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
        <div class="p-6">
            <div class="rounded-xl border border-brand-burgundy/20 bg-brand-burgundy-soft p-4 text-sm font-medium text-brand-burgundy">
                Estrutura inicial criada. Próximo passo: implantar indicadores e fluxo de medição conforme regras do contrato.
            </div>
        </div>
    </section>
@endsection
