@extends('layouts.app')

@section('title', 'Veiculos - Omega286')
@section('eyebrow', 'Operacao')
@section('page-title', 'Veiculos')

@section('actions')
    <a href="{{ route('veiculos.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="plus" class="h-4 w-4"></i>
        Nova mobilizacao
    </a>
@endsection

@section('content')
    @php
        $statusLabel = [
            'pendente' => 'Pendente',
            'em_andamento' => 'Em andamento',
            'concluido' => 'Concluido',
            'bloqueado' => 'Bloqueado',
        ];
        $statusClass = [
            'pendente' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
            'em_andamento' => 'border-brand-burgundy/20 bg-brand-burgundy-soft text-brand-burgundy',
            'concluido' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'bloqueado' => 'border-red-200 bg-red-50 text-red-700',
        ];
        $stepClass = [
            true => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            false => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
        ];
        $done = fn (bool $ok) => $ok ? 'Concluido' : 'Pendente';
        $allChecked = fn ($items, array $keys) => collect($keys)->every(fn ($key) => data_get($items, $key) === true);
        $hasInitial = fn ($s) => filled($s->data_inicio_atividade)
            && filled($s->data_fim_atividade)
            && filled($s->contrato)
            && filled($s->linha_contratual)
            && filled($s->criterio_tecnico)
            && filled($s->finalidade);
        $hasVehicle = fn ($s) => filled($s->placa)
            && filled($s->renavam)
            && filled($s->marca)
            && filled($s->modelo)
            && filled($s->crlv_path);
        $hasTag = fn ($s) => filled($s->tag_data_solicitacao)
            && filled($s->tag_numero_protocolo)
            && filled($s->tag_evidencia_path)
            && $allChecked($s->tag_checklist_data, ['dados_completos', 'evidencia_salva']);
        $hasSub = fn ($s) => filled($s->subcontratacao_data_analise)
            && filled($s->subcontratacao_data_autorizacao)
            && filled($s->subcontratacao_cartao_cnpj_path)
            && filled($s->subcontratacao_minuta_path)
            && filled($s->subcontratacao_contrato_social_path)
            && filled($s->subcontratacao_documento_veiculo_path)
            && $allChecked($s->subcontratacao_checklist_data, ['analise_inicial', 'autorizacao_aprovada']);
        $hasSvg = fn ($s) => filled($s->svg_data_postagem)
            && filled($s->svg_protocolo)
            && filled($s->svg_evidencia_path)
            && $allChecked($s->svg_checklist_data, ['mobilizacao_postada', 'fluxo_acompanhado']);
        $hasVistoria = fn ($s) => filled($s->vistoria_previsao_inicio)
            && filled($s->vistoria_previsao_fim)
            && filled($s->vistoria_data_agendada)
            && $s->vistoria_resultado === 'aprovado';
    @endphp

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Frota e mobilizacao de veiculos</h2>
                <p class="mt-1 text-sm text-brand-gray">Acompanhe as solicitacoes, TAG, subcontratacao, SVG, vistoria e finalizacao.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por placa, modelo, contrato..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-96">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1420px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="sticky left-0 z-[1] w-[300px] bg-white px-5 py-4">Veiculo / solicitacao</th>
                        <th class="w-[130px] px-4 py-4">Placa</th>
                        <th class="w-[160px] px-4 py-4">Tag</th>
                        <th class="w-[160px] px-4 py-4">Contrato</th>
                        <th class="w-[130px] px-4 py-4">Periodo</th>
                        <th class="w-[140px] px-4 py-4">Status</th>
                        <th class="w-[120px] px-3 py-4">Inicial</th>
                        <th class="w-[120px] px-3 py-4">Veiculo</th>
                        <th class="w-[120px] px-3 py-4">TAG</th>
                        <th class="w-[150px] px-3 py-4">Subcontratacao</th>
                        <th class="w-[120px] px-3 py-4">SVG</th>
                        <th class="w-[120px] px-3 py-4">Vistoria</th>
                        <th class="w-[130px] px-3 py-4">Finalizacao</th>
                        <th class="w-[140px] px-4 py-4 text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($solicitacoes as $solicitacao)
                        @php
                            $steps = [
                                'inicial' => $hasInitial($solicitacao),
                                'veiculo' => $hasVehicle($solicitacao),
                                'tag' => $hasTag($solicitacao),
                                'subcontratacao' => $hasSub($solicitacao),
                                'svg' => $hasSvg($solicitacao),
                                'vistoria' => $hasVistoria($solicitacao),
                            ];
                            $finalizada = collect($steps)->every(fn ($stepDone) => $stepDone);
                            $veiculoTitulo = $solicitacao->placa ?: 'Solicitacao #'.$solicitacao->id;
                            $veiculoSubtitulo = trim(($solicitacao->marca ?: '').' '.($solicitacao->modelo ?: '')) ?: ($solicitacao->finalidade ?: 'Demanda em preenchimento');
                        @endphp
                        <tr class="transition hover:bg-brand-gray-soft/40">
                            <td class="sticky left-0 z-[1] bg-white px-5 py-4 shadow-[8px_0_18px_rgba(17,17,17,0.03)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-sm font-bold text-brand-burgundy">
                                        <i data-lucide="truck" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $veiculoTitulo }}</p>
                                        <p class="text-xs text-brand-gray">{{ $veiculoSubtitulo }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $solicitacao->placa ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $solicitacao->tag_numero_protocolo ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ optional($solicitacao->tag_data_solicitacao)->format('d/m/Y') ?: 'Sem data' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-brand-black">{{ $solicitacao->contrato ?: '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ $solicitacao->linha_contratual ?: 'Linha nao informada' }}</p>
                            </td>
                            <td class="px-4 py-4 text-xs text-brand-gray">
                                <p>{{ optional($solicitacao->data_inicio_atividade)->format('d/m/Y') ?: '--' }}</p>
                                <p>{{ optional($solicitacao->data_fim_atividade)->format('d/m/Y') ?: '--' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass[$solicitacao->status] ?? $statusClass['em_andamento'] }}">
                                    {{ $statusLabel[$solicitacao->status] ?? $solicitacao->status }}
                                </span>
                            </td>
                            @foreach ($steps as $stepDone)
                                <td class="px-3 py-4">
                                    <span class="inline-flex h-8 min-w-24 items-center justify-center rounded-full border px-3 text-xs font-bold {{ $stepClass[$stepDone] }}">
                                        {{ $done($stepDone) }}
                                    </span>
                                </td>
                            @endforeach
                            <td class="px-3 py-4">
                                <span class="inline-flex h-8 min-w-24 items-center justify-center rounded-full border px-3 text-xs font-bold {{ $stepClass[$finalizada] }}">
                                    {{ $finalizada ? 'Finalizada' : 'Pendente' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('veiculos.solicitacoes.edit', $solicitacao) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Abrir
                                    </a>
                                    <a href="{{ route('veiculos.solicitacoes.edit', $solicitacao) }}#step-finalizacao" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="list-checks" class="h-4 w-4"></i>
                                        Gerenciar
                                    </a>
                                    <form method="POST" action="{{ route('veiculos.solicitacoes.destroy', $solicitacao) }}" onsubmit="return confirm('Deseja realmente excluir esta mobilizacao? Esta acao nao pode ser desfeita.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-100">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="car-front" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhuma mobilizacao cadastrada.</p>
                                <p class="mt-1 text-sm text-brand-gray">Crie uma solicitacao para iniciar o controle da mobilizacao.</p>
                                <a href="{{ route('veiculos.create') }}" class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Nova mobilizacao
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $solicitacoes->links() }}
        </div>
    </section>
@endsection
