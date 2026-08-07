<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use Carbon\Carbon;

/**
 * Colunas do cadastro de colaborador para exportações (planilha do efetivo e ficha individual).
 */
final class ColaboradorCadastroExportCampos
{
    /**
     * @return list<string>
     */
    public static function headersPlanilhaEfetivo(): array
    {
        return array_map(
            static fn (array $col) => $col['header'],
            self::colunasPlanilhaEfetivo(),
        );
    }

    /**
     * @return list<string>
     */
    public static function linhaPlanilhaEfetivo(Colaborador $colaborador): array
    {
        return array_map(
            static fn (array $col) => (string) ($col['valor']($colaborador) ?? ''),
            self::colunasPlanilhaEfetivo(),
        );
    }

    /**
     * @return list<array{titulo: string, campos: list<array{0: string, 1: string}>}>
     */
    public static function secoesFicha(Colaborador $colaborador): array
    {
        $porSecao = [];
        foreach (self::colunasPlanilhaEfetivo() as $col) {
            $porSecao[$col['secao']][] = [
                $col['header'],
                (string) ($col['valor']($colaborador) ?? ''),
            ];
        }

        $secoes = [];
        foreach ($porSecao as $titulo => $campos) {
            $secoes[] = ['titulo' => $titulo, 'campos' => $campos];
        }

        return $secoes;
    }

    /**
     * @return list<array{header: string, secao: string, valor: callable(Colaborador): string}>
     */
    private static function colunasPlanilhaEfetivo(): array
    {
        return [
            ...self::grupo('01 — Identificação', [
                ['Matrícula', fn (Colaborador $c) => self::txt($c->matricula)],
                ['Nome completo', fn (Colaborador $c) => self::txt($c->nome)],
                ['CPF', fn (Colaborador $c) => self::txt($c->cpf)],
                ['RG', fn (Colaborador $c) => self::txt($c->rg)],
                ['Telefone', fn (Colaborador $c) => self::txt($c->telefone)],
                ['E-mail', fn (Colaborador $c) => self::txt($c->email)],
                ['Status', fn (Colaborador $c) => self::rotuloStatus($c->status)],
                ['Presença na obra liberada', fn (Colaborador $c) => $c->presenca_obra_liberado ? 'Sim' : 'Não'],
            ]),
            ...self::grupo('02 — Dados pessoais', [
                ['Data de nascimento', fn (Colaborador $c) => self::data($c->data_nascimento)],
                ['Local de nascimento', fn (Colaborador $c) => self::txt($c->local_nascimento)],
                ['UF de nascimento', fn (Colaborador $c) => self::txt($c->uf_nascimento)],
                ['Nacionalidade', fn (Colaborador $c) => self::txt($c->nacionalidade)],
                ['Estado civil', fn (Colaborador $c) => self::txt($c->estado_civil)],
                ['Cônjuge', fn (Colaborador $c) => self::txt($c->conjuge)],
                ['Sexo', fn (Colaborador $c) => self::txt($c->sexo)],
                ['Cor', fn (Colaborador $c) => self::txt($c->cor)],
                ['Grau de instrução', fn (Colaborador $c) => self::txt($c->grau_instrucao)],
                ['Nome do pai', fn (Colaborador $c) => self::txt($c->filiacao_pai)],
                ['Nome da mãe', fn (Colaborador $c) => self::txt($c->filiacao_mae)],
            ]),
            ...self::grupo('03 — Documentos e endereço', [
                ['Carteira profissional', fn (Colaborador $c) => self::txt($c->carteira_profissional)],
                ['Série CTPS', fn (Colaborador $c) => self::txt($c->serie_ctps)],
                ['Data CTPS', fn (Colaborador $c) => self::data($c->data_ctps)],
                ['Vencimento CTPS', fn (Colaborador $c) => self::data($c->vencimento_ctps)],
                ['PIS', fn (Colaborador $c) => self::txt($c->pis)],
                ['Título de eleitor', fn (Colaborador $c) => self::txt($c->titulo_eleitor)],
                ['Zona eleitoral', fn (Colaborador $c) => self::txt($c->zona_eleitoral)],
                ['Seção eleitoral', fn (Colaborador $c) => self::txt($c->secao_eleitoral)],
                ['Identidade', fn (Colaborador $c) => self::txt($c->carteira_identidade)],
                ['Emissão identidade', fn (Colaborador $c) => self::data($c->emissao_identidade)],
                ['Órgão emissor', fn (Colaborador $c) => self::txt($c->orgao_emissor)],
                ['Endereço', fn (Colaborador $c) => self::txt($c->endereco)],
                ['Número', fn (Colaborador $c) => self::txt($c->numero)],
                ['Bairro', fn (Colaborador $c) => self::txt($c->bairro)],
                ['Cidade', fn (Colaborador $c) => self::txt($c->cidade)],
                ['UF', fn (Colaborador $c) => self::txt($c->estado)],
                ['CEP', fn (Colaborador $c) => self::txt($c->cep)],
            ]),
            ...self::grupo('04 — Contrato e jornada', [
                ['Tipo de contrato', fn (Colaborador $c) => self::txt($c->tipo_contrato)],
                ['Centro de custo', fn (Colaborador $c) => self::txt($c->centro_custo)],
                ['Cargo', fn (Colaborador $c) => self::txt($c->cargo)],
                ['CBO', fn (Colaborador $c) => self::txt($c->cbo)],
                ['Departamento', fn (Colaborador $c) => self::txt($c->departamento)],
                ['Jornada semanal', fn (Colaborador $c) => self::txt($c->jornada_semanal)],
                ['Horário (texto)', fn (Colaborador $c) => self::txt($c->horario)],
                ['Escala de horários', fn (Colaborador $c) => self::txt($c->horarioEscala?->nome)],
                ['Offset ciclo escala', fn (Colaborador $c) => self::txt($c->horario_escala_ciclo_offset)],
                ['Local de trabalho', fn (Colaborador $c) => self::txt($c->local_trabalho)],
            ]),
            ...self::grupo('05 — Admissão e contatos', [
                ['Data de admissão', fn (Colaborador $c) => self::data($c->data_admissao)],
                ['Opção FGTS', fn (Colaborador $c) => self::data($c->data_opcao_fgts)],
                ['Data de demissão', fn (Colaborador $c) => self::data($c->data_demissao)],
                ['Forma de pagamento', fn (Colaborador $c) => self::txt($c->forma_pagamento)],
                ['Salário inicial', fn (Colaborador $c) => MoedaBr::format(filled($c->salario_inicial) ? (float) $c->salario_inicial : null) ?: ''],
                ['Almoço', fn (Colaborador $c) => self::txt($c->almoco)],
                ['Dependentes', fn (Colaborador $c) => self::txt($c->dependentes)],
                ['Emergência — Nome', fn (Colaborador $c) => self::txt($c->contato_emergencia_nome)],
                ['Emergência — Telefone', fn (Colaborador $c) => self::txt($c->contato_emergencia_telefone)],
                ['Emergência — Parentesco', fn (Colaborador $c) => self::txt($c->contato_emergencia_parentesco)],
                ['Observações', fn (Colaborador $c) => self::txt($c->observacoes)],
            ]),
            ...self::grupo('06 — Mobilização SGC Vale', [
                ['Mobilização SGC', fn (Colaborador $c) => self::rotuloMobilizacao($c->mobilizacao_status)],
                ['Data postagem SGC', fn (Colaborador $c) => self::data($c->sgc_data_postagem)],
                ['Nº solicitação SGC', fn (Colaborador $c) => self::txt($c->sgc_numero_solicitacao)],
                ['Data aprovação SGC', fn (Colaborador $c) => self::data($c->sgc_data_aprovacao)],
                ['Entrega do crachá SGC', fn (Colaborador $c) => self::data($c->sgc_data_entrega_cracha)],
                ['Observações SGC', fn (Colaborador $c) => self::txt($c->sgc_observacoes)],
            ]),
        ];
    }

    /**
     * @param  list<array{0: string, 1: callable(Colaborador): string}>  $campos
     * @return list<array{header: string, secao: string, valor: callable(Colaborador): string}>
     */
    private static function grupo(string $secao, array $campos): array
    {
        $out = [];
        foreach ($campos as [$header, $valor]) {
            $out[] = [
                'header' => $header,
                'secao' => $secao,
                'valor' => $valor,
            ];
        }

        return $out;
    }

    public static function indiceColunaStatus(): int
    {
        $headers = self::headersPlanilhaEfetivo();
        $index = array_search('Status', $headers, true);

        return $index === false ? 6 : $index;
    }

    public static function rotuloStatus(?string $status): string
    {
        return match ($status) {
            'ativo' => 'Ativo',
            'afastado' => 'Afastado INSS',
            'desligado' => 'Desligado',
            default => $status ? ucfirst($status) : '',
        };
    }

    public static function rotuloMobilizacao(?string $status): string
    {
        return match ($status) {
            'postado_sgc' => 'Postado no SGC',
            'aprovado' => 'Aprovado',
            'mobilizacao_concluida' => 'Mobilização concluída',
            'pendente' => 'Pendente',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : 'Pendente',
        };
    }

    public static function txt(mixed $value): string
    {
        return filled($value) ? trim((string) $value) : '';
    }

    public static function data(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
