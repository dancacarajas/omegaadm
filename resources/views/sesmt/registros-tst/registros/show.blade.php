@extends('layouts.app')

@section('title', 'Registro TST #' . $registro->id)
@section('eyebrow', 'SSMA / Registros TST')
@section('page-title', 'Registro de campo')

@section('actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sesmt.registros-tst.registros.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Lista
        </a>
        @if ($podeEditar)
            <a href="{{ route('sesmt.registros-tst.registros.edit', $registro) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="pencil" class="h-4 w-4"></i>
                Editar
            </a>
            <form method="POST" action="{{ route('sesmt.registros-tst.registros.destroy', $registro) }}" onsubmit="return confirm('Excluir este registro permanentemente?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 transition hover:bg-red-100">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Excluir
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Dados do registro</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-xs font-bold uppercase text-brand-gray">Data</dt>
                    <dd class="mt-1 font-semibold text-brand-black">{{ $registro->data->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase text-brand-gray">Colaborador</dt>
                    <dd class="mt-1 font-semibold text-brand-black">{{ $registro->colaborador?->nome ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase text-brand-gray">Atividade</dt>
                    <dd class="mt-1 text-brand-black">{{ $registro->atividade?->nome ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase text-brand-gray">Descrição da atividade</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-brand-black">{{ $registro->descricao }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase text-brand-gray">Enviado em</dt>
                    <dd class="mt-1 text-brand-black">{{ $registro->created_at->format('d/m/Y H:i') }}@if ($registro->usuario) — {{ $registro->usuario->name }}@endif</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">
                Registro fotográfico
                @if ($registro->fotos->isNotEmpty())
                    <span class="ml-1 font-normal normal-case text-brand-gray">({{ $registro->fotos->count() }})</span>
                @endif
            </h2>
            @if ($registro->fotos->isNotEmpty())
                <div class="tst-fotos-grid tst-fotos-grid--show mt-4">
                    @foreach ($registro->fotos as $foto)
                        @php $url = $foto->urlPublica(); @endphp
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="tst-foto-thumb tst-foto-thumb--link">
                                <img src="{{ $url }}" alt="Foto {{ $loop->iteration }}">
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-brand-gray">Nenhuma foto anexada.</p>
            @endif
        </section>
    </div>
@endsection
