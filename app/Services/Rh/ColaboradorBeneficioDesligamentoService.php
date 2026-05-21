<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class ColaboradorBeneficioDesligamentoService
{
    /**
     * Desativa direito e benefício ativo de todos os vínculos do colaborador desligado.
     */
    public function desativarTodos(Colaborador $colaborador, ?CarbonInterface $dataReferencia = null): int
    {
        $dataRef = $dataReferencia
            ?? ($colaborador->data_demissao ? Carbon::parse($colaborador->data_demissao) : today());

        $nota = sprintf(
            '[Auto %s] Benefícios desativados por desligamento do colaborador.',
            $dataRef->format('d/m/Y')
        );

        $desativados = 0;

        ColaboradorBeneficio::query()
            ->where('colaborador_id', $colaborador->id)
            ->where(function ($query) {
                $query->where('tem_direito', true)
                    ->orWhere('beneficio_ativo', true);
            })
            ->each(function (ColaboradorBeneficio $vinculo) use ($nota, &$desativados) {
                $observacoes = trim((string) $vinculo->observacoes);
                $vinculo->update([
                    'tem_direito' => false,
                    'beneficio_ativo' => false,
                    'observacoes' => $observacoes === '' ? $nota : $observacoes."\n".$nota,
                ]);
                $desativados++;
            });

        return $desativados;
    }
}
