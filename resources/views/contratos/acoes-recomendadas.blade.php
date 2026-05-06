@extends('layouts.app')

@section('title', 'Ações recomendadas - Omega286')
@section('eyebrow', 'Contrato')
@section('page-title', 'Ações Recomendadas')

@section('content')
    <section class="mb-5 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Ações recomendadas para o Slide 5</h2>
            <p class="mt-1 text-sm text-brand-gray">Esta tela carrega automaticamente as 5 funções críticas da apresentação e permite cadastrar Ação recomendada e Responsável.</p>
        </div>
        <form method="GET" class="grid gap-3 p-5 md:grid-cols-[1fr_170px_auto] md:items-end">
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                <select name="contrato" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Selecionar...</option>
                    @foreach ($contratos as $contrato)
                        <option value="{{ $contrato }}" @selected($contratoSelecionado === $contrato)>{{ $contrato }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Competência</span>
                <input type="month" name="competencia" value="{{ $competenciaMes }}" required class="mt-2 h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <button class="inline-flex h-11 items-center justify-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                Carregar funções
            </button>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <form method="POST" action="{{ route('contratos.acoes-recomendadas.salvar') }}">
            @csrf
            <input type="hidden" name="contrato" value="{{ $contratoSelecionado }}">
            <input type="hidden" name="competencia" value="{{ $competenciaMes }}">

            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                <div>
                    <h3 class="text-base font-bold text-brand-black">{{ $contratoSelecionado ?: 'Selecione um contrato' }} · {{ \Carbon\Carbon::createFromFormat('Y-m', $competenciaMes)->format('m/Y') }}</h3>
                    <p class="text-sm text-brand-gray">Somente funções que entram no Slide 5 (top 5 críticas).</p>
                </div>
                <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    Salvar ações recomendadas
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                        <tr>
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">Função (Slide 5)</th>
                            <th class="px-3 py-3">Pendências</th>
                            <th class="px-3 py-3">Ação recomendada</th>
                            <th class="px-3 py-3">Responsável</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($funcoes as $idx => $item)
                            <tr>
                                <td class="px-3 py-3 text-xs font-bold text-brand-gray">
                                    {{ $item['ordem'] }}
                                    <input type="hidden" name="funcoes[{{ $idx }}][ordem]" value="{{ $item['ordem'] }}">
                                    <input type="hidden" name="funcoes[{{ $idx }}][funcao]" value="{{ $item['funcao'] }}">
                                    <input type="hidden" name="funcoes[{{ $idx }}][pendencias]" value="{{ $item['pendencias'] }}">
                                </td>
                                <td class="px-3 py-3 font-semibold text-brand-black">{{ $item['funcao'] }}</td>
                                <td class="px-3 py-3 text-lg font-bold text-brand-burgundy">{{ $item['pendencias'] }}</td>
                                <td class="px-3 py-3">
                                    <input
                                        name="funcoes[{{ $idx }}][acao_recomendada]"
                                        value="{{ $item['acao_recomendada'] }}"
                                        class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"
                                        placeholder="Ex.: Força-tarefa documental e validação diária"
                                    >
                                </td>
                                <td class="px-3 py-3">
                                    <input
                                        name="funcoes[{{ $idx }}][responsavel]"
                                        value="{{ $item['responsavel'] }}"
                                        class="h-10 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"
                                        placeholder="Ex.: Gestão PGU"
                                    >
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-brand-gray">
                                    Nenhuma função crítica encontrada para este contrato/competência.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </section>
@endsection
