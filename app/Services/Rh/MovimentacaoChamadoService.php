<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Support\Rh\ColaboradorSnapshot;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use App\Support\Rh\MovimentacaoEtapaStatus;
use App\Support\Rh\MovimentacaoWorkflowCatalog;
use Illuminate\Support\Facades\DB;

final class MovimentacaoChamadoService
{
    public function __construct(
        private readonly MovimentacaoWorkflowService $workflowService,
        private readonly MovimentacaoLogService $logService,
    ) {}

    /**
     * @param  array<string, mixed>  $dadosDepois
     */
    public function abrir(Colaborador $colaborador, string $tipo, array $dadosDepois, ?int $solicitanteId = null): RhMovimentacaoChamado
    {
        abort_unless(in_array($tipo, MovimentacaoChamadoTipo::todos(), true), 422, 'Tipo de chamado inválido.');
        abort_unless(MovimentacaoWorkflowCatalog::paraTipo($tipo) !== null, 422, 'Fluxo não configurado para este tipo.');

        return DB::transaction(function () use ($colaborador, $tipo, $dadosDepois, $solicitanteId) {
            $workflow = MovimentacaoWorkflowCatalog::paraTipo($tipo);

            $chamado = RhMovimentacaoChamado::query()->create([
                'protocolo' => $this->gerarProtocolo(),
                'colaborador_id' => $colaborador->id,
                'tipo' => $tipo,
                'status' => $workflow['status_inicial'] ?? MovimentacaoChamadoStatus::ABERTO,
                'solicitante_id' => $solicitanteId,
                'responsavel_atual_id' => $solicitanteId,
                'data_abertura' => today(),
                'data_prevista' => $dadosDepois['data_prevista'] ?? $dadosDepois['data_efetiva'] ?? $dadosDepois['data_inicio'] ?? null,
                'data_efetiva' => $dadosDepois['data_efetiva'] ?? $dadosDepois['data_inicio'] ?? null,
                'motivo' => $dadosDepois['motivo_texto'] ?? $dadosDepois['motivo'] ?? null,
                'observacao' => $dadosDepois['observacoes'] ?? $dadosDepois['observacao'] ?? null,
                'dados_antes_json' => ColaboradorSnapshot::capturar($colaborador),
                'dados_depois_json' => $dadosDepois,
            ]);

            $this->workflowService->instanciarEtapas($chamado, $tipo);
            $this->logService->registrar($chamado, 'chamado_aberto', null, null, ['tipo' => $tipo], $solicitanteId);

            return $chamado->fresh(['etapas.checklistItens', 'etapaAtual', 'colaborador']);
        });
    }

    public function concluirEtapa(RhMovimentacaoEtapa $etapa, ?string $observacao, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($etapa, $observacao, $usuarioId) {
            $etapa->update([
                'status' => MovimentacaoEtapaStatus::CONCLUIDA,
                'concluido_em' => now(),
                'concluido_por_id' => $usuarioId,
                'observacao' => $observacao ?? $etapa->observacao,
            ]);

            $chamado = $etapa->chamado;
            $this->logService->registrar($chamado, 'etapa_concluida', 'etapa', $etapa->slug, $etapa->nome, $usuarioId);
            $this->workflowService->avancarEtapaAtual($chamado->fresh());
        });
    }

    private function gerarProtocolo(): string
    {
        $ano = (int) date('Y');
        $sequencia = RhMovimentacaoChamado::query()
            ->where('protocolo', 'like', "MOV-RH-{$ano}-%")
            ->count() + 1;

        return sprintf('MOV-RH-%d-%04d', $ano, $sequencia);
    }
}
