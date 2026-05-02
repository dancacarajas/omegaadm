@extends('layouts.app')

@section('title', 'Solicitacao de veiculo - Omega286')
@section('eyebrow', 'Veiculos')
@section('page-title', 'Mobilizacao de veiculo')

@section('actions')
    <a href="{{ route('veiculos.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $solicitacao ??= null;
        $value = function (string $field, mixed $default = '') use ($solicitacao) {
            $stored = old($field, data_get($solicitacao, $field, $default));

            return $stored instanceof \DateTimeInterface ? $stored->format('Y-m-d') : $stored;
        };
        $checked = fn (string $field, string $key): bool => (bool) old("{$field}.{$key}", data_get($solicitacao?->{$field}, $key, false));
        $selected = fn (string $field, string $option): bool => old($field, data_get($solicitacao, $field)) === $option;
        $checks = [
            'veiculo_solicitado' => ['title' => 'Veiculo solicitado conforme a demanda do contrato', 'help' => 'Registrar qual tipo de veiculo sera necessario e para qual finalidade contratual.'],
            'periodo_definido' => ['title' => 'Data de inicio e fim da atividade definida', 'help' => 'O periodo precisa ser claro para evitar conflito de programacao.'],
            'inspecao_prevista' => ['title' => 'Data de liberacao para inspecao prevista', 'help' => 'Nao aguardar a vespera da operacao para tratar vistoria.'],
            'linha_confirmada' => ['title' => 'Linha contratual confirmada', 'help' => 'Validar com o gestor ou responsavel pelo contrato.'],
            'criterios_conferidos' => ['title' => 'Checklist Excel de criterios tecnicos conferido', 'help' => 'O veiculo solicitado precisa atender ao que o contrato exige.'],
            'anexo_validado' => ['title' => 'Documentacao completa conforme Anexo 6 validada', 'help' => 'Sem documentacao completa, o processo tende a gerar pendencia.'],
        ];
        $steps = [
            ['id' => 'step-inicial', 'number' => '01', 'label' => 'Inicial'],
            ['id' => 'step-veiculo', 'number' => '02', 'label' => 'Veiculo'],
            ['id' => 'step-tag', 'number' => '03', 'label' => 'TAG'],
            ['id' => 'step-subcontratacao', 'number' => '04', 'label' => 'Subcontratacao'],
            ['id' => 'step-svg', 'number' => '05', 'label' => 'SVG'],
            ['id' => 'step-vistoria', 'number' => '06', 'label' => 'Vistoria'],
            ['id' => 'step-finalizacao', 'number' => '07', 'label' => 'Finalizacao'],
        ];
        $tagChecks = [
            'crlv_conferido' => ['title' => 'CRLV conferido antes do envio', 'help' => 'Usar o documento oficial como fonte de verdade.'],
            'dados_completos' => ['title' => 'Dados completos do veiculo preenchidos', 'help' => 'Placa, RENAVAM, marca, modelo, ano, proprietario e fornecedor.'],
            'evidencia_salva' => ['title' => 'Evidencia da solicitacao salva', 'help' => 'Guardar print, protocolo ou confirmacao de envio.'],
        ];
        $subcontratacaoChecks = [
            'analise_inicial' => ['title' => 'Analise inicial enviada', 'help' => 'Salvar evidencia de envio.'],
            'autorizacao_aprovada' => ['title' => 'Autorizacao solicitada apos aprovacao', 'help' => 'Nao encerrar a etapa apenas com analise inicial.'],
        ];
        $subcontratacaoDocs = [
            'subcontratacao_cartao_cnpj' => ['title' => 'Cartao CNPJ', 'help' => 'Conferir razao social, CNPJ e dados cadastrais do fornecedor.'],
            'subcontratacao_minuta' => ['title' => 'Minuta contratual Omega / fornecedor', 'help' => 'Documento base da relacao contratual.'],
            'subcontratacao_contrato_social' => ['title' => 'Contrato social do fornecedor', 'help' => 'Validar empresa, representantes e poderes.'],
            'subcontratacao_documento_veiculo' => ['title' => 'Documentacao do veiculo', 'help' => 'Dados precisam estar iguais ao CRLV.'],
        ];
        $svgChecks = [
            'documentacao_reunida' => ['title' => 'Documentacao completa reunida', 'help' => 'Anexo 6, CRLV, fornecedor, subcontratacao e dados do veiculo.'],
            'mobilizacao_postada' => ['title' => 'Mobilizacao postada no SVG', 'help' => 'Registrar data da postagem.'],
            'fluxo_acompanhado' => ['title' => 'Fluxo de aprovacao Vale acompanhado', 'help' => 'Acompanhar ate aprovacao, pendencia ou retorno conclusivo.'],
            'pendencias_corrigidas' => ['title' => 'Pendencias corrigidas, se houver', 'help' => 'Registrar correcao, responsavel e novo envio.'],
        ];
        $vistoriaChecks = [
            'data_prevista' => ['title' => 'Data de vistoria prevista', 'help' => 'Planejar antes da data de inicio da atividade.'],
            'veiculo_disponivel' => ['title' => 'Veiculo disponivel para inspecao', 'help' => 'Confirmar local, responsavel e condicao fisica.'],
            'checklist_revisado' => ['title' => 'Checklist tecnico revisado antes da vistoria', 'help' => 'Evitar reprovacao por item conhecido.'],
            'resultado_registrado' => ['title' => 'Resultado da vistoria registrado', 'help' => 'Salvar aprovacao, reprovacao ou pendencia.'],
        ];
        $finalizacaoChecks = [
            'solicitacao' => ['title' => 'Solicitacao inicial completa', 'help' => 'Demanda, periodo, linha contratual e criterio tecnico registrados.'],
            'tag' => ['title' => 'TAG solicitada e evidencia salva', 'help' => 'Dados conferidos conforme CRLV.'],
            'subcontratacao' => ['title' => 'Subcontratacao analisada e autorizada', 'help' => 'Analise inicial e autorizacao final confirmadas.'],
            'svg' => ['title' => 'Mobilizacao SVG aprovada/finalizada', 'help' => 'Fluxo Vale concluido sem pendencia impeditiva.'],
            'vistoria' => ['title' => 'Vistoria aprovada ou formalmente liberada', 'help' => 'Data, status e evidencia registrados.'],
        ];
    @endphp

    <form method="POST" action="{{ $solicitacao ? route('veiculos.solicitacoes.update', $solicitacao) : route('veiculos.solicitacoes.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if ($solicitacao)
            @method('PUT')
        @endif
        <input type="hidden" name="status" value="{{ $value('status', 'em_andamento') }}">
        <input type="hidden" name="current_step" id="current_step" value="{{ old('current_step', request('step', 'step-inicial')) }}">

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                Corrija os campos destacados para continuar.
            </div>
        @endif

        <div class="bs-stepper js-stepper rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="bs-stepper-header overflow-x-auto border-b border-zinc-200 p-4" role="tablist">
                @foreach ($steps as $index => $step)
                    <div class="step {{ $index === 0 ? 'active' : '' }}" data-target="#{{ $step['id'] }}">
                        <button type="button" class="step-trigger min-w-max gap-2 rounded-lg px-2 py-2" role="tab" aria-controls="{{ $step['id'] }}" id="{{ $step['id'] }}-trigger">
                            <span class="bs-stepper-circle">{{ $step['number'] }}</span>
                            <span class="bs-stepper-label">{{ $step['label'] }}</span>
                        </button>
                    </div>
                    @if (! $loop->last)
                        <div class="line"></div>
                    @endif
                @endforeach
            </div>

            <div class="border-b border-zinc-200 bg-white px-5 py-3">
                <div class="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-brand-gray">
                    <span>Progresso do fluxo</span>
                    <span data-step-progress-label>0%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200">
                    <div data-step-progress-bar class="h-full w-0 rounded-full bg-brand-burgundy transition-all duration-300 ease-out"></div>
                </div>
            </div>

            <div class="bs-stepper-content p-5 lg:p-6">
                <div id="step-inicial" class="content active dstepper-block step-visible" role="tabpanel" aria-labelledby="step-inicial-trigger">
                    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_390px]">
                        <section class="min-w-0 rounded-[28px] border border-zinc-200 bg-white p-6 lg:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-black text-brand-burgundy shadow-sm">Etapa inicial</span>
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy">Base do processo</span>
                            </div>

                            <div class="mt-5 max-w-5xl">
                                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl">Solicitacao do veiculo conforme a demanda do contrato.</h2>
                                <p class="mt-3 text-sm leading-6 text-brand-gray">Antes de abrir qualquer formulario, confirme se a demanda atende ao contrato, ao periodo de uso, a linha contratual e aos criterios tecnicos exigidos.</p>
                            </div>

                            <div class="mt-6 grid gap-4 xl:grid-cols-4">
                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Periodo contratual</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Definir data de inicio da atividade e data de fim.</p>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Inicio</span>
                                            <input type="date" name="data_inicio_atividade" value="{{ $value('data_inicio_atividade') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Fim</span>
                                            <input type="date" name="data_fim_atividade" value="{{ $value('data_fim_atividade') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Inspecao</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Prever data de liberacao para inspecao com antecedencia.</p>
                                    <label class="mt-4 block space-y-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Liberacao para inspecao</span>
                                        <input type="date" name="data_liberacao_inspecao" value="{{ $value('data_liberacao_inspecao') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </label>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Linha contratual</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Confirmar a linha correta antes de vincular o veiculo.</p>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Contrato</span>
                                            <input name="contrato" value="{{ $value('contrato') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Linha</span>
                                            <input name="linha_contratual" value="{{ $value('linha_contratual') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Criterio tecnico</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Conferir o checklist Excel e os requisitos contratuais.</p>
                                    <label class="mt-4 block space-y-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Criterio exigido</span>
                                        <input name="criterio_tecnico" value="{{ $value('criterio_tecnico') }}" placeholder="Ex.: caminhonete 4x4, capacidade, ano minimo..." class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </label>
                                </section>
                            </div>

                            <section class="mt-5 rounded-2xl border border-zinc-200 bg-white p-5">
                                <p class="text-sm font-black text-[#3f0812]">Demanda solicitada</p>
                                <p class="mt-2 text-xs leading-5 text-brand-gray">Aqui nao entram placa, RENAVAM, marca ou modelo. Esta etapa registra apenas a necessidade do contrato.</p>
                                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                    <label class="space-y-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Finalidade da solicitacao</span>
                                        <input name="finalidade" value="{{ $value('finalidade') }}" placeholder="Ex.: apoio a manutencao, transporte de equipe..." class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </label>
                                    <label class="space-y-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Responsavel</span>
                                        <input name="responsavel" value="{{ $value('responsavel') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </label>
                                </div>
                            </section>

                            <label class="mt-5 block space-y-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
                                <textarea name="observacoes" rows="4" class="w-full rounded-2xl border border-zinc-200 px-4 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('observacoes') }}</textarea>
                            </label>
                        </section>

                        <aside class="h-fit rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-28">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Checklist</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Pendencias do Step 01</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="list-checks" class="h-5 w-5"></i>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($checks as $key => $item)
                                    <label class="flex cursor-pointer gap-3 rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-burgundy/40 hover:bg-brand-gray-soft/40" data-auto-check-card="{{ $key }}">
                                        <input type="hidden" name="checklist_data[{{ $key }}]" value="0">
                                        <input type="checkbox" name="checklist_data[{{ $key }}]" value="1" @checked($checked("checklist_data", $key)) data-auto-check="{{ $key }}" class="mt-1 h-5 w-5 rounded border-zinc-300 text-brand-burgundy accent-[#6f1731]">
                                        <span>
                                            <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-5">
                                <button name="status" value="em_andamento" data-next-step="step-veiculo" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-brand-burgundy bg-white px-4 text-sm font-semibold text-brand-burgundy shadow-sm transition hover:bg-brand-burgundy-soft">
                                    <i data-lucide="circle-check" class="h-4 w-4"></i>
                                    Concluir Step 01
                                </button>
                                <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar solicitacao
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>

                <div id="step-veiculo" class="content" role="tabpanel" aria-labelledby="step-veiculo-trigger">
                    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_390px]">
                        <section class="min-w-0 rounded-[28px] border border-zinc-200 bg-white p-6 lg:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-black text-brand-burgundy shadow-sm">Step 02</span>
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy">Cadastro do veiculo</span>
                            </div>

                            <div class="mt-5 max-w-5xl">
                                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl">Identificacao do veiculo e documentos.</h2>
                                <p class="mt-3 text-sm leading-6 text-brand-gray">Nesta etapa o veiculo ja foi definido. Cadastre os dados conforme CRLV e anexe a documentacao obrigatoria para seguir com TAG e mobilizacao.</p>
                            </div>

                            <div class="mt-6 grid gap-4 xl:grid-cols-4">
                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-4">
                                    <p class="text-sm font-black text-[#3f0812]">Dados principais</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Use o CRLV como fonte principal para evitar divergencia cadastral.</p>
                                    <div class="mt-4 grid gap-3 md:grid-cols-4">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Placa</span>
                                            <input name="placa" value="{{ $value('placa') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm uppercase outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">RENAVAM</span>
                                            <input name="renavam" value="{{ $value('renavam') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Tipo</span>
                                            <input name="tipo" value="{{ $value('tipo') }}" placeholder="Caminhonete, van..." class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Cor</span>
                                            <input name="cor" value="{{ $value('cor') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Marca e modelo</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Informe os dados exatamente como constam no documento.</p>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Marca</span>
                                            <input name="marca" value="{{ $value('marca') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Modelo</span>
                                            <input name="modelo" value="{{ $value('modelo') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Ano do veiculo</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Confirme ano de fabricacao e ano modelo antes de prosseguir.</p>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ano fabricacao</span>
                                            <input name="ano_fabricacao" value="{{ $value('ano_fabricacao') }}" maxlength="4" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Ano modelo</span>
                                            <input name="ano_modelo" value="{{ $value('ano_modelo') }}" maxlength="4" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        </label>
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Proprietario</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Registre a empresa proprietaria conforme documento.</p>
                                    <label class="mt-4 block space-y-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Empresa proprietaria</span>
                                        <input name="proprietario" value="{{ $value('proprietario') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </label>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Fornecedor</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Quando aplicavel, informe locadora ou subcontratada.</p>
                                    <label class="mt-4 block space-y-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Fornecedor / locadora</span>
                                        <input name="fornecedor" value="{{ $value('fornecedor') }}" class="h-11 w-full rounded-lg border border-zinc-200 px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </label>
                                </section>
                            </div>
                        </section>

                        <aside class="h-fit rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-28">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Anexos</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Documentos do veiculo</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="paperclip" class="h-5 w-5"></i>
                                </div>
                            </div>

                            <label class="mt-5 block rounded-2xl border border-zinc-200 bg-white p-4">
                                <span class="block text-sm font-black text-brand-black">CRLV do veiculo</span>
                                <span class="mt-1 block text-xs leading-5 text-brand-gray">Anexe PDF ou imagem do CRLV atualizado.</span>
                                <input type="file" name="crlv" data-existing-file="{{ filled($solicitacao?->crlv_path) ? '1' : '0' }}" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-4 block w-full text-sm text-brand-gray file:mr-4 file:rounded-lg file:border-0 file:bg-brand-burgundy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                            </label>

                            <label class="mt-3 block rounded-2xl border border-zinc-200 bg-white p-4">
                                <span class="block text-sm font-black text-brand-black">Documentacoes adicionais</span>
                                <span class="mt-1 block text-xs leading-5 text-brand-gray">Contrato, autorizacao, laudos, evidencias ou documentos complementares.</span>
                                <input type="file" name="documentos_adicionais[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" class="mt-4 block w-full text-sm text-brand-gray file:mr-4 file:rounded-lg file:border-0 file:bg-brand-gray file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                            </label>

                            <div class="mt-5 rounded-2xl border border-brand-burgundy/20 bg-brand-burgundy-soft p-4">
                                <p class="text-sm font-black text-brand-burgundy">Conferencia obrigatoria</p>
                                <p class="mt-1 text-xs leading-5 text-brand-burgundy">Placa, RENAVAM, marca, modelo, ano, cor e proprietario devem bater com o CRLV antes de avancar.</p>
                            </div>

                            <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-5">
                                <button type="button" onclick="document.getElementById('step-inicial-trigger').click()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                    Voltar Step 01
                                </button>
                                <button data-next-step="step-tag" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar cadastro
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>

                <div id="step-tag" class="content" role="tabpanel" aria-labelledby="step-tag-trigger">
                    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_390px]">
                        <section class="min-w-0 rounded-[28px] border border-zinc-200 bg-white p-6 lg:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-black text-brand-burgundy shadow-sm">Step 03</span>
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy">Forms oficial</span>
                            </div>

                            <div class="mt-5 max-w-5xl">
                                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl">Solicitacao de TAG pelo Forms.</h2>
                                <p class="mt-3 text-sm leading-6 text-brand-gray">Preencha o formulario oficial com os dados completos do veiculo. O CRLV deve ser conferido imediatamente para evitar erro de cadastro.</p>
                            </div>

                            <div class="mt-6 flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-brand-burgundy to-brand-burgundy-dark p-5 text-white shadow-xl shadow-brand-burgundy/20 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-lg font-black">Abrir formulario de solicitacao de TAG</p>
                                    <p class="mt-1 text-sm text-white/85">Conferir placa, RENAVAM, marca, modelo, ano e proprietario conforme CRLV antes de enviar.</p>
                                </div>
                                <a href="https://forms.office.com/pages/responsepage.aspx?id=G1eTeCxs70y02n1LJmoGJhknOqHDgJlOtoirO7vT9mJUMDNOOE0xOU45RENMWU5KMlozWEdaMDdUTS4u&route=shorturl" target="_blank" rel="noopener" class="inline-flex h-12 shrink-0 items-center justify-center rounded-full bg-white px-7 text-sm font-black text-brand-burgundy shadow-sm transition hover:bg-brand-gray-soft">
                                    Abrir Forms
                                </a>
                            </div>

                            <details class="mt-6 rounded-2xl border border-zinc-200 bg-white">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-black text-[#3f0812]">
                                    Complemento: orientacoes de preenchimento do Forms de Solicitacao de TAG
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-200 text-brand-burgundy">+</span>
                                </summary>
                                <div class="border-t border-zinc-200 px-5 py-4">
                                    <p class="text-sm leading-6 text-brand-gray">Use o CRLV como fonte principal. Confira solicitante, data da solicitacao, numero do contrato, empresa, placa, cor, proprietario, tipo, fabricante, modelo, ano de fabricacao e UF/RENAVAM antes do envio.</p>
                                </div>
                            </details>

                            <div class="mt-5 space-y-3">
                                @foreach ($tagChecks as $key => $item)
                                    <label class="flex cursor-pointer gap-4 rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-burgundy/40 hover:bg-brand-gray-soft/40" data-auto-check-card="tag_{{ $key }}">
                                        <input type="hidden" name="tag_checklist_data[{{ $key }}]" value="0">
                                        <input type="checkbox" name="tag_checklist_data[{{ $key }}]" value="1" @checked($checked("tag_checklist_data", $key)) data-auto-check="tag_{{ $key }}" class="mt-1 h-5 w-5 rounded border-zinc-300 text-brand-burgundy accent-[#6f1731]">
                                        <span>
                                            <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>

                        <aside class="h-fit rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-28">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Registro da TAG</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Evidencia e protocolo</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="file-check-2" class="h-5 w-5"></i>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da solicitacao</span>
                                    <input type="date" name="tag_data_solicitacao" value="{{ $value('tag_data_solicitacao') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Protocolo / confirmacao</span>
                                    <input name="tag_numero_protocolo" value="{{ $value('tag_numero_protocolo') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="block rounded-2xl border border-zinc-200 bg-white p-4">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Evidencia</span>
                                    <input type="file" name="tag_evidencia" data-existing-file="{{ filled($solicitacao?->tag_evidencia_path) ? '1' : '0' }}" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-3 block w-full text-sm text-brand-gray file:mr-4 file:rounded-lg file:border-0 file:bg-brand-burgundy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
                                    <textarea name="tag_observacoes" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('tag_observacoes') }}</textarea>
                                </label>
                            </div>

                            <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-5">
                                <button type="button" onclick="document.getElementById('step-veiculo-trigger').click()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                    Voltar Step 02
                                </button>
                                <button data-next-step="step-subcontratacao" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar TAG
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>

                <div id="step-subcontratacao" class="content" role="tabpanel" aria-labelledby="step-subcontratacao-trigger">
                    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_390px]">
                        <section class="min-w-0 rounded-[28px] border border-zinc-200 bg-white p-6 lg:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-black text-brand-burgundy shadow-sm">Segundo</span>
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy">Duas fases</span>
                            </div>

                            <div class="mt-5 max-w-5xl">
                                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl">Subcontratacao do fornecedor e do veiculo.</h2>
                                <p class="mt-3 text-sm leading-6 text-brand-gray">A solicitacao e feita em duas etapas: primeiro a analise inicial; depois, se aprovado, a solicitacao de autorizacao.</p>
                            </div>

                            <div class="mt-6 flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-brand-burgundy to-brand-burgundy-dark p-5 text-white shadow-xl shadow-brand-burgundy/20 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-lg font-black">Abrir formulario de subcontratacao</p>
                                    <p class="mt-1 text-sm text-white/85">Enviar cartao CNPJ, minuta Omega/fornecedor, contrato social do fornecedor e dados do veiculo.</p>
                                </div>
                                <a href="https://forms.office.com/" target="_blank" rel="noopener" class="inline-flex h-12 shrink-0 items-center justify-center rounded-full bg-white px-7 text-sm font-black text-brand-burgundy shadow-sm transition hover:bg-brand-gray-soft">
                                    Abrir formulario
                                </a>
                            </div>

                            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                                    <p class="text-sm font-black text-[#3f0812]">Fase 1 - Analise</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Enviar a solicitacao inicial para avaliacao da subcontratacao.</p>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                                    <p class="text-sm font-black text-[#3f0812]">Fase 2 - Autorizacao</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Apos aprovacao da analise, solicitar a autorizacao correspondente.</p>
                                </section>
                            </div>

                            <details class="mt-5 rounded-2xl border border-zinc-200 bg-white">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-black text-[#3f0812]">
                                    Complemento: orientacoes do Forms - Fase 1: Analise de Subcontratacao
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-200 text-brand-burgundy">+</span>
                                </summary>
                                <div class="border-t border-zinc-200 px-5 py-4">
                                    <p class="text-sm leading-6 text-brand-gray">Anexe documentos cadastrais do fornecedor, minuta, contrato social e dados do veiculo. Esta fase nao substitui a autorizacao final.</p>
                                </div>
                            </details>

                            <details class="mt-3 rounded-2xl border border-zinc-200 bg-white">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-black text-[#3f0812]">
                                    Complemento: orientacoes do Forms - Fase 2: Autorizacao de Subcontratacao
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-200 text-brand-burgundy">+</span>
                                </summary>
                                <div class="border-t border-zinc-200 px-5 py-4">
                                    <p class="text-sm leading-6 text-brand-gray">Somente avance para esta fase depois da analise aprovada. Registre protocolo, data e evidencia da autorizacao solicitada.</p>
                                </div>
                            </details>

                            <div class="mt-5 grid gap-3 lg:grid-cols-2">
                                @foreach ($subcontratacaoDocs as $input => $item)
                                    <label class="block rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-burgundy/40 hover:bg-brand-gray-soft/40">
                                        <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                                        <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                                        <input type="file" name="{{ $input }}" data-existing-file="{{ filled(data_get($solicitacao, $input.'_path')) ? '1' : '0' }}" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" class="mt-4 block w-full text-sm text-brand-gray file:mr-4 file:rounded-lg file:border-0 file:bg-brand-burgundy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                                    </label>
                                @endforeach
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($subcontratacaoChecks as $key => $item)
                                    <label class="flex cursor-pointer gap-4 rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-burgundy/40 hover:bg-brand-gray-soft/40" data-auto-check-card="subcontratacao_{{ $key }}">
                                        <input type="hidden" name="subcontratacao_checklist_data[{{ $key }}]" value="0">
                                        <input type="checkbox" name="subcontratacao_checklist_data[{{ $key }}]" value="1" @checked($checked("subcontratacao_checklist_data", $key)) data-auto-check="subcontratacao_{{ $key }}" class="mt-1 h-5 w-5 rounded border-zinc-300 text-brand-burgundy accent-[#6f1731]">
                                        <span>
                                            <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>

                        <aside class="h-fit rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-28">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Subcontratacao</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Controle da etapa</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="file-signature" class="h-5 w-5"></i>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da analise</span>
                                    <input type="date" name="subcontratacao_data_analise" value="{{ $value('subcontratacao_data_analise') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da autorizacao</span>
                                    <input type="date" name="subcontratacao_data_autorizacao" value="{{ $value('subcontratacao_data_autorizacao') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Protocolo</span>
                                    <input name="subcontratacao_protocolo" value="{{ $value('subcontratacao_protocolo') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="block rounded-2xl border border-zinc-200 bg-white p-4">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Evidencia</span>
                                    <input type="file" name="subcontratacao_evidencia" data-existing-file="{{ filled($solicitacao?->subcontratacao_evidencia_path) ? '1' : '0' }}" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" class="mt-3 block w-full text-sm text-brand-gray file:mr-4 file:rounded-lg file:border-0 file:bg-brand-burgundy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
                                    <textarea name="subcontratacao_observacoes" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('subcontratacao_observacoes') }}</textarea>
                                </label>
                            </div>

                            <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-5">
                                <button type="button" onclick="document.getElementById('step-tag-trigger').click()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                    Voltar Step 03
                                </button>
                                <button data-next-step="step-svg" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar etapa
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>

                <div id="step-svg" class="content" role="tabpanel" aria-labelledby="step-svg-trigger">
                    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_390px]">
                        <section class="min-w-0 rounded-[28px] border border-zinc-200 bg-white p-6 lg:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-black text-brand-burgundy shadow-sm">Terceiro</span>
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy">Aprovacao Vale</span>
                            </div>

                            <div class="mt-5 max-w-5xl">
                                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl">Postagem da mobilizacao no SVG.</h2>
                                <p class="mt-3 text-sm leading-6 text-brand-gray">Com os documentos reunidos, realizar a postagem no SVG e acompanhar o fluxo de aprovacao Vale ate retorno conclusivo.</p>
                            </div>

                            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                                <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                                    <p class="text-sm font-black text-[#3f0812]">Postagem</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Registrar data, responsavel e evidencia da postagem no SVG.</p>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                                    <p class="text-sm font-black text-[#3f0812]">Acompanhamento</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Aguardar fluxo de aprovacao Vale e tratar pendencias.</p>
                                </section>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($svgChecks as $key => $item)
                                    <label class="flex cursor-pointer gap-4 rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-burgundy/40 hover:bg-brand-gray-soft/40" data-auto-check-card="svg_{{ $key }}">
                                        <input type="hidden" name="svg_checklist_data[{{ $key }}]" value="0">
                                        <input type="checkbox" name="svg_checklist_data[{{ $key }}]" value="1" @checked($checked("svg_checklist_data", $key)) data-auto-check="svg_{{ $key }}" class="mt-1 h-5 w-5 rounded border-zinc-300 text-brand-burgundy accent-[#6f1731]">
                                        <span>
                                            <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>

                        <aside class="h-fit rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-28">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">SVG</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Postagem e retorno</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="upload-cloud" class="h-5 w-5"></i>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data da postagem</span>
                                    <input type="date" name="svg_data_postagem" value="{{ $value('svg_data_postagem') }}" data-svg-postagem class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Protocolo / solicitacao</span>
                                    <input name="svg_protocolo" value="{{ $value('svg_protocolo') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                </label>
                                <label class="block rounded-2xl border border-zinc-200 bg-white p-4">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-brand-gray">Evidencia da postagem</span>
                                    <input type="file" name="svg_evidencia" data-existing-file="{{ filled($solicitacao?->svg_evidencia_path) ? '1' : '0' }}" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" class="mt-3 block w-full text-sm text-brand-gray file:mr-4 file:rounded-lg file:border-0 file:bg-brand-burgundy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
                                    <textarea name="svg_observacoes" rows="4" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('svg_observacoes') }}</textarea>
                                </label>
                            </div>

                            <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-5">
                                <button type="button" onclick="document.getElementById('step-subcontratacao-trigger').click()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                    Voltar Step 04
                                </button>
                                <button data-next-step="step-vistoria" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar SVG
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>

                <div id="step-vistoria" class="content" role="tabpanel" aria-labelledby="step-vistoria-trigger">
                    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_390px]">
                        <section class="min-w-0 rounded-[28px] border border-zinc-200 bg-white p-6 lg:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-black text-brand-burgundy shadow-sm">Quarto</span>
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy">Prevenir atraso</span>
                            </div>

                            <div class="mt-5 max-w-5xl">
                                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl">Agendamento de vistoria com antecedencia.</h2>
                                <p class="mt-3 text-sm leading-6 text-brand-gray">A vistoria precisa ser prevista antes do inicio da atividade. O sistema calcula uma janela de SLA entre 3 e 10 dias apos a postagem no SVG.</p>
                            </div>

                            <div class="mt-6 grid gap-4 xl:grid-cols-3">
                                <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2">
                                    <p class="text-sm font-black text-[#3f0812]">Previsao automatica pelo SLA do SVG</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray" data-vistoria-sla-text>Informe a data de postagem no SVG para calcular a janela prevista.</p>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Inicio da janela</span>
                                            <input type="date" name="vistoria_previsao_inicio" value="{{ $value('vistoria_previsao_inicio') }}" data-vistoria-previsao-inicio readonly class="h-11 w-full rounded-lg border border-zinc-200 bg-brand-gray-soft px-3 text-sm text-brand-black outline-none">
                                        </label>
                                        <label class="space-y-2">
                                            <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Fim da janela</span>
                                            <input type="date" name="vistoria_previsao_fim" value="{{ $value('vistoria_previsao_fim') }}" data-vistoria-previsao-fim readonly class="h-11 w-full rounded-lg border border-zinc-200 bg-brand-gray-soft px-3 text-sm text-brand-black outline-none">
                                        </label>
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                                    <p class="text-sm font-black text-[#3f0812]">Agendamento</p>
                                    <p class="mt-2 text-xs leading-5 text-brand-gray">Registrar a data escolhida dentro da janela prevista.</p>
                                    <label class="mt-4 block space-y-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Data agendada</span>
                                        <input type="date" name="vistoria_data_agendada" value="{{ $value('vistoria_data_agendada') }}" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                    </label>
                                </section>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($vistoriaChecks as $key => $item)
                                    <label class="flex cursor-pointer gap-4 rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-burgundy/40 hover:bg-brand-gray-soft/40" data-auto-check-card="vistoria_{{ $key }}">
                                        <input type="hidden" name="vistoria_checklist_data[{{ $key }}]" value="0">
                                        <input type="checkbox" name="vistoria_checklist_data[{{ $key }}]" value="1" @checked($checked("vistoria_checklist_data", $key)) data-auto-check="vistoria_{{ $key }}" class="mt-1 h-5 w-5 rounded border-zinc-300 text-brand-burgundy accent-[#6f1731]">
                                        <span>
                                            <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>

                        <aside class="h-fit rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-28">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Vistoria</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Controle da inspecao</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-burgundy-soft text-brand-burgundy">
                                    <i data-lucide="calendar-check" class="h-5 w-5"></i>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Resultado</span>
                                    <select name="vistoria_resultado" class="h-11 w-full rounded-lg border border-zinc-200 bg-white px-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">
                                        <option value="">Selecione...</option>
                                        <option value="pendente" @selected($selected('vistoria_resultado', 'pendente'))>Pendente</option>
                                        <option value="aprovado" @selected($selected('vistoria_resultado', 'aprovado'))>Aprovado</option>
                                        <option value="reprovado" @selected($selected('vistoria_resultado', 'reprovado'))>Reprovado</option>
                                        <option value="com_pendencia" @selected($selected('vistoria_resultado', 'com_pendencia'))>Com pendencia</option>
                                    </select>
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-bold uppercase tracking-wide text-brand-gray">Observacoes</span>
                                    <textarea name="vistoria_observacoes" rows="5" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10">{{ $value('vistoria_observacoes') }}</textarea>
                                </label>
                            </div>

                            <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-5">
                                <button type="button" onclick="document.getElementById('step-svg-trigger').click()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                    Voltar Step 05
                                </button>
                                <button data-next-step="step-finalizacao" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar vistoria
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>

                <div id="step-finalizacao" class="content" role="tabpanel" aria-labelledby="step-finalizacao-trigger">
                    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_390px]">
                        <section class="min-w-0 rounded-[28px] border border-zinc-200 bg-white p-6 lg:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <span class="inline-flex w-fit items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700 shadow-sm">Conclusao</span>
                                <span class="inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy" data-finalizacao-badge>Analise automatica</span>
                            </div>

                            <div class="mt-5 max-w-5xl">
                                <h2 class="text-3xl font-black leading-tight text-[#3f0812] lg:text-4xl" data-finalizacao-title>Mobilizacao em analise.</h2>
                                <p class="mt-3 text-sm leading-6 text-brand-gray" data-finalizacao-description>O sistema verifica os dados preenchidos nas etapas anteriores e informa automaticamente se o processo pode ser finalizado.</p>
                            </div>

                            <div class="mt-6 rounded-2xl border border-zinc-200 bg-brand-gray-soft p-5">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-black text-[#3f0812]">Status automatico do processo</p>
                                        <p class="mt-1 text-xs leading-5 text-brand-gray" data-finalizacao-progress>Preencha as etapas anteriores para liberar a conclusao.</p>
                                    </div>
                                    <span class="inline-flex w-fit items-center rounded-full bg-white px-4 py-2 text-xs font-black text-brand-gray shadow-sm" data-finalizacao-status>Nao finalizada</span>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach ($finalizacaoChecks as $key => $item)
                                    <div class="flex gap-4 rounded-2xl border border-zinc-200 bg-white p-4 transition" data-finalizacao-item="{{ $key }}">
                                        <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded border border-zinc-300 bg-white text-white" data-finalizacao-icon>
                                            <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                        </span>
                                        <span>
                                            <span class="block text-sm font-black text-brand-black">{{ $item['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5 text-brand-gray">{{ $item['help'] }}</span>
                                            <span class="mt-2 block text-xs font-bold text-brand-burgundy" data-finalizacao-message></span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <aside class="h-fit rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm 2xl:sticky 2xl:top-28">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Finalizacao</p>
                                    <h3 class="mt-1 text-lg font-black text-brand-black">Encerramento</h3>
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                    <i data-lucide="badge-check" class="h-5 w-5"></i>
                                </div>
                            </div>

                            <div class="mt-5 rounded-2xl border border-zinc-200 bg-white p-4">
                                <p class="text-sm font-black text-brand-black">Regra de conclusao</p>
                                <p class="mt-2 text-xs leading-5 text-brand-gray">A mobilizacao so fica finalizada quando solicitacao, TAG, subcontratacao, SVG e vistoria estiverem completos.</p>
                            </div>

                            <div class="mt-5 flex flex-col gap-2 border-t border-zinc-200 pt-5">
                                <button type="button" onclick="document.getElementById('step-vistoria-trigger').click()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                    Voltar Step 06
                                </button>
                                <button name="status" value="em_andamento" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Salvar pendencias
                                </button>
                                <button name="status" value="concluido" data-finalizacao-submit disabled class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-4 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:text-zinc-500 disabled:shadow-none">
                                    <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                                    Finalizar mobilizacao
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection





