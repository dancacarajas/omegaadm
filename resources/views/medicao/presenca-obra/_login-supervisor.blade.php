<section class="overflow-hidden rounded-xl border border-brand-burgundy/20 bg-white shadow-sm">
    <div class="border-b border-zinc-200 bg-gradient-to-br from-brand-burgundy-soft/60 to-white px-5 py-4">
        <h2 class="text-sm font-bold text-brand-burgundy">{{ $loginTitulo ?? 'Confirmar presença (supervisor)' }}</h2>
        <p class="mt-1 text-sm text-brand-gray">
            {{ $loginDescricao ?? 'Informe matrícula e CPF para marcar quem está presente ou ausente na obra. Funciona sem internet após o primeiro acesso no aparelho.' }}
        </p>
    </div>

    <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
        <form method="POST" action="{{ route('presenca-obra.identificar.store') }}" class="contents">
            @csrf
            @if (! empty($redirectAfterLogin))
                <input type="hidden" name="redirect" value="{{ $redirectAfterLogin }}">
            @endif
            <div>
                <label for="matricula" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Matrícula</label>
                <input
                    type="text"
                    name="matricula"
                    id="matricula"
                    value="{{ old('matricula') }}"
                    required
                    autocomplete="username"
                    inputmode="numeric"
                    class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black"
                    placeholder="Ex.: 22541"
                >
            </div>
            <div>
                <label for="cpf" class="block text-xs font-bold uppercase tracking-wide text-brand-gray">CPF</label>
                <input
                    type="text"
                    name="cpf"
                    id="cpf"
                    value="{{ old('cpf') }}"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-brand-black"
                    placeholder="Somente números"
                >
            </div>
            <div class="flex flex-col gap-2 sm:flex-row lg:col-span-1 lg:flex-col xl:flex-row">
                <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-burgundy-dark">
                    <i data-lucide="log-in" class="h-4 w-4"></i>
                    {{ $loginBotao ?? 'Entrar para confirmar' }}
                </button>
            </div>
        </form>
    </div>

    <p class="border-t border-zinc-100 px-5 py-3 text-xs text-brand-gray">
        Acesso liberado apenas para supervisores autorizados no cadastro do colaborador.
    </p>
</section>
