<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Regras de fase do candidato no recrutamento (alinhadas à ficha RH e ao painel de preenchimento).
 */
final class RecrutamentoCandidatoFase
{
    public static function faseAtualLabel(array $state, int $position): string
    {
        if (blank($state["candidato_{$position}_data_aceite"] ?? null)) {
            $nome = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));

            return $nome !== ''
                ? 'Cadastro — aguardando data de aceite'
                : 'Cadastro iniciado';
        }

        if (! self::etapaExameMedicoConcluida($state, $position)) {
            return 'Exame médico';
        }
        if (! self::etapaTreinamentosConcluida($state, $position)) {
            return self::emTreinamentoSoDataInicio($state, $position)
                ? 'Em treinamento'
                : 'Aguardando treinamentos';
        }
        if (! self::etapaAssinaturaConcluida($state, $position)) {
            return 'Treinamento concluído';
        }
        if (! self::etapaSgcConcluida($state, $position)) {
            return 'SGC / mobilização';
        }
        if (! self::etapaLiberacaoConcluida($state, $position)) {
            return 'Liberação';
        }

        return 'Concluído';
    }

    public static function abaParaFase(string $label): string
    {
        if (str_starts_with($label, 'Cadastro')) {
            return 'cadastro';
        }

        return match ($label) {
            'Exame médico' => 'exame_medico',
            'Aguardando treinamentos' => 'treinamentos',
            'Em treinamento' => 'treinamentos',
            'Treinamento concluído' => 'assinatura',
            'SGC / mobilização' => 'sgc',
            'Liberação' => 'liberacao',
            'Concluído' => 'concluido',
            default => 'outros',
        };
    }

    public static function etapaExameMedicoConcluida(array $state, int $position): bool
    {
        $trainingStart = $state["candidato_{$position}_exameMedico_data_inicio"]
            ?? $state["candidato_{$position}_treinamentos_data_inicio"]
            ?? null;
        $trainingEnd = $state["candidato_{$position}_exameMedico_data_fim"]
            ?? $state["candidato_{$position}_treinamentos_data_fim"]
            ?? null;
        $trainingConfirmedAt = $state["candidato_{$position}_exameMedico_data_confirmacao"]
            ?? $state["candidato_{$position}_treinamentos_data_confirmacao"]
            ?? null;
        $scheduledAt = $state["candidato_{$position}_exameMedico_data_agendamento"]
            ?? $state["candidato_{$position}_treinamentos_data_agendamento"]
            ?? null;

        if (blank($trainingEnd) && filled($trainingStart)) {
            try {
                $trainingEnd = Carbon::parse($trainingStart)->addDays(5)->toDateString();
            } catch (\Throwable) {
                $trainingEnd = null;
            }
        }

        return filled($trainingStart) && filled($trainingConfirmedAt)
            && (filled($scheduledAt) || filled($trainingEnd));
    }

    public static function emTreinamentoSoDataInicio(array $state, int $position): bool
    {
        if (self::treinamentosCapacitacaoEfetiva($state, $position)) {
            return false;
        }

        $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
        if (! filled($trainingStart)) {
            return false;
        }

        if (self::hasLegacyMirroredTrainingData($state, $position)) {
            return false;
        }

        if (self::treinamentoInicioIgualExameSemConfirmacaoTreino($state, $position)) {
            return false;
        }

        return true;
    }

    public static function treinamentosCapacitacaoEfetiva(array $state, int $position): bool
    {
        if (empty($state["candidato_{$position}_treinamentos_capacitacao"])) {
            return false;
        }

        $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
        $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;

        return filled($trainingStart) && filled($trainingConfirmedAt);
    }

    public static function treinamentoInicioIgualExameSemConfirmacaoTreino(array $state, int $position): bool
    {
        $exameIni = trim((string) ($state["candidato_{$position}_exameMedico_data_inicio"] ?? ''));
        $trIni = trim((string) ($state["candidato_{$position}_treinamentos_data_inicio"] ?? ''));
        if ($exameIni === '' || $trIni === '' || $exameIni !== $trIni) {
            return false;
        }

        return ! filled($state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null);
    }

    public static function etapaTreinamentosConcluida(array $state, int $position): bool
    {
        if (self::treinamentosCapacitacaoEfetiva($state, $position)) {
            return ! self::hasLegacyMirroredTrainingData($state, $position);
        }

        $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
        $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;

        return filled($trainingStart)
            && filled($trainingConfirmedAt)
            && ! self::hasLegacyMirroredTrainingData($state, $position);
    }

    public static function etapaAssinaturaConcluida(array $state, int $position): bool
    {
        return filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
    }

    public static function etapaSgcConcluida(array $state, int $position): bool
    {
        $hasPendency = filled($state["candidato_{$position}_sgc_pendencia_descricao"] ?? null);
        $pendencyDone = $hasPendency
            ? filled($state["candidato_{$position}_sgc_data_nova_postagem"] ?? null)
            : filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);

        return filled($state["candidato_{$position}_sgc_data_postagem"] ?? null)
            && filled($state["candidato_{$position}_sgc_numero_postagem"] ?? null)
            && $pendencyDone
            && filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);
    }

    public static function etapaLiberacaoConcluida(array $state, int $position): bool
    {
        return filled($state["candidato_{$position}_liberacao_orientado_data"] ?? null)
            && filled($state["candidato_{$position}_liberacao_epi_data"] ?? null)
            && filled($state["candidato_{$position}_liberacao_rota_endereco"] ?? null);
    }

    public static function hasLegacyMirroredTrainingData(array $state, int $position): bool
    {
        $trainingStart = trim((string) ($state["candidato_{$position}_treinamentos_data_inicio"] ?? ''));
        $trainingConfirmed = trim((string) ($state["candidato_{$position}_treinamentos_data_confirmacao"] ?? ''));
        if ($trainingStart === '' || $trainingConfirmed === '') {
            return false;
        }

        $exameStart = trim((string) ($state["candidato_{$position}_exameMedico_data_inicio"] ?? ''));
        $exameConfirmed = trim((string) ($state["candidato_{$position}_exameMedico_data_confirmacao"] ?? ''));
        if ($exameStart === '' || $exameConfirmed === '') {
            return false;
        }

        $sgcPosted = filled($state["candidato_{$position}_sgc_data_postagem"] ?? null);
        $signed = filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
        if ($sgcPosted || $signed) {
            return false;
        }

        return $trainingStart === $exameStart && $trainingConfirmed === $exameConfirmed;
    }

    /**
     * Sincroniza flag de capacitação com o mesmo critério do JS do formulário (início + confirmação).
     */
    public static function sincronizarTreinamentosCapacitacao(array &$state, int $position): void
    {
        $start = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
        $conf = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;
        $state["candidato_{$position}_treinamentos_capacitacao"] = filled($start) && filled($conf);
    }
}
