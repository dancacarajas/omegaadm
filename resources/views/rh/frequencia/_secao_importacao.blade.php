<div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
    <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Ferramentas</p>
        <h3 class="mt-1 text-lg font-bold text-brand-black">Importar e exportar marcações</h3>
        <p class="mt-1 text-sm text-brand-gray">CSV do relógio, AFD e exportação para outros sistemas.</p>
    </motion>

    <div class="space-y-6 p-5">
        <div>
            <h4 class="text-sm font-bold text-brand-black">Importar CSV</h4>
            <p class="mt-1 text-xs text-brand-gray">Separador <code class="rounded bg-zinc-100 px-1">;</code> — matrícula, CPF, Dia e marcações.</p>
            <form method="POST" action="{{ route('rh.frequencia.importar-csv') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                @csrf
                <input type="hidden" name="escopo_colaboradores" value="todos">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data início</span>
                        <input type="date" name="data_inicio" value="{{ old('data_inicio', request('csv_data_inicio', $exportInicio)) }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                    </label>
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data fim</span>
                        <input type="date" name="data_fim" value="{{ old('data_fim', request('csv_data_fim', $exportFim)) }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10">
                    </label>
                </motion>
                <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Arquivo CSV</span>
                        <input type="file" name="arquivo" accept=".csv,.txt,text/csv" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-brand-gray outline-none file:mr-3 file:rounded-md file:border-0 file:bg-emerald-700 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                    </label>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                        <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                        Importar CSV
                    </button>
                </motion>
            </form>
        </motion>

        <div class="border-t border-zinc-100 pt-5">
            <h4 class="text-sm font-bold text-brand-black">Importar AFD</h4>
            <form method="POST" action="{{ route('rh.frequencia.importar-afd') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                @csrf
                <label class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Arquivo AFD</span>
                    <input type="file" name="arquivo" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-brand-gray outline-none file:mr-3 file:rounded-md file:border-0 file:bg-brand-burgundy file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white">
                </label>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                    Importar
                </button>
            </form>
        </motion>

        <div class="border-t border-zinc-100 pt-5">
            <h4 class="text-sm font-bold text-brand-black">Exportar AFD</h4>
            <form method="GET" action="{{ route('rh.frequencia.exportar-afd') }}" class="mt-3 space-y-3">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data início</span>
                        <input type="date" name="data_inicio" value="{{ $exportInicio }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <label class="space-y-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data fim</span>
                        <input type="date" name="data_fim" value="{{ $exportFim }}" required class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                    </label>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-brand-burgundy/30 bg-brand-burgundy-soft px-5 text-sm font-bold text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        Exportar
                    </button>
                </motion>
                <input type="hidden" name="data" value="{{ $data }}">
                <input type="hidden" name="mes" value="{{ $mes }}">
                @if (request('busca'))
                    <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-brand-gray">
                        <input type="checkbox" name="filtrar_busca" value="1" checked class="rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy/20">
                        <span>Aplicar busca da listagem: «{{ request('busca') }}»</span>
                    </label>
                    <input type="hidden" name="busca" value="{{ request('busca') }}">
                @endif
            </form>
        </motion>
    </motion>
</motion>
