<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use Illuminate\Support\Facades\DB;

final class MovimentacaoFinalizacaoService
{
    public function __construct(
        private readonly MovimentacaoWorkflowService $workflowService,
        private readonly MovimentacaoLogService $logService,
        private readonly ColaboradorMovimentacaoService $movimentacaoLegadaService,
    ) {}

    public function finalizar(RhMovimentacaoChamado $chamado, ?int $usuarioId = null): RhMovimentacaoChamado
    {
        abort_unless($chamado->isAberto(), 422, 'Chamado já encerrado.');

        $pendencias = $this->workflowService->podeFinalizar($chamado);
        abort_if($pendencias !== [], 422, 'Pendências: '.implode(' | ', $pendencias));

        return DB::transaction(function () use ($chamado, $usuarioId) {
            $colaborador = $chamado->colaborador;
            abort_if($colaborador === null, 404);

            $payload = $this->montarPayloadMovimentacao($chamado);
            $mov = $this->movimentacaoLegadaService->registrar($colaborador, $payload, $usuarioId);

            $chamado->update([
                'status' => MovimentacaoChamadoStatus::CONCLUIDO,
                'finalizado_em' => now(),
                'finalizado_por_id' => $usuarioId,
                'colaborador_movimentacao_id' => $mov->id,
                'etapa_atual_id' => null,
            ]);

            $this->logService->registrar($chamado, 'chamado_finalizado', null, null, ['movimentacao_id' => $mov->id], $usuarioId);

            return $chamado->fresh();
        });
    }

    /** @return array<string, mixed> */
    private function montarPayloadMovimentacao(RhMovimentacaoChamado $chamado): array
    {
        $depois = $chamado->dados_depois_json ?? [];
        $tipo = MovimentacaoChamadoTipo::tipoMovimentacaoLegado($chamado->tipo);

        $base = array_merge($depois, [
            'tipo' => $tipo,
            'data_inicio' => $depois['data_efetiva'] ?? $depois['data_inicio'] ?? $chamado->data_efetiva?->format('Y-m-d') ?? today()->toDateString(),
            'data_fim' => $depois['data_fim'] ?? null,
            'motivo_texto' => $depois['motivo_texto'] ?? $chamado->motivo,
            'observacoes' => $depois['observacoes'] ?? $chamado->observacao,
        ]);

        return $base;
    }
}
