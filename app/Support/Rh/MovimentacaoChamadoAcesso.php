<?php

namespace App\Support\Rh;

use App\Models\User;

/**
 * Controle de acesso ao módulo de chamados (LGPD / áreas do Nada Consta).
 */
final class MovimentacaoChamadoAcesso
{
    public const MODULO = 'rh';

    public function podeEditarChamado(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->podeAcaoNoModulo(self::MODULO, 'chamados_movimentacao_editar')
            || $user->podeAcaoNoModulo(self::MODULO, 'editar');
    }

    public function podeValidarNadaConstaRh(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->podeAcaoNoModulo(self::MODULO, 'chamados_movimentacao_validar_rh')
            || $user->podeAcaoNoModulo(self::MODULO, 'editar');
    }

    public function podeEditarAreaNadaConsta(?User $user, string $area): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->podeValidarNadaConstaRh($user)) {
            return true;
        }

        $chave = MovimentacaoDesligamentoCatalog::permissaoArea($area);

        return $user->podeAcaoNoModulo(self::MODULO, $chave);
    }

    /** @return list<string> */
    public function areasEditaveis(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        if ($this->podeValidarNadaConstaRh($user)) {
            return array_keys(MovimentacaoDesligamentoCatalog::labelsAreas());
        }

        return array_values(array_filter(
            array_keys(MovimentacaoDesligamentoCatalog::labelsAreas()),
            fn (string $area) => $this->podeEditarAreaNadaConsta($user, $area)
        ));
    }
}
