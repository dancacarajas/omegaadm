<?php

namespace App\Support\Rh;

/**
 * WebCard: adiantamento salarial no cartão — direito de X% do salário (teto mensal configurável).
 * O extrato informa o valor de direito; renovação do saldo no dia 23.
 */
final class WebcardRegraConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function padroes(?int $ano = null): array
    {
        $ano ??= (int) date('Y');

        return [
            'ano_vigencia' => $ano,
            'percentual_limite_por_solicitacao' => 30.0,
            'limite_mensal' => 1500.0,
            'dia_renovacao_saldo' => 23,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    public static function resolver(?array $raw, ?int $ano = null): self
    {
        if ($raw === null || $raw === []) {
            return new self(self::padroes($ano));
        }

        return new self(self::mesclar(self::padroes($ano), $raw));
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function mesclar(array $base, array $raw): array
    {
        $out = $base;
        $out['ano_vigencia'] = (int) ($raw['ano_vigencia'] ?? $base['ano_vigencia']);
        $percentual = (float) ($raw['percentual_limite_por_solicitacao'] ?? $base['percentual_limite_por_solicitacao'] ?? 30);
        $out['percentual_limite_por_solicitacao'] = min(100, max(0.01, $percentual));
        $out['limite_mensal'] = max(0.01, (float) ($raw['limite_mensal'] ?? $base['limite_mensal']));
        $dia = (int) ($raw['dia_renovacao_saldo'] ?? $base['dia_renovacao_saldo']);
        $out['dia_renovacao_saldo'] = min(28, max(1, $dia));

        return $out;
    }

    /** @param array<string, mixed> $dados */
    public function __construct(private array $dados) {}

    public function anoVigencia(): int
    {
        return (int) ($this->dados['ano_vigencia'] ?? date('Y'));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->dados;
    }

    public function percentualLimitePorSolicitacao(): float
    {
        return (float) ($this->dados['percentual_limite_por_solicitacao'] ?? 30);
    }

    public function limitePorSolicitacaoParaSalario(?float $salario): float
    {
        if ($salario === null || $salario <= 0) {
            return 0.0;
        }

        return round($salario * $this->percentualLimitePorSolicitacao() / 100, 2);
    }

    public function limiteMensal(): float
    {
        return (float) ($this->dados['limite_mensal'] ?? 1500);
    }

    public function diaRenovacaoSaldo(): int
    {
        return (int) ($this->dados['dia_renovacao_saldo'] ?? 23);
    }
}
