@extends('layouts.app')

@section('title', $chamado->protocolo.' - Omega286')
@section('eyebrow', 'RH / Chamados')
@section('page-title', $chamado->protocolo)

@section('actions')
    <a href="{{ route('rh.chamados-movimentacao.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar à lista
    </a>
    <a href="{{ route('rh.chamados-movimentacao.pdf', $chamado) }}" target="_blank" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
        <i data-lucide="file-text" class="h-4 w-4"></i>
        PDF do chamado
    </a>
    @if ($pdfAnexo ?? null)
        <a href="{{ route('rh.chamados-movimentacao.anexos.download', $pdfAnexo) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">
            <i data-lucide="download" class="h-4 w-4"></i>
            PDF arquivado
        </a>
    @endif
    @if ($chamado->isAberto())
        <a href="{{ route('rh.efetivo.show', $chamado->colaborador) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand-burgundy/25 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
            <i data-lucide="user" class="h-4 w-4"></i>
            Ficha do colaborador
        </a>
    @endif
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 flex flex-col gap-2 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-900 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
            @if (session('recrutamento_vaga_id'))
                <a href="{{ route('rh.recrutamento.edit', session('recrutamento_vaga_id')) }}" class="inline-flex h-9 shrink-0 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800">
                    <i data-lucide="briefcase" class="h-3.5 w-3.5"></i>
                    Abrir vaga de substituição
                </a>
            @endif
        </div>
    @endif

    {{-- Hero do chamado --}}
    <section class="relative mb-6 overflow-hidden rounded-3xl border border-brand-burgundy/20 bg-brand-burgundy-dark shadow-lg shadow-brand-burgundy/15">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36]"></div>
        <div class="pointer-events-none absolute -right-10 top-0 h-56 w-56 rounded-full bg-white/[0.07] blur-3xl"></div>
        <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between sm:p-8">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-brand-burgundy-soft backdrop-blur-sm">
                    <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-white/90"></i>
                    {{ $chamado->tipoLabel() }}
                </span>
                <h2 class="mt-4 truncate text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $chamado->colaborador->nome }}</h2>
                <p class="mt-2 text-sm text-brand-burgundy-soft/90">
                    {{ $chamado->colaborador->cargo ?? '—' }}
                    @if ($chamado->colaborador->centro_custo)
                        · {{ $chamado->colaborador->centro_custo }}
                    @endif
                </p>
                <p class="mt-1 font-mono text-xs font-bold text-white/80">{{ $chamado->protocolo }}</p>
            </div>
            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                <span class="inline-flex rounded-2xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-white backdrop-blur-sm">
                    {{ $chamado->statusLabel() }}
                </span>
                @if ($chamado->etapaAtual)
                    <p class="text-xs text-brand-burgundy-soft/90">Etapa atual: <strong class="text-white">{{ $chamado->etapaAtual->nome }}</strong></p>
                @endif
                @if ($chamado->data_prevista)
                    <p class="text-xs text-brand-burgundy-soft/80">Previsto: {{ $chamado->data_prevista->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </section>

    @if ($chamado->tipo === \App\Support\Rh\MovimentacaoChamadoTipo::AFASTAMENTO_INSS)
        @include('rh.chamados-movimentacao._painel_afastamento_inss')
    @endif

    @if (! $chamado->isAberto())
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-5 py-4 text-sm text-zinc-700">
            <i data-lucide="lock" class="mt-0.5 h-5 w-5 shrink-0 text-zinc-500"></i>
            <div>
                <p class="font-bold">Processo encerrado — somente leitura</p>
                <p class="mt-1 text-xs">Edição bloqueada. Histórico e anexos permanecem disponíveis para consulta.</p>
            </div>
        </div>
    @elseif (! ($podeEditar ?? true))
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            Seu perfil não permite editar este chamado. Visualização liberada.
        </div>
    @endif

    @if ($chamado->tipo === \App\Support\Rh\MovimentacaoChamadoTipo::DESLIGAMENTO)
        @include('rh.chamados-movimentacao._painel_desligamento')
    @endif

    @if ($chamado->status === \App\Support\Rh\MovimentacaoChamadoStatus::AGUARDANDO_FINALIZACAO)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-950 shadow-sm">
            <i data-lucide="badge-check" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"></i>
            <div>
                <p class="font-bold">Todas as etapas foram concluídas</p>
                <p class="mt-1 text-xs text-emerald-900/90">Use o botão <strong>Finalizar processo</strong> abaixo para aplicar as alterações no cadastro do colaborador.</p>
            </div>
        </div>
    @elseif ($chamado->isAberto() && count($pendenciasFinalizacao) > 0)
        @include('rh.chamados-movimentacao._painel_pendencias_finalizacao')
    @endif

    {{-- Etapas --}}
    <section id="timeline-etapas" class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100 scroll-mt-6">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-md shadow-brand-burgundy/25">
                    <i data-lucide="route" class="h-5 w-5"></i>
                </span>
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-zinc-900">Linha do tempo — etapas</h3>
                    <p class="text-xs text-zinc-500">{{ $chamado->etapas->count() }} etapa(s) no fluxo</p>
                </div>
            </div>
        </div>

        <ol class="divide-y divide-zinc-100 p-6 sm:p-8">
            @foreach ($chamado->etapas as $etapa)
                @php
                    $etapaAtual = $chamado->etapa_atual_id === $etapa->id;
                    $concluida = $etapa->isConcluida();
                @endphp
                <li class="flex gap-4 py-5 first:pt-0 last:pb-0">
                    <div class="flex flex-col items-center">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-bold shadow-sm {{ $concluida ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : ($etapaAtual ? 'bg-brand-burgundy text-white shadow-brand-burgundy/25' : 'bg-zinc-100 text-zinc-500 ring-1 ring-zinc-200') }}">
                            @if ($concluida)
                                <i data-lucide="check" class="h-5 w-5"></i>
                            @else
                                {{ $etapa->ordem }}
                            @endif
                        </span>
                        @if (! $loop->last)
                            <span class="mt-2 w-0.5 flex-1 min-h-[2rem] rounded-full bg-zinc-200"></span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 rounded-2xl border p-5 transition {{ $etapaAtual ? 'border-brand-burgundy/30 bg-brand-burgundy-soft/20 ring-1 ring-brand-burgundy/10' : 'border-zinc-200/80 bg-zinc-50/30' }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h4 class="font-bold text-zinc-900">{{ $etapa->nome }}</h4>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-600">{{ $etapa->status }}</span>
                                    @if ($etapa->isAtrasada())
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-700">
                                            <i data-lucide="alarm-clock" class="h-3 w-3"></i>
                                            Atrasada
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @php
                                $bloqueiosEtapa = $bloqueiosPorEtapa[$etapa->id] ?? [];
                            @endphp
                            @if (! empty($bloqueiosEtapa))
                                @include('rh.chamados-movimentacao._accordion_pendencias_compacto', [
                                    'pendenciasItens' => $bloqueiosEtapa,
                                    'titulo' => 'Pendências desta etapa',
                                ])
                            @endif
                            @if (($podeEditar ?? true) && $chamado->isAberto() && ! $etapa->isConcluida() && empty($bloqueiosEtapa))
                                <form method="POST" action="{{ route('rh.chamados-movimentacao.etapas.concluir', $etapa) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex h-9 items-center gap-1 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                                        <i data-lucide="check-circle" class="h-3.5 w-3.5"></i>
                                        Concluir etapa
                                    </button>
                                </form>
                            @endif
                        </div>
                        @if ($etapa->checklistItens->isNotEmpty())
                            <ul class="mt-4 space-y-2">
                                @foreach ($etapa->checklistItens as $item)
                                    <li class="flex flex-wrap items-center gap-2 text-xs text-zinc-600">
                                        @if (($podeEditar ?? true) && $chamado->isAberto())
                                            <form method="POST" action="{{ route('rh.chamados-movimentacao.checklist.toggle', $item) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 bg-white px-2 py-1 font-semibold hover:bg-zinc-50">
                                                    <i data-lucide="{{ $item->status === 'concluido' ? 'check-square' : 'square' }}" class="h-3.5 w-3.5"></i>
                                                    {{ $item->status === 'concluido' ? 'Desmarcar' : 'Concluir' }}
                                                </button>
                                            </form>
                                        @else
                                            <i data-lucide="{{ $item->status === 'concluido' ? 'check-square' : 'square' }}" class="h-3.5 w-3.5 text-zinc-400"></i>
                                        @endif
                                        <span>{{ $item->nome }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($chamado->logs->isNotEmpty())
        <section class="mt-6 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h3 class="text-sm font-bold text-zinc-900">Histórico do processo</h3>
            </div>
            <ul class="max-h-64 divide-y divide-zinc-100 overflow-y-auto text-xs">
                @foreach ($chamado->logs as $log)
                    <li class="flex justify-between gap-4 px-5 py-2.5">
                        <span><strong>{{ $log->acao }}</strong> · {{ $log->usuario->name ?? 'Sistema' }}</span>
                        <span class="shrink-0 text-zinc-500">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($chamado->isAberto() && ($podeEditar ?? true))
        <section class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm">
            @if (count($pendenciasFinalizacao) === 0)
                <form method="POST" action="{{ route('rh.chamados-movimentacao.finalizar', $chamado) }}" onsubmit="return confirm('Finalizar chamado e aplicar alterações no cadastro?')">
                    @csrf
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                        <i data-lucide="badge-check" class="h-4 w-4"></i>
                        Finalizar processo
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('rh.chamados-movimentacao.cancelar', $chamado) }}" class="inline" onsubmit="return confirm('Cancelar chamado?')">
                @csrf
                <input type="hidden" name="motivo_cancelamento" value="Cancelado pelo usuário">
                <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 text-sm font-bold text-red-700 transition hover:bg-red-100">
                    <i data-lucide="ban" class="h-4 w-4"></i>
                    Cancelar chamado
                </button>
            </form>
        </section>
    @else
        <p class="mt-6 flex items-center gap-2 text-sm text-zinc-500">
            <i data-lucide="info" class="h-4 w-4 text-zinc-400"></i>
            Chamado encerrado em {{ $chamado->finalizado_em?->format('d/m/Y H:i') ?? $chamado->cancelado_em?->format('d/m/Y H:i') }}.
        </p>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-accordion-pendencias], [data-accordion-grupo]').forEach(function (el) {
            var chevron = el.querySelector('summary [data-lucide="chevron-down"]');
            if (!chevron) return;
            var sync = function () {
                chevron.style.transform = el.open ? 'rotate(180deg)' : '';
            };
            el.addEventListener('toggle', sync);
            sync();
        });
    })();
</script>
@endpush
