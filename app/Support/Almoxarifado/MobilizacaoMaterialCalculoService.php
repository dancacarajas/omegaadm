<?php

namespace App\Support\Almoxarifado;

use App\Models\Almoxarifado\MobilizacaoMaterial;
use Carbon\Carbon;

final class MobilizacaoMaterialCalculoService
{
    /** @var list<string> */
    public const CAMPOS_HISTORICO = [
        'disciplina',
        'categoria_descricao',
        'situacao_tratativa',
        'situacao_sigo_descricao',
        'status',
        'quantidade_necessaria',
        'quantidade_pedida_sigo',
        'quantidade_em_compra',
        'quantidade_recebida',
        'numero_pm',
        'numero_oc',
        'fornecedor',
        'comprador_responsavel',
        'previsao_entrega',
        'prioridade',
        'observacao_almoxarife',
        'observacao_gestao',
    ];

    public function recalcular(MobilizacaoMaterial $material, bool $forcarStatusAutomatico = true): MobilizacaoMaterial
    {
        $necessaria = max(0, (float) $material->quantidade_necessaria);
        $emCompra = max(0, (float) $material->quantidade_em_compra);
        $recebida = max(0, (float) $material->quantidade_recebida);

        $material->saldo_a_comprar = max(0, round($necessaria - $emCompra - $recebida, 2));
        $material->saldo_a_receber = max(0, round($emCompra - $recebida, 2));

        if ($forcarStatusAutomatico && $material->status !== MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO) {
            $material->status = $this->inferirStatus($material);
        }

        $material->acao_do_dia = $this->acaoDoDia($material);

        if ($material->status === MobilizacaoMaterialStatus::RECEBIDO_TOTAL && ! $material->data_recebimento_total) {
            $material->data_recebimento_total = now()->toDateString();
        }

        return $material;
    }

    public function inferirStatus(MobilizacaoMaterial $material): string
    {
        $necessaria = (float) $material->quantidade_necessaria;
        $emCompra = (float) $material->quantidade_em_compra;
        $recebida = (float) $material->quantidade_recebida;

        if ($necessaria > 0 && $recebida >= $necessaria) {
            return MobilizacaoMaterialStatus::RECEBIDO_TOTAL;
        }

        if ($recebida > 0 && ($necessaria <= 0 || $recebida < $necessaria)) {
            return MobilizacaoMaterialStatus::RECEBIDO_PARCIAL;
        }

        if ($emCompra > 0 && ($necessaria <= 0 || $emCompra < $necessaria)) {
            return MobilizacaoMaterialStatus::COMPRA_PARCIAL;
        }

        if ($necessaria > 0 && $emCompra >= $necessaria) {
            return MobilizacaoMaterialStatus::EM_COMPRAS;
        }

        if (filled($material->numero_pm) && blank($material->numero_oc) && $emCompra <= 0) {
            return MobilizacaoMaterialStatus::PEDIDO_NO_SIGO;
        }

        return MobilizacaoMaterialStatus::SEM_TRATATIVA;
    }

    public function acaoDoDia(MobilizacaoMaterial $material): string
    {
        if ($material->status === MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO) {
            return 'Sem ação.';
        }

        if ($this->estaAtrasado($material)) {
            return 'Atrasado. Cobrar Compras.';
        }

        return match ($material->status) {
            MobilizacaoMaterialStatus::SEM_TRATATIVA => 'Validar se precisa pedir.',
            MobilizacaoMaterialStatus::PEDIDO_NO_SIGO => 'Cobrar Compras pela PM.',
            MobilizacaoMaterialStatus::EM_COMPRAS => 'Acompanhar OC e previsão de entrega.',
            MobilizacaoMaterialStatus::COMPRA_PARCIAL => 'Cobrar complemento da compra.',
            MobilizacaoMaterialStatus::RECEBIDO_PARCIAL => 'Acompanhar saldo pendente.',
            MobilizacaoMaterialStatus::RECEBIDO_TOTAL => 'Finalizado.',
            default => 'Acompanhar item.',
        };
    }

    public function estaAtrasado(MobilizacaoMaterial $material): bool
    {
        if ($material->status === MobilizacaoMaterialStatus::RECEBIDO_TOTAL
            || $material->status === MobilizacaoMaterialStatus::CANCELADO_NAO_NECESSARIO) {
            return false;
        }

        if (! $material->previsao_entrega) {
            return false;
        }

        return Carbon::parse($material->previsao_entrega)->startOfDay()->lt(now()->startOfDay());
    }

    public function semPrevisaoEmCompras(MobilizacaoMaterial $material): bool
    {
        return $material->status === MobilizacaoMaterialStatus::EM_COMPRAS
            && ! $material->previsao_entrega;
    }

    public function divergenciaQuantidade(MobilizacaoMaterial $material): bool
    {
        $necessaria = (float) $material->quantidade_necessaria;

        return $necessaria > 0 && (float) $material->quantidade_recebida > $necessaria;
    }
}
