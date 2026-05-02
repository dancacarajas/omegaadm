@extends('layouts.app')

@section('title', 'RDO - Omega286')
@section('eyebrow', 'Operação')
@section('page-title', $rdo->titulo ?: 'RDO')

@section('actions')
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('rdo.pdf', $rdo) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-brand-burgundy/20 bg-brand-burgundy-soft px-4 py-2 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:bg-brand-burgundy-soft/80">
            <i data-lucide="file-text" class="h-4 w-4"></i>
            Baixar PDF
        </a>
        <a href="{{ route('rdo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Voltar
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-5">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">{{ $rdo->data?->format('d/m/Y') }}</p>
                    <h2 class="mt-1 text-2xl font-bold text-brand-black">{{ $rdo->titulo ?: 'Relatório diário de obra' }}</h2>
                    <p class="mt-1 text-sm text-brand-gray">{{ $rdo->frente ?: '-' }} · {{ $rdo->area ?: '-' }} · {{ $rdo->disciplina ?: '-' }}</p>
                </div>
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Transmitido</span>
            </div>
        </section>

        @if ($rdo->evidencia_path)
            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                <img src="{{ asset('storage/'.$rdo->evidencia_path) }}" alt="Evidência do RDO" class="max-h-[420px] w-full object-cover">
            </section>
        @endif

        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Linha do tempo</p>
                <div class="mt-4 space-y-3">
                    @forelse ($rdo->atividades ?? [] as $atividade)
                        <div class="rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">{{ $atividade['inicio'] ?? '--' }} até {{ $atividade['fim'] ?? '--' }}</p>
                            <p class="mt-2 text-sm font-semibold text-brand-black">{{ $atividade['descricao'] ?? '-' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-brand-gray">Nenhuma atividade registrada.</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Equipe</p>
                <div class="mt-4 space-y-3">
                    @forelse ($rdo->equipe ?? [] as $pessoa)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-4">
                            <div>
                                <p class="font-semibold text-brand-black">{{ $pessoa['nome'] ?? '-' }}</p>
                                <p class="text-xs text-brand-gray">{{ $pessoa['funcao'] ?? 'Função não informada' }}</p>
                            </div>
                            <span class="text-xs font-bold text-brand-gray">{{ $pessoa['matricula'] ?? '-' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-brand-gray">Nenhuma pessoa registrada.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Responsáveis</p>
                <p class="mt-4 font-semibold text-brand-black">Supervisor: {{ $rdo->supervisor_nome ?: '-' }} {{ $rdo->supervisor_matricula ? '· '.$rdo->supervisor_matricula : '' }}</p>
                <p class="mt-2 font-semibold text-brand-black">Encarregado: {{ $rdo->encarregado_nome ?: '-' }} {{ $rdo->encarregado_matricula ? '· '.$rdo->encarregado_matricula : '' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Observações e ocorrências</p>
                <p class="mt-4 whitespace-pre-line text-sm text-brand-black">{{ $rdo->observacoes ?: '-' }}</p>
                <div class="mt-4 rounded-lg border border-zinc-200 bg-brand-gray-soft/50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ocorrências</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-brand-black">{{ $rdo->ocorrencias ?: '-' }}</p>
                </div>
            </div>
        </section>
    </div>
@endsection
