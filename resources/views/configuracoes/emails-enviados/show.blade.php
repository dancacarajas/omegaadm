@extends('layouts.app')

@section('title', 'Detalhe do envio - Omega286')
@section('eyebrow', 'Configurações')
@section('page-title', 'Detalhe do e-mail enviado')

@section('actions')
    <a href="{{ route('configuracoes.emails-enviados.index', request()->only(['categoria', 'tipo', 'destinatario', 'de', 'ate'])) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200/80 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-zinc-300">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar ao hub
    </a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-3xl border border-zinc-200/80 bg-white shadow-lg ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 bg-gradient-to-r from-zinc-50/90 to-white px-6 py-5">
            <h2 class="text-lg font-bold text-zinc-900">{{ $envio->nome ?? $envio->tipo }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ $envio->assunto }}</p>
        </div>
        <dl class="grid gap-6 p-6 sm:grid-cols-2 sm:p-8">
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Enviado em</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ $envio->enviado_em->format('d/m/Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Status</dt>
                <dd class="mt-1 font-semibold text-emerald-700">{{ ucfirst($envio->status) }}</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Destinatário</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ $envio->destinatario }}</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Remetente</dt>
                <dd class="mt-1 text-sm text-zinc-800">
                    @if ($envio->from_name)
                        {{ $envio->from_name }} &lt;{{ $envio->from_address }}&gt;
                    @else
                        {{ $envio->from_address ?? '—' }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Mailer</dt>
                <dd class="mt-1 font-mono text-sm text-zinc-800">{{ $envio->mailer ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Categoria / tipo</dt>
                <dd class="mt-1 text-sm text-zinc-800">{{ $envio->categoria }} · <code class="rounded bg-zinc-100 px-1">{{ $envio->tipo }}</code></dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Anexos</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ $envio->anexos_qtd }}</dd>
            </div>
            <div>
                <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Disparado por</dt>
                <dd class="mt-1 text-sm text-zinc-800">
                    @if ($envio->enviadoPor)
                        {{ $envio->enviadoPor->name }} ({{ $envio->enviadoPor->email }})
                    @else
                        Sistema automático
                    @endif
                </dd>
            </div>
            @if ($envio->referencia_tipo)
                <div class="sm:col-span-2">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Referência</dt>
                    <dd class="mt-1 font-mono text-sm text-zinc-800">{{ $envio->referencia_tipo }} #{{ $envio->referencia_id }}</dd>
                </div>
            @endif
        </dl>

        @if ($itemCatalogo && ($itemCatalogo['preview_route'] ?? null))
            <div class="border-t border-zinc-100 px-6 py-5 sm:px-8">
                <a href="{{ route($itemCatalogo['preview_route'], $itemCatalogo['preview_params']) }}" target="_blank" rel="noopener"
                   class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                    Abrir modelo deste tipo de e-mail
                </a>
            </div>
        @endif
    </div>
@endsection
