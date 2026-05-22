@extends('layouts.app')

@section('title', 'Novo chamado de movimentação - Omega286')
@section('eyebrow', 'RH / Chamados')
@section('page-title', 'Abrir chamado de movimentação')

@section('actions')
    <a href="{{ route('rh.chamados-movimentacao.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300 hover:shadow-md">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar à lista
    </a>
@endsection

@section('content')
    <section class="relative mb-6 overflow-hidden rounded-3xl border border-brand-burgundy/20 bg-brand-burgundy-dark shadow-lg shadow-brand-burgundy/15">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-burgundy-dark via-brand-burgundy to-[#7a1a36]"></div>
        <div class="pointer-events-none absolute -right-10 top-0 h-56 w-56 rounded-full bg-white/[0.07] blur-3xl"></div>
        <div class="relative p-6 sm:p-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-bold tracking-wide text-brand-burgundy-soft backdrop-blur-sm">
                <i data-lucide="plus-circle" class="h-3.5 w-3.5 text-white/90"></i>
                Nova solicitação
            </span>
            <h2 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">Abrir chamado</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-brand-burgundy-soft/90">
                O cadastro do colaborador <strong class="text-white">não será alterado</strong> neste momento. As etapas do fluxo serão criadas automaticamente; a alteração só ocorre na <strong class="text-white">finalização</strong> do chamado.
            </p>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/50 ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-brand-burgundy/[0.04] via-zinc-50/90 to-white px-6 py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-burgundy text-white shadow-md shadow-brand-burgundy/25">
                    <i data-lucide="file-plus-2" class="h-5 w-5"></i>
                </span>
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-zinc-900">Dados do chamado</h3>
                    <p class="text-xs text-zinc-500">Preencha as informações iniciais do processo</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('rh.chamados-movimentacao.store') }}" enctype="multipart/form-data" class="space-y-6 p-6 sm:p-8">
            @csrf
            <div class="grid gap-6 lg:grid-cols-2">
                <label class="space-y-2 lg:col-span-2">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                        <i data-lucide="user" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                        Colaborador
                    </span>
                    <select name="colaborador_id" required class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                        <option value="">Selecione o colaborador</option>
                        @foreach ($colaboradores as $c)
                            <option value="{{ $c->id }}" @selected(old('colaborador_id', $colaborador?->id) == $c->id)>{{ $c->nome }}@if($c->matricula) ({{ $c->matricula }})@endif</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                        <i data-lucide="git-branch" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                        Tipo de movimentação
                    </span>
                    <select name="tipo" id="tipo" required class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                        @foreach ($tipos as $k => $l)
                            <option value="{{ $k }}" @selected(old('tipo', $tipo) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                        <i data-lucide="calendar" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                        Data prevista / efeito
                    </span>
                    <input type="date" name="data_efetiva" value="{{ old('data_efetiva', today()->format('Y-m-d')) }}" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                </label>

                <label class="space-y-2" id="campo-rescisao">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                        <i data-lucide="file-x" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                        Tipo de rescisão
                    </span>
                    <select name="tipo_rescisao" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium text-zinc-900 outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                        <option value="">—</option>
                        @foreach ($tiposRescisao as $k => $l)
                            <option value="{{ $k }}" @selected(old('tipo_rescisao') === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-2 lg:col-span-2">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                        <i data-lucide="message-square-text" class="h-3.5 w-3.5 text-brand-burgundy/70"></i>
                        Motivo / observação
                    </span>
                    <input type="text" name="motivo_texto" value="{{ old('motivo_texto') }}" placeholder="Descreva brevemente o motivo da solicitação" class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                </label>
            </div>

            @include('rh.chamados-movimentacao._form_desligamento')
            @include('rh.chamados-movimentacao._form_afastamento_inss')

            <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-100 pt-6">
                <a href="{{ route('rh.chamados-movimentacao.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-5 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
                    Cancelar
                </a>
                <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-brand-burgundy px-6 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="send" class="h-4 w-4"></i>
                    Abrir chamado
                </button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script>
    (function () {
        const tipo = document.getElementById('tipo');
        const campoRescisao = document.getElementById('campo-rescisao');
        const camposDesligamento = document.getElementById('campos-desligamento');
        const formInss = document.getElementById('form-afastamento-inss');
        const inicio = document.getElementById('data_inicio_afastamento');
        const fim = document.getElementById('data_final_atestado');
        const dias = document.getElementById('quantidade_dias');

        function calcDias() {
            if (!inicio?.value || !fim?.value || !dias) return;
            const a = new Date(inicio.value);
            const b = new Date(fim.value);
            if (b < a) return;
            dias.value = Math.round((b - a) / 86400000) + 1;
        }

        function toggle() {
            const t = tipo?.value;
            const isDesl = t === 'desligamento';
            if (campoRescisao) campoRescisao.classList.toggle('hidden', !isDesl);
            if (camposDesligamento) {
                camposDesligamento.classList.toggle('hidden', !isDesl);
                camposDesligamento.querySelectorAll('input, select, textarea').forEach((el) => {
                    if (['data_prevista', 'ultimo_dia_trabalhado', 'gestor_responsavel', 'havera_substituicao_vaga'].includes(el.name)) {
                        el.required = isDesl;
                    }
                });
            }
            const rescisao = document.querySelector('[name="tipo_rescisao"]');
            if (rescisao) rescisao.required = isDesl;
            const motivo = document.querySelector('[name="motivo_texto"]');
            if (motivo) motivo.required = isDesl;
            if (formInss) {
                formInss.classList.toggle('hidden', t !== 'afastamento_inss');
                formInss.querySelectorAll('input, select').forEach((el) => {
                    if (el.name === 'atestado_medico') el.required = t === 'afastamento_inss';
                });
            }
        }
        tipo?.addEventListener('change', toggle);
        inicio?.addEventListener('change', calcDias);
        fim?.addEventListener('change', calcDias);
        toggle();
        calcDias();
    })();
</script>
@endpush
