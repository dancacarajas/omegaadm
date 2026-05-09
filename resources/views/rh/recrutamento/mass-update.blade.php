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
                    'vagasTitulos' => ($vagasTitulosOpcoes ?? collect())->all(),
                    'nomesPorAba' => $nomesPorAba ?? [],
                    'fasesPorAba' => $fasesPorAba ?? [],
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
                        searchQuery: '',
                        filtroVaga: '',
                        filtroPosicao: '',
                        filtroColaborador: '',
                        filtroFase: '',
                        somenteElegiveis: false,
                        ...rest,
                        init() {
                            this.$watch('tab', () => {
                                this.filtroColaborador = '';
                                this.filtroFase = '';
                            });
                        },
                        totalLinhas(slug) {
                            return document.querySelectorAll(`tr[data-mass-row="${slug}"][data-candidate-row]`).length;
                        },
                        linhaVisivel(el) {
                            if (!el || !el.hasAttribute('data-candidate-row')) {
                                return true;
                            }
                            const q = (this.searchQuery || '').trim().toLowerCase();
                            if (q) {
                                const n = el.dataset.buscaNome || '';
                                const v = el.dataset.buscaVaga || '';
                                const f = el.dataset.buscaFase || '';
                                if (!n.includes(q) && !v.includes(q) && !f.includes(q)) {
                                    return false;
                                }
                            }
                            if (this.filtroVaga && (el.dataset.vagaTitulo || '') !== this.filtroVaga) {
                                return false;
                            }
                            if (this.filtroPosicao) {
                                const pos = String(el.dataset.candPosicao || '');
                                if (pos !== String(this.filtroPosicao).trim()) {
                                    return false;
                                }
                            }
                            if (this.somenteElegiveis && el.dataset.temAcaoLote !== '1') {
                                return false;
                            }
                            if (this.filtroColaborador && (el.dataset.colaborador || '') !== this.filtroColaborador) {
                                return false;
                            }
                            if (this.filtroFase && (el.dataset.faseExata || '') !== this.filtroFase) {
                                return false;
                            }
                            return true;
                        },
                        nenhumResultadoFiltro(slug) {
                            const rows = document.querySelectorAll(`tr[data-mass-row="${slug}"][data-candidate-row]`);
                            if (!rows.length) {
                                return false;
                            }
                            for (const tr of rows) {
                                if (this.linhaVisivel(tr)) {
                                    return false;
                                }
                            }
                            return true;
                        },
                        contagemVisivel(slug) {
                            let n = 0;
                            document.querySelectorAll(`tr[data-mass-row="${slug}"][data-candidate-row]`).forEach((tr) => {
                                if (this.linhaVisivel(tr)) {
                                    n++;
                                }
                            });
                            return n;
                        },
                        limparFiltros() {
                            this.searchQuery = '';
                            this.filtroVaga = '';
                            this.filtroPosicao = '';
                            this.filtroColaborador = '';
                            this.filtroFase = '';
                            this.somenteElegiveis = false;
                        },
                        toggleTab(slug) {
                            document.querySelectorAll(`input[data-mass-cb][data-tab="${slug}"]:not(:disabled)`).forEach((el) => {
                                const tr = el.closest('tr');
                                if (tr && !this.linhaVisivel(tr)) {
                                    return;
                                }
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
                                const tr = el.closest('tr');
                                if (tr && !this.linhaVisivel(tr)) {
                                    return;
                                }
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
                @php
                    $iconesAbasRecrutamento = [
                        'cadastro' => 'user-plus',
                        'exame_medico' => 'stethoscope',
                        'treinamentos' => 'graduation-cap',
                        'assinatura' => 'file-signature',
                        'sgc' => 'truck',
                        'liberacao' => 'key-round',
                        'concluido' => 'badge-check',
                    ];
                @endphp
                <div class="rounded-2xl border border-zinc-200/90 bg-gradient-to-br from-white via-white to-brand-gray-soft/40 p-2 shadow-sm">
                    <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-brand-gray">Etapas do processo</p>
                    <div class="flex flex-wrap gap-1.5 sm:gap-2">
                        @foreach ($abasTitulos as $slug => $titulo)
                            @php
                                $qtd = ($porAba[$slug] ?? collect())->count();
                                $icone = $iconesAbasRecrutamento[$slug] ?? 'circle-dot';
                            @endphp
                            <button
                                type="button"
                                @click="tab = '{{ $slug }}'"
                                :class="tab === '{{ $slug }}'
                                    ? 'border-brand-burgundy/80 bg-white text-brand-burgundy shadow-md shadow-brand-burgundy/10 ring-1 ring-brand-burgundy/25'
                                    : 'border-transparent bg-white/40 text-brand-gray hover:border-zinc-200 hover:bg-white hover:text-brand-black hover:shadow-sm'"
                                class="group inline-flex min-w-0 max-w-full items-center gap-2.5 rounded-xl border px-2.5 py-2 text-left text-xs font-bold transition-all duration-200 sm:px-3 sm:py-2.5"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors duration-200"
                                    :class="tab === '{{ $slug }}'
                                        ? 'bg-brand-burgundy text-white shadow-inner shadow-black/10'
                                        : 'bg-zinc-100 text-zinc-500 group-hover:bg-brand-burgundy/10 group-hover:text-brand-burgundy'"
                                >
                                    <i data-lucide="{{ $icone }}" class="h-4 w-4 sm:h-[18px] sm:w-[18px]"></i>
                                </span>
                                <span class="min-w-0 flex-1 leading-snug">{{ $titulo }}</span>
                                <span
                                    class="inline-flex min-w-[1.5rem] justify-center rounded-full px-2 py-0.5 text-[10px] font-black tabular-nums transition-colors duration-200"
                                    :class="tab === '{{ $slug }}'
                                        ? 'bg-brand-burgundy-soft text-brand-burgundy'
                                        : 'bg-zinc-100 text-zinc-600 group-hover:bg-zinc-200'"
                                >
                                    {{ $qtd }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-dashed border-zinc-200 bg-gradient-to-br from-zinc-50/95 to-white p-4 shadow-sm">
                    <p class="mb-3 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-brand-gray">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-brand-burgundy shadow-sm ring-1 ring-zinc-200/80">
                            <i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i>
                        </span>
                        Busca e filtros (aba atual)
                    </p>
                    <div class="flex flex-wrap items-end gap-3">
                        <label class="min-w-[200px] flex-1 sm:max-w-xs">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-gray">Colaborador</span>
                            <select x-model="filtroColaborador" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                <option value="">Todos nesta etapa</option>
                                <template x-for="nome in (nomesPorAba[tab] || [])" :key="'c-' + nome">
                                    <option :value="nome" x-text="nome"></option>
                                </template>
                            </select>
                        </label>
                        <label class="min-w-[200px] flex-1 sm:max-w-md">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-gray">Situação (fase)</span>
                            <select x-model="filtroFase" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                <option value="">Todas nesta etapa</option>
                                <template x-for="faseItem in (fasesPorAba[tab] || [])" :key="'f-' + faseItem">
                                    <option :value="faseItem" x-text="faseItem"></option>
                                </template>
                            </select>
                        </label>
                        <label class="min-w-[200px] flex-1">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-gray">Busca livre</span>
                            <input
                                type="search"
                                x-model="searchQuery"
                                placeholder="Contém em nome, vaga ou fase…"
                                class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"
                            >
                        </label>
                        <label class="min-w-[180px] sm:max-w-xs">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-gray">Vaga</span>
                            <select x-model="filtroVaga" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                <option value="">Todas</option>
                                <template x-for="titulo in vagasTitulos" :key="titulo">
                                    <option :value="titulo" x-text="titulo"></option>
                                </template>
                            </select>
                        </label>
                        <label class="w-24 min-w-[5rem]">
                            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-gray">Posição</span>
                            <input
                                type="number"
                                min="1"
                                x-model="filtroPosicao"
                                placeholder="Nº"
                                class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10"
                            >
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 pb-2 text-xs font-semibold text-brand-black" x-show="['exame_medico','treinamentos','assinatura','sgc'].includes(tab)">
                            <input type="checkbox" x-model="somenteElegiveis" class="h-4 w-4 rounded border-zinc-300 accent-brand-burgundy">
                            Só elegíveis para lote
                        </label>
                        <button type="button" @click="limparFiltros()" class="h-11 shrink-0 rounded-lg border border-zinc-200 bg-white px-4 text-xs font-semibold text-brand-black shadow-sm hover:border-brand-burgundy hover:text-brand-burgundy">
                            Limpar filtros
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-brand-gray" x-show="totalLinhas(tab) > 0 && contagemVisivel(tab) < totalLinhas(tab)">
                        Exibindo <span class="font-bold text-brand-black" x-text="contagemVisivel(tab)"></span>
                        de <span x-text="totalLinhas(tab)"></span> nesta aba.
                    </p>
                </div>

                @foreach ($abasTitulos as $slug => $titulo)
                    @php
                        $tabComSelecao = in_array($slug, ['exame_medico', 'treinamentos', 'assinatura', 'sgc'], true);
                        $mostraColunaExameOk = in_array($slug, ['exame_medico', 'treinamentos'], true);
                        $colspanTabela = ($tabComSelecao ? 1 : 0) + 4 + ($mostraColunaExameOk ? 1 : 0) + 1;
                    @endphp
                    <div x-show="tab === '{{ $slug }}'" x-cloak class="mt-4 space-y-4">
                        @if (! empty($indicadoresPorAba[$slug] ?? []))
                            <div class="grid grid-cols-2 gap-3 md:grid-cols-2 lg:grid-cols-4">
                                @foreach ($indicadoresPorAba[$slug] as $ind)
                                    <div class="rounded-xl border border-zinc-200/90 bg-gradient-to-br from-white via-white to-brand-gray-soft/30 p-4 shadow-sm ring-1 ring-zinc-100/80">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-[10px] font-bold uppercase leading-tight tracking-wide text-brand-gray">{{ $ind['label'] }}</p>
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-burgundy/10 text-brand-burgundy">
                                                <i data-lucide="{{ $ind['icon'] }}" class="h-4 w-4"></i>
                                            </span>
                                        </div>
                                        <p class="mt-2 text-2xl font-black tabular-nums text-brand-black">{{ $ind['valor'] }}</p>
                                        @if (($ind['hint'] ?? '') !== '')
                                            <p class="mt-1 text-[11px] leading-snug text-brand-gray">{{ $ind['hint'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
                                        @if ($mostraColunaExameOk)
                                            <th class="px-3 py-3">Exame OK</th>
                                        @endif
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
                                            $temAcaoLote = match ($slug) {
                                                'exame_medico' => $pag === '1',
                                                'treinamentos' => $pi === '1' || $pc === '1',
                                                'assinatura' => $pa === '1',
                                                'sgc' => $ps === '1',
                                                default => false,
                                            };
                                        @endphp
                                        <tr
                                            class="hover:bg-brand-gray-soft/30"
                                            data-mass-row="{{ $slug }}"
                                            data-candidate-row
                                            data-busca-nome="{{ \Illuminate\Support\Str::lower($row['nome']) }}"
                                            data-busca-vaga="{{ \Illuminate\Support\Str::lower($row['vaga_titulo'] ?? '') }}"
                                            data-busca-fase="{{ \Illuminate\Support\Str::lower($row['fase'] ?? '') }}"
                                            data-vaga-titulo="{{ $row['vaga_titulo'] }}"
                                            data-colaborador="{{ $row['nome'] }}"
                                            data-fase-exata="{{ $row['fase'] }}"
                                            data-cand-posicao="{{ $row['posicao'] }}"
                                            data-tem-acao-lote="{{ $temAcaoLote ? '1' : '0' }}"
                                            x-show="linhaVisivel($el)"
                                        >
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
                                            @if ($mostraColunaExameOk)
                                                <td class="px-3 py-3">
                                                    @if ($row['exame_concluido'] ?? false)
                                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-800">Sim</span>
                                                    @else
                                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-900">Não</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-3 py-3 text-right">
                                                <a href="{{ route('rh.recrutamento.edit', $row['vaga_id']) }}" class="inline-flex h-9 items-center rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-burgundy hover:border-brand-burgundy">Abrir</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $colspanTabela }}" class="px-3 py-8 text-center text-sm text-brand-gray">Nenhum candidato aprovado nesta etapa.</td>
                                        </tr>
                                    @endforelse
                                    @if (($porAba[$slug] ?? collect())->isNotEmpty())
                                        <tr x-show="nenhumResultadoFiltro('{{ $slug }}')" x-cloak>
                                            <td colspan="{{ $colspanTabela }}" class="px-3 py-8 text-center text-sm text-brand-gray">
                                                Nenhum candidato corresponde aos filtros.
                                                <button type="button" @click="limparFiltros()" class="ml-1 font-semibold text-brand-burgundy hover:underline">Limpar filtros</button>
                                            </td>
                                        </tr>
                                    @endif
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
