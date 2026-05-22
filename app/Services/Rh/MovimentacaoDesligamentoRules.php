<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Models\Rh\RhMovimentacaoNadaConstaItem;
use App\Support\Rh\MovimentacaoChamadoTipo;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;

final class MovimentacaoDesligamentoRules
{
    /**
     * @return list<string>
     */
    public function pendenciasFinalizacao(RhMovimentacaoChamado $chamado): array
    {
        $pendencias = [];
        $chamado->loadMissing(['etapas', 'anexos', 'nadaConsta.itens']);

        foreach ($chamado->etapas as $etapa) {
            if (! $etapa->obrigatoria || ! $etapa->bloqueia_finalizacao) {
                continue;
            }
            if (! $etapa->isConcluida()) {
                $pendencias[] = "Etapa pendente: {$etapa->nome}";
            }
        }

        $pendencias = array_merge($pendencias, $this->pendenciasAnexos($chamado));
        $pendencias = array_merge($pendencias, $this->pendenciasSigo($chamado));
        $pendencias = array_merge($pendencias, $this->pendenciasNadaConsta($chamado));

        return $pendencias;
    }

    /**
     * @return list<string>
     */
    public function pendenciasConcluirEtapa(RhMovimentacaoEtapa $etapa, RhMovimentacaoChamado $chamado): array
    {
        if ($chamado->tipo !== MovimentacaoChamadoTipo::DESLIGAMENTO) {
            return [];
        }

        $chamado->loadMissing(['anexos', 'nadaConsta.itens']);

        return match ($etapa->slug) {
            'solicitacao' => $this->pendenciasSolicitacao($chamado),
            'cadastro_sigo' => array_merge(
                $this->pendenciasSigo($chamado),
                $this->pendenciasAnexos($chamado)
            ),
            'nada_consta' => $this->pendenciasNadaConsta($chamado),
            'dp_folha' => array_merge(
                $this->pendenciasNadaConsta($chamado),
                $this->pendenciasDpFolha($chamado),
                $this->pendenciasChecklistEtapa($etapa)
            ),
            'finalizacao' => $this->pendenciasFinalizacao($chamado),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function pendenciasChecklistEtapa(RhMovimentacaoEtapa $etapa): array
    {
        $pendencias = [];
        $etapa->loadMissing('checklistItens');

        foreach ($etapa->checklistItens as $item) {
            if ($item->obrigatorio && $item->status !== 'concluido') {
                $pendencias[] = "Checklist: marque \"{$item->nome}\" como concluído.";
            }
        }

        return $pendencias;
    }

    /**
     * @return list<string>
     */
    private function pendenciasSolicitacao(RhMovimentacaoChamado $chamado): array
    {
        $d = $chamado->dados_depois_json ?? [];
        $pendencias = [];

        foreach ([
            'ultimo_dia_trabalhado' => 'Último dia trabalhado',
            'tipo_rescisao' => 'Tipo de rescisão',
            'motivo_texto' => 'Motivo',
            'gestor_responsavel' => 'Gestor responsável',
        ] as $campo => $label) {
            if (blank($d[$campo] ?? null)) {
                $pendencias[] = "Solicitação: informe {$label}.";
            }
        }

        return $pendencias;
    }

    /**
     * @return list<string>
     */
    private function pendenciasSigo(RhMovimentacaoChamado $chamado): array
    {
        $sigo = $chamado->dados_depois_json['sigo'] ?? [];
        $pendencias = [];

        $cadastrado = $sigo['cadastrado'] ?? false;
        if (! filter_var($cadastrado, FILTER_VALIDATE_BOOLEAN)) {
            $pendencias[] = 'Cadastre o desligamento no SIGO (marque como cadastrado).';
        }
        if (blank($sigo['data_cadastro'] ?? null)) {
            $pendencias[] = 'Informe a data do cadastro no SIGO.';
        }
        if (blank($sigo['responsavel_cadastro'] ?? null)) {
            $pendencias[] = 'Informe o responsável pelo cadastro no SIGO.';
        }

        return $pendencias;
    }

    /**
     * @return list<string>
     */
    private function pendenciasAnexos(RhMovimentacaoChamado $chamado): array
    {
        $tipoRescisao = (string) ($chamado->dados_depois_json['tipo_rescisao'] ?? '');
        $obrigatorios = MovimentacaoDesligamentoCatalog::anexosObrigatoriosPorTipoRescisao($tipoRescisao);
        $tiposPresentes = $chamado->anexos->pluck('tipo_documento')->all();
        $pendencias = [];

        foreach ($obrigatorios as $tipo) {
            if (! in_array($tipo, $tiposPresentes, true)) {
                $label = MovimentacaoDesligamentoCatalog::labelsAnexos()[$tipo] ?? $tipo;
                $pendencias[] = "Anexo obrigatório ausente: {$label}.";
            }
        }

        return $pendencias;
    }

    /**
     * @return list<string>
     */
    public function pendenciasNadaConsta(RhMovimentacaoChamado $chamado, bool $paraAcaoValidarRh = false): array
    {
        $pendencias = [];
        $nada = $chamado->nadaConsta;

        if ($nada === null) {
            return ['Nada Consta Demissional não iniciado.'];
        }

        $nada->loadMissing('itens');

        $temNadaConstaAnexo = $chamado->anexos->contains(
            fn ($a) => in_array($a->tipo_documento, [
                MovimentacaoDesligamentoCatalog::ANEXO_NADA_CONSTA_ASSINADO,
                MovimentacaoDesligamentoCatalog::ANEXO_PACOTE_DOCUMENTOS,
            ], true)
        );
        if (! $temNadaConstaAnexo) {
            $pendencias[] = 'Anexe o pacote único de documentos (inclui o Nada Consta assinado) ou o Nada Consta separadamente.';

            return $pendencias;
        }

        if (MovimentacaoDesligamentoCatalog::chamadoTemPacoteDocumentos($chamado)) {
            if (! $paraAcaoValidarRh && ! $nada->validado_rh) {
                $pendencias[] = 'Valide o Nada Consta pelo RH (botão na seção Nada Consta).';
            }

            return $pendencias;
        }

        foreach ($nada->itens as $item) {
            if ($item->tem_debito === null) {
                $area = MovimentacaoDesligamentoCatalog::labelArea($item->area);
                $pendencias[] = "Nada Consta: conferir item \"{$item->item}\" em {$area}.";
                continue;
            }

            if ($item->pendenciaAberta()) {
                $pendencias[] = "Nada Consta: pendência aberta em {$item->area}/{$item->item}.";
            }

            if ($item->tem_debito && blank($item->descricao_pendencia)) {
                $pendencias[] = "Nada Consta: descreva a pendência em {$item->area}/{$item->item}.";
            }

            if ($item->tem_debito) {
                $pendencias = array_merge($pendencias, $this->pendenciasAnexosItem($item, $chamado));
            }
        }

        if (! $paraAcaoValidarRh && ! $nada->validado_rh) {
            $pendencias[] = 'Nada Consta aguarda validação final do RH.';
        }

        return $pendencias;
    }

    /**
     * @return list<string>
     */
    private function pendenciasDpFolha(RhMovimentacaoChamado $chamado): array
    {
        $pendencias = [];
        foreach ($this->pendenciasSigo($chamado) as $p) {
            $pendencias[] = 'DP/Folha: '.$p;
        }
        foreach ($this->pendenciasAnexos($chamado) as $p) {
            $pendencias[] = 'DP/Folha: '.$p;
        }
        foreach ($this->pendenciasNadaConsta($chamado) as $p) {
            $pendencias[] = 'DP/Folha: '.$p;
        }

        return $pendencias;
    }

    public function sigoCompleto(RhMovimentacaoChamado $chamado): bool
    {
        return $this->pendenciasSigo($chamado) === [];
    }

    public function anexosObrigatoriosCompletos(RhMovimentacaoChamado $chamado): bool
    {
        $chamado->loadMissing('anexos');

        return $this->pendenciasAnexos($chamado) === [];
    }

    public function nadaConstaCompleto(RhMovimentacaoChamado $chamado): bool
    {
        return $this->pendenciasNadaConsta($chamado) === [];
    }

    /**
     * @return list<string>
     */
    private function pendenciasAnexosItem(RhMovimentacaoNadaConstaItem $item, RhMovimentacaoChamado $chamado): array
    {
        if (MovimentacaoDesligamentoCatalog::chamadoTemPacoteDocumentos($chamado)) {
            return [];
        }

        $pendencias = [];
        $area = MovimentacaoDesligamentoCatalog::labelArea($item->area);

        if ($item->status_tratativa === MovimentacaoDesligamentoCatalog::TRATATIVA_REGULARIZADO
            && $item->anexo_termo_baixa_id === null
            && $item->anexo_evidencia_id === null) {
            $pendencias[] = "Nada Consta ({$area}): anexe evidência ou termo de baixa para o item regularizado.";
        }

        if ($item->status_tratativa === MovimentacaoDesligamentoCatalog::TRATATIVA_AUTORIZADO_DESCONTO
            && $item->anexo_autorizacao_desconto_id === null) {
            $pendencias[] = "Nada Consta ({$area}): anexe autorização de desconto.";
        }

        if ($item->status_tratativa === MovimentacaoDesligamentoCatalog::TRATATIVA_PENDENTE
            && $item->anexo_evidencia_id === null) {
            $pendencias[] = "Nada Consta ({$area}): anexe evidência da pendência em aberto.";
        }

        return $pendencias;
    }
}
