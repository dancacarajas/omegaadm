@extends('layouts.app')

@section('title', 'Perfis - Omega286')
@section('eyebrow', 'Controle de acesso')
@section('page-title', 'Perfis')

@section('actions')
    <a href="{{ route('perfis.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
        <i data-lucide="shield-plus" class="h-4 w-4"></i>
        Novo perfil
    </a>
@endsection

@section('content')
    <section class="mb-5 grid gap-3 md:grid-cols-4">
        @foreach ([
            ['Perfis', $indicadores['total'], 'shield', 'bg-brand-burgundy-soft text-brand-burgundy'],
            ['Ativos', $indicadores['ativos'], 'shield-check', 'bg-emerald-50 text-emerald-700'],
            ['Inativos', $indicadores['inativos'], 'shield-x', 'bg-zinc-100 text-brand-gray'],
            ['Usuários', $indicadores['usuarios'], 'users', 'bg-brand-gray text-white'],
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
                <h2 class="text-xl font-bold text-brand-black">Perfis de acesso</h2>
                <p class="mt-1 text-sm text-brand-gray">Controle permissões por módulo e ações disponíveis para cada tipo de usuário.</p>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                    <input name="busca" value="{{ request('busca') }}" placeholder="Buscar perfil..." class="h-11 w-full rounded-lg border border-zinc-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:w-80">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-5 py-4">Perfil</th>
                        <th class="px-4 py-4">Permissões</th>
                        <th class="px-4 py-4">Usuários</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($perfis as $perfil)
                        <tr class="transition hover:bg-brand-gray-soft/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-burgundy-soft text-brand-burgundy">
                                        <i data-lucide="shield" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-brand-black">{{ $perfil->nome }}</p>
                                        <p class="text-xs text-brand-gray">{{ $perfil->descricao ?: 'Sem descrição' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full bg-brand-gray-soft px-3 py-1 text-xs font-bold text-brand-gray">
                                    {{ collect($perfil->permissoes ?? [])->filter(fn ($acoes) => collect($acoes)->contains(true))->count() }} módulos
                                </span>
                            </td>
                            <td class="px-4 py-4 font-semibold text-brand-black">{{ $perfil->usuarios_count }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $perfil->ativo ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-brand-gray-soft text-brand-gray' }}">
                                    {{ $perfil->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('perfis.show', $perfil) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        Ver
                                    </a>
                                    <a href="{{ route('perfis.edit', $perfil) }}" class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand-burgundy px-3 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="shield-plus" class="h-7 w-7"></i>
                                </div>
                                <p class="mt-4 text-base font-bold text-brand-black">Nenhum perfil cadastrado.</p>
                                <p class="mt-1 text-sm text-brand-gray">Crie perfis para organizar as permissões do sistema.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-5">
            {{ $perfis->links() }}
        </div>
    </section>
@endsection
