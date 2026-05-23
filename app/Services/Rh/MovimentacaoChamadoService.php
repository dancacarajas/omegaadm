<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoChecklistItem;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Support\Rh\ColaboradorSnapshot;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use App\Support\Rh\MovimentacaoEtapaStatus;
use App\Support\Rh\MovimentacaoWorkflowCatalog;
use Illuminate\Support\Facades\DB;

final class MovimentacaoChamadoService
{
    public function __construct(
        private readonly MovimentacaoWorkflowService $workflowService,
        private readonly MovimentacaoLogService $logService,
        private readonly MovimentacaoNadaConstaService $nadaConstaService,
        private readonly MovimentacaoDesligamentoRules $desligamentoRules,
        private readonly MovimentacaoSubstituicaoVagaService $substituicaoVagaService,
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
                'chamado_origem_id' => $dadosDepois['chamado_origem_id'] ?? null,
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
                'dados_depois_json' => $this->sanitizarDadosDepois($dadosDepois),
            ]);

            $this->workflowService->instanciarEtapas($chamado, $tipo);

            if ($tipo === MovimentacaoChamadoTipo::DESLIGAMENTO) {
                $this->nadaConstaService->inicializar($chamado);
                $this->substituicaoVagaService->criarSeSolicitado($chamado->fresh(), $colaborador);
                $chamado = $chamado->fresh(['etapas.checklistItens', 'colaborador', 'anexos', 'nadaConsta.itens']);
                app(MovimentacaoDesligamentoChecklistAutoService::class)->sincronizar($chamado, $solicitanteId);
                app(MovimentacaoDesligamentoAutoProgressaoService::class)->sincronizar($chamado, $solicitanteId);
            }

            $this->logService->registrar($chamado, 'chamado_aberto', null, null, ['tipo' => $tipo], $solicitanteId);

            return $chamado->fresh(['etapas.checklistItens', 'etapaAtual', 'colaborador', 'nadaConsta.itens']);
        });
    }

    public function alternarChecklist(RhMovimentacaoChecklistItem $item, ?int $usuarioId = null): void
    {
        $concluido = $item->status === 'concluido';
        $item->update([
            'status' => $concluido ? 'pendente' : 'concluido',
            'concluido_em' => $concluido ? null : now(),
            'concluido_por_id' => $concluido ? null : $usuarioId,
        ]);
    }

    /**
     * @return list<RhMovimentacaoChecklistItem>
     */
    public function concluirTodosChecklistDaEtapa(RhMovimentacaoEtapa $etapa, ?int $usuarioId = null): array
    {
        $etapa->loadMissing('checklistItens');
        $atualizados = [];

        foreach ($etapa->checklistItens as $item) {
            if ($item->status === 'concluido') {
                continue;
            }

            $item->update([
                'status' => 'concluido',
                'concluido_em' => now(),
                'concluido_por_id' => $usuarioId,
            ]);
            $atualizados[] = $item->fresh();
        }

        return $atualizados;
    }

    public function concluirEtapa(RhMovimentacaoEtapa $etapa, ?string $observacao, ?int $usuarioId = null): void
    {
        $chamado = $etapa->chamado()->with(['anexos', 'nadaConsta.itens', 'etapas.checklistItens'])->firstOrFail();

        if ($chamado->tipo === MovimentacaoChamadoTipo::DESLIGAMENTO) {
            $bloqueios = $this->desligamentoRules->pendenciasConcluirEtapa($etapa, $chamado);
            abort_if($bloqueios !== [], 422, implode(' ', $bloqueios));
        }

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

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function sanitizarDadosDepois(array $dados): array
    {
        foreach ([
            'atestado_medico',
            'relatorio_medico',
            'declaracao_comparecimento',
            'colaborador_id',
            'tipo',
        ] as $chave) {
            unset($dados[$chave]);
        }

        return $dados;
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
