@extends('layouts.app')

@section('title', 'Detalhes do patrimônio - Omega286')
@section('eyebrow', 'Gestão patrimonial')
@section('page-title', $patrimonio->nome)

@section('actions')
    <a href="{{ route('patrimonial.fluxo.edit', $patrimonio) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="workflow" class="h-4 w-4"></i>
        Fluxo
    </a>
    <a href="{{ route('patrimonial.edit', $patrimonio) }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="pencil" class="h-4 w-4"></i>
        Editar
    </a>
@endsection

@section('content')
    @php
        $statusLabel = [
            'ativo' => 'Ativo',
            'em_uso' => 'Em uso',
            'em_manutencao' => 'Em manutenção',
            'reserva' => 'Reserva',
            'baixado' => 'Baixado',
        ];
        $condicaoLabel = [
            'novo' => 'Novo',
            'bom' => 'Bom',
            'regular' => 'Regular',
            'danificado' => 'Danificado',
            'inutilizado' => 'Inutilizado',
        ];
        $linha = function (string $label, $value) {
            return '<div class="rounded-lg border border-zinc-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-brand-gray">'.$label.'</p><p class="mt-2 text-sm font-semibold text-brand-black">'.e($value ?: '-').'</p></div>';
        };
    @endphp

    <div class="space-y-5">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                        <i data-lucide="package-check" class="h-7 w-7"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">TAG {{ $patrimonio->tag_patrimonial }}</p>
                        <h2 class="mt-1 text-2xl font-bold text-brand-black">{{ $patrimonio->nome }}</h2>
                        <p class="mt-1 text-sm text-brand-gray">{{ $patrimonio->categoria ?: 'Categoria não informada' }}{{ $patrimonio->modelo ? ' · '.$patrimonio->modelo : '' }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-brand-burgundy/20 bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">{{ $statusLabel[$patrimonio->status] ?? $patrimonio->status }}</span>
                    <span class="rounded-full border border-zinc-200 bg-brand-gray-soft px-3 py-1 text-xs font-bold text-brand-gray">{{ $condicaoLabel[$patrimonio->condicao] ?? $patrimonio->condicao }}</span>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {!! $linha('Contrato', $patrimonio->contrato) !!}
            {!! $linha('Responsável', $patrimonio->responsavel) !!}
            {!! $linha('Localização', $patrimonio->localizacao ?: $patrimonio->setor) !!}
            {!! $linha('Valor', $patrimonio->valor ? 'R$ '.number_format((float) $patrimonio->valor, 2, ',', '.') : null) !!}
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Ficha patrimonial</p>
            <h2 class="mt-1 text-xl font-bold text-brand-black">Informações completas</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {!! $linha('Tipo', $patrimonio->tipo) !!}
                {!! $linha('Marca', $patrimonio->marca) !!}
                {!! $linha('Modelo', $patrimonio->modelo) !!}
                {!! $linha('Nº de série', $patrimonio->numero_serie) !!}
                {!! $linha('Centro de custo', $patrimonio->centro_custo) !!}
                {!! $linha('Fornecedor', $patrimonio->fornecedor) !!}
                {!! $linha('Data de aquisição', optional($patrimonio->data_aquisicao)->format('d/m/Y')) !!}
                {!! $linha('Data de entrada', optional($patrimonio->data_entrada)->format('d/m/Y')) !!}
                {!! $linha('Setor', $patrimonio->setor) !!}
                {!! $linha('Última conferência', optional($patrimonio->ultima_conferencia)->format('d/m/Y')) !!}
                {!! $linha('Próxima conferência', optional($patrimonio->proxima_conferencia)->format('d/m/Y')) !!}
            </div>
            <div class="mt-4 rounded-lg border border-zinc-200 bg-brand-gray-soft/50 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observações</p>
                <p class="mt-2 whitespace-pre-line text-sm text-brand-black">{{ $patrimonio->observacoes ?: '-' }}</p>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <a href="{{ route('patrimonial.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Voltar ao inventário
            </a>
            <form method="POST" action="{{ route('patrimonial.destroy', $patrimonio) }}" onsubmit="return confirm('Deseja realmente excluir este patrimônio?');">
                @csrf
                @method('DELETE')
                <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-100">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Excluir patrimônio
                </button>
            </form>
        </div>
    </div>
@endsection
