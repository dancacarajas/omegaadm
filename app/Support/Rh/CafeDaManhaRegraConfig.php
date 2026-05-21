<?php

namespace App\Support\Rh;

/**
 * Café da manhã: parâmetros padrão do ACT (fallback). Valores vigentes ficam em
 * beneficio_extrato_regras.parametros (modal Extrato → Regras) e opcionalmente no campo Valor do benefício.
 */
final class CafeDaManhaRegraConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function padroes(?int $ano = null): array
    {
        $ano ??= (int) date('Y');

        return [
            'ano_vigencia' => $ano,
            'valor_mensal_cheio' => 175.0,
            'valor_diario' => 7.95,
            'valor_diario_fds_feriado' => null,
            'periodo_vigencia_inicio' => '2025-06-01',
            'periodo_vigencia_fim' => '2026-05-31',
            'minutos_minimos_dia_trabalhado' => 1,
            'teto_mensal_ativo' => true,
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
        $out['valor_mensal_cheio'] = max(0, (float) ($raw['valor_mensal_cheio'] ?? $base['valor_mensal_cheio']));
        $out['valor_diario'] = max(0, (float) ($raw['valor_diario'] ?? $base['valor_diario']));
        $fds = $raw['valor_diario_fds_feriado'] ?? $base['valor_diario_fds_feriado'];
        $out['valor_diario_fds_feriado'] = ($fds === '' || $fds === null) ? null : max(0, (float) $fds);
        $out['periodo_vigencia_inicio'] = self::normalizarData($raw['periodo_vigencia_inicio'] ?? $base['periodo_vigencia_inicio']);
        $out['periodo_vigencia_fim'] = self::normalizarData($raw['periodo_vigencia_fim'] ?? $base['periodo_vigencia_fim']);
        $out['minutos_minimos_dia_trabalhado'] = max(1, (int) ($raw['minutos_minimos_dia_trabalhado'] ?? $base['minutos_minimos_dia_trabalhado']));
        $out['teto_mensal_ativo'] = filter_var($raw['teto_mensal_ativo'] ?? $base['teto_mensal_ativo'], FILTER_VALIDATE_BOOLEAN);

        return $out;
    }

    private static function normalizarData(mixed $valor): string
    {
        $texto = trim((string) $valor);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return $texto;
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $texto)) {
            $p = explode('/', $texto);

            return sprintf('%s-%s-%s', $p[2], $p[1], $p[0]);
        }

        return $texto;
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

    public function valorMensalCheio(): float
    {
        return (float) ($this->dados['valor_mensal_cheio'] ?? 175);
    }

    public function valorDiario(): float
    {
        return (float) ($this->dados['valor_diario'] ?? 7.95);
    }

    public function valorDiarioFdsFeriado(): ?float
    {
        $v = $this->dados['valor_diario_fds_feriado'] ?? null;

        return $v === null ? null : (float) $v;
    }

    public function valorDiarioParaDia(bool $fdsOuFeriado): float
    {
        if ($fdsOuFeriado) {
            return $this->valorDiarioFdsFeriado() ?? $this->valorDiario();
        }

        return $this->valorDiario();
    }

    public function minutosMinimosDiaTrabalhado(): int
    {
        return (int) ($this->dados['minutos_minimos_dia_trabalhado'] ?? 1);
    }

    public function tetoMensalAtivo(): bool
    {
        return (bool) ($this->dados['teto_mensal_ativo'] ?? true);
    }

    public function periodoVigenciaInicio(): string
    {
        return (string) ($this->dados['periodo_vigencia_inicio'] ?? '2025-06-01');
    }

    public function periodoVigenciaFim(): string
    {
        return (string) ($this->dados['periodo_vigencia_fim'] ?? '2026-05-31');
    }
}
