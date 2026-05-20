<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use Carbon\Carbon;

final class ColaboradorMovimentacaoService
{
    public function registrar(Colaborador $colaborador, array $data, ?int $userId = null): ColaboradorMovimentacao
    {
        $tipo = (string) $data['tipo'];
        $payload = $this->montarPayload($colaborador, $data);
        $payload['registrado_por_user_id'] = $userId;

        $movimentacao = ColaboradorMovimentacao::create($payload);
        $this->aplicarNoCadastro($colaborador, $movimentacao);

        return $movimentacao;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function montarPayload(Colaborador $colaborador, array $data): array
    {
        $tipo = (string) $data['tipo'];
        $base = [
            'colaborador_id' => $colaborador->id,
            'tipo' => $tipo,
            'data_inicio' => $data['data_inicio'],
            'data_fim' => $data['data_fim'] ?? null,
            'status_anterior' => $colaborador->status,
            'motivo_texto' => $data['motivo_texto'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
        ];

        return match ($tipo) {
            ColaboradorMovimentacaoTipos::DESLIGAMENTO => array_merge($base, [
                'status_novo' => 'desligado',
                'tipo_rescisao' => $data['tipo_rescisao'] ?? null,
                'motivo_codigo' => $data['motivo_codigo'] ?? null,
            ]),
            ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO => array_merge($base, [
                'centro_custo_anterior' => $colaborador->centro_custo,
                'centro_custo_novo' => $data['centro_custo_novo'] ?? null,
                'tipo_contrato_anterior' => $colaborador->tipo_contrato,
                'tipo_contrato_novo' => $data['tipo_contrato_novo'] ?? $colaborador->tipo_contrato,
                'local_trabalho_anterior' => $colaborador->local_trabalho,
                'local_trabalho_novo' => $data['local_trabalho_novo'] ?? $colaborador->local_trabalho,
                'departamento_anterior' => $colaborador->departamento,
                'departamento_novo' => $data['departamento_novo'] ?? $colaborador->departamento,
            ]),
            ColaboradorMovimentacaoTipos::PROMOCAO => array_merge($base, [
                'cargo_anterior' => $colaborador->cargo,
                'cargo_novo' => $data['cargo_novo'] ?? null,
                'salario_anterior' => $colaborador->salario_inicial,
                'salario_novo' => $data['salario_novo'] ?? null,
                'departamento_anterior' => $colaborador->departamento,
                'departamento_novo' => $data['departamento_novo'] ?? $colaborador->departamento,
            ]),
            ColaboradorMovimentacaoTipos::MUDANCA_FUNCAO => array_merge($base, [
                'cargo_anterior' => $colaborador->cargo,
                'cargo_novo' => $data['cargo_novo'] ?? null,
                'departamento_anterior' => $colaborador->departamento,
                'departamento_novo' => $data['departamento_novo'] ?? $colaborador->departamento,
            ]),
            ColaboradorMovimentacaoTipos::FERIAS => array_merge($base, [
                'dias_ferias' => isset($data['dias_ferias']) ? (int) $data['dias_ferias'] : $this->calcularDiasFerias($data),
                'abono_pecuniario' => (bool) ($data['abono_pecuniario'] ?? false),
            ]),
            ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS => array_merge($base, [
                'status_novo' => 'afastado',
                'especie_beneficio_inss' => $data['especie_beneficio_inss'] ?? null,
                'cid' => $data['cid'] ?? null,
            ]),
            default => $base,
        };
    }

    private function aplicarNoCadastro(Colaborador $colaborador, ColaboradorMovimentacao $mov): void
    {
        $updates = match ($mov->tipo) {
            ColaboradorMovimentacaoTipos::DESLIGAMENTO => [
                'data_demissao' => $mov->data_inicio,
                'status' => 'desligado',
            ],
            ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO => array_filter([
                'centro_custo' => $mov->centro_custo_novo,
                'tipo_contrato' => $mov->tipo_contrato_novo,
                'local_trabalho' => $mov->local_trabalho_novo,
                'departamento' => $mov->departamento_novo,
            ], fn ($v) => $v !== null && $v !== ''),
            ColaboradorMovimentacaoTipos::PROMOCAO => array_filter([
                'cargo' => $mov->cargo_novo,
                'salario_inicial' => $mov->salario_novo,
                'departamento' => $mov->departamento_novo,
            ], fn ($v) => $v !== null && $v !== ''),
            ColaboradorMovimentacaoTipos::MUDANCA_FUNCAO => array_filter([
                'cargo' => $mov->cargo_novo,
                'departamento' => $mov->departamento_novo,
            ], fn ($v) => $v !== null && $v !== ''),
            ColaboradorMovimentacaoTipos::FERIAS => $this->updatesFeriasOuInss($mov, $colaborador),
            ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS => $this->updatesFeriasOuInss($mov, $colaborador),
            default => [],
        };

        if ($updates !== []) {
            $colaborador->update($updates);
        }
    }

    /** @return array<string, mixed> */
    private function updatesFeriasOuInss(ColaboradorMovimentacao $mov, Colaborador $colaborador): array
    {
        if ($this->periodoEncerrado($mov)) {
            if ($colaborador->status !== 'desligado') {
                return ['status' => 'ativo'];
            }

            return [];
        }

        if ($mov->data_inicio && Carbon::parse($mov->data_inicio)->lte(today())) {
            return ['status' => 'afastado'];
        }

        return [];
    }

    private function periodoEncerrado(ColaboradorMovimentacao $mov): bool
    {
        return $mov->data_fim !== null && Carbon::parse($mov->data_fim)->lte(today());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function calcularDiasFerias(array $data): ?int
    {
        if (empty($data['data_inicio']) || empty($data['data_fim'])) {
            return isset($data['dias_ferias']) ? (int) $data['dias_ferias'] : null;
        }

        $ini = Carbon::parse($data['data_inicio']);
        $fim = Carbon::parse($data['data_fim']);

        return $ini->diffInDays($fim) + 1;
    }
}
