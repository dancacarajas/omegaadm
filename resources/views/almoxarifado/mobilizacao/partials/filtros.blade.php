@php
    use App\Support\Almoxarifado\MobilizacaoPlanilhaCatalogo;
    $filtrosRapidos = [
        '' => 'Todos',
        'sem_tratativa' => 'Sem tratativa',
        'pedido_sigo' => 'Pedido no SIGO',
        'em_compras' => 'Em compras',
        'compra_parcial' => 'Compra parcial',
        'recebido_parcial' => 'Recebido parcial',
        'recebido_total' => 'Recebido total',
        'atrasados' => 'Atrasados',
        'sem_previsao' => 'Sem previsão',
        'criticos' => 'Críticos',
        'cancelado' => 'Cancelados',
    ];
    $temFiltro = request()->filled('busca') || request()->filled('contrato_id') || request()->filled('filtro_rapido')
        || request()->filled('status') || request()->filled('disciplina') || request()->filled('categoria_descricao')
        || request()->filled('prioridade') || request()->filled('numero_pm') || request()->filled('numero_oc');
    $limparUrl = $limparUrl ?? request()->url();
@endphp
<form method="GET" action="{{ $action ?? request()->url() }}" class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
        <label class="space-y-2 sm:col-span-2 lg:col-span-4">
            <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                <i data-lucide="search" class="h-3.5 w-3.5"></i>
                Buscar
            </span>
            <input name="busca" value="{{ request('busca') }}" placeholder="Código, material, PM ou OC…" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
        </label>
        <label class="space-y-2 lg:col-span-2">
            <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                Contrato
            </span>
            <select name="contrato_id" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium text-brand-black outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Todos</option>
                @foreach ($contratos as $c)
                    <option value="{{ $c->id }}" @selected((string) request('contrato_id') === (string) $c->id)>{{ $c->numero }} — {{ $c->nome }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-2 lg:col-span-2">
            <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-gray">
                <i data-lucide="zap" class="h-3.5 w-3.5"></i>
                Filtro rápido
            </span>
            <select name="filtro_rapido" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium text-brand-black outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                @foreach ($filtrosRapidos as $key => $label)
                    <option value="{{ $key }}" @selected(request('filtro_rapido') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-2 lg:col-span-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Status</span>
            <select name="status" class="h-12 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 text-sm font-medium text-brand-black outline-none transition focus:border-brand-burgundy focus:bg-white focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Todos</option>
                @foreach ($statusLabels as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-2">
            <button type="submit" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-brand-burgundy px-4 text-sm font-bold text-white shadow-md shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="filter" class="h-4 w-4"></i>
                Filtrar
            </button>
            @if ($temFiltro)
                <a href="{{ $limparUrl }}" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-brand-gray transition hover:border-zinc-300 hover:text-brand-black" title="Limpar filtros">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </a>
            @endif
        </div>
    </div>
    <div class="mt-4 grid gap-4 border-t border-zinc-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Disciplina</span>
            <select name="disciplina" class="h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Todas</option>
                @foreach (MobilizacaoPlanilhaCatalogo::disciplinas() as $d)
                    <option value="{{ $d }}" @selected(request('disciplina') === $d)>{{ $d }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Categoria</span>
            <select name="categoria_descricao" class="h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Todas</option>
                @foreach (MobilizacaoPlanilhaCatalogo::categorias() as $cat)
                    <option value="{{ $cat }}" @selected(request('categoria_descricao') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </label>
        <label class="space-y-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Prioridade</span>
            <select name="prioridade" class="h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Todas</option>
                @foreach ($prioridadeLabels as $key => $label)
                    <option value="{{ $key }}" @selected(request('prioridade') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <div class="grid grid-cols-2 gap-2">
            <label class="space-y-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">PM</span>
                <input name="numero_pm" value="{{ request('numero_pm') }}" class="h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
            </label>
            <label class="space-y-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">OC</span>
                <input name="numero_oc" value="{{ request('numero_oc') }}" class="h-11 w-full rounded-2xl border border-zinc-200 bg-zinc-50/50 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
            </label>
        </div>
    </div>
    @if ($temFiltro)
        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
            <span class="text-[11px] font-bold uppercase tracking-wider text-brand-gray">Filtros ativos:</span>
            @if (request()->filled('busca'))
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-burgundy-soft px-2.5 py-1 text-xs font-semibold text-brand-burgundy">{{ request('busca') }}</span>
            @endif
            @if (request()->filled('filtro_rapido'))
                <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-brand-gray">{{ $filtrosRapidos[request('filtro_rapido')] ?? request('filtro_rapido') }}</span>
            @endif
            @if (request()->filled('disciplina'))
                <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-brand-gray">{{ request('disciplina') }}</span>
            @endif
        </div>
    @endif
</form>
