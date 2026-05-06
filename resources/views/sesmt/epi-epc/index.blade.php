@extends('layouts.app')

@section('title', 'Gestão de EPI/EPC — SSMA - Omega286')
@section('eyebrow', 'SSMA')
@section('page-title', 'Gestão de EPI / EPC')

@section('actions')
    <a href="{{ route('sesmt.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Controle de Conformidade
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">
            {{ session('success') }}
        </div>
    @endif

    <p class="mb-6 max-w-3xl text-sm text-brand-gray">
        Tela dedicada ao controle de <strong class="text-brand-black">EPI</strong> (entregas por colaborador, CA e evidências) e de <strong class="text-brand-black">EPC</strong> (instalações e conformidade por local), independente do registro mensal.
    </p>

    @php
        $ind = $indicadores;
    @endphp

    <section class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-gray">EPIs entregues</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $ind['epi_entregues'] }}</p>
            <p class="mt-1 text-xs text-brand-gray">Status <em>Entregue</em></p>
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-900/80">EPIs pendentes</p>
            <p class="mt-2 text-3xl font-bold text-amber-950">{{ $ind['epi_pendentes'] }}</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-red-900/80">EPIs vencidos</p>
            <p class="mt-2 text-3xl font-bold text-red-950">{{ $ind['epi_vencidos'] }}</p>
            <p class="mt-1 text-xs text-red-900/70">Status <em>Vencido</em></p>
        </article>
        <article class="rounded-xl border border-orange-300 bg-orange-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-orange-950/80">CA vencido</p>
            <p class="mt-2 text-3xl font-bold text-orange-950">{{ $ind['ca_vencido'] }}</p>
            <p class="mt-1 text-xs text-orange-900/80">Validade do CA &lt; hoje (pendente ou entregue)</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-900/80">EPC conforme</p>
            <p class="mt-2 text-3xl font-bold text-emerald-950">{{ $ind['epc_conforme'] }}</p>
            <p class="mt-1 text-xs text-emerald-900/70">Conforme e sem correção</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-red-900/80">EPC não conforme</p>
            <p class="mt-2 text-3xl font-bold text-red-950">{{ $ind['epc_nao_conforme'] }}</p>
            <p class="mt-1 text-xs text-red-900/70">NC ou exige correção</p>
        </article>
    </section>

    <section class="mb-8 grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-gray">Pendências por colaborador</h2>
            <p class="mt-1 text-xs text-brand-gray">Entrega pendente ou CA vencido (exc. cancelados).</p>
            <ul class="mt-4 divide-y divide-zinc-100">
                @forelse ($ind['pendencias_colaborador'] as $row)
                    <li class="flex justify-between py-2 text-sm">
                        <span class="font-semibold text-brand-black">{{ $row->colaborador }}</span>
                        <span class="text-brand-gray">{{ $row->total }}</span>
                    </li>
                @empty
                    <li class="py-4 text-sm text-brand-gray">Nenhuma pendência agrupada.</li>
                @endforelse
            </ul>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wide text-brand-gray">Pendências por local (EPC)</h2>
            <p class="mt-1 text-xs text-brand-gray">Não conforme ou necessita correção.</p>
            <ul class="mt-4 divide-y divide-zinc-100">
                @forelse ($ind['pendencias_local'] as $row)
                    <li class="flex justify-between py-2 text-sm">
                        <span class="font-semibold text-brand-black">{{ $row->local }}</span>
                        <span class="text-brand-gray">{{ $row->total }}</span>
                    </li>
                @empty
                    <li class="py-4 text-sm text-brand-gray">Nenhuma pendência agrupada.</li>
                @endforelse
            </ul>
        </article>
    </section>

    {{-- EPI --}}
    <section class="mb-10 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">EPI — Equipamento de Proteção Individual</h2>
                <p class="mt-1 text-sm text-brand-gray">Controle por colaborador, CA, entrega e substituição.</p>
            </div>
            @if ($podeEditar)
                <a href="{{ route('sesmt.epi-epc.epi.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Novo EPI
                </a>
            @endif
        </div>
        <form method="GET" class="flex flex-wrap gap-3 border-b border-zinc-100 bg-white px-5 py-4">
            <input type="hidden" name="page_epc" value="{{ request('page_epc', 1) }}">
            <label class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                <input name="epi_busca" value="{{ request('epi_busca') }}" placeholder="Colaborador, EPI, CA..." class="h-10 w-full rounded-lg border border-zinc-200 bg-white pl-9 pr-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <select name="epi_status" class="h-10 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="">Todos os status</option>
                @foreach (\App\Models\SsmaEpiEntrega::STATUS as $k => $label)
                    <option value="{{ $k }}" @selected(request('epi_status') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-10 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white hover:bg-brand-burgundy-dark">Filtrar EPI</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-3">Colaborador</th>
                        <th class="px-4 py-3">Cargo</th>
                        <th class="px-4 py-3">EPI</th>
                        <th class="px-4 py-3">CA / validade</th>
                        <th class="px-4 py-3">Entrega / subst.</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($epis as $epi)
                        @php $caVenc = $epi->caEstaVencido() && in_array($epi->status, ['pendente', 'entregue'], true); @endphp
                        <tr class="hover:bg-brand-gray-soft/50 @if ($caVenc) bg-orange-50/60 @endif">
                            <td class="px-4 py-3 font-semibold text-brand-black">{{ $epi->colaborador }}</td>
                            <td class="px-4 py-3 text-brand-gray">{{ $epi->cargo ?: '—' }}</td>
                            <td class="px-4 py-3 text-brand-gray">{{ \Illuminate\Support\Str::limit($epi->epi_obrigatorio, 48) }}</td>
                            <td class="px-4 py-3 text-xs">
                                <span class="font-mono">{{ $epi->ca_numero ?: '—' }}</span>
                                @if ($epi->validade_ca)
                                    <span class="mt-0.5 block @if ($caVenc) font-bold text-orange-700 @else text-brand-gray @endif">Val.: {{ $epi->validade_ca->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-brand-gray">
                                <span class="block">Entr.: {{ $epi->data_entrega?->format('d/m/Y') ?? '—' }}</span>
                                @if ($epi->data_substituicao)
                                    <span class="block">Subst.: {{ $epi->data_substituicao->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold">{{ $epi->rotuloStatus() }}</span>
                                @if ($epi->evidencia_path)
                                    <a href="{{ asset('storage/'.$epi->evidencia_path) }}" target="_blank" rel="noopener" class="ml-1 text-xs font-bold text-brand-burgundy underline-offset-2 hover:underline">Evid.</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if ($podeEditar)
                                    <a href="{{ route('sesmt.epi-epc.epi.edit', $epi) }}" class="text-sm font-bold text-brand-burgundy hover:underline">Editar</a>
                                    <form action="{{ route('sesmt.epi-epc.epi.destroy', $epi) }}" method="POST" class="mt-1 inline" onsubmit="return confirm('Excluir este registro de EPI?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">Excluir</button>
                                    </form>
                                @else
                                    <span class="text-xs text-brand-gray">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-brand-gray">Nenhum EPI encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($epis->hasPages())
            <div class="border-t border-zinc-100 px-5 py-3">{{ $epis->links() }}</div>
        @endif
    </section>

    {{-- EPC --}}
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-black">EPC — Equipamento de Proteção Coletiva</h2>
                <p class="mt-1 text-sm text-brand-gray">Instalações por local, condição e plano de correção.</p>
            </div>
            @if ($podeEditar)
                <a href="{{ route('sesmt.epi-epc.epc.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Novo EPC
                </a>
            @endif
        </div>
        <form method="GET" class="flex flex-wrap gap-3 border-b border-zinc-100 bg-white px-5 py-4">
            <input type="hidden" name="page_epi" value="{{ request('page_epi', 1) }}">
            <label class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-gray"></i>
                <input name="epc_busca" value="{{ request('epc_busca') }}" placeholder="Local, tipo, risco..." class="h-10 w-full rounded-lg border border-zinc-200 bg-white pl-9 pr-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            </label>
            <input name="epc_local" value="{{ request('epc_local') }}" placeholder="Local" class="h-10 w-36 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            <select name="epc_condicao" class="h-10 rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="">Todas condições</option>
                @foreach (\App\Models\SsmaEpcRegistro::CONDICOES as $k => $label)
                    <option value="{{ $k }}" @selected(request('epc_condicao') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-10 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white hover:bg-brand-burgundy-dark">Filtrar EPC</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-white text-xs uppercase tracking-wide text-brand-gray">
                    <tr>
                        <th class="px-4 py-3">Local</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Condição</th>
                        <th class="px-4 py-3">Correção?</th>
                        <th class="px-4 py-3">Risco</th>
                        <th class="px-4 py-3">Resp. / prazo</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($epcs as $epc)
                        <tr class="hover:bg-brand-gray-soft/50 @if ($epc->estaNaoConforme()) bg-red-50/40 @endif">
                            <td class="px-4 py-3 font-semibold text-brand-black">{{ $epc->local }}</td>
                            <td class="px-4 py-3 text-brand-gray">{{ \Illuminate\Support\Str::limit($epc->tipo_epc, 40) }}</td>
                            <td class="px-4 py-3 text-xs">{{ $epc->rotuloCondicao() }}</td>
                            <td class="px-4 py-3">{{ $epc->necessita_correcao ? 'Sim' : 'Não' }}</td>
                            <td class="px-4 py-3 text-xs text-brand-gray">{{ \Illuminate\Support\Str::limit($epc->risco_associado ?? '—', 40) }}</td>
                            <td class="px-4 py-3 text-xs">
                                {{ $epc->responsavel ?: '—' }}
                                <span class="block text-brand-gray">{{ $epc->prazo?->format('d/m/Y') ?? '' }}</span>
                                @if ($epc->evidencia_foto_path)
                                    <a href="{{ asset('storage/'.$epc->evidencia_foto_path) }}" target="_blank" rel="noopener" class="text-brand-burgundy hover:underline">Foto</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if ($podeEditar)
                                    <a href="{{ route('sesmt.epi-epc.epc.edit', $epc) }}" class="text-sm font-bold text-brand-burgundy hover:underline">Editar</a>
                                    <form action="{{ route('sesmt.epi-epc.epc.destroy', $epc) }}" method="POST" class="mt-1 inline" onsubmit="return confirm('Excluir este EPC?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">Excluir</button>
                                    </form>
                                @else
                                    <span class="text-xs text-brand-gray">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-brand-gray">Nenhum EPC encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($epcs->hasPages())
            <div class="border-t border-zinc-100 px-5 py-3">{{ $epcs->links() }}</div>
        @endif
    </section>
@endsection
