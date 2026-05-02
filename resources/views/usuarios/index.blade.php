@extends('layouts.app')

@section('title', 'Usuários - Omega286')
@section('eyebrow', 'Controle de acesso')
@section('page-title', 'Usuários')

@section('actions')
    <a href="{{ route('usuarios.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="user-plus" class="h-4 w-4"></i>
        Novo usuário
    </a>
@endsection

@section('content')
    @php
        $statusClass = [
            'ativo' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'inativo' => 'border-zinc-200 bg-brand-gray-soft text-brand-gray',
            'bloqueado' => 'border-red-200 bg-red-50 text-red-700',
        ];
    @endphp

    <section class="mb-5 grid gap-3 md:grid-cols-4">
        @foreach ([
            ['Usuários', $indicadores['total'], 'users', 'bg-brand-burgundy-soft text-brand-burgundy'],
            ['Ativos', $indicadores['ativos'], 'user-check', 'bg-emerald-50 text-emerald-700'],
            ['Inativos', $indicadores['inativos'], 'user-x', 'bg-zinc-100 text-brand-gray'],
            ['Perfis ativos', $indicadores['perfis'], 'shield-check', 'bg-brand-gray text-white'],
        ] as [$label, $value, $icon, $tone])
            <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $tone }}">
                    <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                </span>
                <p class="mt-3 text-xs font-bold text-brand-gray">{{ $label }}</p>
                <p class="mt-1 text-2xl font-black text-brand-black">{{ $value }}</p>
            </article>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">Usuários cadastrados</h2>
                <p class="mt-1 text-sm text-brand-gray">Gerencie acesso, perfil, status e dados básicos de cada usuário.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome, e-mail, cargo..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-80">
                </label>
                <select name="status" class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    <option value="">Todos</option>
                    <option value="ativo" @selected(request('status') === 'ativo')>Ativos</option>
                    <option value="inativo" @selected(request('status') === 'inativo')>Inativos</option>
                    <option value="bloqueado" @selected(request('status') === 'bloqueado')>Bloqueados</option>
                </select>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Usuário</th>
                        <th class="px-4 py-4">Perfil</th>
                        <th class="px-4 py-4">Contratos</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Último acesso</th>
                        <th class="px-4 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="transition hover:bg-brand-gray-soft/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="user" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $usuario->name }}</p>
                                        <p class="text-xs text-brand-gray">{{ $usuario->email }}{{ $usuario->telefone ? ' · '.$usuario->telefone : '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $usuario->perfil?->nome ?: 'Sem perfil' }}</td>
                            <td class="px-4 py-4">
                                @if ($usuario->todos_contratos)
                                    <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">Todos</span>
                                @else
                                    <span class="rounded-full bg-brand-gray-soft px-3 py-1 text-xs font-bold text-brand-gray">{{ $usuario->contratos->count() }} contrato(s)</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass[$usuario->status] ?? $statusClass['ativo'] }}">
                                    {{ ucfirst($usuario->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-brand-gray">{{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('usuarios.show', $usuario) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('usuarios.edit', $usuario) }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="user-plus" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum usuário cadastrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Crie o primeiro usuário para controlar o acesso ao sistema.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $usuarios->links() }}
        </div>
    </section>
@endsection
