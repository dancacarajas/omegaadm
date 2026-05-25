@extends('layouts.app')

@section('title', 'E-mails Enviados - Omega286')
@section('eyebrow', 'Configurações')
@section('page-title', 'E-mails Enviados')

@section('actions')
    <a href="{{ route('configuracoes.email.edit') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
        <i data-lucide="settings" class="h-4 w-4"></i>
        Configuração de E-mail
    </a>
@endsection

@section('content')
    <p class="mb-6 max-w-3xl text-sm text-zinc-600">
        Hub de todos os e-mails automáticos do sistema: catálogo por módulo, pré-visualização e histórico de envios registrados a partir de agora.
    </p>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Enviados (30 dias)</p>
            <p class="mt-2 text-3xl font-black text-brand-burgundy">{{ number_format($kpis['total_30d'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Hoje</p>
            <p class="mt-2 text-3xl font-black text-zinc-900">{{ number_format($kpis['hoje'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:col-span-2">
            <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Por categoria (30 dias)</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($kpis['por_categoria'] as $cat => $total)
                    <span class="rounded-full bg-brand-burgundy-soft px-3 py-1 text-xs font-bold text-brand-burgundy">
                        {{ $categorias[$cat] ?? $cat }}: {{ $total }}
                    </span>
                @empty
                    <span class="text-xs text-zinc-400">Nenhum envio registrado ainda.</span>
                @endforelse
            </div>
        </div>
    </div>

    <section class="mb-8 overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <h2 class="text-lg font-bold text-zinc-900">Catálogo de e-mails do sistema</h2>
            <p class="mt-1 text-sm text-zinc-500">Tipos de mensagem, gatilho, mailer e links para configurar ou pré-visualizar.</p>
        </div>
        <div class="space-y-8 p-6 sm:p-8">
            @foreach ($porCategoria as $categoria => $itens)
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">{{ $categorias[$categoria] ?? $categoria }}</h3>
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        @foreach ($itens as $item)
                            <article class="rounded-2xl border border-zinc-100 bg-zinc-50/50 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <h4 class="font-bold text-zinc-900">{{ $item['nome'] }}</h4>
                                    <span class="rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase text-zinc-600 ring-1 ring-zinc-200">{{ $item['mailer_label'] }}</span>
                                </div>
                                <p class="mt-2 text-sm text-zinc-600">{{ $item['descricao'] }}</p>
                                <p class="mt-2 text-xs text-zinc-500"><strong>Gatilho:</strong> {{ $item['gatilho'] }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if ($item['preview_route'])
                                        <a href="{{ route($item['preview_route'], $item['preview_params']) }}" target="_blank" rel="noopener"
                                           class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-bold text-brand-burgundy transition hover:border-brand-burgundy">
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            Pré-visualizar
                                        </a>
                                    @endif
                                    @if ($item['config_route'])
                                        <a href="{{ route($item['config_route']) }}{{ $item['config_anchor'] ? '#'.$item['config_anchor'] : '' }}"
                                           class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-bold text-zinc-700 transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                            <i data-lucide="settings-2" class="h-3.5 w-3.5"></i>
                                            Configurar
                                        </a>
                                    @endif
                                    <a href="{{ route('configuracoes.emails-enviados.index', ['tipo' => $item['tipo']]) }}"
                                       class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-bold text-zinc-700 transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                        <i data-lucide="history" class="h-3.5 w-3.5"></i>
                                        Histórico
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <h2 class="text-lg font-bold text-zinc-900">Histórico de envios</h2>
            <p class="mt-1 text-sm text-zinc-500">Registro automático de cada mensagem enviada pelo sistema (a partir da ativação desta versão).</p>
        </div>

        <form method="GET" action="{{ route('configuracoes.emails-enviados.index') }}" class="grid gap-4 border-b border-zinc-100 p-6 sm:grid-cols-2 lg:grid-cols-5">
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Categoria
                <select name="categoria" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">
                    <option value="">Todas</option>
                    @foreach ($categorias as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(($filtros['categoria'] ?? '') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Tipo
                <select name="tipo" class="mt-1 h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">
                    <option value="">Todos</option>
                    @foreach ($tiposCatalogo as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(($filtros['tipo'] ?? '') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Destinatário
                <input type="text" name="destinatario" value="{{ $filtros['destinatario'] ?? '' }}" placeholder="e-mail"
                       class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                De
                <input type="date" name="de" value="{{ $filtros['de'] ?? '' }}"
                       class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Até
                <input type="date" name="ate" value="{{ $filtros['ate'] ?? '' }}"
                       class="mt-1 h-10 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filtrar
                </button>
                <a href="{{ route('configuracoes.emails-enviados.index') }}" class="inline-flex h-10 items-center rounded-xl border border-zinc-200 px-4 text-sm font-semibold text-zinc-600 hover:bg-zinc-50">Limpar</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            @if ($envios === null)
                <p class="p-8 text-sm text-amber-800">Execute <code class="rounded bg-amber-100 px-1">php artisan migrate</code> para ativar o histórico.</p>
            @elseif ($envios->isEmpty())
                <p class="p-8 text-sm text-zinc-500">Nenhum envio encontrado com os filtros atuais.</p>
            @else
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-zinc-100 bg-zinc-50/80 text-[10px] font-bold uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Data/hora</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Destinatário</th>
                            <th class="px-4 py-3">Assunto</th>
                            <th class="px-4 py-3">Mailer</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($envios as $envio)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-600">{{ $envio->enviado_em->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-zinc-900">{{ $envio->nome ?? $envio->tipo }}</span>
                                    <span class="block text-[10px] uppercase text-zinc-400">{{ $categorias[$envio->categoria] ?? $envio->categoria }}</span>
                                </td>
                                <td class="px-4 py-3 text-zinc-800">{{ $envio->destinatario }}</td>
                                <td class="max-w-xs truncate px-4 py-3 text-zinc-600" title="{{ $envio->assunto }}">{{ $envio->assunto }}</td>
                                <td class="px-4 py-3 text-xs text-zinc-500">{{ $envio->mailer }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('configuracoes.emails-enviados.show', $envio) }}" class="text-xs font-bold text-brand-burgundy hover:underline">Detalhes</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-zinc-100 px-4 py-4">
                    {{ $envios->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
