@php
    $capsulas = $beneficioAdesaoDestinatariosCapsulas ?? [];
    $colabOpcoes = $colaboradoresEmailOpcoes ?? [];
    $userOpcoes = $usuariosEmailOpcoes ?? [];
@endphp

<form method="POST" action="{{ route('configuracoes.email.beneficio-adesao-matriz-destinatarios.update') }}" id="form-beneficio-adesao-destinatarios" class="border-b border-zinc-100 px-4 py-4 sm:px-6 sm:py-5">
    @csrf
    @method('PUT')

    <input type="hidden" name="destinatarios_json" id="beneficio-adesao-destinatarios-json" value="{{ old('destinatarios_json', json_encode(array_map(fn ($c) => ['tipo' => $c['tipo'], 'id' => $c['id']], $capsulas))) }}">

    <div class="rounded-xl border border-violet-100 bg-violet-50/80 px-4 py-3 text-sm text-violet-950">
        <p class="font-semibold">Dois envios separados (não invertam os papéis)</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
            <li><strong>Sua cópia</strong> → remetente <strong>Omega / SMTP central</strong> (286omega@gmail.com ou o configurado acima).</li>
            <li><strong>Matriz (ex. Celiamara)</strong> → remetente <strong>Jarbas</strong> via SMTP Zimbra (bloco âmbar).</li>
        </ul>
    </div>

    <label class="mt-4 block space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
        Sua cópia — remetente do sistema (não coloque Celiamara aqui)
        <input type="email" name="beneficio_adesao_copia_email" required
               value="{{ old('beneficio_adesao_copia_email', $beneficio_adesao_copia_email ?? \App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService::EMAIL_COPIA_INTERNA_PADRAO) }}"
               class="mt-1 h-11 w-full max-w-xl rounded-xl border border-zinc-200 px-3 text-sm" placeholder="jarbas.alves@omegaservice.com.br">
        <span class="mt-1 block font-normal normal-case text-zinc-500">E-mail que <strong>você</strong> recebe para conferir o pedido (caixa Zimbra corporativa).</span>
    </label>

    <p class="mt-6 text-sm font-bold text-zinc-900">Destinatários da Matriz — remetente Jarbas (Zimbra)</p>
    <p class="mt-0.5 text-xs text-zinc-500">
        Quem recebe o pedido como se você tivesse enviado (ex.: {{ \App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService::RESPONSAVEL_MATRIZ }}).
        <strong class="text-zinc-700">Não coloque jarbas.alves@omegaservice.com.br aqui</strong> — use o campo “Sua cópia” acima.
    </p>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
        <label class="min-w-0 flex-1 space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:max-w-xs">
            Colaborador
            <select id="beneficio-adesao-select-colaborador" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900 outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Selecione…</option>
                @foreach ($colabOpcoes as $op)
                    <option value="{{ $op['id'] }}">{{ $op['nome'] }}@if ($op['matricula']) · {{ $op['matricula'] }}@endif — {{ $op['email'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="min-w-0 flex-1 space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:max-w-xs">
            Usuário
            <select id="beneficio-adesao-select-usuario" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900 outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Selecione…</option>
                @foreach ($userOpcoes as $op)
                    <option value="{{ $op['id'] }}">{{ $op['nome'] }} — {{ $op['email'] }}</option>
                @endforeach
            </select>
        </label>
        <button type="button" id="beneficio-adesao-btn-adicionar" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl border border-brand-burgundy/30 bg-brand-burgundy-soft px-4 text-sm font-bold text-brand-burgundy transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Adicionar
        </button>
    </div>

    <p id="beneficio-adesao-destinatarios-erro" class="mt-2 hidden text-xs font-semibold text-red-600"></p>

    <div id="beneficio-adesao-capsulas" class="mt-4 flex min-h-[2.5rem] flex-wrap gap-2">
        @foreach ($capsulas as $cap)
            <span
                class="beneficio-adesao-capsula inline-flex max-w-full items-center gap-1.5 rounded-full border border-brand-burgundy/25 bg-brand-burgundy-soft py-1.5 pl-3 pr-1.5 text-xs font-semibold text-brand-burgundy"
                data-key="{{ $cap['key'] }}"
            >
                <span class="truncate">{{ $cap['nome'] }}</span>
                <span class="shrink-0 rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-burgundy/80">{{ $cap['tipo_label'] }}</span>
                <button type="button" class="beneficio-adesao-capsula-remove flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white" title="Remover" aria-label="Remover {{ $cap['nome'] }}">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </span>
        @endforeach
    </div>
    <p id="beneficio-adesao-capsulas-vazio" class="mt-2 text-xs text-zinc-400 {{ count($capsulas) > 0 ? 'hidden' : '' }}">Nenhum destinatário — o envio à Matriz fica indisponível.</p>

    <div class="mt-4 flex justify-end">
        <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-xs font-bold text-brand-burgundy shadow-sm transition hover:border-brand-burgundy">
            <i data-lucide="save" class="h-3.5 w-3.5"></i>
            Salvar destinatários
        </button>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const colabOpcoes = @json($colabOpcoes);
    const userOpcoes = @json($userOpcoes);
    const hidden = document.getElementById('beneficio-adesao-destinatarios-json');
    const container = document.getElementById('beneficio-adesao-capsulas');
    const vazio = document.getElementById('beneficio-adesao-capsulas-vazio');
    const erro = document.getElementById('beneficio-adesao-destinatarios-erro');
    const selColab = document.getElementById('beneficio-adesao-select-colaborador');
    const selUser = document.getElementById('beneficio-adesao-select-usuario');
    const btnAdd = document.getElementById('beneficio-adesao-btn-adicionar');
    const form = document.getElementById('form-beneficio-adesao-destinatarios');

    if (!hidden || !container) return;

    const itens = new Map();

    const resolverCapsula = (tipo, id) => {
        const lista = tipo === 'colaborador' ? colabOpcoes : userOpcoes;
        const found = lista.find((o) => o.id === id);
        if (!found) return null;
        return {
            key: `${tipo}:${id}`,
            tipo,
            id,
            nome: found.nome,
            tipo_label: tipo === 'colaborador' ? 'Colaborador' : 'Usuário',
        };
    };

    const parseInitial = () => {
        try {
            const raw = JSON.parse(hidden.value || '[]');
            if (!Array.isArray(raw)) return;
            raw.forEach((item) => {
                if (!item?.tipo || !item?.id) return;
                const cap = resolverCapsula(item.tipo, parseInt(item.id, 10));
                if (cap) itens.set(cap.key, cap);
            });
        } catch (_) { /* ignore */ }
        render();
    };

    const syncHidden = () => {
        hidden.value = JSON.stringify(
            Array.from(itens.values()).map((c) => ({ tipo: c.tipo, id: c.id }))
        );
    };

    const render = () => {
        container.querySelectorAll('.beneficio-adesao-capsula').forEach((el) => el.remove());
        itens.forEach((cap) => {
            const span = document.createElement('span');
            span.className = 'beneficio-adesao-capsula inline-flex max-w-full items-center gap-1.5 rounded-full border border-brand-burgundy/25 bg-brand-burgundy-soft py-1.5 pl-3 pr-1.5 text-xs font-semibold text-brand-burgundy';
            span.dataset.key = cap.key;
            span.innerHTML = `
                <span class="truncate">${cap.nome}</span>
                <span class="shrink-0 rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-burgundy/80">${cap.tipo_label}</span>
                <button type="button" class="beneficio-adesao-capsula-remove flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white" title="Remover">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            `;
            span.querySelector('.beneficio-adesao-capsula-remove')?.addEventListener('click', () => {
                itens.delete(cap.key);
                syncHidden();
                render();
                if (window.lucide) window.lucide.createIcons();
            });
            container.appendChild(span);
        });
        vazio?.classList.toggle('hidden', itens.size > 0);
        syncHidden();
        if (window.lucide) window.lucide.createIcons();
    };

    const mostrarErro = (msg) => {
        if (!erro) return;
        if (!msg) {
            erro.classList.add('hidden');
            erro.textContent = '';
            return;
        }
        erro.textContent = msg;
        erro.classList.remove('hidden');
    };

    btnAdd?.addEventListener('click', () => {
        mostrarErro('');
        const colabId = selColab?.value ? parseInt(selColab.value, 10) : 0;
        const userId = selUser?.value ? parseInt(selUser.value, 10) : 0;

        if (colabId && userId) {
            mostrarErro('Selecione apenas Colaborador ou Usuário, não os dois ao mesmo tempo.');
            return;
        }

        let cap = null;
        if (colabId) {
            cap = resolverCapsula('colaborador', colabId);
            selColab.value = '';
        } else if (userId) {
            cap = resolverCapsula('usuario', userId);
            selUser.value = '';
        } else {
            mostrarErro('Selecione um colaborador ou um usuário.');
            return;
        }

        if (!cap) {
            mostrarErro('Pessoa sem e-mail válido no cadastro.');
            return;
        }

        if (itens.has(cap.key)) {
            mostrarErro('Esta pessoa já está na lista.');
            return;
        }

        itens.set(cap.key, cap);
        render();
    });

    form?.addEventListener('submit', () => syncHidden());

    parseInitial();
})();
</script>
@endpush
