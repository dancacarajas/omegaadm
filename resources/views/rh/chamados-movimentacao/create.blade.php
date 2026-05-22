@extends('layouts.app')

@section('title', 'Novo chamado de movimentação - Omega286')
@section('page-title', 'Abrir chamado de movimentação')

@section('content')
    <p class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        O cadastro do colaborador <strong>não será alterado</strong> agora. As etapas do fluxo serão criadas automaticamente; a alteração só ocorre na <strong>finalização</strong> do chamado.
    </p>

    <form method="POST" action="{{ route('rh.chamados-movimentacao.store') }}" class="max-w-2xl space-y-6 rounded-xl border bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label class="text-xs font-bold uppercase text-zinc-500">Colaborador</label>
            <select name="colaborador_id" required class="mt-2 h-11 w-full rounded-lg border px-3 text-sm">
                <option value="">Selecione</option>
                @foreach ($colaboradores as $c)
                    <option value="{{ $c->id }}" @selected(old('colaborador_id', $colaborador?->id) == $c->id)>{{ $c->nome }} @if($c->matricula)({{ $c->matricula }})@endif</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-zinc-500">Tipo de movimentação</label>
            <select name="tipo" id="tipo" required class="mt-2 h-11 w-full rounded-lg border px-3 text-sm">
                @foreach ($tipos as $k => $l)
                    <option value="{{ $k }}" @selected(old('tipo', $tipo) === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-zinc-500">Data prevista / efeito</label>
                <input type="date" name="data_efetiva" value="{{ old('data_efetiva', today()->format('Y-m-d')) }}" class="mt-2 h-11 w-full rounded-lg border px-3 text-sm">
            </div>
            <div id="campo-rescisao">
                <label class="text-xs font-bold uppercase text-zinc-500">Tipo de rescisão</label>
                <select name="tipo_rescisao" class="mt-2 h-11 w-full rounded-lg border px-3 text-sm">
                    <option value="">—</option>
                    @foreach ($tiposRescisao as $k => $l)
                        <option value="{{ $k }}" @selected(old('tipo_rescisao') === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-zinc-500">Motivo / observação</label>
            <input type="text" name="motivo_texto" value="{{ old('motivo_texto') }}" class="mt-2 h-11 w-full rounded-lg border px-3 text-sm">
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('rh.chamados-movimentacao.index') }}" class="h-10 rounded-lg border px-4 text-sm font-semibold">Cancelar</a>
            <button type="submit" class="h-10 rounded-lg bg-brand-burgundy px-5 text-sm font-bold text-white">Abrir chamado</button>
        </div>
    </form>
@endsection
