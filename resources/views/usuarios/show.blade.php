@extends('layouts.app')

@section('title', 'Usuário - Omega286')
@section('eyebrow', 'Controle de acesso')
@section('page-title', 'Usuário')

@section('actions')
    <div class="flex gap-2">
        <a href="{{ route('usuarios.edit', $usuario) }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="pencil" class="h-4 w-4"></i>
            Editar
        </a>
        <a href="{{ route('usuarios.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Voltar
        </a>
    </div>
@endsection

@section('content')
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                    <i data-lucide="user" class="h-7 w-7"></i>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">{{ $usuario->perfil?->nome ?: 'Sem perfil' }}</p>
                    <h2 class="text-2xl font-bold text-brand-black">{{ $usuario->name }}</h2>
                    <p class="text-sm text-brand-gray">{{ $usuario->email }}</p>
                </div>
            </div>
            <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $usuario->status === 'ativo' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-brand-gray-soft text-brand-gray' }}">
                {{ ucfirst($usuario->status) }}
            </span>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-4">
            @foreach ([
                ['Cargo', $usuario->cargo ?: '-'],
                ['Telefone', $usuario->telefone ?: '-'],
                ['Criado em', $usuario->created_at?->format('d/m/Y H:i') ?: '-'],
                ['Último acesso', $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?: '-'],
            ] as [$label, $value])
                <div class="rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-4">
                    <p class="text-xs font-bold uppercase text-brand-gray">{{ $label }}</p>
                    <p class="mt-2 font-semibold text-brand-black">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-5">
            <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Contratos vinculados</p>
            @if ($usuario->todos_contratos)
                <p class="mt-2 text-base font-bold text-brand-black">Acesso a todos os contratos</p>
                <p class="mt-1 text-sm text-brand-gray">Este usuário não possui restrição por contrato.</p>
            @else
                <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($usuario->contratos as $contrato)
                        <div class="rounded-lg border border-zinc-200 bg-white p-3">
                            <p class="text-sm font-bold text-brand-black">{{ $contrato->numero }} · {{ $contrato->nome }}</p>
                            <p class="text-xs text-brand-gray">{{ $contrato->cliente ?: 'Cliente não informado' }}</p>
                        </div>
                    @empty
                        <p class="text-sm font-semibold text-brand-gray">Nenhum contrato vinculado. Ao logar, este usuário não visualizará dados contratuais.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </section>
@endsection
