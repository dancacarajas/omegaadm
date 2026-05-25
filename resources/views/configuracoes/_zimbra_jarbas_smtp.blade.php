<section class="mb-6 overflow-hidden rounded-3xl border-2 border-amber-200/80 bg-white shadow-lg shadow-amber-100/40 ring-1 ring-amber-100">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-amber-100 bg-gradient-to-r from-amber-50/90 to-white px-6 py-5">
        <div>
            <h2 class="text-lg font-bold text-zinc-900">SMTP Zimbra — envio como Jarbas</h2>
            <p class="mt-1 max-w-2xl text-sm text-zinc-600">
                <strong class="text-zinc-800">Separado do SMTP central acima.</strong> Usado somente quando o benefício precisa enviar e-mail
                para a Matriz (ex.: Celiamara) <em>como se</em> <strong>jarbas.alves@omegaservice.com.br</strong> tivesse enviado.
                O SMTP central continua disparando a cópia para você e todos os demais e-mails do sistema.
            </p>
            <p class="mt-2 text-xs text-amber-900/90">
                Outlook: <strong>smtp.omegaservice.com.br</strong>, porta <strong>587</strong>, <strong>STARTTLS</strong> (= TLS neste formulário).
                Conta com 2FA: senha de aplicativo Zimbra (<code class="rounded bg-amber-100 px-1">sistema-omega</code>), não a senha de login.
            </p>
        </div>
        <span class="rounded-full bg-amber-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-950 ring-1 ring-amber-200">Benefício / Matriz</span>
    </div>

    <form method="POST" action="{{ route('configuracoes.email.zimbra-jarbas.update') }}" class="space-y-6 p-6 sm:p-8">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-sky-100 bg-sky-50/80 px-4 py-3 text-sm text-sky-950">
            <p class="font-semibold">Dois envios ao disparar benefício Matriz</p>
            <ol class="mt-2 list-inside list-decimal space-y-1 text-xs">
                <li><strong>Para você</strong> — sempre <code class="rounded bg-sky-100 px-1">{{ \App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService::EMAIL_NOTIFICACAO_INTERNA_JARBAS }}</code> com remetente <strong>Omega / 286omega@gmail.com</strong> (SMTP central burgundy).</li>
                <li><strong>Cópia automática benefício</strong> (campo abaixo) + destinatários Matriz — remetente <strong>Jarbas</strong> (este SMTP Zimbra).</li>
            </ol>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:col-span-2">
                Host SMTP Zimbra
                <input type="text" name="zimbra_host" value="{{ old('zimbra_host', $zimbra_host ?? 'smtp.omegaservice.com.br') }}" required
                       class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="smtp.omegaservice.com.br">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Porta
                <input type="number" name="zimbra_port" value="{{ old('zimbra_port', $zimbra_port ?? 587) }}" required min="1" max="65535"
                       class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Criptografia
                <select name="zimbra_encryption" class="mt-1 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm">
                    @foreach ($criptografiasZimbra ?? [] as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(old('zimbra_encryption', $zimbra_encryption ?? 'tls') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:col-span-2">
                Usuário SMTP (conta Zimbra)
                <input type="email" name="zimbra_username" value="{{ old('zimbra_username', $zimbra_username ?? '') }}" required
                       class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm" placeholder="jarbas.alves@omegaservice.com.br">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Senha de aplicativo Zimbra
                <input type="password" name="zimbra_password" autocomplete="new-password" placeholder="{{ ($zimbra_senha_configurada ?? false) ? 'Deixe em branco para manter' : 'Senha de aplicativo' }}"
                       class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:col-span-2">
                Nome remetente
                <input type="text" name="zimbra_from_name" value="{{ old('zimbra_from_name', $zimbra_from_name ?? '') }}" required
                       class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                E-mail remetente (igual ao usuário SMTP)
                <input type="email" name="zimbra_from_address" value="{{ old('zimbra_from_address', $zimbra_from_address ?? '') }}" required
                       class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <label class="space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:col-span-3">
                Cópia automática benefício — quem recebe com remetente Jarbas (Zimbra)
                <input type="email" name="beneficio_adesao_copia_email" value="{{ old('beneficio_adesao_copia_email', $beneficio_adesao_copia_email ?? '') }}"
                       class="mt-1 h-11 w-full max-w-xl rounded-xl border border-zinc-200 px-3 text-sm" placeholder="ex.: parceiro ou e-mail da Matriz">
                <span class="mt-1 block font-normal normal-case text-zinc-500">
                    <strong>Não</strong> use {{ \App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService::EMAIL_NOTIFICACAO_INTERNA_JARBAS }} aqui — sua cópia com remetente Omega é automática.
                </span>
            </label>
        </div>

        <div class="grid gap-4 rounded-2xl border border-amber-100 bg-amber-50/50 p-4 sm:grid-cols-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Status senha Zimbra</p>
                <p class="mt-1 text-sm font-semibold {{ ($zimbra_senha_configurada ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ ($zimbra_senha_configurada ?? false) ? 'Configurada' : 'Não configurada' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wide text-zinc-500">Última atualização Zimbra</p>
                <p class="mt-1 text-sm font-semibold text-zinc-800">
                    @if ($zimbra_ultima_atualizacao ?? null)
                        {{ $zimbra_ultima_atualizacao->format('d/m/Y H:i') }}
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-amber-700 px-5 text-sm font-bold text-white shadow-md transition hover:bg-amber-800">
                <i data-lucide="save" class="h-4 w-4"></i>
                Salvar SMTP Zimbra
            </button>
        </div>
    </form>

    <div class="border-t border-amber-100 bg-zinc-50/40 px-6 py-5 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Teste só do Zimbra (não usa o SMTP central)</p>
        <form method="POST" action="{{ route('configuracoes.email.zimbra-jarbas.testar') }}" class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end">
            @csrf
            <label class="min-w-0 flex-1 space-y-1 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Destinatário do teste
                <input type="email" name="email_teste_zimbra" value="{{ old('email_teste_zimbra', $zimbra_from_address ?? auth()->user()?->email) }}" required
                       class="mt-1 h-11 w-full rounded-xl border border-zinc-200 px-3 text-sm">
            </label>
            <button type="submit" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl border border-amber-300 bg-white px-5 text-sm font-bold text-amber-900 shadow-sm transition hover:border-amber-500">
                <i data-lucide="send" class="h-4 w-4"></i>
                Enviar teste Zimbra
            </button>
        </form>
    </div>
</section>
