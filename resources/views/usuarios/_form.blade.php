@csrf
@if ($usuario->exists)
    @method('PUT')
@endif

<section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex flex-col gap-2 border-b border-zinc-100 pb-5">
        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Cadastro de usuário</p>
        <h2 class="text-xl font-bold text-brand-black">Dados de acesso</h2>
        <p class="text-sm text-brand-gray">Defina o perfil, dados de contato e senha de acesso ao sistema.</p>
    </div>

    @php
        $colaboradores = $colaboradores ?? collect();
        $colaboradorId = old('colaborador_id', $usuario->colaborador_id ?? '');
        if (! $usuario->exists && $colaboradorId) {
            $usuario->colaborador_id = (int) $colaboradorId;
            $usuario->setRelation('colaborador', $colaboradores->firstWhere('id', (int) $colaboradorId));
        }
        $colaboradoresJson = $colaboradores->mapWithKeys(fn ($c) => [
            (string) $c->id => [
                'nome' => $c->nome,
                'telefone' => $c->telefone ?? '',
                'cargo' => $c->cargo ?? '',
                'foto_url' => filled($c->foto_path) ? route('rh.efetivo.foto.show', $c) : null,
            ],
        ]);
        $fotoUrl = $usuario->urlFotoPerfil();
    @endphp

    <div class="mb-6 rounded-xl border border-dashed border-brand-burgundy/25 bg-gradient-to-br from-brand-burgundy/[0.03] to-brand-gray-soft/40 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <div id="usuario-foto-preview" class="shrink-0">
                @if ($fotoUrl)
                    <img src="{{ $fotoUrl }}" alt="Foto de perfil" class="h-24 w-24 rounded-full object-cover shadow-md ring-4 ring-white">
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-brand-burgundy-soft text-xl font-bold text-brand-burgundy ring-4 ring-white">
                        {{ $usuario->iniciais() }}
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-xs font-bold uppercase text-brand-gray">Foto de perfil</span>
                <p class="mt-1 text-sm text-brand-gray">Opcional. JPG, PNG, GIF ou WebP até 5&nbsp;MB. Se o usuário for vinculado a um colaborador do efetivo que já tenha foto, a mesma imagem será usada automaticamente.</p>
                @if ($usuario->usaFotoDoColaborador())
                    <p class="mt-2 text-xs font-semibold text-brand-burgundy">Usando a foto do cadastro no efetivo ({{ $usuario->colaborador?->nome }}).</p>
                @endif
                <input type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-3 block w-full text-sm text-brand-black file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-burgundy file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-brand-burgundy-dark">
                @error('foto_perfil') <span class="mt-2 block text-xs font-semibold text-brand-burgundy">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <label class="lg:col-span-3">
            <span class="text-xs font-bold uppercase text-brand-gray">Colaborador do efetivo</span>
            <select name="colaborador_id" id="usuario-colaborador-select" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="">Selecione para preencher o nome automaticamente</option>
                @foreach ($colaboradores as $colab)
                    <option value="{{ $colab->id }}" @selected((string) $colaboradorId === (string) $colab->id)>
                        {{ $colab->nome }}@if ($colab->matricula) ({{ $colab->matricula }})@endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-brand-gray">Opcional. Ao escolher um colaborador ativo do RH, o nome, telefone, cargo e a foto do efetivo (se existir) são aplicados automaticamente.</p>
            @error('colaborador_id') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="lg:col-span-2">
            <span class="text-xs font-bold uppercase text-brand-gray">Nome completo *</span>
            <input id="usuario-name-input" name="name" value="{{ old('name', $usuario->name) }}" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" required>
            @error('name') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">Status *</span>
            <select name="status" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" required>
                <option value="ativo" @selected(old('status', $usuario->status) === 'ativo')>Ativo</option>
                <option value="inativo" @selected(old('status', $usuario->status) === 'inativo')>Inativo</option>
                <option value="bloqueado" @selected(old('status', $usuario->status) === 'bloqueado')>Bloqueado</option>
            </select>
            @error('status') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">E-mail *</span>
            <input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" required>
            @error('email') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">Telefone</span>
            <input id="usuario-telefone-input" name="telefone" value="{{ old('telefone', $usuario->telefone) }}" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @error('telefone') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">Cargo / função</span>
            <input id="usuario-cargo-input" name="cargo" value="{{ old('cargo', $usuario->cargo) }}" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
            @error('cargo') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">Perfil</span>
            <select name="perfil_id" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                <option value="">Sem perfil</option>
                @foreach ($perfis as $perfil)
                    <option value="{{ $perfil->id }}" @selected((string) old('perfil_id', $usuario->perfil_id) === (string) $perfil->id)>{{ $perfil->nome }}</option>
                @endforeach
            </select>
            @error('perfil_id') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <div class="lg:col-span-3 rounded-xl border border-zinc-200 bg-brand-gray-soft/40 p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Contratos vinculados</p>
                    <h3 class="mt-1 text-base font-bold text-brand-black">Restrição de visão por contrato</h3>
                    <p class="mt-1 text-sm text-brand-gray">Se não marcar acesso total, o usuário visualizará somente dados relacionados aos contratos selecionados.</p>
                </div>
                <label class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-3">
                    <input type="hidden" name="todos_contratos" value="0">
                    <input type="checkbox" name="todos_contratos" value="1" class="h-5 w-5 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked(old('todos_contratos', $usuario->todos_contratos ?? false))>
                    <span class="text-sm font-bold text-brand-black">Acesso a todos os contratos</span>
                </label>
            </div>

            @php
                $contratosSelecionados = collect(old('contratos', $usuario->exists ? $usuario->contratos->pluck('id')->all() : []))->map(fn ($id) => (string) $id)->all();
            @endphp

            <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($contratos as $contrato)
                    <label class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-3 transition hover:border-brand-burgundy/30">
                        <input type="checkbox" name="contratos[]" value="{{ $contrato->id }}" class="mt-1 h-5 w-5 rounded border-zinc-300 text-brand-burgundy focus:ring-brand-burgundy" @checked(in_array((string) $contrato->id, $contratosSelecionados, true))>
                        <span>
                            <strong class="block text-sm text-brand-black">{{ $contrato->numero }} · {{ $contrato->nome }}</strong>
                            <small class="text-brand-gray">{{ $contrato->cliente ?: 'Cliente não informado' }}</small>
                        </span>
                    </label>
                @empty
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 text-sm font-semibold text-brand-gray">
                        Nenhum contrato ativo cadastrado.
                    </div>
                @endforelse
            </div>
            @error('contratos') <span class="mt-2 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </div>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">{{ $usuario->exists ? 'Nova senha' : 'Senha *' }}</span>
            <input type="password" name="password" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" @required(! $usuario->exists)>
            @error('password') <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>
            <span class="text-xs font-bold uppercase text-brand-gray">Confirmar senha</span>
            <input type="password" name="password_confirmation" class="mt-1 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" @required(! $usuario->exists)>
        </label>
    </div>

    @push('scripts')
        <script>
            (function () {
                const map = @json($colaboradoresJson);
                const select = document.getElementById('usuario-colaborador-select');
                const nameInput = document.getElementById('usuario-name-input');
                const telefoneInput = document.getElementById('usuario-telefone-input');
                const cargoInput = document.getElementById('usuario-cargo-input');
                const fotoPreview = document.getElementById('usuario-foto-preview');
                const iniciais = @json($usuario->iniciais());

                if (!select || !nameInput) {
                    return;
                }

                function atualizarFotoPreview(fotoUrl) {
                    if (!fotoPreview) {
                        return;
                    }
                    if (fotoUrl) {
                        fotoPreview.innerHTML = '<img src="' + fotoUrl + '" alt="Foto de perfil" class="h-24 w-24 rounded-full object-cover shadow-md ring-4 ring-white">';
                    } else {
                        fotoPreview.innerHTML = '<div class="flex h-24 w-24 items-center justify-center rounded-full bg-brand-burgundy-soft text-xl font-bold text-brand-burgundy ring-4 ring-white">' + iniciais + '</div>';
                    }
                }

                select.addEventListener('change', function () {
                    const dados = map[select.value];
                    if (!dados) {
                        atualizarFotoPreview(null);
                        return;
                    }
                    nameInput.value = dados.nome || '';
                    if (telefoneInput && dados.telefone) {
                        telefoneInput.value = dados.telefone;
                    }
                    if (cargoInput && dados.cargo) {
                        cargoInput.value = dados.cargo;
                    }
                    atualizarFotoPreview(dados.foto_url || null);
                });
            })();
        </script>
    @endpush

    <div class="mt-6 flex justify-end gap-2 border-t border-zinc-100 pt-5">
        <a href="{{ route('usuarios.index') }}" class="inline-flex h-10 items-center rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">Cancelar</a>
        <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
            <i data-lucide="save" class="h-4 w-4"></i>
            Salvar usuário
        </button>
    </div>
</section>
