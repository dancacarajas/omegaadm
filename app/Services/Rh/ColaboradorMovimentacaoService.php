<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Support\Rh\ColaboradorMovimentacaoSituacao;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use Carbon\Carbon;

final class ColaboradorMovimentacaoService
{
    public function registrar(Colaborador $colaborador, array $data, ?int $userId = null): ColaboradorMovimentacao
    {
        $tipo = (string) $data['tipo'];
        $payload = $this->montarPayload($colaborador, $data);
        $payload['registrado_por_user_id'] = $userId;
        $payload = array_merge($payload, $this->metadadosSituacaoInicial($payload));

        $movimentacao = ColaboradorMovimentacao::create($payload);

        if ($movimentacao->isFinalizada()) {
            $this->sincronizarCadastroColaborador($colaborador);
        }

        return $movimentacao;
    }

    public function atualizar(ColaboradorMovimentacao $movimentacao, array $data): ColaboradorMovimentacao
    {
        $colaborador = $movimentacao->colaborador;
        if ($colaborador === null) {
            return $movimentacao;
        }

        $payload = $this->montarPayloadAtualizacao($movimentacao, $data);
        $movimentacao->update($payload);

        if ($movimentacao->isFinalizada()) {
            $this->sincronizarCadastroColaborador($colaborador->fresh());
        }

        return $movimentacao->fresh();
    }

    public function finalizar(ColaboradorMovimentacao $movimentacao, array $data, ?int $userId = null): ColaboradorMovimentacao
    {
        abort_unless($movimentacao->isPendente(), 404, 'Esta movimentação já foi finalizada ou cancelada.');

        $updates = [
            'situacao' => ColaboradorMovimentacaoSituacao::FINALIZADA,
            'finalizada_em' => now(),
            'finalizada_por_user_id' => $userId,
        ];

        if (ColaboradorMovimentacaoSituacao::tipoPermitePendente((string) $movimentacao->tipo)) {
            $updates['data_fim'] = $data['data_fim'] ?? today()->toDateString();
        }

        $movimentacao->update($updates);

        $colaborador = $movimentacao->colaborador;
        if ($colaborador !== null) {
            $this->sincronizarCadastroColaborador($colaborador->fresh());
        }

        return $movimentacao->fresh();
    }

    public function cancelar(ColaboradorMovimentacao $movimentacao): ColaboradorMovimentacao
    {
        abort_unless($movimentacao->isPendente(), 404, 'Somente processos pendentes podem ser cancelados.');

        $movimentacao->update([
            'situacao' => ColaboradorMovimentacaoSituacao::CANCELADA,
            'finalizada_em' => now(),
        ]);

        $colaborador = $movimentacao->colaborador;
        if ($colaborador !== null) {
            $this->sincronizarCadastroColaborador($colaborador->fresh());
        }

        return $movimentacao->fresh();
    }

    public function sincronizarCadastroColaborador(Colaborador $colaborador): void
    {
        $hoje = today();

        $desligamento = ColaboradorMovimentacao::query()
            ->efetiva()
            ->where('colaborador_id', $colaborador->id)
            ->where('tipo', ColaboradorMovimentacaoTipos::DESLIGAMENTO)
            ->where('data_inicio', '<=', $hoje->toDateString())
            ->orderByDesc('data_inicio')
            ->orderByDesc('id')
            ->first();

        if ($desligamento !== null) {
            $colaborador->update([
                'status' => 'desligado',
                'data_demissao' => $desligamento->data_inicio,
            ]);

            return;
        }

        if ($colaborador->data_demissao !== null) {
            $colaborador->update(['data_demissao' => null]);
        }

        foreach ([
            ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            ColaboradorMovimentacaoTipos::PROMOCAO,
            ColaboradorMovimentacaoTipos::MUDANCA_FUNCAO,
        ] as $tipoCadastral) {
            $movimentacoes = ColaboradorMovimentacao::query()
                ->efetiva()
                ->where('colaborador_id', $colaborador->id)
                ->where('tipo', $tipoCadastral)
                ->where('data_inicio', '<=', $hoje->toDateString())
                ->orderBy('data_inicio')
                ->orderBy('id')
                ->get();

            foreach ($movimentacoes as $mov) {
                $this->aplicarNoCadastro($colaborador->fresh(), $mov);
            }
        }

        $colaborador->refresh();

        $afastadoVigente = ColaboradorMovimentacao::query()
            ->efetiva()
            ->where('colaborador_id', $colaborador->id)
            ->whereIn('tipo', [
                ColaboradorMovimentacaoTipos::FERIAS,
                ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS,
            ])
            ->whereDate('data_inicio', '<=', $hoje)
            ->where(function ($query) use ($hoje) {
                $query->whereNull('data_fim')
                    ->orWhereDate('data_fim', '>=', $hoje);
            })
            ->exists();

        if ($afastadoVigente) {
            $colaborador->update(['status' => 'afastado']);

            return;
        }

        if ($colaborador->status === 'afastado') {
            $colaborador->update(['status' => 'ativo']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function montarPayloadAtualizacao(ColaboradorMovimentacao $movimentacao, array $data): array
    {
        $data['tipo'] = $movimentacao->tipo;
        $colaborador = $movimentacao->colaborador;
        $payload = $this->montarPayload($colaborador, $data);

        unset($payload['colaborador_id'], $payload['tipo'], $payload['status_anterior']);

        $camposAnteriores = [
            'centro_custo_anterior',
            'tipo_contrato_anterior',
            'local_trabalho_anterior',
            'departamento_anterior',
            'cargo_anterior',
            'salario_anterior',
        ];

        foreach ($camposAnteriores as $campo) {
            if (filled($movimentacao->{$campo})) {
                $payload[$campo] = $movimentacao->{$campo};
            }
        }

        return $payload;
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function metadadosSituacaoInicial(array $payload): array
    {
        if (! empty($payload['manter_pendente'])) {
            return ['situacao' => ColaboradorMovimentacaoSituacao::PENDENTE];
        }

        $tipo = (string) ($payload['tipo'] ?? '');

        if ($tipo === ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS) {
            return ['situacao' => ColaboradorMovimentacaoSituacao::PENDENTE];
        }

        if (ColaboradorMovimentacaoSituacao::tipoFinalizaAoRegistrar($tipo)) {
            return [
                'situacao' => ColaboradorMovimentacaoSituacao::FINALIZADA,
                'finalizada_em' => now(),
            ];
        }

        if ($tipo === ColaboradorMovimentacaoTipos::FERIAS && filled($payload['data_fim'] ?? null)) {
            return [
                'situacao' => ColaboradorMovimentacaoSituacao::FINALIZADA,
                'finalizada_em' => now(),
            ];
        }

        return ['situacao' => ColaboradorMovimentacaoSituacao::PENDENTE];
    }
}
