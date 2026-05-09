@extends('layouts.app')

@section('title', 'Atualização em massa - Recrutamento')
@section('eyebrow', 'RH')
@section('page-title', 'Atualização em massa')

@section('actions')
    <a href="{{ route('rh.recrutamento.index', request()->only(['contrato'])) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar ao recrutamento
    </a>
@endsection

@section('content')
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 bg-gradient-to-br from-white to-brand-gray-soft/70 p-5">
            <h2 class="text-xl font-bold text-brand-black">Etapas do fluxo — atualização em lote</h2>
            <p class="mt-1 text-sm text-brand-gray">Marque os candidatos na etapa e use as ações em lote: <strong class="text-brand-black">agendamento do exame médico</strong> (aba Exame médico), <strong class="text-brand-black">início</strong> e <strong class="text-brand-black">confirmação</strong> de treinamentos, <strong class="text-brand-black">assinatura</strong> e <strong class="text-brand-black">SGC</strong>. Cada modal só aplica quem for elegível naquela linha.</p>
            <form method="GET" action="{{ route('rh.recrutamento.atualizacao-massa') }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                <label class="flex-1 sm:max-w-md">
                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-gray">Centro de custo</span>
                    <select name="contrato" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10" onchange="this.form.submit()">
                        <option value="">Selecione…</option>
                        @foreach (($centrosDeCusto ?? collect()) as $centroDeCusto)
                            <option value="{{ $centroDeCusto }}" @selected(($contratoSelecionado ?? '') === $centroDeCusto)>{{ $centroDeCusto }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        @if (($contratoSelecionado ?? '') === '')
            <div class="p-8 text-center text-sm text-brand-gray">
                Escolha um <strong class="text-brand-black">centro de custo</strong> para listar os candidatos por etapa.
            </div>
        @else
            @php
                $applyUrl = route('rh.recrutamento.atualizacao-massa.aplicar');
                $initialTab = 'cadastro';
                foreach (($abasTitulos ?? []) as $slugIni => $_tit) {
                    if (($porAba[$slugIni] ?? collect())->isNotEmpty()) {
                        $initialTab = $slugIni;
                        break;
                    }
                }
                $massCfg = [
                    'applyUrl' => $applyUrl,
                    'csrf' => csrf_token(),
                    'contrato' => $contratoSelecionado,
                    'initialTab' => $initialTab,
                ];
            @endphp
            <script>
                function massRecruitUi(cfg) {
                    const initial = cfg.initialTab || 'cadastro';
                    const { initialTab: _i, ...rest } = cfg;
                    const eligKeyForAcao = {
                        exame_medico_data_agendamento: 'eligAgendamentoExame',
                        treinamentos_data_inicio: 'eligInicio',
                        treinamentos_data_confirmacao: 'eligConf',
                        assinatura_confirmacao: 'eligAssinatura',
                        sgc_lote: 'eligSgc',
                    };
                    return {
                        tab: initial,
                        modalOpen: false,
                        modalTab: null,
                        modalAcao: null,
                        dataAgendamentoExame: '',
                        dataInicio: '',
                        dataConfirmacaoTreino: '',
                        assinaturaDataConfirmacao: '',
                        assinaturaDataProgramada: '',
                        sgcDataPostagem: '',
                        sgcNumero: '',
                        sgcDataMobilizacao: '',
                        loading: false,
                        feedback: '',
                        ...rest,
                        toggleTab(slug) {
                            document.querySelectorAll(`input[data-mass-cb][data-tab="${slug}"]:not(:disabled)`).forEach((el) => {
                                el.checked = !el.checked;
                            });
                        },
                        openModal(slug, acao) {
                            this.modalTab = slug;
                            this.modalAcao = acao;
                            this.feedback = '';
                            this.dataAgendamentoExame = '';
                            this.dataInicio = '';
                            this.dataConfirmacaoTreino = '';
                            this.assinaturaDataConfirmacao = '';
                            this.assinaturaDataProgramada = '';
                            this.sgcDataPostagem = '';
                            this.sgcNumero = '';
                            this.sgcDataMobilizacao = '';
                            this.modalOpen = true;
                        },
                        selectedItems() {
                            const sel = [];
                            const key = eligKeyForAcao[this.modalAcao] || null;
                            document.querySelectorAll(`input[data-mass-cb][data-tab="${this.modalTab}"]:checked:not(:disabled)`).forEach((el) => {
                                if (key && el.dataset[key] !== '1') {
                                    return;
                                }
                                sel.push({
                                    vaga_id: parseInt(el.dataset.vagaId, 10),
                                    posicao: parseInt(el.dataset.posicao, 10),
                                });
                            });
                            return sel;
                        },
                        async submit() {
                            this.feedback = '';
                            const itens = this.selectedItems();
                            if (itens.length === 0) {
                                this.feedback = 'Marque ao menos um candidato elegível para esta ação.';
                                return;
                            }
                            const acao = this.modalAcao;
                            const body = { contrato: this.contrato, acao, itens };
                            if (acao === 'exame_medico_data_agendamento') {
                                if (!this.dataAgendamentoExame) {
                                    this.feedback = 'Informe a data de agendamento do exame médico.';
                                    return;
                                }
                                body.data_agendamento_exame = this.dataAgendamentoExame;
                            } else if (acao === 'treinamentos_data_inicio') {
                                if (!this.dataInicio) {
                                    this.feedback = 'Informe a data de início.';
                                    return;
                                }
                                body.data_inicio = this.dataInicio;
                            } else if (acao === 'treinamentos_data_confirmacao') {
                                if (!this.dataConfirmacaoTreino) {
                                    this.feedback = 'Informe a data de confirmação.';
                                    return;
                                }
                                body.data_confirmacao = this.dataConfirmacaoTreino;
                            } else if (acao === 'assinatura_confirmacao') {
                                if (!this.assinaturaDataConfirmacao) {
                                    this.feedback = 'Informe a data de confirmação da assinatura.';
                                    return;
                                }
                                body.assinatura_data_confirmacao = this.assinaturaDataConfirmacao;
                                if (this.assinaturaDataProgramada) {
                                    body.assinatura_data_programada = this.assinaturaDataProgramada;
                                }
                            } else if (acao === 'sgc_lote') {
                                if (!this.sgcDataPostagem || !this.sgcNumero.trim() || !this.sgcDataMobilizacao) {
                                    this.feedback = 'Preencha data de postagem, número e data de mobilização.';
                                    return;
                                }
                                body.sgc_data_postagem = this.sgcDataPostagem;
                                body.sgc_numero_postagem = this.sgcNumero.trim();
                                body.sgc_data_mobilizacao = this.sgcDataMobilizacao;
                            }
                            this.loading = true;
                            try {
                                const res = await fetch(this.applyUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': this.csrf,
                                        Accept: 'application/json',
                                    },
                                    body: JSON.stringify(body),
                                });
                                const data = await res.json().catch(() => ({}));
                                if (!res.ok) {
                                    this.feedback = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Não foi possível salvar.');
                                    this.loading = false;
                                    return;
                                }
                                let msg = data.mensagem || 'Concluído.';
                                if (data.ignorados && data.ignorados.length) {
                                    msg += ' Ignorados: ' + data.ignorados.length + '.';
                                }
                                window.alert(msg);
                                window.location.reload();
                            } catch (e) {
                                this.feedback = 'Erro de rede. Tente novamente.';
                            }
                            this.loading = false;
                        },
                    };
                }
            </script>
            {{-- JSON com " não pode ir dentro de x-data="..." — usar aspas simples no atributo --}}
            <div class="p-5 sm:p-6" x-data='massRecruitUi(@json($massCfg))'>
                <div class="flex flex-wrap gap-2 border-b border-zinc-200 pb-3">
                    @foreach ($abasTitulos as $slug => $titulo)
                        @php $qtd = ($porAba[$slug] ?? collect())->count(); @endphp
                        <button
                            type="button"
                            @click="tab = '{{ $slug }}'"
                            :class="tab === '{{ $slug }}'
                                ? 'border-brand-burgundy bg-brand-burgundy-soft text-brand-burgundy'
                                : 'border-zinc-200 bg-white text-brand-gray hover:border-brand-burgundy/40'"
                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold transition"
                        >
                            {{ $titulo }}
                            <span class="rounded-full bg-white/80 px-2 py-0.5 text-[10px] tabular-nums text-brand-black">{{ $qtd }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($abasTitulos as $slug => $titulo)
                    @php
                        $tabComSelecao = in_array($slug, ['exame_medico', 'treinamentos', 'assinatura', 'sgc'], true);
                    @endphp
                    <div x-show="tab === '{{ $slug }}'" x-cloak class="mt-4 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-brand-gray">
                                <strong class="text-brand-black">{{ $titulo }}</strong>
                                @if ($slug === 'exame_medico')
                                    <span class="block mt-1 text-xs">Registra a <strong>data de agendamento do exame médico</strong> (campo da ficha, passo Exame). Só candidatos ainda na etapa de exame, sem exame concluído.</span>
                                @elseif ($slug === 'treinamentos')
                                    <span class="block mt-1 text-xs">Início e confirmação de treino exigem <strong>exame concluído</strong> na ficha. A confirmação exige <strong>data de início</strong> dos treinamentos já informada.</span>
                                @elseif ($slug === 'assinatura')
                                    <span class="block mt-1 text-xs">Registra a <strong>data de confirmação da assinatura</strong> (e opcionalmente a data programada) para os selecionados.</span>
                                @elseif ($slug === 'sgc')
                                    <span class="block mt-1 text-xs">Preenche postagem, número e mobilização. <strong>Ignora</strong> candidatos com pendência SGC aberta na ficha.</span>
                                @endif
                            </p>
                            @if ($tabComSelecao)
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="toggleTab('{{ $slug }}')" class="inline-flex h-10 items-center rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm hover:border-brand-burgundy">
                                        Inverter seleção nesta aba
                                    </button>
                                    @if ($slug === 'exame_medico')
                                        <button type="button" @click="openModal('{{ $slug }}', 'exame_medico_data_agendamento')" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                            <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                                            Agendamento do exame médico…
                                        </button>
                                    @endif
                                    @if ($slug === 'treinamentos')
                                        <button type="button" @click="openModal('{{ $slug }}', 'treinamentos_data_inicio')" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                            <i data-lucide="calendar-plus" class="h-4 w-4"></i>
                                            Início dos treinamentos…
                                        </button>
                                    @endif
                                    @if ($slug === 'treinamentos')
                                        <button type="button" @click="openModal('{{ $slug }}', 'treinamentos_data_confirmacao')" class="inline-flex h-10 items-center gap-2 rounded-lg border border-brand-burgundy bg-brand-burgundy-soft px-4 text-xs font-semibold text-brand-burgundy shadow-sm hover:bg-brand-burgundy/15">
                                            <i data-lucide="calendar-check-2" class="h-4 w-4"></i>
                                            Confirmação dos treinamentos…
                                        </button>
                                    @endif
                                    @if ($slug === 'assinatura')
                                        <button type="button" @click="openModal('{{ $slug }}', 'assinatura_confirmacao')" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                            <i data-lucide="file-signature" class="h-4 w-4"></i>
                                            Confirmação da assinatura…
                                        </button>
                                    @endif
                                    @if ($slug === 'sgc')
                                        <button type="button" @click="openModal('{{ $slug }}', 'sgc_lote')" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-burgundy px-4 text-xs font-semibold text-white shadow-sm shadow-brand-burgundy/20 hover:bg-brand-burgundy-dark">
                                            <i data-lucide="package" class="h-4 w-4"></i>
                                            Postagem e mobilização SGC…
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-zinc-200">
                            <table class="w-full min-w-[720px] text-left text-sm">
                                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-bold uppercase tracking-wide text-brand-gray">
                                    <tr>
                                        @if ($tabComSelecao)
                                            <th class="w-10 px-3 py-3"></th>
                                        @endif
                                        <th class="px-3 py-3">Candidato</th>
                                        <th class="px-3 py-3">Vaga</th>
                                        <th class="px-3 py-3">Pos.</th>
                                        <th class="px-3 py-3">Situação (fase)</th>
                                        <th class="px-3 py-3">Exame OK</th>
                                        <th class="px-3 py-3 text-right">Ficha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @forelse(($porAba[$slug] ?? collect()) as $row)
                                        @php
                                            $pag = ($row['pode_definir_agendamento_exame'] ?? false) ? '1' : '0';
                                            $pi = ($row['pode_definir_inicio_treino'] ?? false) ? '1' : '0';
                                            $pc = ($row['pode_definir_confirmacao_treino'] ?? false) ? '1' : '0';
                                            $pa = ($row['pode_definir_assinatura'] ?? false) ? '1' : '0';
                                            $ps = ($row['pode_definir_sgc'] ?? false) ? '1' : '0';
                                            $cbDisabled = match ($slug) {
                                                'exame_medico' => $pag !== '1',
                                                'treinamentos' => $pi !== '1' && $pc !== '1',
                                                'assinatura' => $pa !== '1',
                                                'sgc' => $ps !== '1',
                                                default => true,
                                            };
                                            $cbTitle = match ($slug) {
                                                'exame_medico' => 'Sem elegibilidade para agendamento do exame em lote',
                                                'treinamentos' => 'Sem elegibilidade para início ou confirmação de treino',
                                                'assinatura' => 'Sem elegibilidade para assinatura em lote',
                                                'sgc' => 'Sem elegibilidade (assinatura pendente, SGC fechado ou pendência na ficha)',
                                                default => '',
                                            };
                                        @endphp
                                        <tr class="hover:bg-brand-gray-soft/30" data-mass-row="{{ $slug }}">
                                            @if ($tabComSelecao)
                                                <td class="px-3 py-3">
                                                    <input
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-zinc-300 accent-brand-burgundy"
                                                        data-mass-cb
                                                        data-tab="{{ $slug }}"
                                                        data-vaga-id="{{ $row['vaga_id'] }}"
                                                        data-posicao="{{ $row['posicao'] }}"
                                                        data-elig-agendamento-exame="{{ $pag }}"
                                                        data-elig-inicio="{{ $pi }}"
                                                        data-elig-conf="{{ $pc }}"
                                                        data-elig-assinatura="{{ $pa }}"
                                                        data-elig-sgc="{{ $ps }}"
                                                        @if ($cbDisabled) disabled title="{{ $cbTitle }}" @endif
                                                    >
                                                </td>
                                            @endif
                                            <td class="px-3 py-3 font-medium text-brand-black">{{ $row['nome'] }}</td>
                                            <td class="px-3 py-3 text-brand-gray">{{ $row['vaga_titulo'] ?: '—' }}</td>
                                            <td class="px-3 py-3 tabular-nums font-bold">{{ $row['posicao'] }}</td>
                                            <td class="px-3 py-3 text-brand-black">{{ $row['fase'] }}</td>
                                            <td class="px-3 py-3">
                                                @if ($row['exame_concluido'] ?? false)
                                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-800">Sim</span>
                                                @else
                                                    <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-900">Não</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <a href="{{ route('rh.recrutamento.edit', $row['vaga_id']) }}" class="inline-flex h-9 items-center rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-burgundy hover:border-brand-burgundy">Abrir</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $tabComSelecao ? 7 : 6 }}" class="px-3 py-8 text-center text-sm text-brand-gray">Nenhum candidato aprovado nesta etapa.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div
                    x-show="modalOpen"
                    x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                    @keydown.escape.window="modalOpen = false"
                >
                    <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl" @click.outside="modalOpen = false">
                        <div x-show="modalAcao === 'exame_medico_data_agendamento'">
                            <h3 class="text-lg font-black text-brand-black">Agendamento do exame médico</h3>
                            <p class="mt-2 text-sm text-brand-gray">Grava a <strong>data de agendamento</strong> no passo Exame da ficha. Só se aplica a candidatos ainda nesta etapa.</p>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data do agendamento</span>
                                <input type="date" x-model="dataAgendamentoExame" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                        </div>
                        <div x-show="modalAcao === 'treinamentos_data_inicio'">
                            <h3 class="text-lg font-black text-brand-black">Início dos treinamentos</h3>
                            <p class="mt-2 text-sm text-brand-gray">Aplica só nas linhas marcadas elegíveis para <strong>início</strong> (exame OK, treino em aberto).</p>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de início</span>
                                <input type="date" x-model="dataInicio" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                        </div>
                        <div x-show="modalAcao === 'treinamentos_data_confirmacao'">
                            <h3 class="text-lg font-black text-brand-black">Confirmação dos treinamentos</h3>
                            <p class="mt-2 text-sm text-brand-gray">Só candidatos com <strong>início</strong> já preenchido e sem treino concluído.</p>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de confirmação</span>
                                <input type="date" x-model="dataConfirmacaoTreino" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                        </div>
                        <div x-show="modalAcao === 'assinatura_confirmacao'">
                            <h3 class="text-lg font-black text-brand-black">Assinatura documental</h3>
                            <p class="mt-2 text-sm text-brand-gray">Treinamentos devem estar concluídos. Confirmação obrigatória; programada opcional.</p>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data de confirmação da assinatura</span>
                                <input type="date" x-model="assinaturaDataConfirmacao" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data programada (opcional)</span>
                                <input type="date" x-model="assinaturaDataProgramada" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                        </div>
                        <div x-show="modalAcao === 'sgc_lote'">
                            <h3 class="text-lg font-black text-brand-black">SGC — postagem e mobilização</h3>
                            <p class="mt-2 text-sm text-brand-gray">Assinatura concluída, SGC em aberto e <strong>sem</strong> pendência textual na ficha.</p>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da postagem</span>
                                <input type="date" x-model="sgcDataPostagem" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Número / protocolo</span>
                                <input type="text" x-model="sgcNumero" placeholder="Protocolo" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                            <label class="mt-4 block">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da mobilização</span>
                                <input type="date" x-model="sgcDataMobilizacao" class="mt-2 h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                            </label>
                        </div>
                        <p class="mt-3 text-xs text-amber-800" x-show="feedback" x-text="feedback"></p>
                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" @click="modalOpen = false" class="h-10 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black">Cancelar</button>
                            <button type="button" @click="submit()" :disabled="loading" class="h-10 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white disabled:opacity-50">
                                <span x-show="!loading">Aplicar</span>
                                <span x-show="loading">Salvando…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
