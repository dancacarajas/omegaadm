<?php

namespace App\Support\Rh;

/**
 * Turnover de RH conforme fórmula padrão:
 * Geral = [(Admissões + Desligamentos) ÷ 2] ÷ Efetivo médio × 100
 * Efetivo médio = (Efetivo inicial + Efetivo final) ÷ 2
 */
final class TurnoverIndicadoresPeriodo
{
    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumoEfetivo
     * @param  array{desligamentos_voluntarios?: int}  $extras
     * @return array{
     *     efetivo_medio: float,
     *     efetivo_medio_label: string,
     *     turnover_geral: ?float,
     *     turnover_geral_label: string,
     *     turnover_desligamento: ?float,
     *     turnover_desligamento_label: string,
     *     turnover_voluntario: ?float,
     *     turnover_voluntario_label: string,
     *     turnover_involuntario: ?float,
     *     turnover_involuntario_label: string,
     *     desligamentos_voluntarios: int,
     *     desligamentos_involuntarios: int
     * }
     */
    public static function calcular(array $resumoEfetivo, array $extras = []): array
    {
        $ini = (int) ($resumoEfetivo['efetivo_inicial'] ?? 0);
        $fim = (int) ($resumoEfetivo['efetivo_final'] ?? 0);
        $adm = (int) ($resumoEfetivo['admitidos'] ?? 0);
        $des = (int) ($resumoEfetivo['desligados'] ?? 0);

        $media = ($ini + $fim) / 2.0;
        $voluntarios = min($des, max(0, (int) ($extras['desligamentos_voluntarios'] ?? 0)));
        $involuntarios = max(0, $des - $voluntarios);

        $pct = static function (float $numerador) use ($media): ?float {
            if ($media <= 0) {
                return null;
            }

            return round(($numerador / $media) * 100, 1);
        };

        $meioMovimentacao = ($adm + $des) / 2.0;
        $turnoverGeral = $pct($meioMovimentacao);
        $turnoverDesligamento = $pct((float) $des);
        $turnoverVoluntario = $pct((float) $voluntarios);
        $turnoverInvoluntario = $pct((float) $involuntarios);

        $fmtPct = static fn (?float $v): string => $v === null
            ? '—'
            : number_format($v, 1, ',', '.').'%';

        return [
            'efetivo_medio' => $media,
            'efetivo_medio_label' => self::fmtNumero($media),
            'turnover_geral' => $turnoverGeral,
            'turnover_geral_label' => $fmtPct($turnoverGeral),
            'turnover_desligamento' => $turnoverDesligamento,
            'turnover_desligamento_label' => $fmtPct($turnoverDesligamento),
            'turnover_voluntario' => $turnoverVoluntario,
            'turnover_voluntario_label' => $fmtPct($turnoverVoluntario),
            'turnover_involuntario' => $turnoverInvoluntario,
            'turnover_involuntario_label' => $fmtPct($turnoverInvoluntario),
            'desligamentos_voluntarios' => $voluntarios,
            'desligamentos_involuntarios' => $involuntarios,
        ];
    }

    private static function fmtNumero(float $n): string
    {
        if (fmod($n, 1.0) === 0.0) {
            return (string) (int) $n;
        }

        return number_format($n, 1, ',', '.');
    }
}
