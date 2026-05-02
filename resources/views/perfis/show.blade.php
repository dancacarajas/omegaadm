@extends('layouts.app')

@section('title', 'Perfil - Omega286')
@section('eyebrow', 'Controle de acesso')
@section('page-title', 'Perfil')

@section('actions')
    <div class="flex gap-2">
        <a href="{{ route('perfis.edit', $perfil) }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="pencil" class="h-4 w-4"></i>
            Editar
        </a>
        <a href="{{ route('perfis.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Voltar
        </a>
    </div>
@endsection

@section('content')
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-6">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">{{ $perfil->ativo ? 'Ativo' : 'Inativo' }}</p>
            <h2 class="mt-1 text-2xl font-bold text-brand-black">{{ $perfil->nome }}</h2>
            <p class="mt-1 text-sm text-brand-gray">{{ $perfil->descricao ?: 'Sem descrição' }}</p>
            <p class="mt-3 text-sm font-semibold text-brand-black">{{ $perfil->usuarios_count }} usuário(s) vinculado(s)</p>
        </div>

        <div class="overflow-x-auto p-6">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-brand-gray-soft text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-3">Módulo</th>
                        @foreach ($acoes as $acaoLabel)
                            <th class="px-4 py-3 text-center">{{ $acaoLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($modulos as $modulo => $moduloLabel)
                        <tr>
                            <td class="px-4 py-4 font-bold text-brand-black">{{ $moduloLabel }}</td>
                            @foreach ($acoes as $acao => $acaoLabel)
                                @php $allowed = (bool) data_get($perfil->permissoes, "{$modulo}.{$acao}", false); @endphp
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full {{ $allowed ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-400' }}">
                                        <i data-lucide="{{ $allowed ? 'check' : 'minus' }}" class="h-4 w-4"></i>
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
