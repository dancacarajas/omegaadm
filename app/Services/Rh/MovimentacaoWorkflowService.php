<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoChecklistItem;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoEtapaStatus;
use App\Support\Rh\MovimentacaoWorkflowCatalog;
use Illuminate\Support\Str;

final class MovimentacaoWorkflowService
{
    public function __construct(
        private readonly MovimentacaoLogService $logService,
    ) {}

    public function instanciarEtapas(RhMovimentacaoChamado $chamado, string $tipo): void
    {
        $workflow = MovimentacaoWorkflowCatalog::paraTipo($tipo);
        if ($workflow === null) {
            return;
        }

        $ordem = 1;
        $primeiraEtapa = null;

        foreach ($workflow['etapas'] as $def) {
            $prazo = isset($def['prazo_dias']) ? now()->addDays((int) $def['prazo_dias'])->toDateString() : null;

            $etapa = RhMovimentacaoEtapa::query()->create([
                'chamado_id' => $chamado->id,
                'ordem' => $ordem,
                'slug' => $def['slug'],
                'nome' => $def['nome'],
                'status' => $ordem === 1 ? MovimentacaoEtapaStatus::EM_ANDAMENTO : MovimentacaoEtapaStatus::PENDENTE,
                'obrigatoria' => $def['obrigatoria'] ?? true,
                'papel_responsavel' => $def['papel_responsavel'] ?? null,
                'prazo' => $prazo,
                'iniciado_em' => $ordem === 1 ? now() : null,
                'bloqueia_finalizacao' => $def['bloqueia_finalizacao'] ?? true,
            ]);

            if ($ordem === 1) {
                $primeiraEtapa = $etapa;
            }

            foreach ($def['checklists'] ?? [] as $itemNome) {
                RhMovimentacaoChecklistItem::query()->create([
                    'etapa_id' => $etapa->id,
                    'slug' => Str::slug($itemNome),
                    'nome' => $itemNome,
                    'status' => 'pendente',
                    'obrigatorio' => true,
                ]);
            }

            $ordem++;
        }

        if ($primeiraEtapa !== null) {
            $statusChamado = $workflow['etapas'][0]['status_chamado'] ?? $workflow['status_inicial'];
            $chamado->update([
                'etapa_atual_id' => $primeiraEtapa->id,
                'status' => $statusChamado ?? MovimentacaoChamadoStatus::ABERTO,
            ]);
        }
    }

    public function podeFinalizar(RhMovimentacaoChamado $chamado): array
    {
        $pendencias = [];

        foreach ($chamado->etapas as $etapa) {
            if (! $etapa->obrigatoria || ! $etapa->bloqueia_finalizacao) {
                continue;
            }
            if (! $etapa->isConcluida()) {
                $pendencias[] = "Etapa pendente: {$etapa->nome}";
            }
            foreach ($etapa->checklistItens as $item) {
                if ($item->obrigatorio && ! in_array($item->status, ['ok', 'nao_aplica'], true)) {
                    $pendencias[] = "Checklist: {$etapa->nome} → {$item->nome}";
                }
            }
        }

        return $pendencias;
    }

    public function avancarEtapaAtual(RhMovimentacaoChamado $chamado): void
    {
        $chamado->load('etapas');
        $proxima = $chamado->etapas->first(fn ($e) => ! $e->isConcluida());

        if ($proxima === null) {
            $chamado->update([
                'etapa_atual_id' => null,
                'status' => MovimentacaoChamadoStatus::EM_EXECUCAO,
            ]);

            return;
        }

        if ($proxima->status === MovimentacaoEtapaStatus::PENDENTE) {
            $proxima->update([
                'status' => MovimentacaoEtapaStatus::EM_ANDAMENTO,
                'iniciado_em' => now(),
            ]);
        }

        $chamado->update(['etapa_atual_id' => $proxima->id]);
    }
}
