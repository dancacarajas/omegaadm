<?php

namespace App\Support\Almoxarifado;

use App\Models\Almoxarifado\MobilizacaoMaterial;
use Illuminate\Support\Collection;

final class MobilizacaoCobrancaTextoService
{
    /**
     * @param  Collection<int, MobilizacaoMaterial>  $itens
     */
    public function gerar(Collection $itens): string
    {
        $itens->loadMissing('contrato');
        $contratos = $itens->pluck('contrato.numero')->filter()->unique()->values();
        $contratoTxt = $contratos->isEmpty()
            ? '[contrato]'
            : $contratos->implode(', ');

        $linhas = [
            "Prezados, gentileza verificar o andamento dos materiais abaixo relacionados ao contrato {$contratoTxt}. Os itens seguem pendentes de atualização, atendimento ou entrega conforme status informado no controle.",
            '',
        ];

        $statusLabels = MobilizacaoMaterialStatus::labels();

        foreach ($itens as $item) {
            $linhas[] = '---';
            $linhas[] = 'Material: '.($item->descricao_material ?: '-');
            if ($item->codigo_material) {
                $linhas[] = 'Código: '.$item->codigo_material;
            }
            $linhas[] = 'PM: '.($item->numero_pm ?: '-');
            $linhas[] = 'OC: '.($item->numero_oc ?: '-');
            $linhas[] = 'Qtd necessária: '.number_format((float) $item->quantidade_necessaria, 2, ',', '.');
            $linhas[] = 'Qtd em compra: '.number_format((float) $item->quantidade_em_compra, 2, ',', '.');
            $linhas[] = 'Qtd recebida: '.number_format((float) $item->quantidade_recebida, 2, ',', '.');
            $linhas[] = 'Previsão: '.($item->previsao_entrega?->format('d/m/Y') ?: 'Sem previsão');
            $linhas[] = 'Situação: '.($statusLabels[$item->status] ?? $item->status);
            $linhas[] = 'Pendência: '.($item->acao_do_dia ?: '-');
            $linhas[] = '';
        }

        return trim(implode("\n", $linhas));
    }
}
