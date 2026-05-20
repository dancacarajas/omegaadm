@extends('layouts.app')

@section('title', 'Ficha do colaborador - Omega286')
@section('eyebrow', 'RH / Efetivo')
@section('page-title', 'Ficha do colaborador')

@section('actions')
    <a href="{{ route('rh.efetivo.movimentacoes.create', ['colaborador' => $colaborador, 'tipo' => 'desligamento']) }}" class="inline-flex h-10 items-center gap-2 rounded-md bg-brand-burgundy px-4 py-2 text-sm font-semibold text-white shadow-sm">
        <i data-lucide="git-branch" class="h-4 w-4"></i>
        Movimentação
    </a>
    <a href="{{ route('rh.efetivo.edit', $colaborador) }}" class="h-10 rounded-md border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm">Editar</a>
    <a href="{{ route('rh.efetivo.index') }}" class="h-10 rounded-md border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm">Voltar</a>
@endsection

@section('content')
    @php
        $item = fn ($label, $value) => ['label' => $label, 'value' => filled($value) ? $value : '-'];
        $date = fn ($value) => $value ? $value->format('d/m/Y') : '-';
        $money = fn ($value) => filled($value) ? 'R$ '.number_format((float) $value, 2, ',', '.') : '-';
        $statusLabel = [
            'ativo' => 'Ativo',
            'afastado' => 'Afastado',
            'desligado' => 'Desligado',
        ];
        $mobilizacaoLabel = [
            'pendente' => 'Pendente',
            'postado_sgc' => 'Postado no SGC',
            'aprovado' => 'Aprovado',
            'mobilizacao_concluida' => 'Mobilização concluída',
        ];
        $section = function (string $title, array $fields) {
            return compact('title', 'fields');
        };
        $sections = [
            $section('Dados pessoais', [
                $item('Nome completo', $colaborador->nome),
                $item('Matrícula', $colaborador->matricula),
                $item('Telefone', $colaborador->telefone),
                $item('CPF', $colaborador->cpf),
                $item('RG', $colaborador->rg),
                $item('Data de nascimento', $date($colaborador->data_nascimento)),
                $item('Local de nascimento', $colaborador->local_nascimento),
                $item('UF de nascimento', $colaborador->uf_nascimento),
                $item('Nacionalidade', $colaborador->nacionalidade),
                $item('Estado civil', $colaborador->estado_civil),
                $item('Cônjuge', $colaborador->conjuge),
                $item('Sexo', $colaborador->sexo),
                $item('Cor', $colaborador->cor),
                $item('Grau de instrução', $colaborador->grau_instrucao),
                $item('Filiação - Pai', $colaborador->filiacao_pai),
                $item('Filiação - Mãe', $colaborador->filiacao_mae),
            ]),
            $section('Documentos e endereço', [
                $item('Carteira profissional', $colaborador->carteira_profissional),
                $item('Série CTPS', $colaborador->serie_ctps),
                $item('Data CTPS', $date($colaborador->data_ctps)),
                $item('Vencimento CTPS', $date($colaborador->vencimento_ctps)),
                $item('PIS', $colaborador->pis),
                $item('Título de eleitor', $colaborador->titulo_eleitor),
                $item('Zona eleitoral', $colaborador->zona_eleitoral),
                $item('Seção eleitoral', $colaborador->secao_eleitoral),
                $item('Carteira de identidade', $colaborador->carteira_identidade),
                $item('Emissão da identidade', $date($colaborador->emissao_identidade)),
                $item('Órgão emissor', $colaborador->orgao_emissor),
                $item('Endereço', $colaborador->endereco),
                $item('Número', $colaborador->numero),
                $item('Bairro', $colaborador->bairro),
                $item('Cidade', $colaborador->cidade),
                $item('Estado', $colaborador->estado),
                $item('CEP', $colaborador->cep),
            ]),
            $section('Dados do contrato', [
                $item('Tipo de contrato', $colaborador->tipo_contrato),
                $item('Status', $statusLabel[$colaborador->status] ?? ucfirst((string) $colaborador->status)),
                $item('Departamento', $colaborador->departamento),
                $item('Centro de custo', $colaborador->centro_custo),
                $item('Cargo', $colaborador->cargo),
                $item('CBO', $colaborador->cbo),
                $item('Jornada semanal', $colaborador->jornada_semanal),
                $item('Horário', $colaborador->horario),
                $item('Cadastro de horários', $colaborador->horarioEscala?->nome),
            ]),
            $section('Dados da admissão', [
                $item('Data de admissão', $date($colaborador->data_admissao)),
                $item('Data da opção pelo FGTS', $date($colaborador->data_opcao_fgts)),
                $item('Data de demissão', $date($colaborador->data_demissao)),
                $item('Forma de pagamento', $colaborador->forma_pagamento),
                $item('Salário inicial', $money($colaborador->salario_inicial)),
                $item('Local de trabalho', $colaborador->local_trabalho),
                $item('Almoço', $colaborador->almoco),
                $item('Dependentes', $colaborador->dependentes),
                $item('Contato de emergência', $colaborador->contato_emergencia_nome),
                $item('Telefone de emergência', $colaborador->contato_emergencia_telefone),
                $item('Parentesco', $colaborador->contato_emergencia_parentesco),
                $item('Observações', $colaborador->observacoes),
            ]),
        ];
    @endphp

    <div class="grid gap-5 xl:grid-cols-[320px_1fr]">
        <aside class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            @if (filled($colaborador->foto_path))
                <div class="h-28 w-28 overflow-hidden rounded-xl ring-1 ring-zinc-200">
                    <img src="{{ $colaborador->urlFotoPerfil() }}" alt="Foto de perfil" class="h-full w-full object-cover">
                </div>
            @else
                <div class="flex h-28 w-28 items-center justify-center rounded-xl bg-brand-burgundy text-4xl font-bold text-white">
                    {{ mb_substr($colaborador->nome, 0, 1) }}
                </div>
            @endif
            <h2 class="mt-5 text-xl font-bold text-brand-black">{{ $colaborador->nome }}</h2>
            <p class="mt-1 text-sm text-brand-gray">{{ $colaborador->cargo ?: 'Cargo não informado' }}</p>
            <div class="mt-5 rounded-md bg-brand-burgundy-soft px-3 py-2 text-sm font-semibold text-brand-burgundy">
                {{ $statusLabel[$colaborador->status] ?? ucfirst((string) $colaborador->status) }}
            </div>
            <form method="POST" action="{{ route('rh.efetivo.destroy', $colaborador) }}" class="mt-5" onsubmit="return confirm('Remover este colaborador do efetivo?')">
                @csrf
                @method('DELETE')
                <button class="h-10 w-full rounded-md border border-zinc-200 text-sm font-semibold text-brand-black">Remover cadastro</button>
            </form>
        </aside>

        <div class="space-y-5">
            @foreach ($sections as $section)
                <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-brand-black">{{ $section['title'] }}</h3>
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        @foreach ($section['fields'] as $field)
                            <div class="{{ in_array($field['label'], ['Dependentes', 'Observações'], true) ? 'md:col-span-3' : '' }}">
                                <p class="text-xs font-semibold uppercase text-brand-gray">{{ $field['label'] }}</p>
                                <p class="mt-1 whitespace-pre-line text-sm font-semibold text-brand-black">{{ $field['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-brand-black">Histórico de movimentações</h3>
                        <p class="mt-1 text-sm text-brand-gray">Desligamento, transferências, promoções, férias e afastamentos INSS.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tiposMovimentacao as $tipoKey => $tipoLabel)
                            <a href="{{ route('rh.efetivo.movimentacoes.create', ['colaborador' => $colaborador, 'tipo' => $tipoKey]) }}" class="rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-xs font-semibold text-brand-black hover:border-brand-burgundy hover:text-brand-burgundy">{{ $tipoLabel }}</a>
                        @endforeach
                    </div>
                </div>
                @if (session('success'))
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900">{{ session('success') }}</div>
                @endif
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                        <thead class="border-b border-zinc-100 text-xs font-bold uppercase tracking-wide text-brand-gray">
                            <tr>
                                <th class="py-2 pr-3">Data</th>
                                <th class="py-2 pr-3">Tipo</th>
                                <th class="py-2 pr-3">Resumo</th>
                                <th class="py-2 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($colaborador->movimentacoes as $mov)
                                <tr>
                                    <td class="whitespace-nowrap py-3 pr-3 font-medium text-brand-black">
                                        {{ $mov->data_inicio->format('d/m/Y') }}
                                        @if ($mov->data_fim)<span class="text-brand-gray"> — {{ $mov->data_fim->format('d/m/Y') }}</span>@endif
                                    </td>
                                    <td class="py-3 pr-3"><span class="rounded-full bg-brand-burgundy-soft px-2 py-0.5 text-xs font-bold text-brand-burgundy">{{ $mov->tipoLabel() }}</span></td>
                                    <td class="py-3 pr-3 text-brand-gray">{{ $mov->resumoAlteracao() }}</td>
                                    <td class="py-3 text-right">
                                        <form method="POST" action="{{ route('rh.efetivo.movimentacoes.destroy', [$colaborador, $mov]) }}" class="inline" onsubmit="return confirm('Remover este registro do histórico?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-brand-gray">Nenhuma movimentação registrada. Use o botão «Movimentação» acima.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-brand-black">Mobilização SGC Vale</h3>
                        <p class="mt-1 text-sm text-brand-gray">Controle de postagem, aprovação e entrega do crachá.</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full {{ $colaborador->mobilizacao_status === 'mobilizacao_concluida' ? 'bg-brand-burgundy-soft text-brand-burgundy' : 'bg-brand-gray-soft text-brand-gray' }} px-3 py-1.5 text-xs font-bold">
                        <span class="h-1.5 w-1.5 rounded-full {{ $colaborador->mobilizacao_status === 'mobilizacao_concluida' ? 'bg-brand-burgundy' : 'bg-brand-gray' }}"></span>
                        {{ $mobilizacaoLabel[$colaborador->mobilizacao_status] ?? 'Pendente' }}
                    </span>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach ([
                        $item('Status da mobilização', $mobilizacaoLabel[$colaborador->mobilizacao_status] ?? 'Pendente'),
                        $item('Data de postagem no SGC', $date($colaborador->sgc_data_postagem)),
                        $item('Número da solicitação', $colaborador->sgc_numero_solicitacao),
                        $item('Data de aprovação', $date($colaborador->sgc_data_aprovacao)),
                        $item('Data de entrega do crachá', $date($colaborador->sgc_data_entrega_cracha)),
                        $item('Observações da mobilização', $colaborador->sgc_observacoes),
                    ] as $field)
                        <div class="{{ $field['label'] === 'Observações da mobilização' ? 'md:col-span-3' : '' }}">
                            <p class="text-xs font-semibold uppercase text-brand-gray">{{ $field['label'] }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm font-semibold text-brand-black">{{ $field['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection
