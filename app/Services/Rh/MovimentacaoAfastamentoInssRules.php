<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoEtapa;
use App\Support\Rh\MovimentacaoAfastamentoInssCatalog;
use App\Support\Rh\MovimentacaoChamadoTipo;
use App\Support\Rh\MovimentacaoWorkflowCatalog;

final class MovimentacaoAfastamentoInssRules
{
    /**
     * Pendências extras para finalização do chamado de afastamento INSS.
     *
     * @return list<string>
     */
    public function pendenciasFinalizacao(RhMovimentacaoChamado $chamado): array
    {
        $pendencias = [];
        $dados = $chamado->dados_depois_json ?? [];

        if (blank($dados['classificacao'] ?? null)) {
            $pendencias[] = 'Classificação do afastamento não registrada (etapa 3).';
        }

        if (blank($dados['resultado_final'] ?? null)) {
            $pendencias[] = 'Defina o resultado do processo antes de finalizar.';
        }

        $chamado->loadMissing(['etapas', 'anexos']);

        $temAtestado = $chamado->anexos->contains(
            fn ($a) => $a->tipo_documento === MovimentacaoAfastamentoInssCatalog::ANEXO_ATESTADO
        );
        if (! $temAtestado) {
            $pendencias[] = 'Anexo obrigatório: atestado médico.';
        }

        foreach ($chamado->etapas as $etapa) {
            if (! $this->etapaObrigatoria($chamado, $etapa)) {
                continue;
            }
            if (! $etapa->obrigatoria || ! $etapa->bloqueia_finalizacao) {
                continue;
            }
            if (! $etapa->isConcluida()) {
                $pendencias[] = "Etapa pendente: {$etapa->nome}";
            }
        }

        if ($this->exigeAnexoAso($dados)) {
            $temAso = $chamado->anexos->contains(
                fn ($a) => $a->tipo_documento === MovimentacaoAfastamentoInssCatalog::ANEXO_ASO_RETORNO
            );
            if (! $temAso) {
                $pendencias[] = 'ASO de retorno obrigatório não anexado (afastamento ≥ 30 dias ou retorno ao trabalho).';
            }
        }

        return $pendencias;
    }

    public function etapaObrigatoria(RhMovimentacaoChamado $chamado, RhMovimentacaoEtapa $etapa): bool
    {
        $def = $this->definicaoEtapa($chamado->tipo, $etapa->slug);
        $condicional = $def['condicional'] ?? null;
        if ($condicional === null) {
            return true;
        }

        $dados = $chamado->dados_depois_json ?? [];

        return match ($condicional) {
            'tst' => MovimentacaoAfastamentoInssCatalog::exigeEtapaTst($dados),
            'inss' => MovimentacaoAfastamentoInssCatalog::exigeEtapaInss($dados),
            'retorno' => $this->exigeEtapaRetorno($dados),
            'aso_retorno' => MovimentacaoAfastamentoInssCatalog::exigeAsoRetorno($dados),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function exigeEtapaRetorno(array $dados): bool
    {
        $resultado = (string) ($dados['resultado_final'] ?? '');
        if (in_array($resultado, [
            MovimentacaoAfastamentoInssCatalog::RESULTADO_RETORNO,
            MovimentacaoAfastamentoInssCatalog::RESULTADO_ALTA_MEDICA,
        ], true)) {
            return true;
        }

        return ($dados['classificacao'] ?? '') === MovimentacaoAfastamentoInssCatalog::CLASS_RETORNO;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function exigeAnexoAso(array $dados): bool
    {
        if (! MovimentacaoAfastamentoInssCatalog::exigeAsoRetorno($dados)) {
            return false;
        }

        $aso = $dados['aso_retorno'] ?? [];
        if (($aso['resultado'] ?? '') === 'inapto') {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function definicaoEtapa(string $tipo, string $slug): ?array
    {
        $workflow = MovimentacaoWorkflowCatalog::paraTipo($tipo);
        if ($workflow === null) {
            return null;
        }

        foreach ($workflow['etapas'] as $def) {
            if (($def['slug'] ?? null) === $slug) {
                return $def;
            }
        }

        return null;
    }

    /**
     * Monta payload para colaborador_movimentacoes na finalização.
     *
     * @param  array<string, mixed>  $depois
     * @return array<string, mixed>
     */
    public function payloadMovimentacaoLegada(RhMovimentacaoChamado $chamado, array $depois): array
    {
        $resultado = (string) ($depois['resultado_final'] ?? MovimentacaoAfastamentoInssCatalog::RESULTADO_AFASTAMENTO_ATIVO);
        $dataInicio = $depois['data_inicio_afastamento'] ?? $depois['data_efetiva'] ?? $chamado->data_efetiva?->format('Y-m-d');
        $dataFim = $depois['data_final_atestado'] ?? $depois['data_fim'] ?? null;

        if (in_array($resultado, [
            MovimentacaoAfastamentoInssCatalog::RESULTADO_RETORNO,
            MovimentacaoAfastamentoInssCatalog::RESULTADO_ALTA_MEDICA,
        ], true)) {
            $dataFim = $depois['data_retorno_real'] ?? $depois['data_retorno_prevista'] ?? $dataFim ?? today()->toDateString();
        }

        $payload = [
            'tipo' => MovimentacaoChamadoTipo::tipoMovimentacaoLegado(MovimentacaoChamadoTipo::AFASTAMENTO_INSS),
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'especie_beneficio_inss' => MovimentacaoAfastamentoInssCatalog::mapearEspecieInss($depois),
            'motivo_texto' => $depois['motivo_texto'] ?? $chamado->motivo,
            'observacoes' => $depois['observacoes'] ?? $chamado->observacao,
            'resultado_chamado' => $resultado,
        ];

        if (filled($depois['cid'] ?? null)) {
            $payload['cid'] = $depois['cid'];
        }

        if ($resultado !== MovimentacaoAfastamentoInssCatalog::RESULTADO_AFASTAMENTO_ATIVO) {
            $payload['forcar_finalizada'] = true;
        }

        return $payload;
    }
}
