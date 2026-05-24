@csrf
@if ($perfil->exists)
    @method('PUT')
@endif

<section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex flex-col gap-2 border-b border-zinc-100 pb-5">
        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Gestão de perfil</p>
        <h2 class="text-xl font-bold text-brand-black">Permissões do perfil</h2>
        <p class="text-sm text-brand-gray">Marque as ações liberadas para cada módulo do sistema.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1fr_2fr_auto]">
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">Nome do perfil *</span>
            <input name="nome" value="{{ old('nome', $perfil->nome) }}" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" required>
            @error('nome') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">Descrição</span>
            <input name="descricao" value="{{ old('descricao', $perfil->descricao) }}" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @error('descricao') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="flex items-end gap-3 rounded-lg border border-zinc-200 px-4 py-3">
            <input type="hidden" name="ativo" value="0">
            <input type="checkbox" name="ativo" value="1" class="h-5 w-5 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked(old('ativo', $perfil->ativo ?? true))>
            <span class="pb-0.5 text-sm font-bold text-brand-black">Perfil ativo</span>
        </label>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-zinc-200 bg-brand-gray-soft text-xs uppercase tracking-wide text-brand-gray">
                <tr>
                    <th class="px-4 py-3">Módulo</th>
                    @foreach ($acoes as $acaoLabel)
                        <th class="px-4 py-3 text-center">{{ $acaoLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white">
                @foreach ($modulos as $modulo => $moduloLabel)
                    <tr>
                        <td class="px-4 py-4 font-bold text-brand-black">{{ $moduloLabel }}</td>
                        @foreach ($acoes as $acao => $acaoLabel)
                            @php
                                $checked = (bool) old("permissoes.{$modulo}.{$acao}", data_get($perfil->permissoes, "{$modulo}.{$acao}", false));
                            @endphp
                            <td class="px-4 py-4 text-center">
                                <input type="hidden" name="permissoes[{{ $modulo }}][{{ $acao }}]" value="0">
                                <input type="checkbox" name="permissoes[{{ $modulo }}][{{ $acao }}]" value="1" class="h-5 w-5 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($checked)>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-5">
        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">SSMA — áreas do menu lateral</p>
        <p class="mt-1 text-sm text-brand-gray">Marque quais opções do SSMA este perfil pode abrir. Só vale se o módulo <strong class="text-brand-black">SSMA</strong> estiver liberado na tabela acima.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($sesmtSecoes as $secaoKey => $secaoLabel)
                @php
                    $checked = (bool) old(
                        "permissoes.sesmt.secoes.{$secaoKey}",
                        $sesmtSecoesInicial[$secaoKey] ?? true,
                    );
                @endphp
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-3 shadow-sm transition hover:border-brand-burgundy/40">
                    <input type="hidden" name="permissoes[sesmt][secoes][{{ $secaoKey }}]" value="0">
                    <input type="checkbox" name="permissoes[sesmt][secoes][{{ $secaoKey }}]" value="1" class="mt-0.5 h-5 w-5 shrink-0 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($checked)>
                    <span class="text-sm font-semibold text-brand-black">{{ $secaoLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-5">
        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">RH — áreas do menu lateral</p>
        <p class="mt-1 text-sm text-brand-gray">Marque quais opções do RH este perfil pode abrir. Só vale se o módulo <strong class="text-brand-black">RH</strong> estiver liberado na tabela acima.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($rhSecoes as $secaoKey => $secaoLabel)
                @php
                    $checked = (bool) old(
                        "permissoes.rh.secoes.{$secaoKey}",
                        $rhSecoesInicial[$secaoKey] ?? true,
                    );
                @endphp
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-3 shadow-sm transition hover:border-brand-burgundy/40">
                    <input type="hidden" name="permissoes[rh][secoes][{{ $secaoKey }}]" value="0">
                    <input type="checkbox" name="permissoes[rh][secoes][{{ $secaoKey }}]" value="1" class="mt-0.5 h-5 w-5 shrink-0 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($checked)>
                    <span class="text-sm font-semibold text-brand-black">{{ $secaoLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-5">
        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Almoxarifado — áreas do menu lateral</p>
        <p class="mt-1 text-sm text-brand-gray">Marque quais opções do Almoxarifado este perfil pode abrir. Só vale se o módulo <strong class="text-brand-black">Almoxarifado</strong> estiver liberado na tabela acima.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($almoxarifadoSecoes as $secaoKey => $secaoLabel)
                @php
                    $checked = (bool) old(
                        "permissoes.almoxarifado.secoes.{$secaoKey}",
                        $almoxarifadoSecoesInicial[$secaoKey] ?? true,
                    );
                @endphp
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-3 shadow-sm transition hover:border-brand-burgundy/40">
                    <input type="hidden" name="permissoes[almoxarifado][secoes][{{ $secaoKey }}]" value="0">
                    <input type="checkbox" name="permissoes[almoxarifado][secoes][{{ $secaoKey }}]" value="1" class="mt-0.5 h-5 w-5 shrink-0 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked($checked)>
                    <span class="text-sm font-semibold text-brand-black">{{ $secaoLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-2 border-t border-zinc-100 pt-5">
        <a href="{{ route('perfis.index') }}" class="inline-flex h-10 items-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">Cancelar</a>
        <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="save" class="h-4 w-4"></i>
            Salvar perfil
        </button>
    </div>
</section>
