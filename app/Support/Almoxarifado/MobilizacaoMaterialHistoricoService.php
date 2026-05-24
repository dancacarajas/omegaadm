<?php

namespace App\Support\Almoxarifado;

use App\Models\Almoxarifado\MobilizacaoMaterial;
use App\Models\Almoxarifado\MobilizacaoMaterialHistorico;
use App\Models\User;

final class MobilizacaoMaterialHistoricoService
{
    public function registrarAlteracoes(
        MobilizacaoMaterial $material,
        array $antes,
        ?User $usuario = null,
        ?string $observacao = null,
    ): void {
        $usuario ??= auth()->user();

        foreach (MobilizacaoMaterialCalculoService::CAMPOS_HISTORICO as $campo) {
            $valorAntes = $this->serializarValor($antes[$campo] ?? null);
            $valorNovo = $this->serializarValor($material->getAttribute($campo));

            if ($valorAntes === $valorNovo) {
                continue;
            }

            MobilizacaoMaterialHistorico::create([
                'mobilizacao_material_id' => $material->id,
                'usuario_id' => $usuario?->id,
                'campo_alterado' => $campo,
                'valor_anterior' => $valorAntes,
                'valor_novo' => $valorNovo,
                'observacao' => $observacao,
            ]);
        }
    }

    private function serializarValor(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
