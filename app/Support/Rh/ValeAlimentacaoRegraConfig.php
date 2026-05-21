<?php

namespace App\Support\Rh;

/**
 * Parâmetros configuráveis do vale/auxílio alimentação (ACT / vigência anual).
 */
final class ValeAlimentacaoRegraConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function padroes(?int $ano = null): array
    {
        $ano ??= (int) date('Y');

        return [
            'ano_vigencia' => $ano,
            'desconto_faltas' => [
                ['de' => 1, 'ate' => 1, 'percentual' => 20],
                ['de' => 2, 'ate' => 2, 'percentual' => 50],
                ['de' => 3, 'ate' => null, 'percentual' => 100],
            ],
            'proporcional_admissao_demissao' => true,
            'afastamento_acidente_trabalho' => [
                'ativo' => true,
                'meses_limite_integral' => 3,
            ],
            'recarga_natal' => [
                'ativo' => true,
                'valor_integral' => 925.0,
                'data_pagamento_limite' => '2025-12-21',
                'periodo_atestados_inicio' => '2025-06-20',
                'periodo_atestados_fim' => '2025-12-20',
                'faixas_atestados' => [
                    ['de' => 0, 'ate' => 4, 'percentual_valor' => 100],
                    ['de' => 5, 'ate' => 7, 'percentual_valor' => 75],
                    ['de' => 8, 'ate' => 11, 'percentual_valor' => 50],
                    ['de' => 12, 'ate' => null, 'percentual_valor' => 0],
                ],
                'perda_uma_falta_injustificada_percentual' => 100,
                'exige_sindicalizado' => true,
                'cargos_excluidos_texto' => "gestão\ncoordenador\ncoordenadora\ngerente\ngerência\nsupervisor\nsupervisora\nexecutivo\nexecutiva\ndiretor\ndiretora",
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    public static function resolver(?array $raw, ?int $ano = null): self
    {
        $base = self::padroes($ano);
        if ($raw === null || $raw === []) {
            return new self($base);
        }

        return new self(self::mesclar($base, $raw));
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function mesclar(array $base, array $raw): array
    {
        $ano = (int) ($raw['ano_vigencia'] ?? $base['ano_vigencia']);
        $out = self::padroes($ano);

        if (isset($raw['desconto_faltas']) && is_array($raw['desconto_faltas'])) {
            $out['desconto_faltas'] = self::normalizarFaixasFaltas($raw['desconto_faltas']);
        }

        $out['proporcional_admissao_demissao'] = filter_var(
            $raw['proporcional_admissao_demissao'] ?? $out['proporcional_admissao_demissao'],
            FILTER_VALIDATE_BOOLEAN
        );

        if (isset($raw['afastamento_acidente_trabalho']) && is_array($raw['afastamento_acidente_trabalho'])) {
            $a = $raw['afastamento_acidente_trabalho'];
            $out['afastamento_acidente_trabalho'] = [
                'ativo' => filter_var($a['ativo'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'meses_limite_integral' => max(1, (int) ($a['meses_limite_integral'] ?? 3)),
            ];
        }

        if (isset($raw['recarga_natal']) && is_array($raw['recarga_natal'])) {
            $out['recarga_natal'] = self::normalizarRecargaNatal($raw['recarga_natal'], $out['recarga_natal']);
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $faixas
     * @return list<array{de: int, ate: ?int, percentual: float}>
     */
    public static function normalizarFaixasFaltas(array $faixas): array
    {
        $out = [];
        foreach ($faixas as $faixa) {
            if (! is_array($faixa)) {
                continue;
            }
            $de = max(0, (int) ($faixa['de'] ?? 0));
            $ateRaw = $faixa['ate'] ?? null;
            $ate = ($ateRaw === '' || $ateRaw === null) ? null : max($de, (int) $ateRaw);
            $pct = min(100, max(0, (float) ($faixa['percentual'] ?? 0)));

            if ($de <= 0 && $pct <= 0) {
                continue;
            }

            $out[] = [
                'de' => $de > 0 ? $de : 1,
                'ate' => $ate,
                'percentual' => $pct,
            ];
        }

        usort($out, fn (array $a, array $b): int => $a['de'] <=> $b['de']);

        return $out !== [] ? $out : self::padroes()['desconto_faltas'];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $padrao
     * @return array<string, mixed>
     */
    public static function normalizarRecargaNatal(array $raw, array $padrao): array
    {
        $natal = $padrao;
        $natal['ativo'] = filter_var($raw['ativo'] ?? $natal['ativo'], FILTER_VALIDATE_BOOLEAN);
        $natal['valor_integral'] = max(0, (float) ($raw['valor_integral'] ?? $natal['valor_integral']));
        $natal['data_pagamento_limite'] = self::normalizarData($raw['data_pagamento_limite'] ?? $natal['data_pagamento_limite']);
        $natal['periodo_atestados_inicio'] = self::normalizarData($raw['periodo_atestados_inicio'] ?? $natal['periodo_atestados_inicio']);
        $natal['periodo_atestados_fim'] = self::normalizarData($raw['periodo_atestados_fim'] ?? $natal['periodo_atestados_fim']);
        $natal['perda_uma_falta_injustificada_percentual'] = min(100, max(0, (float) (
            $raw['perda_uma_falta_injustificada_percentual'] ?? $natal['perda_uma_falta_injustificada_percentual']
        )));
        $natal['exige_sindicalizado'] = filter_var($raw['exige_sindicalizado'] ?? $natal['exige_sindicalizado'], FILTER_VALIDATE_BOOLEAN);
        $natal['cargos_excluidos_texto'] = trim((string) ($raw['cargos_excluidos_texto'] ?? $natal['cargos_excluidos_texto']));

        if (isset($raw['faixas_atestados']) && is_array($raw['faixas_atestados'])) {
            $faixas = [];
            foreach ($raw['faixas_atestados'] as $faixa) {
                if (! is_array($faixa)) {
                    continue;
                }
                $de = max(0, (int) ($faixa['de'] ?? 0));
                $ateRaw = $faixa['ate'] ?? null;
                $ate = ($ateRaw === '' || $ateRaw === null) ? null : max($de, (int) $ateRaw);
                $faixas[] = [
                    'de' => $de,
                    'ate' => $ate,
                    'percentual_valor' => min(100, max(0, (float) ($faixa['percentual_valor'] ?? 0))),
                ];
            }
            if ($faixas !== []) {
                $natal['faixas_atestados'] = $faixas;
            }
        }

        return $natal;
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

    public function proporcionalAdmissaoDemissao(): bool
    {
        return (bool) ($this->dados['proporcional_admissao_demissao'] ?? true);
    }

    /** @return list<array{de: int, ate: ?int, percentual: float}> */
    public function faixasDescontoFaltas(): array
    {
        return $this->dados['desconto_faltas'] ?? self::padroes()['desconto_faltas'];
    }

    public function percentualDescontoPorFaltas(int $faltas): float
    {
        if ($faltas <= 0) {
            return 0.0;
        }

        foreach ($this->faixasDescontoFaltas() as $faixa) {
            $de = (int) $faixa['de'];
            $ate = $faixa['ate'];
            if ($faltas >= $de && ($ate === null || $faltas <= $ate)) {
                return ((float) $faixa['percentual']) / 100;
            }
        }

        $ultima = $this->faixasDescontoFaltas();
        $last = $ultima[array_key_last($ultima)] ?? null;

        return $last ? ((float) $last['percentual']) / 100 : 1.0;
    }

    public function textoFaixaDesconto(int $faltas): string
    {
        if ($faltas <= 0) {
            return 'Sem falta injustificada no período de apuração → valor integral (após proporcional).';
        }

        $pct = (int) round($this->percentualDescontoPorFaltas($faltas) * 100);

        return "{$faltas} falta(s) injustificada(s) no período → desconto {$pct}%.";
    }

    /** @return array{ativo: bool, meses_limite_integral: int} */
    public function afastamentoAcidente(): array
    {
        return $this->dados['afastamento_acidente_trabalho'] ?? self::padroes()['afastamento_acidente_trabalho'];
    }

    /** @return array<string, mixed> */
    public function recargaNatal(): array
    {
        return $this->dados['recarga_natal'] ?? self::padroes()['recarga_natal'];
    }

    /** @return list<string> */
    public function cargosExcluidosRecargaNatal(): array
    {
        $texto = (string) ($this->recargaNatal()['cargos_excluidos_texto'] ?? '');
        $linhas = preg_split('/[\r\n,;]+/', $texto) ?: [];

        return array_values(array_filter(array_map(
            fn (string $l): string => mb_strtolower(trim($l)),
            $linhas
        )));
    }

    public function percentualValorRecargaPorAtestados(int $quantidadeAtestados): float
    {
        $natal = $this->recargaNatal();
        if (! ($natal['ativo'] ?? false)) {
            return 0.0;
        }

        foreach ($natal['faixas_atestados'] ?? [] as $faixa) {
            $de = (int) $faixa['de'];
            $ate = $faixa['ate'];
            if ($quantidadeAtestados >= $de && ($ate === null || $quantidadeAtestados <= $ate)) {
                return ((float) $faixa['percentual_valor']) / 100;
            }
        }

        return 0.0;
    }
}
