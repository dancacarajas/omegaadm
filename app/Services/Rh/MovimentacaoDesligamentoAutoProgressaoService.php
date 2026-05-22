<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoChamado;
use App\Support\Rh\MovimentacaoChamadoTipo;

/**
 * Conclui etapas do desligamento automaticamente quando os requisitos da etapa já foram atendidos.
 */
final class MovimentacaoDesligamentoAutoProgressaoService
{
    public function __construct(
        private readonly MovimentacaoDesligamentoRules $desligamentoRules,
        private readonly MovimentacaoChamadoService $chamadoService,
        private readonly MovimentacaoDesligamentoChecklistAutoService $checklistAuto,
    ) {}

    public function sincronizar(RhMovimentacaoChamado $chamado, ?int $usuarioId = null): void
    {
        if (! $chamado->isAberto() || $chamado->tipo !== MovimentacaoChamadoTipo::DESLIGAMENTO) {
            return;
        }

        $chamado->loadMissing(['etapas.checklistItens', 'colaborador', 'anexos', 'nadaConsta.itens']);
        $this->checklistAuto->sincronizar($chamado, $usuarioId);
        $chamado->loadMissing(['etapas.checklistItens', 'anexos', 'nadaConsta.itens']);

        foreach ($chamado->etapas->sortBy('ordem') as $etapa) {
            if ($etapa->isConcluida()) {
                continue;
            }

            $chamado->refresh();
            $chamado->loadMissing(['anexos', 'nadaConsta.itens']);

            if ($this->desligamentoRules->pendenciasConcluirEtapa($etapa, $chamado) !== []) {
                break;
            }

            $this->chamadoService->concluirEtapa($etapa, null, $usuarioId);
        }
    }
}
