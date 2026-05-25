@php
    $capsulas = $tstDestinatariosCapsulas ?? [];
    $colabOpcoes = $colaboradoresEmailOpcoes ?? [];
    $userOpcoes = $usuariosEmailOpcoes ?? [];
@endphp

<div id="tst-destinatarios"></div>
<form method="POST" action="{{ route('configuracoes.email.tst-destinatarios.update') }}" id="form-tst-destinatarios" class="border-b border-zinc-100 px-4 py-4 sm:px-6 sm:py-5">
    @csrf
    @method('PUT')

    <input type="hidden" name="destinatarios_json" id="tst-destinatarios-json" value="{{ old('destinatarios_json', json_encode(array_map(fn ($c) => ['tipo' => $c['tipo'], 'id' => $c['id']], $capsulas))) }}">

    <p class="text-sm font-bold text-zinc-900">Destinatários</p>
    <p class="mt-0.5 text-xs text-zinc-500">Selecione colaboradores ou usuários com e-mail cadastrado. Cada pessoa adicionada aparece na lista abaixo.</p>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
        <label class="min-w-0 flex-1 space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:max-w-xs">
            Colaborador
            <select id="tst-select-colaborador" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900 outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Selecione…</option>
                @foreach ($colabOpcoes as $op)
                    <option value="{{ $op['id'] }}">{{ $op['nome'] }}@if ($op['matricula']) · {{ $op['matricula'] }}@endif — {{ $op['email'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="min-w-0 flex-1 space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:max-w-xs">
            Usuário
            <select id="tst-select-usuario" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-900 outline-none focus:border-brand-burgundy focus:ring-4 focus:ring-brand-burgundy/10">
                <option value="">Selecione…</option>
                @foreach ($userOpcoes as $op)
                    <option value="{{ $op['id'] }}">{{ $op['nome'] }} — {{ $op['email'] }}</option>
                @endforeach
            </select>
        </label>
        <button type="button" id="tst-btn-adicionar" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl border border-brand-burgundy/30 bg-brand-burgundy-soft px-4 text-sm font-bold text-brand-burgundy transition hover:border-brand-burgundy hover:bg-brand-burgundy/10">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Adicionar
        </button>
    </div>

    <p id="tst-destinatarios-erro" class="mt-2 hidden text-xs font-semibold text-red-600"></p>

    <div id="tst-capsulas" class="mt-4 flex min-h-[2.5rem] flex-wrap gap-2">
        @foreach ($capsulas as $cap)
            <span
                class="tst-capsula inline-flex max-w-full items-center gap-1.5 rounded-full border border-brand-burgundy/25 bg-brand-burgundy-soft py-1.5 pl-3 pr-1.5 text-xs font-semibold text-brand-burgundy"
                data-key="{{ $cap['key'] }}"
                data-tipo="{{ $cap['tipo'] }}"
                data-id="{{ $cap['id'] }}"
                data-nome="{{ $cap['nome'] }}"
            >
                <span class="truncate">{{ $cap['nome'] }}</span>
                <span class="shrink-0 rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-burgundy/80">{{ $cap['tipo_label'] }}</span>
                <button type="button" class="tst-capsula-remove flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white" title="Remover" aria-label="Remover {{ $cap['nome'] }}">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </span>
        @endforeach
    </div>
    <p id="tst-capsulas-vazio" class="mt-2 text-xs text-zinc-400 {{ count($capsulas) > 0 ? 'hidden' : '' }}">Nenhum destinatário — o envio automático fica desativado.</p>

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
    const hidden = document.getElementById('tst-destinatarios-json');
    const container = document.getElementById('tst-capsulas');
    const vazio = document.getElementById('tst-capsulas-vazio');
    const erro = document.getElementById('tst-destinatarios-erro');
    const selColab = document.getElementById('tst-select-colaborador');
    const selUser = document.getElementById('tst-select-usuario');
    const btnAdd = document.getElementById('tst-btn-adicionar');
    const form = document.getElementById('form-tst-destinatarios');

    if (!hidden || !container) return;

    const itens = new Map();

    const parseInitial = () => {
        try {
            const raw = JSON.parse(hidden.value || '[]');
            if (!Array.isArray(raw)) return;
            raw.forEach((item) => {
                if (!item?.tipo || !item?.id) return;
                const key = `${item.tipo}:${item.id}`;
                const cap = resolverCapsula(item.tipo, parseInt(item.id, 10));
                if (cap) itens.set(key, cap);
            });
        } catch (_) { /* ignore */ }
        render();
    };

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

    const syncHidden = () => {
        hidden.value = JSON.stringify(
            Array.from(itens.values()).map((c) => ({ tipo: c.tipo, id: c.id }))
        );
    };

    const render = () => {
        container.querySelectorAll('.tst-capsula').forEach((el) => el.remove());
        itens.forEach((cap) => {
            const span = document.createElement('span');
            span.className = 'tst-capsula inline-flex max-w-full items-center gap-1.5 rounded-full border border-brand-burgundy/25 bg-brand-burgundy-soft py-1.5 pl-3 pr-1.5 text-xs font-semibold text-brand-burgundy';
            span.dataset.key = cap.key;
            span.innerHTML = `
                <span class="truncate">${cap.nome}</span>
                <span class="shrink-0 rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-burgundy/80">${cap.tipo_label}</span>
                <button type="button" class="tst-capsula-remove flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-brand-burgundy transition hover:bg-brand-burgundy hover:text-white" title="Remover">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            `;
            span.querySelector('.tst-capsula-remove')?.addEventListener('click', () => {
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

    container.addEventListener('click', (e) => {
        const btn = e.target.closest('.tst-capsula-remove');
        if (!btn) return;
        const cap = btn.closest('.tst-capsula');
        const key = cap?.dataset?.key;
        if (key) {
            itens.delete(key);
            syncHidden();
            render();
        }
    });

    form?.addEventListener('submit', () => syncHidden());

    parseInitial();
})();
</script>
@endpush
