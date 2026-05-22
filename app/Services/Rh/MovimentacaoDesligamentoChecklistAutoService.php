<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoChecklistItem;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Support\Rh\MovimentacaoChamadoTipo;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;

/**
 * Marca itens de checklist como concluídos quando os dados/anexos do chamado já atendem o requisito.
 */
final class MovimentacaoDesligamentoChecklistAutoService
{
    public function __construct(
        private readonly MovimentacaoDesligamentoRules $desligamentoRules,
    ) {}

    public function sincronizar(RhMovimentacaoChamado $chamado, ?int $usuarioId = null): void
    {
        if (! $chamado->isAberto() || $chamado->tipo !== MovimentacaoChamadoTipo::DESLIGAMENTO) {
            return;
        }

        $chamado->loadMissing(['etapas.checklistItens', 'colaborador', 'anexos', 'nadaConsta.itens']);

        foreach ($chamado->etapas as $etapa) {
            foreach ($etapa->checklistItens as $item) {
                if ($item->status === 'concluido') {
                    continue;
                }

                if ($this->deveConcluir($item, $etapa, $chamado)) {
                    $item->update([
                        'status' => 'concluido',
                        'concluido_em' => now(),
                        'concluido_por_id' => $usuarioId,
                    ]);
                }
            }
        }
    }

    private function deveConcluir(
        RhMovimentacaoChecklistItem $item,
        RhMovimentacaoEtapa $etapa,
        RhMovimentacaoChamado $chamado,
    ): bool {
        return match ($etapa->slug) {
            'solicitacao' => $this->itemSolicitacao($item->slug, $chamado),
            'cadastro_sigo' => $this->itemCadastroSigo($item->slug, $chamado),
            'nada_consta' => $this->itemNadaConsta($item->slug, $chamado),
            'dp_folha' => $this->itemDpFolha($item->slug, $chamado),
            default => false,
        };
    }

    private function itemSolicitacao(string $slug, RhMovimentacaoChamado $chamado): bool
    {
        $d = $chamado->dados_depois_json ?? [];

        return match ($slug) {
            'dados-do-colaborador-contrato-e-funcao-conferidos' => $chamado->colaborador_id !== null
                && $chamado->relationLoaded('colaborador')
                && $chamado->colaborador !== null,
            'data-prevista-e-ultimo-dia-trabalhado-informados' => filled($chamado->data_prevista)
                && filled($d['ultimo_dia_trabalhado'] ?? null),
            'tipo-de-rescisao-e-motivo-registrados' => filled($d['tipo_rescisao'] ?? null)
                && filled($d['motivo_texto'] ?? null),
            'gestor-e-substituicao-de-vaga-informados' => filled($d['gestor_responsavel'] ?? null)
                && in_array($d['havera_substituicao_vaga'] ?? null, ['sim', 'nao'], true),
            default => false,
        };
    }

    private function itemCadastroSigo(string $slug, RhMovimentacaoChamado $chamado): bool
    {
        $temPacote = MovimentacaoDesligamentoCatalog::chamadoTemPacoteDocumentos($chamado);
        $tipos = $chamado->anexos->pluck('tipo_documento')->all();

        return match ($slug) {
            'desligamento-cadastrado-no-sigo' => $this->desligamentoRules->sigoCompleto($chamado),
            'folha-de-ponto-anexada' => $temPacote
                || in_array(MovimentacaoDesligamentoCatalog::ANEXO_FOLHA_PONTO, $tipos, true),
            'documento-do-desligamento-anexado' => $temPacote
                || in_array(MovimentacaoDesligamentoCatalog::ANEXO_DOCUMENTO_DESLIGAMENTO, $tipos, true),
            'anexos-obrigatorios-por-tipo-de-rescisao-conferidos' => $this->desligamentoRules->anexosObrigatoriosCompletos($chamado),
            default => false,
        };
    }

    private function itemNadaConsta(string $slug, RhMovimentacaoChamado $chamado): bool
    {
        $nada = $chamado->nadaConsta;
        if ($nada === null) {
            return false;
        }

        $nada->loadMissing('itens');
        $temPacote = MovimentacaoDesligamentoCatalog::chamadoTemPacoteDocumentos($chamado);
        $temAnexoNada = $chamado->anexos->contains(
            fn ($a) => in_array($a->tipo_documento, [
                MovimentacaoDesligamentoCatalog::ANEXO_NADA_CONSTA_ASSINADO,
                MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS,
            ], true)
        );

        return match ($slug) {
            'checklist-por-area-preenchido' => $temPacote
                || $nada->itens->every(fn ($i) => $i->tem_debito !== null),
            'pendencias-tratadas-ou-autorizadas' => $temPacote
                || ! $nada->itens->contains(fn ($i) => $i->pendenciaAberta()),
            'nada-consta-assinado-anexado' => $temAnexoNada,
            'validacao-final-do-rh' => (bool) $nada->validado_rh,
            default => false,
        };
    }

    private function itemDpFolha(string $slug, RhMovimentacaoChamado $chamado): bool
    {
        $temPacote = MovimentacaoDesligamentoCatalog::chamadoTemPacoteDocumentos($chamado);
        $tipos = $chamado->anexos->pluck('tipo_documento')->all();
        $nada = $chamado->nadaConsta;

        return match ($slug) {
            'desligamento-cadastrado-no-sigo' => $this->desligamentoRules->sigoCompleto($chamado),
            'folha-de-ponto-anexada' => $temPacote
                || in_array(MovimentacaoDesligamentoCatalog::ANEXO_FOLHA_PONTO, $tipos, true),
            'documento-de-desligamento-anexado' => $temPacote
                || in_array(MovimentacaoDesligamentoCatalog::ANEXO_DOCUMENTO_DESLIGAMENTO, $tipos, true),
            'nada-consta-anexado' => $temPacote
                || in_array(MovimentacaoDesligamentoCatalog::ANEXO_NADA_CONSTA_ASSINADO, $tipos, true),
            'pendencias-do-nada-consta-tratadas' => $nada !== null
                && $this->desligamentoRules->pendenciasNadaConsta($chamado) === [],
            default => false,
        };
    }
}
