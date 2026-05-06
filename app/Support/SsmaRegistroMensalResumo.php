<?php

namespace App\Support;

use App\Models\SsmaRegistroMensal;

/** Texto automático do que foi preenchido nas etapas (registro mensal). */
class SsmaRegistroMensalResumo
{
    /**
     * @return list<string>
     */
    public static function linhas(?array $etapas): array
    {
        if ($etapas === null || $etapas === []) {
            return ['Nenhuma etapa com dados neste registro ainda.'];
        }

        $linhas = [];

        foreach (SsmaRegistroMensal::ETAPAS as $slug => $label) {
            $e = $etapas[$slug] ?? null;
            if (! is_array($e)) {
                continue;
            }

            $trecho = match ($slug) {
                'auditoria_mensal' => self::resumoAuditoria($e),
                'inspecao_mensal_canteiro' => self::resumoInspecao($e),
                'treinamentos_mensais' => self::resumoTreinamentos($e, $label),
                'registro_acoes_proativas' => self::resumoProativas($e, $label),
                'boas_praticas_kaizen' => self::resumoKaizen($e, $label),
                'acoes_reativas' => self::resumoReativas($e, $label),
                'campanha_seguranca' => self::resumoCampanha($e, $label),
                'registro_acidente' => self::resumoAcidente($e, $label),
                default => self::resumoGenerico($e, $label),
            };

            if ($trecho !== null) {
                $linhas[] = $trecho;
            }
        }

        return $linhas === [] ? ['Nenhuma etapa com dados neste registro ainda.'] : $linhas;
    }

    private static function resumoAuditoria(array $e): ?string
    {
        $p = $e['passou_auditoria'] ?? null;
        if ($p === null && empty($e['data_auditoria']) && empty($e['descricao'])) {
            return null;
        }

        $sit = match ($p) {
            'sim' => 'aprovada',
            'nao' => 'com pendência / não aprovada',
            default => 'situação não informada',
        };

        return 'Auditoria mensal: '.$sit.'.';
    }

    private static function resumoInspecao(array $e): ?string
    {
        $p = $e['passou_inspecao'] ?? null;
        if ($p === null && empty($e['data_inspecao']) && empty($e['descricao'])) {
            return null;
        }

        $sit = match ($p) {
            'sim' => 'aprovada',
            'nao' => 'com pendência / não aprovada',
            default => 'situação não informada',
        };

        return 'Inspeção mensal de canteiro: '.$sit.'.';
    }

    private static function resumoTreinamentos(array $e, string $label): ?string
    {
        $n = count($e['linhas'] ?? []);

        return $n > 0 ? $label.': '.$n.' linha(s) de treinamento registrada(s).' : null;
    }

    private static function resumoProativas(array $e, string $label): ?string
    {
        $map = [
            'quase_acidente' => 'Quase acidente',
            'termo_interdicao_vale' => 'Termo interdição Vale',
            'termo_notificacao_vale' => 'Termo notificação Vale',
            'interdicao_interna_omega' => 'Interdição interna Omega',
            'notificacao_interna_omega' => 'Notificação interna Omega',
        ];
        $partes = [];
        foreach ($map as $bloco => $nome) {
            $n = count($e[$bloco]['linhas'] ?? []);
            if ($n > 0) {
                $partes[] = $nome.' ('.$n.')';
            }
        }

        return $partes === [] ? null : $label.': '.implode('; ', $partes).'.';
    }

    private static function resumoKaizen(array $e, string $label): ?string
    {
        if (empty($e['titulo']) && empty($e['ganhos_processo']) && empty($e['responsaveis'])) {
            $ids = $e['colaborador_ids'] ?? [];
            if (! is_array($ids) || $ids === []) {
                return null;
            }
        }

        $tit = trim((string) ($e['titulo'] ?? ''));

        return $tit !== ''
            ? $label.': “'.$tit.'”.'
            : $label.': projeto registrado (detalhes no formulário).';
    }

    private static function resumoReativas(array $e, string $label): ?string
    {
        $map = [
            'primeiros_socorros' => 'Primeiros socorros',
            'restricao_trabalho' => 'Restrição de trabalho',
            'tratamento_medico' => 'Tratamento médico',
            'regra_ouro' => 'Regra de ouro',
            'telemetria' => 'Telemetria',
        ];
        $partes = [];
        foreach ($map as $bloco => $nome) {
            $n = count($e[$bloco]['linhas'] ?? []);
            if ($n > 0) {
                $partes[] = $nome.' ('.$n.')';
            }
        }

        return $partes === [] ? null : $label.': '.implode('; ', $partes).'.';
    }

    private static function resumoCampanha(array $e, string $label): ?string
    {
        $itens = $e['itens'] ?? $e['campanhas'] ?? [];
        $n = is_array($itens) ? count($itens) : 0;

        return $n > 0 ? $label.': '.$n.' campanha(s) / reunião(ões).' : null;
    }

    private static function resumoAcidente(array $e, string $label): ?string
    {
        $n = count($e['linhas'] ?? []);
        $ev1 = ! empty($e['evidencia_1_path']);
        $ev2 = ! empty($e['evidencia_2_path']);

        if ($n === 0 && ! $ev1 && ! $ev2) {
            return null;
        }

        $s = $label.': '.$n.' linha(s) de ocorrência.';
        if ($ev1 || $ev2) {
            $s .= ' Evidências anexadas.';
        }

        return $s;
    }

    private static function resumoGenerico(array $e, string $label): ?string
    {
        if (empty($e['realizado']) && empty($e['observacoes']) && empty($e['data_referencia'])) {
            return null;
        }

        $ok = ! empty($e['realizado']);

        return $label.': '.($ok ? 'marcada como realizada.' : 'com anotações ou pendente de conclusão.');
    }
}
